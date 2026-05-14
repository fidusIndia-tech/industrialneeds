<?php

namespace App\Http\Controllers\Admin;

use App\CPU\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\Translation;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\DB;

class SubCategoryController extends Controller
{
    public function index( Request $request )
    {
        $query_param = [];
        $search = $request['search'];
        if($request->has('search'))
        {
            $key = explode(' ', $request['search']);
            $categories = Category::where(['position'=>1])->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
            $query_param = ['search' => $request['search']];
        }else{
            $categories=Category::where(['position'=>1]);
        }
        $categories = $categories->latest()->paginate(Helpers::pagination_limit())->appends($query_param);
        return view('admin-views.category.sub-category-view',compact('categories','search'));
    }

    public function store(Request $request)
    {
        $category = new Category;
        $category->name = $request->name[array_search('en', $request->lang)];
        $category->slug = Str::slug($request->name[array_search('en', $request->lang)]);
        $category->parent_id = $request->parent_id;
        $category->position = 1;
        $category->priority = $request->priority;
        $category->save();

        foreach($request->lang as $index=>$key)
        {
            if($request->name[$index] && $key != 'en')
            {
                Translation::updateOrInsert(
                    ['translationable_type'  => 'App\Model\Category',
                        'translationable_id'    => $category->id,
                        'locale'                => $key,
                        'key'                   => 'name'],
                    ['value'                 => $request->name[$index]]
                );
            }
        }
        Toastr::success('Category updated successfully!');
        return back();
    }

    public function edit(Request $request)
    {
        $data = Category::where('id', $request->id)->first();

        return response()->json($data);
    }

    public function update(Request $request)
    {
        $category = Category::find($request->id);
        $category->name = $request->name;
        $category->slug = Str::slug($request->name);
        $category->parent_id = $request->parent_id;
        $category->position = 1;
        $category->priority = $request->priority;
        $category->save();
        return response()->json();
    }

    public function delete(Request $request)
    {
        $categories = Category::where('parent_id', $request->id)->get();
        if (!empty($categories)) {

            foreach ($categories as $category) {
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
            $data = Category::where('position', 1)->orderBy('id', 'desc')->get();
            return response()->json($data);
        }
    }
    public function export()
    {
        // position 1 represents sub-categories. We also load the parent category.
        $categories = \App\Model\Category::with(['parent'])->where(['position' => 1])->latest()->get();

        $data = array();
        foreach($categories as $category){
            $data[] = array(
                'Sub Category ID'      => $category->id,
                'Sub Category Name'    => $category->name,
                'Parent Category Name' => $category->parent ? $category->parent->name : 'N/A', // Helps data entry!
                'Slug'                 => $category->slug,
            );
        }

        return (new FastExcel($data))->download('sub_category_list.xlsx');
    }
    public function bulk_import_index()
    {
        session()->forget('sub_category_import_data'); 
        return view('admin-views.category.sub-category-bulk-import');
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
            // VALIDATION: Sub-categories MUST have both a name and a parent_id
            if (!array_key_exists('name', $collection) || !array_key_exists('parent_id', $collection)) {
                Toastr::error('Import Failed: Your Excel headers must be exactly "name" and "parent_id" (all lowercase).');
                return back();
            }
            if ($collection['name'] === null || trim($collection['name']) === "" || $collection['parent_id'] === "") {
                continue; 
            }

            array_push($data, [
                'name'        => $collection['name'],
                'slug'        => \Illuminate\Support\Str::slug($collection['name'], '-'),
                'icon'        => 'def.png', // Sub-categories rarely use icons, default it
                'parent_id'   => $collection['parent_id'], // Uses the ID from the cheat sheet!
                'position'    => 1, // 1 means it is a Sub-Category
                'priority'    => 0,
                'home_status' => 1,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        if (count($data) == 0) {
            Toastr::warning('No valid sub-categories found to import. Please check your file data.');
            return back();
        }

        session()->put('sub_category_import_data', $data);
        return view('admin-views.category.sub-category-bulk-import', ['preview_data' => $data]);
    }

    public function bulk_import_data(Request $request)
    {
        $data = session()->get('sub_category_import_data');

        if (!$data) {
            Toastr::error('Session expired or no data found. Please upload the file again.');
            return redirect()->route('admin.sub-category.bulk-import');
        }

        DB::table('categories')->insert($data);
        session()->forget('sub_category_import_data');

        Toastr::success(count($data) . ' - Sub Categories imported successfully!');
        return redirect()->route('admin.sub-category.view'); 
    }
}
