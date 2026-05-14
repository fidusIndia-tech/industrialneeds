@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Sub Category Bulk Import'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-sm mb-2 mb-sm-0">
                    <h1 class="page-header-title">
                        <i class="tio-add-circle-outlined"></i> {{\App\CPU\translate('Bulk_Import_Sub_Categories')}}
                    </h1>
                </div>
            </div>
        </div>

        <div class="row" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-body">
                        <h5 class="mb-4 text-warning"><i class="tio-info-outined"></i> {{\App\CPU\translate('Crucial Instructions')}} :</h5>
                        <p> 1. {{\App\CPU\translate('Download the format file below.')}}</p>
                        <p> 2. {{\App\CPU\translate('The file MUST contain two exact columns: "name" and "parent_id".')}}</p>
                        <p> 3. {{\App\CPU\translate('To get the correct Parent ID, go to the Main Categories page and click the green Export button to download your ID cheat sheet.')}}</p>
                    </div>
                </div>

                @if(isset($preview_data))
                    <div class="card mt-2 border-primary">
                        <div class="card-header bg-light">
                            <h4 class="mb-0 text-primary">
                                <i class="tio-file-text-outlined"></i> {{\App\CPU\translate('Preview of Sub-Categories to Import')}} ({{ count($preview_data) }} {{\App\CPU\translate('found')}})
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{\App\CPU\translate('Row')}} #</th>
                                            <th>{{\App\CPU\translate('Sub-Category Name')}}</th>
                                            <th class="bg-soft-warning">{{\App\CPU\translate('Assigned Parent ID')}}</th>
                                            <th>{{\App\CPU\translate('Generated Slug')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($preview_data as $key => $sub_category)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td class="font-weight-bold">{{ $sub_category['name'] }}</td>
                                            <td class="font-weight-bold text-warning">{{ $sub_category['parent_id'] }}</td>
                                            <td class="text-muted">{{ $sub_category['slug'] }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <form action="{{route('admin.sub-category.bulk-import')}}" method="POST" class="mt-4 d-flex justify-content-end gap-3">
                                @csrf
                                <a href="{{route('admin.sub-category.bulk-import')}}" class="btn btn-secondary mr-2">{{\App\CPU\translate('Cancel')}}</a>
                                <button type="submit" class="btn btn-success">
                                    <i class="tio-save"></i> {{\App\CPU\translate('Confirm & Import')}}
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="card mt-2">
                        <div class="card-body">
                            <form class="product-form" action="{{route('admin.sub-category.bulk-import-preview')}}" method="POST"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group">
                                            <label class="input-label" for="products_file">{{\App\CPU\translate('Upload_Excel/CSV_File')}}</label>
                                            <div class="custom-file">
                                                <input type="file" name="products_file" class="custom-file-input" id="products_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                                                <label class="custom-file-label" for="products_file">{{\App\CPU\translate('Choose_File')}}</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mt-4">
                                    <div class="col-12 d-flex gap-3">
                                        <a href="{{asset('public/assets/sub_category_bulk_format.xlsx')}}" class="btn btn-secondary mr-2" download>
                                            <i class="tio-download-to"></i> {{\App\CPU\translate('Download_Format')}}
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="tio-visible"></i> {{\App\CPU\translate('Preview Data')}}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });
    </script>
@endpush