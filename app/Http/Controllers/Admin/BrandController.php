<?php

namespace App\Http\Controllers\Admin;

use App\CPU\Helpers;
use App\CPU\ImageManager;
use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\Brand;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Model\Translation;
use Rap2hpoutre\FastExcel\FastExcel;

class BrandController extends Controller
{
    public function add_new()
    {
        $br = Brand::latest()->paginate(Helpers::pagination_limit());
        return view('admin-views.brand.add-new', compact('br'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name.0' => 'required|unique:brands,name',
        ], [
            'name.0.required'   => 'Brand name is required!',
            'name.0.unique'     => 'The brand has already been taken.',
        ]);

        $brand = new Brand;
        $brand->name = $request->name[array_search('en', $request->lang)];
        $brand->image = ImageManager::upload('brand/', 'png', $request->file('image'));
        $brand->status = 1;
        $brand->save();

        foreach($request->lang as $index=>$key)
        {
            if($request->name[$index] && $key != 'en')
            {
                Translation::updateOrInsert(
                    ['translationable_type'  => 'App\Model\Brand',
                        'translationable_id'    => $brand->id,
                        'locale'                => $key,
                        'key'                   => 'name'],
                    ['value'                 => $request->name[$index]]
                );
            }
        }
        Toastr::success('Brand added successfully!');
        return back();
    }

    /**
     * Brand list show, search
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    function list(Request $request)
    {
        $search      = $request['search'];
        $query_param = $search ? ['search' => $request['search']] : '';

        $br = Brand::withCount('brandAllProducts')
            ->with(['brandAllProducts'=> function($query){
                $query->withCount('order_details');
            }])
            ->when($request['search'], function ($q) use($request){
                $key = explode(' ', $request['search']);
                foreach ($key as $value) {
                    $q->Where('name', 'like', "%{$value}%");
                }
            })
            ->latest()->paginate(Helpers::pagination_limit())->appends($query_param);

        return view('admin-views.brand.list', compact('br','search'));
    }

    /**
     * Export brand list by excel
     * @return string|\Symfony\Component\HttpFoundation\StreamedResponse
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function export(){
        $brands = Brand::withCount('brandAllProducts')
            ->with(['brandAllProducts'=> function($query){
                $query->withCount('order_details');
            }])->orderBy('id', 'DESC')->get();

        $data = array();
        foreach($brands as $brand){
            $data[] = array(
                'Brand ID'        => $brand->id,
                'Brand Name'      => $brand->name,
                'Total Product'   => $brand->brand_all_products_count,
                'Total Order' => $brand->brandAllProducts->sum('order_details_count'),
            );
        }

        return (new FastExcel($data))->download('brand_list.xlsx');
    }

    public function edit($id)
    {
        $b = Brand::where(['id' => $id])->withoutGlobalScopes()->first();
        return view('admin-views.brand.edit', compact('b'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name.0' => 'required|unique:brands,name,'.$id,
        ], [
            'name.0.required'   => 'Brand name is required!',
            'name.0.unique'     => 'The brand has already been taken.',
        ]);

        $brand = Brand::find($id);
        $brand->name = $request->name[array_search('en', $request->lang)];
        if ($request->has('image')) {
            $brand->image = ImageManager::update('brand/', $brand['image'], 'png', $request->file('image'));
         }
        $brand->save();
        foreach ($request->lang as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                Translation::updateOrInsert(
                    ['translationable_type' => 'App\Model\Brand',
                        'translationable_id' => $brand->id,
                        'locale' => $key,
                        'key' => 'name'],
                    ['value' => $request->name[$index]]
                );
            }
        }

        Toastr::success('Brand updated successfully!');
        return back();
    }

    public function status_update(Request $request)
    {
        $brand = Brand::find($request['id']);
        $brand->status = $request['status'];

        if($brand->save()){
            $success = 1;
        }else{
            $success = 0;
        }
        return response()->json([
            'success' => $success,
        ], 200);
    }

    public function delete(Request $request)
    {
        $translation = Translation::where('translationable_type','App\Model\Brand')
                                    ->where('translationable_id',$request->id);
        $translation->delete();
        $brand = Brand::find($request->id);
        ImageManager::delete('/brand/' . $brand['image']);
        $brand->delete();
        return response()->json();
    }
    public function bulk_import_index()
    {
        // Clear any old stuck session data so they start fresh
        session()->forget('brand_import_data'); 
        return view('admin-views.brand.bulk-import');
    }

    // Step 1: Read the file and show the preview
    public function bulk_import_preview(Request $request)
    {
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error('You have uploaded a wrong format file, please upload the right file.');
            return back();
        }

        $data = [];
        foreach ($collections as $collection) {
            if (!array_key_exists('name', $collection)) {
                Toastr::error('Import Failed: Your Excel column header must be exactly "name" (all lowercase).');
                return back();
            }
            if ($collection['name'] === null || trim($collection['name']) === "") {
                continue; 
            }

            array_push($data, [
                'name' => $collection['name'],
                'image' => $collection['image'] ?? 'def.png',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (count($data) == 0) {
            Toastr::warning('No valid brands found to import. Please check your file data.');
            return back();
        }

        // Store the validated data temporarily in the session
        session()->put('brand_import_data', $data);

        // Return back to the view, but pass the preview data!
        return view('admin-views.brand.bulk-import', ['preview_data' => $data]);
    }

    // Step 2: Save to the Database
    public function bulk_import_data(Request $request)
    {
        // Pull the data back out of the session
        $data = session()->get('brand_import_data');

        if (!$data) {
            Toastr::error('Session expired or no data found. Please upload the file again.');
            return redirect()->route('admin.brand.bulk-import');
        }

        // Insert into database
        DB::table('brands')->insert($data);
        
        // Clear the session now that we are done
        session()->forget('brand_import_data');

        Toastr::success(count($data) . ' - Brands imported successfully!');
        
        // Redirect them to the Brand List page so they can see their new brands instantly
        return redirect()->route('admin.brand.list'); 
    }
}
