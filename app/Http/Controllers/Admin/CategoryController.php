<?php

namespace App\Http\Controllers\Admin;

use App\CPU\Helpers;
use App\CPU\ImageManager;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\Translation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Brian2694\Toastr\Facades\Toastr;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        if($request->has('search'))
        {
            $key = explode(' ', $request['search']);
            $categories = Category::where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }else{
            $categories = Category::where(['position' => 0]);
        }

        $categories = $categories->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.category.view', compact('categories','search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'image' => 'required',
            'priority'=>'required'
        ], [
            'name.required' => 'Category name is required!',
            'image.required' => 'Category image is required!',
            'priority.required' => 'Category priority is required!',
        ]);

        $category = new Category;
        $category->name = $request->name[array_search('en', $request->lang)];
        $category->slug = Str::slug($request->name[array_search('en', $request->lang)]);
        $category->icon = ImageManager::upload('category/', 'png', $request->file('image'));
        $category->parent_id = 0;
        $category->position = 0;
        $category->priority = $request->priority;
        $category->save();

        $data = [];
        foreach ($request->lang as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                array_push($data, array(
                    'translationable_type' => 'App\Model\Category',
                    'translationable_id' => $category->id,
                    'locale' => $key,
                    'key' => 'name',
                    'value' => $request->name[$index],
                ));
            }
        }
        if (count($data)) {
            Translation::insert($data);
        }

        Toastr::success('Category added successfully!');
        return back();
    }

    public function edit(Request $request, $id)
    {
        $category = category::withoutGlobalScopes()->find($id);
        return view('admin-views.category.category-edit', compact('category'));
    }

    public function update(Request $request)
    {
        $category = Category::find($request->id);
        $category->name = $request->name[array_search('en', $request->lang)];
        $category->slug = Str::slug($request->name[array_search('en', $request->lang)]);
        if ($request->image) {
            $category->icon = ImageManager::update('category/', $category->icon, 'png', $request->file('image'));
        }
        $category->priority = $request->priority;
        $category->save();

        foreach ($request->lang as $index => $key) {
            if ($request->name[$index] && $key != 'en') {
                Translation::updateOrInsert(
                    ['translationable_type' => 'App\Model\Category',
                        'translationable_id' => $category->id,
                        'locale' => $key,
                        'key' => 'name'],
                    ['value' => $request->name[$index]]
                );
            }
        }

        Toastr::success('Category updated successfully!');
        return back();
    }

    public function delete(Request $request)
    {
        $categories = Category::where('parent_id', $request->id)->get();
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $categories1 = Category::where('parent_id', $category->id)->get();
                if (!empty($categories1)) {
                    foreach ($categories1 as $category1) {
                        $translation = Translation::where('translationable_type','App\Model\Category')
                                    ->where('translationable_id',$category1->id);
                        $translation->delete();
                        Category::destroy($category1->id);

                    }
                }
                $translation = Translation::where('translationable_type','App\Model\Category')
                                    ->where('translationable_id',$category->id);
                $translation->delete();
                Category::destroy($category->id);

            }
        }
        $translation = Translation::where('translationable_type','App\Model\Category')
                                    ->where('translationable_id',$request->id);
        $translation->delete();
        Category::destroy($request->id);

        return response()->json();
    }

    public function fetch(Request $request)
    {
        if ($request->ajax()) {
            $data = Category::where('position', 0)->orderBy('id', 'desc')->get();
            return response()->json($data);
        }
    }

    public function status(Request $request)
    {
        $category = Category::find($request->id);
        $category->home_status = $request->home_status;
        $category->save();
        // Toastr::success('Service status updated!');
        // return back();
        return response()->json([
            'success' => 1,
        ], 200);
    }
    public function export()
    {
        // This grabs all main categories (position 0 usually means parent category in this CMS)
        $categories = \App\Model\Category::where(['position' => 0])->latest()->get();

        $data = array();
        foreach($categories as $category){
            $data[] = array(
                'Category ID'   => $category->id,
                'Category Name' => $category->name,
                'Slug'          => $category->slug,
            );
        }

        return (new FastExcel($data))->download('category_list.xlsx');
    }
    public function bulk_import_index()
    {
        session()->forget('category_import_data'); 
        return view('admin-views.category.bulk-import');
    }

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
                'name'        => $collection['name'],
                'slug'        => \Illuminate\Support\Str::slug($collection['name'], '-'), // Auto-generates the URL slug
                'icon'        => $collection['icon'] ?? 'def.png', // Fallback icon
                'parent_id'   => 0, // 0 means it is a Main Category
                'position'    => 0, // 0 means it is a Main Category
                'priority'    => 0, // Default priority
                'home_status' => 1, // Visible on home page by default
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        if (count($data) == 0) {
            Toastr::warning('No valid categories found to import. Please check your file data.');
            return back();
        }

        session()->put('category_import_data', $data);
        return view('admin-views.category.bulk-import', ['preview_data' => $data]);
    }

    public function bulk_import_data(Request $request)
    {
        $data = session()->get('category_import_data');

        if (!$data) {
            Toastr::error('Session expired or no data found. Please upload the file again.');
            return redirect()->route('admin.category.bulk-import');
        }

        DB::table('categories')->insert($data);
        session()->forget('category_import_data');

        Toastr::success(count($data) . ' - Categories imported successfully!');
        return redirect()->route('admin.category.view'); 
    }
}
