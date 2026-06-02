<?php

namespace App\Http\Controllers\Admin;

use App\Country;
use App\Exports\FullProductExport;
use App\CPU\BackEndHelper;
use App\CPU\BulkImportHelper;
use App\CPU\BulkImportProcessor;
use App\CPU\PriceUpdater;
use App\CPU\Helpers;
use App\Model\ProductImportJob;
use Illuminate\Support\Facades\Cache;
use App\CPU\ImageManager;
use App\Http\Controllers\BaseController;
use App\Model\Brand;
use App\Model\Category;
use App\Model\Color;
use App\Model\DealOfTheDay;
use App\Model\FlashDealProduct;
use App\Model\Product;
use App\Model\Review;
use App\Model\Translation;
use App\Model\Wishlist;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Rap2hpoutre\FastExcel\FastExcel;
use Maatwebsite\Excel\Facades\Excel;
use function App\CPU\translate;
use App\Model\Cart;
use App\ShippingCostByCountry;

class ProductController extends BaseController
{
    public function add_new()
    {
        $cat = Category::where(['parent_id' => 0])->get();
        $br = Brand::orderBY('name', 'ASC')->get();
        $countries = Country::where('status', 1)->get();
        return view('admin-views.product.add-new', compact('cat', 'br','countries'));
    }

    public function featured_status(Request $request)
    {
        $product = Product::find($request->id);
        $product->featured = ($product['featured'] == 0 || $product['featured'] == null) ? 1 : 0;
        $product->save();
        $data = $request->status;
        return response()->json($data);
    }

    public function approve_status(Request $request)
    {
        $product = Product::find($request->id);
        $product->request_status = ($product['request_status'] == 0) ? 1 : 0;
        $product->save();

        return redirect()->route('admin.product.list', ['seller', 'status' => $product['request_status']]);
    }

    public function deny(Request $request)
    {
        $product = Product::find($request->id);
        $product->request_status = 2;
        $product->denied_note = $request->denied_note;
        $product->save();

        return redirect()->route('admin.product.list', ['seller', 'status' => 2]);
    }

    public function view($id)
    {
        $product = Product::with(['reviews'])->where(['id' => $id])->first();
        $reviews = Review::where(['product_id' => $id])->paginate(Helpers::pagination_limit());
        return view('admin-views.product.view', compact('product', 'reviews'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required',
            'category_id'       => 'required',
            'brand_id'          => 'required',
            'unit'              => 'required',
            'images'            => 'required',
            'image'             => 'required',
            'tax'               => 'required|min:0',
            'unit_price'        => 'required|numeric|min:1',
            'purchase_price'    => 'required|numeric|min:1',
            'discount'          => 'required|gt:-1',
            'shipping_cost'     => 'required|gt:-1',
            'code'              => 'required|numeric|min:1|digits_between:6,20|unique:products',
            'minimum_order_qty' => 'required|numeric|min:1',
        ], [
            'images.required'       => 'Product images is required!',
            'image.required'        => 'Product thumbnail is required!',
            'category_id.required'  => 'category  is required!',
            'brand_id.required'     => 'brand  is required!',
            'unit.required'         => 'Unit  is required!',
            'code.min'              => 'The code must be positive!',
            'code.digits_between'   => 'The code must be minimum 6 digits!',
            'minimum_order_qty.required' => 'The minimum order quantity is required!',
            'minimum_order_qty.min' => 'The minimum order quantity must be positive!',
        ]);

        if ($request['discount_type'] == 'percent') {
            $dis = ($request['unit_price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['unit_price'] <= $dis) {
            $validator->after(function ($validator) {
                $validator->errors()->add(
                    'unit_price', 'Discount can not be more or equal to the price!'
                );
            });
        }

        // if (is_null($request->description[array_search('en', $request->lang)])) {
        //     $validator->after(function ($validator) {
        //         $validator->errors()->add(
        //             'description', 'description field is required!'
        //         );
        //     });
        // }

        if (is_null($request->name[array_search('en', $request->lang)])) {
            $validator->after(function ($validator) {
                $validator->errors()->add(
                    'name', 'Name field is required!'
                );
            });
        }


        $p = new Product();
        $p->user_id = auth('admin')->id();
        $p->added_by = "admin";
        $p->name = $request->name[array_search('en', $request->lang)];
        $p->code = $request->code;
        $p->slug = Str::slug($request->name[array_search('en', $request->lang)], '-') . '-' . Str::random(6);

        $category = [];

        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }

        $p->category_ids = json_encode($category);
        $p->brand_id = $request->brand_id;
        $p->unit = $request->unit;
        $p->details = $request->description[array_search('en', $request->lang)];

        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $p->colors = json_encode($request->colors);
        } else {
            $colors = [];
            $p->colors = json_encode($colors);
        }
        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $item['options'] = explode(',', implode('|', $request[$str]));
                array_push($choice_options, $item);
            }
        }
        $p->choice_options = json_encode($choice_options);
        //combinations start
        $options = [];
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $colors_active = 1;
            array_push($options, $request->colors);
        }
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }
        //Generates the combinations of customer choice options

        $combinations = Helpers::combinations($options);

        $variations = [];
        $stock_count = 0;
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
                            $color_name = Color::where('code', $item)->first()->name;
                            $str .= $color_name;
                        } else {
                            $str .= str_replace(' ', '', $item);
                        }
                    }
                }
                $item = [];
                $item['type'] = $str;
                $item['price'] = BackEndHelper::currency_to_usd(abs($request['price_' . str_replace('.', '_', $str)]));
                $item['sku'] = $request['sku_' . str_replace('.', '_', $str)];
                $item['qty'] = abs($request['qty_' . str_replace('.', '_', $str)]);
                array_push($variations, $item);
                $stock_count += $item['qty'];
            }
        } else {
            $stock_count = (integer)$request['current_stock'];
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        //combinations end
        $p->variation = json_encode($variations);
        $p->unit_price = BackEndHelper::currency_to_usd($request->unit_price);
        $p->purchase_price = BackEndHelper::currency_to_usd($request->purchase_price);
        $p->tax = $request->tax_type == 'flat' ? BackEndHelper::currency_to_usd($request->tax) : $request->tax;
        $p->tax_type = $request->tax_type;
        $p->discount = $request->discount_type == 'flat' ? BackEndHelper::currency_to_usd($request->discount) : $request->discount;
        $p->discount_type = $request->discount_type;
        $p->attributes = json_encode($request->choice_attributes);
        $p->current_stock = abs($stock_count);
        $p->minimum_order_qty = $request->minimum_order_qty;

        $p->video_provider = 'youtube';
        $p->video_url = $request->video_link;
        $p->request_status = 1;
        $p->shipping_cost = BackEndHelper::currency_to_usd($request->shipping_cost);
        $p->multiply_qty = $request->multiplyQTY=='on'?1:0;

        if ($request->ajax()) {
            return response()->json([], 200);
        } else {
            if ($request->file('images')) {
                foreach ($request->file('images') as $img) {
                    $product_images[] = ImageManager::upload('product/', 'png', $img);
                }
                $p->images = json_encode($product_images);
            }
            $p->thumbnail = ImageManager::upload('product/thumbnail/', 'png', $request->image);

            $p->meta_title = $request->meta_title;
            $p->meta_description = $request->meta_description;
            $p->meta_image = ImageManager::upload('product/meta/', 'png', $request->meta_image);

            $p->save();

            //Custom Code
            foreach($request->country as $country_id=>$item){
                ShippingCostByCountry::create([
                    'country_id' => $country_id,
                    'duration' => $item['duration'],
                    'shipping_cost' => BackEndHelper::currency_to_usd($item['cost']),
                    'product_id' => $p->id,
                ]);
            }



            $data = [];
            foreach ($request->lang as $index => $key) {
                if ($request->name[$index] && $key != 'en') {
                    array_push($data, array(
                        'translationable_type' => 'App\Model\Product',
                        'translationable_id' => $p->id,
                        'locale' => $key,
                        'key' => 'name',
                        'value' => $request->name[$index],
                    ));
                }
                if ($request->description[$index] && $key != 'en') {
                    array_push($data, array(
                        'translationable_type' => 'App\Model\Product',
                        'translationable_id' => $p->id,
                        'locale' => $key,
                        'key' => 'description',
                        'value' => $request->description[$index],
                    ));
                }
            }
            Translation::insert($data);

            Toastr::success(translate('Product added successfully!'));
            return redirect()->route('admin.product.list', ['in_house']);
        }
    }

    function list(Request $request, $type)
    {
        $query_param = [];
        $search = $request['search'];
        if ($type == 'in_house') {
            $pro = Product::where(['added_by' => 'admin']);
        } else {
            $pro = Product::where(['added_by' => 'seller'])->where('request_status', $request->status);
        }

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $pro = $pro->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->Where('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }

        $request_status = $request['status'];
        $pro = $pro->orderBy('id', 'DESC')->paginate(Helpers::pagination_limit())->appends(['status' => $request['status']])->appends($query_param);
        return view('admin-views.product.list', compact('pro', 'search', 'request_status'));
    }

    public function updated_product_list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $pro = Product::where(['added_by' => 'seller'])
                ->where('is_shipping_cost_updated',0)
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->Where('name', 'like', "%{$value}%");
                    }
                });
            $query_param = ['search' => $request['search']];
        } else {
            $pro = Product::where(['added_by' => 'seller'])->where('is_shipping_cost_updated',0);
        }
        $pro = $pro->orderBy('id', 'DESC')->paginate(Helpers::pagination_limit())->appends($query_param);

        return view('admin-views.product.updated-product-list', compact('pro', 'search'));
    }

    public function stock_limit_list(Request $request, $type)
    {
        $stock_limit = Helpers::get_business_settings('stock_limit');
        $sort_oqrderQty = $request['sort_oqrderQty'];
        $query_param = $request->all();
        $search = $request['search'];
        if ($type == 'in_house') {
            $pro = Product::where(['added_by' => 'admin']);
        } else {
            $pro = Product::where(['added_by' => 'seller'])->where('request_status', $request->status);
        }

        if ($request->has('search')) {
            $key = explode(' ', $request['search']);
            $pro = $pro->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->Where('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }

        $request_status = $request['status'];

        $pro = $pro->withCount('order_details')->when($request->sort_oqrderQty == 'quantity_asc', function ($q) use ($request) {
            return $q->orderBy('current_stock', 'asc');
        })
            ->when($request->sort_oqrderQty == 'quantity_desc', function ($q) use ($request) {
                return $q->orderBy('current_stock', 'desc');
            })
            ->when($request->sort_oqrderQty == 'order_asc', function ($q) use ($request) {
                return $q->orderBy('order_details_count', 'asc');
            })
            ->when($request->sort_oqrderQty == 'order_desc', function ($q) use ($request) {
                return $q->orderBy('order_details_count', 'desc');
            })
            ->when($request->sort_oqrderQty == 'default', function ($q) use ($request) {
                return $q->orderBy('id');
            })->where('current_stock', '<', $stock_limit);

        $pro = $pro->orderBy('id', 'DESC')->paginate(Helpers::pagination_limit())->appends(['status' => $request['status']])->appends($query_param);
        return view('admin-views.product.stock-limit-list', compact('pro', 'search', 'request_status', 'sort_oqrderQty'));
    }

    public function update_quantity(Request $request)
    {
        $variations = [];
        $stock_count = $request['current_stock'];
        if ($request->has('type')) {
            foreach ($request['type'] as $key => $str) {
                $item = [];
                $item['type'] = $str;
                $item['price'] = BackEndHelper::currency_to_usd(abs($request['price_' . str_replace('.', '_', $str)]));
                $item['sku'] = $request['sku_' . str_replace('.', '_', $str)];
                $item['qty'] = abs($request['qty_' . str_replace('.', '_', $str)]);
                array_push($variations, $item);
            }
        }

        $product = Product::find($request['product_id']);
        if ($stock_count >= 0) {
            $product->current_stock = $stock_count;
            $product->variation = json_encode($variations);
            $product->save();
            Toastr::success(\App\CPU\translate('product_quantity_updated_successfully!'));
            return back();
        } else {
            Toastr::warning(\App\CPU\translate('product_quantity_can_not_be_less_than_0_!'));
            return back();
        }
    }

    public function status_update(Request $request)
    {

        $product = Product::where(['id' => $request['id']])->first();
        $success = 1;

        if ($request['status'] == 1) {
            if ($product->added_by == 'seller' && ($product->request_status == 0 || $product->request_status == 2)) {
                $success = 0;
            } else {
                $product->status = $request['status'];
            }
        } else {
            $product->status = $request['status'];
        }
        $product->save();
        return response()->json([
            'success' => $success,
        ], 200);
    }
    public function updated_shipping(Request $request)
    {

        $product = Product::where(['id' => $request['product_id']])->first();
        if($request->status == 1)
        {
            $product->shipping_cost = $product->temp_shipping_cost;
            $product->is_shipping_cost_updated = $request->status;
        }else{
            $product->is_shipping_cost_updated = $request->status;
        }

        $product->save();
        return response()->json([

        ], 200);
    }

    public function get_categories(Request $request)
    {
        $cat = Category::where(['parent_id' => $request->parent_id])->get();
        $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        foreach ($cat as $row) {
            if ($row->id == $request->sub_category) {
                $res .= '<option value="' . $row->id . '" selected >' . $row->name . '</option>';
            } else {
                $res .= '<option value="' . $row->id . '">' . $row->name . '</option>';
            }
        }
        return response()->json([
            'select_tag' => $res,
        ]);
    }

    public function sku_combination(Request $request)
    {
        $options = [];
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $colors_active = 1;
            array_push($options, $request->colors);
        } else {
            $colors_active = 0;
        }

        $unit_price = $request->unit_price;
        $product_name = $request->name[array_search('en', $request->lang)];

        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        $combinations = Helpers::combinations($options);
        return response()->json([
            'view' => view('admin-views.product.partials._sku_combinations', compact('combinations', 'unit_price', 'colors_active', 'product_name'))->render(),
        ]);
    }

    public function get_variations(Request $request)
    {
        $product = Product::find($request['id']);
        return response()->json([
            'view' => view('admin-views.product.partials._update_stock', compact('product'))->render()
        ]);
    }

    public function edit($id)
    {
        $product = Product::withoutGlobalScopes()->with('translations')->find($id);
        $product_category = json_decode($product->category_ids);
        $product->colors = json_decode($product->colors);
        $categories = Category::where(['parent_id' => 0])->get();
        $br = Brand::orderBY('name', 'ASC')->get();
        $countries = Country::where('status', 1)->get();

        return view('admin-views.product.edit', compact('categories', 'br', 'product', 'product_category','countries'));
    }

    public function update(Request $request, $id)
    {

        $product = Product::find($id);
        $validator = Validator::make($request->all(), [
            'name'              => 'required',
            'category_id'       => 'required',
            'brand_id'          => 'required',
            'unit'              => 'required',
            'tax'               => 'required|min:0',
            'unit_price'        => 'required|numeric|min:1',
            'purchase_price'    => 'required|numeric|min:1',
            'discount'          =>'required|gt:-1',
            'shipping_cost'     => 'required|gt:-1',
            'code'              => 'required|numeric|min:1|digits_between:6,20|unique:products,code,'.$product->id,
            'minimum_order_qty' => 'required|numeric|min:1',
        ], [
            'name.required'         => 'Product name is required!',
            'category_id.required'  => 'category  is required!',
            'brand_id.required'     => 'brand  is required!',
            'unit.required'         => 'Unit  is required!',
            'code.min'              => 'The code must be positive!',
            'code.digits_between'   => 'The code must be minimum 6 digits!',
            'minimum_order_qty.required' => 'The minimum order quantity is required!',
            'minimum_order_qty.min' => 'The minimum order quantity must be positive!',
        ]);

        if ($request['discount_type'] == 'percent') {
            $dis = ($request['unit_price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['unit_price'] <= $dis) {
            $validator->after(function ($validator) {
                $validator->errors()->add('unit_price', 'Discount can not be more or equal to the price!');
            });
        }

        if (is_null($request->name[array_search('en', $request->lang)])) {
            $validator->after(function ($validator) {
                $validator->errors()->add(
                    'name', 'Name field is required!'
                );
            });
        }
        // if (is_null($request->description[array_search('en', $request->lang)])) {
        //     $validator->after(function ($validator) {
        //         $validator->errors()->add(
        //             'description', 'Description field is required!'
        //         );
        //     });
        // }


        $product->name = $request->name[array_search('en', $request->lang)];

        $category = [];
        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }
        $product->category_ids = json_encode($category);
        $product->brand_id = $request->brand_id;
        $product->unit = $request->unit;
        $product->code = $request->code;
        $product->minimum_order_qty = $request->minimum_order_qty;
        $product->details = $request->description[array_search('en', $request->lang)];
        $product_images = json_decode($product->images);

        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $product->colors = json_encode($request->colors);
        } else {
            $colors = [];
            $product->colors = json_encode($colors);
        }
        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                $item['name'] = 'choice_' . $no;
                $item['title'] = $request->choice[$key];
                $item['options'] = explode(',', implode('|', $request[$str]));
                array_push($choice_options, $item);
            }
        }
        $product->choice_options = json_encode($choice_options);
        $variations = [];
        //combinations start
        $options = [];
        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
            $colors_active = 1;
            array_push($options, $request->colors);
        }
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }
        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        $variations = [];
        $stock_count = 0;
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $item) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $item);
                    } else {
                        if ($request->has('colors_active') && $request->has('colors') && count($request->colors) > 0) {
                            $color_name = Color::where('code', $item)->first()->name;
                            $str .= $color_name;
                        } else {
                            $str .= str_replace(' ', '', $item);
                        }
                    }
                }
                $item = [];
                $item['type'] = $str;
                $item['price'] = BackEndHelper::currency_to_usd(abs($request['price_' . str_replace('.', '_', $str)]));
                $item['sku'] = $request['sku_' . str_replace('.', '_', $str)];
                $item['qty'] = abs($request['qty_' . str_replace('.', '_', $str)]);
                array_push($variations, $item);
                $stock_count += $item['qty'];
            }
        } else {
            $stock_count = (integer)$request['current_stock'];
        }

        if ($validator->errors()->count() > 0) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        // if ($validator->fails()) {
        //     return back()->withErrors($validator)
        //         ->withInput();
        // }

        //combinations end
        $product->variation = json_encode($variations);
        $product->unit_price = BackEndHelper::currency_to_usd($request->unit_price);
        $product->purchase_price = BackEndHelper::currency_to_usd($request->purchase_price);
        $product->tax = $request->tax == 'flat' ? BackEndHelper::currency_to_usd($request->tax) : $request->tax;
        $product->tax_type = $request->tax_type;
        $product->discount = $request->discount_type == 'flat' ? BackEndHelper::currency_to_usd($request->discount) : $request->discount;
        $product->attributes = json_encode($request->choice_attributes);
        $product->discount_type = $request->discount_type;
        $product->current_stock = abs($stock_count);

        $product->video_provider = 'youtube';
        $product->video_url = $request->video_link;
        if ($product->added_by == 'seller' && $product->request_status == 2) {
            $product->request_status = 1;
        }
        $product->shipping_cost = BackEndHelper::currency_to_usd($request->shipping_cost);
        $product->multiply_qty = $request->multiplyQTY=='on'?1:0;
        if ($request->ajax()) {
            return response()->json([], 200);
        } else {
            if ($request->file('images')) {
                foreach ($request->file('images') as $img) {
                    $product_images[] = ImageManager::upload('product/', 'png', $img);
                }
                $product->images = json_encode($product_images);
            }

            if ($request->file('image')) {
                $product->thumbnail = ImageManager::update('product/thumbnail/', $product->thumbnail, 'png', $request->file('image'));
            }

            $product->meta_title = $request->meta_title;
            $product->meta_description = $request->meta_description;
            if ($request->file('meta_image')) {
                $product->meta_image = ImageManager::update('product/meta/', $product->meta_image, 'png', $request->file('meta_image'));
            }

            $product->save();
            //Custom Code
            foreach($request->country as $country_id=>$item){
                if(is_null($item['id'])){
                    ShippingCostByCountry::create([
                        'country_id' => $country_id,
                        'duration' => $item['duration'],
                        'shipping_cost' => BackEndHelper::currency_to_usd($item['cost']),
                        'product_id' => $product->id,
                    ]);
                }else{
                    ShippingCostByCountry::where('id', base64_decode($item['id']))->update([
                        'country_id' => $country_id,
                        'duration' => $item['duration'],
                        'shipping_cost' => BackEndHelper::currency_to_usd($item['cost']),
                    ]);
                }
            }

            foreach ($request->lang as $index => $key) {
                if ($request->name[$index] && $key != 'en') {
                    Translation::updateOrInsert(
                        ['translationable_type' => 'App\Model\Product',
                            'translationable_id' => $product->id,
                            'locale' => $key,
                            'key' => 'name'],
                        ['value' => $request->name[$index]]
                    );
                }
                if ($request->description[$index] && $key != 'en') {
                    Translation::updateOrInsert(
                        ['translationable_type' => 'App\Model\Product',
                            'translationable_id' => $product->id,
                            'locale' => $key,
                            'key' => 'description'],
                        ['value' => $request->description[$index]]
                    );
                }
            }
            Toastr::success('Product updated successfully.');
            return back();
        }
    }

    public function remove_image(Request $request)
    {
        ImageManager::delete('/product/' . $request['image']);
        $product = Product::find($request['id']);
        $array = [];
        if (count(json_decode($product['images'])) < 2) {
            Toastr::warning('You cannot delete all images!');
            return back();
        }
        foreach (json_decode($product['images']) as $image) {
            if ($image != $request['name']) {
                array_push($array, $image);
            }
        }
        Product::where('id', $request['id'])->update([
            'images' => json_encode($array),
        ]);
        Toastr::success('Product image removed successfully!');
        return back();
    }

    public function delete($id)
    {
        $product = Product::find($id);

        $translation = Translation::where('translationable_type', 'App\Model\Product')
            ->where('translationable_id', $id);
        $translation->delete();

        Cart::where('product_id', $product->id)->delete();
        Wishlist::where('product_id', $product->id)->delete();

        foreach (json_decode($product['images'], true) as $image) {
            ImageManager::delete('/product/' . $image);
        }
        ImageManager::delete('/product/thumbnail/' . $product['thumbnail']);
        $product->delete();

        FlashDealProduct::where(['product_id' => $id])->delete();
        DealOfTheDay::where(['product_id' => $id])->delete();

        Toastr::success('Product removed successfully!');
        return back();
    }

   public function bulk_import_index()
    {
        session()->forget('product_import_data');
        $this->bulk_import_cleanup_data();
        $this->bulk_import_cleanup_zip();
        return view('admin-views.product.bulk-import');
    }

    /**
     * Resolve the absolute path of the temp NDJSON file that holds the cleaned preview rows
     * (one product per line), or null if none/missing. Large imports (tens of thousands of
     * rows) are streamed to disk instead of the session to keep memory and I/O bounded.
     */
    private function bulk_import_data_path(): ?string
    {
        $token = session()->get('product_import_token');
        if (!$token) {
            return null;
        }
        $path = 'bulk_import_data/' . $token . '.ndjson';
        return Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null;
    }

    /** Delete the temp preview-data file (if any) and drop its session token. */
    private function bulk_import_cleanup_data(): void
    {
        $token = session()->get('product_import_token');
        if ($token) {
            $path = 'bulk_import_data/' . $token . '.ndjson';
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
        session()->forget('product_import_token');
    }

    /**
     * Delete the temporary product-images ZIP stashed between the preview and confirm steps
     * (if any) and drop its session token. Safe to call when nothing is stashed.
     */
    private function bulk_import_cleanup_zip(): void
    {
        $token = session()->get('product_import_zip');
        if ($token) {
            $path = 'bulk_import_zip/' . $token . '.zip';
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
        session()->forget('product_import_zip');
    }

    /**
     * Resolve the absolute filesystem path of the stashed ZIP, or null if none/missing.
     */
    private function bulk_import_zip_path(): ?string
    {
        $token = session()->get('product_import_zip');
        if (!$token) {
            return null;
        }
        $path = 'bulk_import_zip/' . $token . '.zip';
        return Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null;
    }

    /**
     * Combine the file-provided details with the descriptive text extracted from the product title.
     * Keeps any existing details and appends the extracted block so no information is lost.
     */
    private function bulk_import_merge_details(?string $existing, ?string $extracted): string
    {
        $existing = trim((string)$existing);
        $extracted = trim((string)$extracted);
        if ($extracted === '') {
            return $existing;
        }
        if ($existing === '') {
            return $extracted;
        }
        return $existing . "\n\n" . $extracted;
    }

    /**
     * True if any of the comma-separated filenames is present in the ZIP basename set.
     */
    private function bulk_import_any_zip_file(string $commaSeparated, array $zipBaseNames): bool
    {
        foreach (explode(',', $commaSeparated) as $f) {
            $f = trim($f);
            if ($f !== '' && isset($zipBaseNames[BulkImportHelper::fileMatchKey($f)])) {
                return true;
            }
        }
        return false;
    }

  public function bulk_import_preview(Request $request)
    {
        // Large sheets (tens of thousands of rows) need room and time to parse.
        set_time_limit(0);
        $memLimit = (string)ini_get('memory_limit');
        if (strpos($memLimit, '-1') === false && (int)preg_replace('/[^0-9]/', '', $memLimit) < 1024) {
            @ini_set('memory_limit', '1024M'); // give large sheets room; leave "unlimited" alone
        }

        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error('You have uploaded a wrong format file, please upload the right file.');
            return back();
        }

        // Always start from a clean slate for any previous run's temp data + images ZIP.
        $this->bulk_import_cleanup_data();
        $this->bulk_import_cleanup_zip();

        // Stash the uploaded images ZIP (if any) so the confirm step can read it.
        // A broken/unreadable ZIP must NOT block the product import — images just fall back.
        $zipBaseNames = [];  // lower(basename) => true  (for preview "source" labels)
        $zipStems = [];      // lower(stem) => true
        if ($request->hasFile('images_zip')) {
            $token = uniqid('zip_', true);
            $request->file('images_zip')->storeAs('bulk_import_zip', $token . '.zip', 'local');
            session()->put('product_import_zip', $token);

            $absPath = $this->bulk_import_zip_path();
            $zip = new \ZipArchive();
            if ($absPath && $zip->open($absPath) === true) {
                foreach (BulkImportProcessor::zipEntries($zip) as $entry) {
                    $zipBaseNames[$entry['base']] = true;
                    $zipStems[$entry['stem']] = true;
                }
                $zip->close();
            } else {
                Toastr::warning('The images ZIP could not be read. Products will still import; ZIP images will be skipped.');
                $this->bulk_import_cleanup_zip();
            }
        }

        // Upload-form defaults. Row values override these; these override hard-coded fallbacks.
        $defaults = [
            'brand_name'          => $request->input('default_brand_name'),
            'supplier_currency'   => $request->input('default_supplier_currency'),
            'exchange_rate'       => $request->input('default_exchange_rate'),
            'landed_cost_percent' => $request->input('default_landed_cost_percent'),
            'margin_percent'      => $request->input('default_margin_percent'),
            'unit'                => $request->input('default_unit'),
            'tax'                 => $request->input('default_tax'),
            'stock'               => $request->input('default_stock'),
            'refundable'          => $request->input('default_refundable'),
            'rounding'            => $request->input('price_rounding', 'whole'),
            'allow_below'         => $request->boolean('allow_price_below_purchase'),
            'process_images'      => $request->boolean('process_images'),
        ];
        $defaultBrand = trim((string)($defaults['brand_name'] ?? ''));

        // Brand fallback for rows with no brand value: prefer the form default, else infer from
        // the supplier file name (e.g. "INT_2025-09-16 Telemecanique.xlsx" -> "Telemecanique").
        $inferredBrand = '';
        if ($defaultBrand === '' && $request->hasFile('products_file')) {
            $inferredBrand = BulkImportHelper::inferBrandFromFilename($request->file('products_file')->getClientOriginalName());
        }
        $fallbackBrand = $defaultBrand !== '' ? $defaultBrand : $inferredBrand;

        // Valid existing-id sets keyed by category table position (0=main,1=sub,2=sub-sub).
        $validIds = [
            0 => Category::where('position', 0)->pluck('id')->map(fn ($i) => (int)$i)->toArray(),
            1 => Category::where('position', 1)->pluck('id')->map(fn ($i) => (int)$i)->toArray(),
            2 => Category::where('position', 2)->pluck('id')->map(fn ($i) => (int)$i)->toArray(),
        ];
        $valid_brands = Brand::pluck('id')->map(fn ($i) => (int)$i)->toArray();
        $brandIdToName = Brand::pluck('name', 'id')->toArray(); // for auto-name when brand is given by id

        // Normalised lookup maps so we can flag "new vs existing" without creating anything yet.
        $catMap = BulkImportHelper::buildCategoryMap();
        $brandMap = BulkImportHelper::buildBrandMap();

        // Existing product_code => id, for upsert (update existing) detection.
        $existing_products = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->pluck('id', 'product_code')->toArray();

        // Stream cleaned rows to a temp NDJSON file (one product per line) instead of the
        // session — a 70k-row array in the session/memory is the main cause of slow previews.
        $token = uniqid('imp_', true);
        Storage::disk('local')->makeDirectory('bulk_import_data');
        $dataPath = Storage::disk('local')->path('bulk_import_data/' . $token . '.ndjson');
        $fh = fopen($dataPath, 'w');
        $validCount = 0;

        $sample = [];                 // only the first rows are kept in memory for the preview table
        $sampleLimit = 100;
        $failed_rows = [];            // capped for display (see $failedLimit)
        $failedLimit = 200;
        $failed_total = 0;
        $summary = [
            'total_rows' => 0, 'to_create' => 0, 'to_update' => 0, 'skipped' => 0,
            'new_brands' => 0, 'new_l1' => 0, 'new_l2' => 0, 'new_l3' => 0,
            'calc_purchase' => 0, 'calc_unit' => 0,
            'default_unit' => 0, 'default_moq' => 0, 'default_stock' => 0,
            'unit_below_purchase' => 0,
            'with_image_source' => 0, 'without_image_source' => 0,
        ];
        $previewNewBrand = [];
        $previewNewCat = [];

        // Local helper to record a skipped/failed row without unbounded memory growth.
        $recordFailure = function ($rowNo, $reason) use (&$failed_rows, &$failed_total, &$summary, $failedLimit) {
            $summary['skipped']++;
            $failed_total++;
            if (count($failed_rows) < $failedLimit) {
                $failed_rows[] = ['row' => $rowNo, 'reason' => $reason];
            }
        };

        foreach ($collections as $index => $collection) {
            $rowNo = $index + 1;
            $row = BulkImportHelper::mapRow((array)$collection);

            // Silently skip fully empty (formatting-only) rows — not counted as processed or failed.
            if (BulkImportHelper::isRowEmpty($row)) {
                continue;
            }
            $summary['total_rows']++;

            $code = trim((string)($row['product_code'] ?? ''));

            // product_code / Part# is the anchor for supplier files (product name is optional).
            if ($code === '') {
                $recordFailure($rowNo, 'Missing product code / Part#.');
                continue;
            }

            // Required: a resolvable L1 category, via a valid category_id OR a category name.
            $hasL1Id = isset($row['category_id']) && is_numeric($row['category_id']) && in_array((int)$row['category_id'], $validIds[0], true);
            $hasL1Name = trim((string)($row['category_name'] ?? '')) !== '';
            if (!$hasL1Id && !$hasL1Name) {
                $recordFailure($rowNo, 'Missing category (need a valid category_id or a category / category_name).');
                continue;
            }

            // Brand: valid brand_id wins; else a brand name from the row, else the upload-form default brand.
            $hasBrandId = isset($row['brand_id']) && is_numeric($row['brand_id']) && in_array((int)$row['brand_id'], $valid_brands, true);
            $brandNameRow = trim((string)($row['brand_name'] ?? ''));
            if ($hasBrandId) {
                $brandIdForRow = (int)$row['brand_id'];
                $brandNameForRow = null;
                $brandForName = $brandIdToName[$brandIdForRow] ?? '';
            } else {
                $brandIdForRow = null;
                // Priority: row brand > form default brand > brand inferred from the file name.
                $brandNameForRow = $brandNameRow !== '' ? $brandNameRow : $fallbackBrand;
                $brandForName = $brandNameForRow;
            }
            if (!$hasBrandId && $brandNameForRow === '') {
                $recordFailure($rowNo, 'Missing brand (no brand/brand_name column, no default brand on the form, and none inferable from the file name).');
                continue;
            }

            // Product name: use the column if present, else auto-generate from brand + code + specific category.
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                $name = BulkImportHelper::generateProductName($brandForName, $code, $row['product_specific_category'] ?? '');
            }
            if ($name === '') {
                // Should not happen (code is present), but never store a blank name.
                $name = $code;
            }

            // Clean the title down to just the part/model number; keep the rest of the descriptive
            // text for the product details so nothing is lost. No-op when no confident code is found.
            $titleInfo = BulkImportHelper::extractProductTitleAndDescription($name, $brandForName, $code);
            $name = $titleInfo['product_name'];
            $extractedDescription = $titleInfo['description']; // '' when the title was kept unchanged

            // SEO meta: title from the part number, description from brand/series/type. The "type"
            // comes from the parsed title, else the Product Specific Category, else the category leaf.
            $metaType = $titleInfo['type'] ?: trim((string)($row['product_specific_category'] ?? ''));
            if ($metaType === '') {
                $metaType = trim((string)($row['sub_sub_category_name'] ?? $row['sub_category_name'] ?? $row['category_name'] ?? ''));
            }
            $meta = BulkImportHelper::buildSeoMeta($name, $brandForName, $metaType, $titleInfo['series'] ?? null);

            // Price + unit/MOQ/stock resolution with layered defaults.
            $price = BulkImportHelper::computePricing($row, $defaults);
            if (isset($price['error'])) {
                $summary['unit_below_purchase']++;
                $recordFailure($rowNo, $price['error']);
                continue;
            }
            if ($price['unit_below_purchase']) { $summary['unit_below_purchase']++; }
            if ($price['flags']['calc_purchase']) { $summary['calc_purchase']++; }
            if ($price['flags']['calc_unit'])     { $summary['calc_unit']++; }
            if ($price['flags']['default_unit'])  { $summary['default_unit']++; }
            if ($price['flags']['default_moq'])   { $summary['default_moq']++; }
            if ($price['flags']['default_stock']) { $summary['default_stock']++; }

            $action = isset($existing_products[$code]) ? 'update' : 'create';
            if ($action === 'update') { $summary['to_update']++; } else { $summary['to_create']++; }

            // Best-effort "new record" preview counts (de-duplicated within this preview).
            if (!$hasBrandId && $brandNameForRow !== '') {
                $bkey = BulkImportHelper::normalizeKey($brandNameForRow);
                if (!isset($brandMap[$bkey]) && !isset($previewNewBrand[$bkey])) {
                    $previewNewBrand[$bkey] = true;
                    $summary['new_brands']++;
                }
            }
            foreach ([['category_name', 0, 'new_l1'], ['sub_category_name', 1, 'new_l2'], ['sub_sub_category_name', 2, 'new_l3']] as $levelInfo) {
                [$field, $pos, $skey] = $levelInfo;
                $val = trim((string)($row[$field] ?? ''));
                if ($val === '') { continue; }
                $nkey = BulkImportHelper::normalizeKey($val);
                $existsAtPos = false;
                foreach (($catMap[$pos] ?? []) as $byParent) {
                    if (isset($byParent[$nkey])) { $existsAtPos = true; break; }
                }
                $pkey = $pos . '|' . $nkey;
                if (!$existsAtPos && !isset($previewNewCat[$pkey])) {
                    $previewNewCat[$pkey] = true;
                    $summary[$skey]++;
                }
            }

            $l2Id = (isset($row['sub_category_id']) && is_numeric($row['sub_category_id']) && in_array((int)$row['sub_category_id'], $validIds[1], true)) ? (int)$row['sub_category_id'] : null;
            $l3Id = (isset($row['sub_sub_category_id']) && is_numeric($row['sub_sub_category_id']) && in_array((int)$row['sub_sub_category_id'], $validIds[2], true)) ? (int)$row['sub_sub_category_id'] : null;

            // Label the likely image source for the preview (priority: ZIP file > URL > ZIP auto-match > none).
            $thumbFile = trim((string)($row['thumbnail_file'] ?? ''));
            $galleryFiles = trim((string)($row['gallery_files'] ?? ''));
            $thumbUrl = trim((string)($row['thumbnail_url'] ?? ''));
            $galleryUrls = trim((string)($row['gallery_urls'] ?? ''));
            $codeStem = mb_strtolower($code);
            if (($thumbFile !== '' && isset($zipBaseNames[BulkImportHelper::fileMatchKey($thumbFile)])) ||
                ($galleryFiles !== '' && $this->bulk_import_any_zip_file($galleryFiles, $zipBaseNames))) {
                $imageNote = 'ZIP (named)';
            } elseif ($thumbUrl !== '' || $galleryUrls !== '') {
                $imageNote = 'URL';
            } elseif (isset($zipStems[$codeStem]) || isset($zipStems[$codeStem . '_1'])) {
                $imageNote = 'ZIP (auto)';
            } else {
                $imageNote = 'none';
            }
            if ($imageNote === 'none') { $summary['without_image_source']++; } else { $summary['with_image_source']++; }

            $rowData = [
                '_action'      => $action,
                '_existing_id' => $action === 'update' ? (int)$existing_products[$code] : null,
                '_row'         => $rowNo,
                'name'         => $name,
                'product_code' => $code,
                'cat'          => [
                    'l1' => ['id' => $hasL1Id ? (int)$row['category_id'] : null, 'name' => $row['category_name'] ?? null],
                    'l2' => ['id' => $l2Id, 'name' => $row['sub_category_name'] ?? null],
                    'l3' => ['id' => $l3Id, 'name' => $row['sub_sub_category_name'] ?? null],
                ],
                'brand'        => ['id' => $brandIdForRow, 'name' => $brandNameForRow], // default brand already applied
                'unit'         => $price['unit'],
                'min_qty'      => $price['min_qty'],
                'refundable'   => $price['refundable'],
                'unit_price'   => $price['unit_price'],       // store currency; converted to USD at confirm
                'purchase_price' => $price['purchase_price'], // store currency; converted to USD at confirm
                'tax'          => $price['tax'],
                'discount'     => $price['discount'],
                'discount_type' => $price['discount_type'],
                'current_stock' => $price['current_stock'],
                // Merge any file-provided details with the descriptive text extracted from the title.
                'details'      => $this->bulk_import_merge_details($row['details_html'] ?? $row['details'] ?? '', $extractedDescription),
                'meta_title'        => $meta['meta_title'],
                'meta_description'  => $meta['meta_description'],
                'youtube_video_url' => $row['youtube_video_url'] ?? '',
                'thumbnail_url' => $row['thumbnail_url'] ?? '',
                'gallery_urls' => $row['gallery_urls'] ?? '',
                'thumbnail_file' => $row['thumbnail_file'] ?? '', // matched against the ZIP at confirm
                'gallery_files' => $row['gallery_files'] ?? '',   // matched against the ZIP at confirm
                'image_note'   => $imageNote,
                'flags'        => $price['flags'],
                'unit_below_purchase' => $price['unit_below_purchase'],
            ];

            // Stream this row to disk; only keep a small sample in memory for the preview table.
            fwrite($fh, json_encode($rowData) . "\n");
            $validCount++;
            if (count($sample) < $sampleLimit) {
                $sample[] = $rowData;
            }
        }

        fclose($fh);
        unset($collections); // free the parsed sheet before rendering

        if ($validCount === 0) {
            $this->bulk_import_cleanup_data();
            Toastr::error('No valid products found. Check the failed-rows list below or your column headers.');
            return view('admin-views.product.bulk-import', [
                'preview_summary' => $summary,
                'preview_failed'  => $failed_rows,
                'preview_failed_total' => $failed_total,
            ]);
        }

        if ($defaultBrand === '' && $inferredBrand !== '') {
            Toastr::info("No brand column or default brand set — using '{$inferredBrand}' inferred from the file name for rows without a brand.");
        }
        if ($summary['skipped'] > 0) {
            Toastr::warning("Skipped {$summary['skipped']} row(s). See the failed-rows list for reasons.");
        }

        session()->put('product_import_token', $token);
        session()->put('product_import_filename', $request->hasFile('products_file') ? $request->file('products_file')->getClientOriginalName() : null);
        session()->put('product_import_options', array_merge($defaults, ['resolved_fallback_brand' => $fallbackBrand]));
        session()->forget('product_import_data'); // legacy session payload no longer used
        return view('admin-views.product.bulk-import', [
            'preview_data'    => $sample,
            'preview_total'   => $validCount,
            'preview_summary' => $summary,
            'preview_failed'  => $failed_rows,
            'preview_failed_total' => $failed_total,
        ]);
    }

    /**
     * Confirm import: instead of processing everything in this one (slow) request, create a
     * ProductImportJob pointing at the already-prepared NDJSON file and redirect to the progress
     * page. The browser then drives chunked processing via Ajax so nothing times out.
     */
    public function bulk_import_data(Request $request)
    {
        $token = session()->get('product_import_token');
        $dataPath = $this->bulk_import_data_path();
        if (!$token || !$dataPath) {
            Toastr::error('Session expired or no data found. Please upload the file again.');
            return redirect()->route('admin.product.bulk-import');
        }

        // Count rows quickly (one cheap pass; no row processing here).
        $totalRows = 0;
        $fh = fopen($dataPath, 'r');
        if ($fh) {
            while (fgets($fh) !== false) { $totalRows++; }
            fclose($fh);
        }
        if ($totalRows === 0) {
            $this->bulk_import_cleanup_data();
            Toastr::error('No prepared rows found. Please upload the file again.');
            return redirect()->route('admin.product.bulk-import');
        }

        // Carry the upload-form options to the job. If image processing is on and a ZIP was stashed,
        // hand the ZIP to the job (keep the file); otherwise release the ZIP now.
        $options = session('product_import_options') ?? [];
        $zipToken = session('product_import_zip');
        if (!empty($options['process_images']) && $zipToken) {
            $options['zip_token'] = $zipToken;
            session()->forget('product_import_zip'); // keep the file — the job owns it now
        } else {
            $this->bulk_import_cleanup_zip();         // images off or no ZIP — delete it
        }

        $job = ProductImportJob::create([
            'file_path'          => 'bulk_import_data/' . $token . '.ndjson',
            'original_file_name' => session('product_import_filename'),
            'status'             => 'pending',
            'total_rows'         => $totalRows,
            'import_options'     => $options,
            'admin_id'           => auth('admin')->id(),
        ]);

        // Hand the temp data file over to the job. Drop the session token WITHOUT deleting the file
        // (the job owns it now and the chunk processor cleans it up when finished).
        session()->forget('product_import_token');
        session()->forget('product_import_filename');
        session()->forget('product_import_options');

        return redirect()->route('admin.product.bulk-import-progress', ['job' => $job->id]);
    }

    /** Live progress page for a background import job. */
    public function bulk_import_progress($id)
    {
        $job = ProductImportJob::findOrFail($id);
        return view('admin-views.product.bulk-import-progress', ['job' => $job]);
    }

    /** Read-only JSON snapshot of a job's progress (polled by the progress page). */
    public function bulk_import_progress_status($id)
    {
        $job = ProductImportJob::findOrFail($id);
        return response()->json($this->bulk_import_job_payload($job));
    }

    /**
     * Process the next chunk of a job and return updated progress as JSON.
     * Driven by the progress page in a loop; a cache lock prevents two tabs double-processing.
     */
    public function bulk_import_process_chunk($id)
    {
        set_time_limit(0);
        $job = ProductImportJob::findOrFail($id);

        if ($job->isFinished()) {
            return response()->json($this->bulk_import_job_payload($job));
        }

        // Guard against concurrent chunk runs (e.g. two open tabs). If we can't get the lock,
        // another request is already processing — just return current progress.
        $lock = Cache::lock('product_import_job_' . $job->id, 120);
        if (!$lock->get()) {
            return response()->json($this->bulk_import_job_payload($job));
        }

        try {
            $job->refresh();
            if ($job->isFinished()) {
                return response()->json($this->bulk_import_job_payload($job));
            }
            if ($job->status === 'pending') {
                $job->status = 'processing';
                $job->started_at = now();
                $job->save();
            }

            BulkImportProcessor::processChunk($job, 200);

            if ($job->processed_rows >= $job->total_rows) {
                $job->status = 'completed';
                $job->completed_at = now();
                $job->save();
                // Clean up the temp data file (and any images ZIP) now that the job is done.
                if ($job->file_path && Storage::disk('local')->exists($job->file_path)) {
                    Storage::disk('local')->delete($job->file_path);
                }
                $zipToken = $job->import_options['zip_token'] ?? null;
                if ($zipToken && Storage::disk('local')->exists('bulk_import_zip/' . $zipToken . '.zip')) {
                    Storage::disk('local')->delete('bulk_import_zip/' . $zipToken . '.zip');
                }
            }
        } catch (\Throwable $e) {
            $job->status = 'failed';
            $job->error_message = $e->getMessage();
            $job->completed_at = now();
            $job->save();
            \Illuminate\Support\Facades\Log::error('Bulk import job ' . $job->id . ' failed: ' . $e->getMessage());
        } finally {
            optional($lock)->release();
        }

        return response()->json($this->bulk_import_job_payload($job));
    }

    /** Shared JSON shape for the status + process-chunk endpoints. */
    private function bulk_import_job_payload(ProductImportJob $job): array
    {
        return [
            'status'         => $job->status,
            'total_rows'     => $job->total_rows,
            'processed_rows' => $job->processed_rows,
            'percentage'     => $job->percentage(),
            'created_count'  => $job->created_count,
            'updated_count'  => $job->updated_count,
            'skipped_count'  => $job->skipped_count,
            'failed_count'   => $job->failed_count,
            'new_brands_count'             => $job->new_brands_count,
            'new_categories_count'         => $job->new_categories_count,
            'new_sub_categories_count'     => $job->new_sub_categories_count,
            'new_sub_sub_categories_count' => $job->new_sub_sub_categories_count,
            'calculated_purchase_price_count' => $job->calculated_purchase_price_count,
            'calculated_selling_price_count'  => $job->calculated_selling_price_count,
            'default_unit_used_count'  => $job->default_unit_used_count,
            'default_moq_used_count'   => $job->default_moq_used_count,
            'default_stock_used_count' => $job->default_stock_used_count,
            'with_images_count'            => $job->with_images_count,
            'without_images_count'         => $job->without_images_count,
            'images_from_zip_count'        => $job->images_from_zip_count,
            'images_downloaded_count'      => $job->images_downloaded_count,
            'failed_image_downloads_count' => $job->failed_image_downloads_count,
            'invalid_images_count'         => $job->invalid_images_count,
            'error_message'  => $job->error_message,
            'failed_rows'    => $job->isFinished() ? ($job->failed_rows ?? []) : [],
            'updated_at'     => optional($job->updated_at)->toDateTimeString(),
        ];
    }

    /** Image-pipeline dashboard (live "560 / 6000 done" view). */
    public function image_pipeline()
    {
        return view('admin-views.product.image-pipeline');
    }

    /** JSON progress for the image pipeline, computed from products.image_status. */
    public function image_pipeline_status()
    {
        $counts = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->select('image_status', DB::raw('count(*) as c'))
            ->groupBy('image_status')->pluck('c', 'image_status');

        $get = fn ($k) => (int) ($counts[$k] ?? 0);
        $placeholder = $get('placeholder') + (int) ($counts[''] ?? 0) + (int) ($counts[null] ?? 0);
        $fetched = $get('fetched');
        $reused = $get('reused');
        $failed = $get('failed');
        $review = $get('manual_review');
        $queued = $get('queued');
        $total = (int) array_sum($counts->all());
        $processed = $fetched + $reused + $failed + $review;

        return response()->json([
            'total'         => $total,
            'placeholder'   => $placeholder,
            'queued'        => $queued,
            'processed'     => $processed,
            'fetched'       => $fetched,
            'reused'        => $reused,
            'failed'        => $failed,
            'manual_review' => $review,
            'percentage'    => $total > 0 ? round($processed / $total * 100, 2) : 0,
            'updated_at'    => now()->toDateTimeString(),
        ]);
    }

    /** CSV export of products needing manual image handling (manual_review / failed). */
    public function image_review_export()
    {
        $rows = DB::table('products as p')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereIn('p.image_status', ['manual_review', 'failed'])
            ->orderBy('p.id')
            ->get(['p.id', 'p.name', 'p.product_code', 'p.image_family_key', 'p.thumbnail', 'p.image_error', 'b.name as brand']);

        $filename = 'image_review_' . now()->format('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['product_id', 'name', 'brand', 'mpn', 'family_key', 'current_image', 'error', 'suggested_search']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->id, $r->name, $r->brand, $r->product_code, $r->image_family_key,
                    $r->thumbnail, $r->image_error,
                    trim(($r->brand ? $r->brand . ' ' : '') . $r->product_code), // suggested manual search query
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Visual gallery of products by image status/source — shows the actual fetched thumbnails with
     * source/confidence, paginated and filterable. Read-only.
     */
    public function image_gallery(Request $request)
    {
        $status = $request->get('status', 'fetched'); // fetched | reused | manual_review | failed | with_image | all
        $source = trim((string) $request->get('source', ''));

        $q = DB::table('products as p')
            ->leftJoin('brands as b', 'b.id', '=', 'p.brand_id')
            ->whereNotNull('p.product_code')->where('p.product_code', '!=', '')
            ->orderByDesc('p.image_last_attempt_at')->orderByDesc('p.id');

        if ($status === 'with_image') {
            $q->whereIn('p.image_status', ['fetched', 'reused']);
        } elseif ($status !== 'all') {
            $q->where('p.image_status', $status);
        }
        if ($source !== '') {
            $q->where('p.image_source', 'like', $source . '%');
        }

        $products = $q->select(
            'p.id', 'p.product_code', 'p.name', 'p.slug', 'p.thumbnail', 'p.image_status',
            'p.image_source', 'p.image_confidence', 'p.image_family_key', 'p.image_error', 'b.name as brand'
        )->paginate(60)->appends($request->query());

        $counts = DB::table('products')
            ->whereNotNull('product_code')->where('product_code', '!=', '')
            ->select('image_status', DB::raw('count(*) as c'))->groupBy('image_status')->pluck('c', 'image_status');

        return view('admin-views.product.image-gallery', compact('products', 'status', 'source', 'counts'));
    }

    // ───────────────────────────── Price-only update (admin UI) ─────────────────────────────
    // Match existing products by product_code and update ONLY the price columns (unit_price,
    // purchase_price, discount, discount_type). Never creates products, never touches name/brand/
    // category/description/images/stock/live status. Two steps: upload→preview (read-only), then
    // confirm→apply (with a reversible JSON backup). Shares all logic with the CLI via PriceUpdater.

    /** Absolute path of the temp JSON holding {changes, not_found} for the confirm step, or null. */
    private function price_update_path(): ?string
    {
        $token = session()->get('price_update_token');
        if (!$token) {
            return null;
        }
        $path = 'price_update/' . $token . '.json';
        return Storage::disk('local')->exists($path) ? Storage::disk('local')->path($path) : null;
    }

    /** Delete the temp preview file (if any) and drop the session keys. */
    private function price_update_cleanup(): void
    {
        $token = session()->get('price_update_token');
        if ($token) {
            $path = 'price_update/' . $token . '.json';
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
        session()->forget(['price_update_token', 'price_update_summary']);
    }

    public function price_update_index()
    {
        $this->price_update_cleanup();
        return view('admin-views.product.price-update');
    }

    /** Step 1 — read the sheet, analyse against the catalogue (READ-ONLY), show old→new preview. */
    public function price_update_preview(Request $request)
    {
        set_time_limit(0);
        $request->validate([
            'products_file' => 'required|file|mimes:xlsx,xls,csv,txt',
        ]);

        $defaults = [
            'exchange_rate'       => $request->input('exchange_rate'),
            'landed_cost_percent' => $request->input('landed_cost_percent'),
            'margin_percent'      => $request->input('margin_percent'),
            'rounding'            => $request->input('rounding', 'whole') ?: 'whole',
            'allow_below'         => (bool) $request->input('allow_below'),
        ];

        try {
            $rows = (new FastExcel)->import($request->file('products_file')->getRealPath());
        } catch (\Throwable $e) {
            Toastr::error('Could not read the file: ' . $e->getMessage());
            return back();
        }

        $res = PriceUpdater::analyze($rows, $defaults);
        $notFound = array_values(array_unique($res['not_found']));

        // Persist the change set so the confirm step needs no re-upload (sheets can be large).
        $this->price_update_cleanup();
        $token = Str::random(40);
        Storage::disk('local')->put(
            'price_update/' . $token . '.json',
            json_encode(['changes' => $res['changes'], 'not_found' => $notFound])
        );
        session()->put('price_update_token', $token);

        $summary = [
            'processed' => $res['processed'], 'matched' => $res['matched'], 'changed' => $res['changed'],
            'skipped'   => $res['skipped'],   'invalid' => $res['invalid'], 'not_found' => count($notFound),
        ];
        session()->put('price_update_summary', $summary);

        $sample = array_slice($res['changes'], 0, 100);
        return view('admin-views.product.price-update', compact('sample', 'summary', 'notFound'));
    }

    /** Step 2 — apply the previewed price changes (price columns only) with a reversible backup. */
    public function price_update_apply(Request $request)
    {
        set_time_limit(0);
        $path = $this->price_update_path();
        if (!$path) {
            Toastr::error('Your preview expired. Please upload the file again.');
            return redirect()->route('admin.products.price-update');
        }

        $data = json_decode((string) file_get_contents($path), true) ?: [];
        $changes = $data['changes'] ?? [];
        if (empty($changes)) {
            Toastr::warning('No price changes to apply.');
            return redirect()->route('admin.products.price-update');
        }

        $backup = PriceUpdater::applyChanges($changes);

        Storage::disk('local')->makeDirectory('backups');
        $bf = 'backups/price_update_backup_' . now()->format('Ymd_His') . '.json';
        Storage::disk('local')->put($bf, json_encode($backup, JSON_PRETTY_PRINT));

        $this->price_update_cleanup();
        Toastr::success(count($backup) . ' product price(s) updated. Backup: storage/app/' . $bf);
        return redirect()->route('admin.products.price-update');
    }

    /** Download the not-found product codes (from the current preview) as CSV. */
    public function price_update_not_found_export()
    {
        $path = $this->price_update_path();
        $notFound = [];
        if ($path) {
            $data = json_decode((string) file_get_contents($path), true) ?: [];
            $notFound = $data['not_found'] ?? [];
        }

        $filename = 'price_update_not_found_' . now()->format('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($notFound) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['product_code']);
            foreach ($notFound as $c) {
                fputcsv($out, [$c]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Full product data export. Produces a formatted .xlsx whose columns match the
     * bulk-import headers (so the file can be edited and re-imported) plus extra
     * human-readable reference columns. Read-only: no product is modified.
     */
    public function bulk_export_data(Request $request)
    {
        set_time_limit(0);

        $file_name = 'full_products_export_' . now()->format('Y_m_d_H_i') . '.xlsx';

        return Excel::download(new FullProductExport(), $file_name);
    }

    public function barcode(Request $request, $id)
    {

        if ($request->limit > 270) {
            Toastr::warning(translate('You can not generate more than 270 barcode'));
             return back();
        }
        $product = Product::findOrFail($id);
        $limit =  $request->limit ?? 4;
        return view('admin-views.product.barcode', compact('product', 'limit'));
    }

}
