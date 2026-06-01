@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Product Bulk Import'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a>
                </li>
                <li class="breadcrumb-item" aria-current="page"><a
                        href="{{route('admin.product.list', ['in_house',''])}}">{{\App\CPU\translate('Product')}}</a>
                </li>
                <li class="breadcrumb-item">{{\App\CPU\translate('bulk_import')}} </li>
            </ol>
        </nav>

        <div class="row" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
            <div class="col-12">
                <div class="card border-primary">
                    <div class="card-body">
                        <h5 class="mb-4 text-primary"><i class="tio-info-outined"></i> {{\App\CPU\translate('Crucial Instructions')}} :</h5>
                        <p> 1. {{\App\CPU\translate('Download the format file below.')}}</p>
                        <p> 2. {{\App\CPU\translate('You can use Category/Brand IDs (old way) OR plain names. If both are present, the ID wins.')}}</p>
                        <p> 3. {{\App\CPU\translate('Name columns: brand_name (or Brand), category_name (or Category) = main, sub_category_name (or Subcategory) = sub, sub_sub_category_name (or Sub-subcategory) = sub-sub.')}}</p>
                        <p> 4. {{\App\CPU\translate('Missing categories/brands are created automatically. Casing and extra spaces never create duplicates.')}}</p>
                        <p> 5. {{\App\CPU\translate('A row with an existing product_code UPDATES that product. Price/unit/stock can be left blank and will use the defaults below.')}}</p>
                    </div>
                </div>

                {{-- ===================== IMPORT SUMMARY (after confirm) ===================== --}}
                @isset($import_summary)
                    <div class="card mt-2 border-success">
                        <div class="card-header bg-light">
                            <h4 class="mb-0 text-success"><i class="tio-checkmark-circle-outlined"></i> {{\App\CPU\translate('Import Finished')}}</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr><th>{{\App\CPU\translate('Total rows processed')}}</th><td>{{ $import_summary['total'] }}</td></tr>
                                            <tr class="text-success"><th>{{\App\CPU\translate('Products created')}}</th><td>{{ $import_summary['created'] }}</td></tr>
                                            <tr class="text-primary"><th>{{\App\CPU\translate('Products updated')}}</th><td>{{ $import_summary['updated'] }}</td></tr>
                                            <tr class="text-danger"><th>{{\App\CPU\translate('Products skipped')}}</th><td>{{ $import_summary['skipped'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('New brands created')}}</th><td>{{ $import_summary['new_brands'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('New categories created')}}</th><td>{{ $import_summary['new_categories'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('New subcategories created')}}</th><td>{{ $import_summary['new_subcategories'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('New sub-subcategories created')}}</th><td>{{ $import_summary['new_sub_subcategories'] }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr><th>{{\App\CPU\translate('Rows with calculated purchase price')}}</th><td>{{ $import_summary['calc_purchase_price'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Rows with calculated selling price')}}</th><td>{{ $import_summary['calc_selling_price'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Rows using default unit')}}</th><td>{{ $import_summary['default_unit'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Rows using default MOQ')}}</th><td>{{ $import_summary['default_moq'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Rows using default stock')}}</th><td>{{ $import_summary['default_stock'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Products imported with images')}}</th><td>{{ $import_summary['with_images'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Products imported without images')}}</th><td>{{ $import_summary['without_images'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Images matched from ZIP')}}</th><td>{{ $import_summary['images_from_zip'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Images downloaded successfully')}}</th><td>{{ $import_summary['images_downloaded'] }}</td></tr>
                                            <tr class="text-danger"><th>{{\App\CPU\translate('Failed image downloads')}}</th><td>{{ $import_summary['failed_downloads'] }}</td></tr>
                                            <tr class="text-danger"><th>{{\App\CPU\translate('Invalid image files skipped')}}</th><td>{{ $import_summary['invalid_images'] }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @include('admin-views.product.partials._import-failed-rows', ['rows' => $import_failed ?? [], 'total' => $import_failed_total ?? null])

                            <div class="mt-3 d-flex justify-content-end">
                                <a href="{{route('admin.product.list', ['in_house',''])}}" class="btn btn-secondary mr-3">{{\App\CPU\translate('View Products')}}</a>
                                <a href="{{route('admin.product.bulk-import')}}" class="btn btn-primary">{{\App\CPU\translate('Import Another File')}}</a>
                            </div>
                        </div>
                    </div>
                @endisset

                {{-- ===================== PREVIEW (after upload, before confirm) ===================== --}}
                @isset($preview_summary)
                    @unless(isset($import_summary))
                    <div class="card mt-2 border-info">
                        <div class="card-header bg-light">
                            <h4 class="mb-0 text-info"><i class="tio-visible-outlined"></i> {{\App\CPU\translate('Preview')}}</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr><th>{{\App\CPU\translate('Total rows')}}</th><td>{{ $preview_summary['total_rows'] }}</td></tr>
                                            <tr class="text-success"><th>{{\App\CPU\translate('Will be created')}}</th><td>{{ $preview_summary['to_create'] }}</td></tr>
                                            <tr class="text-primary"><th>{{\App\CPU\translate('Will be updated')}}</th><td>{{ $preview_summary['to_update'] }}</td></tr>
                                            <tr class="text-danger"><th>{{\App\CPU\translate('Will be skipped')}}</th><td>{{ $preview_summary['skipped'] }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-bordered">
                                        <tbody>
                                            <tr><th>{{\App\CPU\translate('New brands (approx.)')}}</th><td>{{ $preview_summary['new_brands'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('New main / sub / sub-sub categories (approx.)')}}</th><td>{{ $preview_summary['new_l1'] }} / {{ $preview_summary['new_l2'] }} / {{ $preview_summary['new_l3'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Calculated purchase / selling price')}}</th><td>{{ $preview_summary['calc_purchase'] }} / {{ $preview_summary['calc_unit'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Default unit / MOQ / stock used')}}</th><td>{{ $preview_summary['default_unit'] }} / {{ $preview_summary['default_moq'] }} / {{ $preview_summary['default_stock'] }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Rows with / without an image source')}}</th><td>{{ $preview_summary['with_image_source'] ?? 0 }} / {{ $preview_summary['without_image_source'] ?? 0 }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            @include('admin-views.product.partials._import-failed-rows', ['rows' => $preview_failed ?? [], 'total' => $preview_failed_total ?? null])
                        </div>
                    </div>
                    @endunless
                @endisset

                @isset($preview_data)
                    <div class="card mt-2 border-success">
                        <div class="card-header bg-light">
                            <h4 class="mb-0 text-success">
                                <i class="tio-file-text-outlined"></i> {{\App\CPU\translate('Products to Import')}} ({{ $preview_total ?? count($preview_data) }} {{\App\CPU\translate('valid rows')}})
                            </h4>
                            @if(($preview_total ?? count($preview_data)) > count($preview_data))
                                <small class="text-muted">{{\App\CPU\translate('Showing the first')}} {{ count($preview_data) }} {{\App\CPU\translate('rows as a sample. All')}} {{ $preview_total }} {{\App\CPU\translate('valid rows will be imported on confirm.')}}</small>
                            @endif
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{\App\CPU\translate('Row')}} #</th>
                                            <th>{{\App\CPU\translate('Action')}}</th>
                                            <th>{{\App\CPU\translate('Product Name')}}</th>
                                            <th>{{\App\CPU\translate('Code')}}</th>
                                            <th>{{\App\CPU\translate('Brand')}}</th>
                                            <th>{{\App\CPU\translate('Category Path')}}</th>
                                            <th>{{\App\CPU\translate('Purchase')}}</th>
                                            <th>{{\App\CPU\translate('Price')}}</th>
                                            <th>{{\App\CPU\translate('Stock')}}</th>
                                            <th>{{\App\CPU\translate('Image')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($preview_data as $product)
                                        <tr>
                                            <td>{{ $product['_row'] }}</td>
                                            <td>
                                                @if($product['_action'] === 'update')
                                                    <span class="badge badge-soft-primary">{{\App\CPU\translate('Update')}}</span>
                                                @else
                                                    <span class="badge badge-soft-success">{{\App\CPU\translate('Create')}}</span>
                                                @endif
                                            </td>
                                            <td class="font-weight-bold text-left">{{ $product['name'] }}</td>
                                            <td>{{ $product['product_code'] }}</td>
                                            <td>{{ $product['brand']['id'] ? ('#'.$product['brand']['id']) : $product['brand']['name'] }}</td>
                                            <td class="text-left">
                                                {{ $product['cat']['l1']['id'] ? ('#'.$product['cat']['l1']['id']) : $product['cat']['l1']['name'] }}
                                                @if($product['cat']['l2']['id'] || $product['cat']['l2']['name']) &rarr; {{ $product['cat']['l2']['id'] ? ('#'.$product['cat']['l2']['id']) : $product['cat']['l2']['name'] }} @endif
                                                @if($product['cat']['l3']['id'] || $product['cat']['l3']['name']) &rarr; {{ $product['cat']['l3']['id'] ? ('#'.$product['cat']['l3']['id']) : $product['cat']['l3']['name'] }} @endif
                                            </td>
                                            <td>{{ number_format($product['purchase_price'], 2) }}</td>
                                            <td>{{ number_format($product['unit_price'], 2) }}</td>
                                            <td>{{ $product['current_stock'] }}</td>
                                            <td>
                                                @php($note = $product['image_note'] ?? 'none')
                                                @if($note === 'none')
                                                    <span class="badge badge-soft-secondary">{{\App\CPU\translate('none')}}</span>
                                                @else
                                                    <span class="badge badge-soft-info">{{ $note }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <form action="{{route('admin.product.bulk-import')}}" method="POST" class="mt-4 d-flex justify-content-end" id="confirm-import-form">
                                @csrf
                                <a href="{{route('admin.product.bulk-import')}}" class="btn btn-secondary mr-3">{{\App\CPU\translate('Cancel')}}</a>
                                <button type="submit" class="btn btn-success" id="confirm-import-btn">
                                    <i class="tio-save"></i> {{\App\CPU\translate('Confirm & Import Products')}}
                                </button>
                            </form>
                            <script>
                                document.getElementById('confirm-import-form').addEventListener('submit', function () {
                                    var btn = document.getElementById('confirm-import-btn');
                                    btn.disabled = true;
                                    btn.innerHTML = '<i class="tio-sync"></i> {{\App\CPU\translate('Starting import...')}}';
                                });
                            </script>
                        </div>
                    </div>
                @endisset

                {{-- ===================== UPLOAD FORM ===================== --}}
                @unless(isset($preview_data) || isset($import_summary))
                    <div class="card mt-2">
                        <div class="card-body">
                            <form class="product-form" action="{{route('admin.product.bulk-import-preview')}}" method="POST"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="products_file">{{\App\CPU\translate('Upload_Excel/CSV_File')}}</label>
                                            <div class="custom-file">
                                                <input type="file" name="products_file" class="custom-file-input" id="products_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                                                <label class="custom-file-label" for="products_file">{{\App\CPU\translate('Choose_File')}}</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="images_zip">{{\App\CPU\translate('Product Images ZIP')}} <small class="text-muted">({{\App\CPU\translate('optional')}})</small></label>
                                            <div class="custom-file">
                                                <input type="file" name="images_zip" class="custom-file-input" id="images_zip" accept=".zip,application/zip,application/x-zip-compressed">
                                                <label class="custom-file-label" for="images_zip">{{\App\CPU\translate('Choose_File')}}</label>
                                            </div>
                                            <small class="text-muted">{{\App\CPU\translate('Name files by product_code, e.g. XCKM115.jpg (thumbnail), XCKM115_1.jpg, XCKM115_2.jpg (gallery). jpg/png/webp only.')}}</small>
                                            <div class="custom-control custom-checkbox mt-2">
                                                <input type="checkbox" class="custom-control-input" id="process_images" name="process_images" value="1" checked>
                                                <label class="custom-control-label" for="process_images">{{\App\CPU\translate('Import images (download thumbnail/gallery URLs and match ZIP files)')}}</label>
                                            </div>
                                            <small class="text-muted">{{\App\CPU\translate('Uncheck for a fast data-only import (products get the placeholder image).')}}</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Prominent default brand — strongly recommended for supplier-specific files. --}}
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="default_brand_name">{{\App\CPU\translate('Default Brand Name')}}
                                                <span class="badge badge-soft-info">{{\App\CPU\translate('recommended for supplier files')}}</span>
                                            </label>
                                            <input type="text" name="default_brand_name" id="default_brand_name" class="form-control" placeholder="Telemecanique" value="{{ old('default_brand_name', request('default_brand_name')) }}">
                                            <small class="text-muted">{{\App\CPU\translate('Applied to every row that has no Brand column/value. Row values override this. If left blank, the brand is inferred from the file name (e.g. "... Telemecanique.xlsx").')}}</small>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-primary mb-3"><i class="tio-settings"></i> {{\App\CPU\translate('Optional Defaults')}}
                                    <small class="text-muted">({{\App\CPU\translate('used only when an Excel cell is blank; row values always win')}})</small>
                                </h6>
                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Supplier Currency')}}</label>
                                        <input type="text" name="default_supplier_currency" class="form-control" placeholder="EUR">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Exchange Rate')}}</label>
                                        <input type="number" step="any" min="0" name="default_exchange_rate" class="form-control" placeholder="1">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Landed Cost %')}}</label>
                                        <input type="number" step="any" min="0" name="default_landed_cost_percent" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Margin %')}}</label>
                                        <input type="number" step="any" min="0" name="default_margin_percent" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Default Unit')}}</label>
                                        <input type="text" name="default_unit" class="form-control" placeholder="pc">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Default Tax %')}}</label>
                                        <input type="number" step="any" min="0" name="default_tax" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Default Stock')}}</label>
                                        <input type="number" step="1" min="0" name="default_stock" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Default Refundable')}}</label>
                                        <select name="default_refundable" class="form-control">
                                            <option value="0">{{\App\CPU\translate('No')}}</option>
                                            <option value="1">{{\App\CPU\translate('Yes')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Price Rounding')}}</label>
                                        <select name="price_rounding" class="form-control">
                                            <option value="whole">{{\App\CPU\translate('Nearest whole number')}}</option>
                                            <option value="none">{{\App\CPU\translate('No rounding (2 decimals)')}}</option>
                                            <option value="5">{{\App\CPU\translate('Round up to nearest 5')}}</option>
                                            <option value="10">{{\App\CPU\translate('Round up to nearest 10')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group d-flex align-items-end">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="allow_below" name="allow_price_below_purchase" value="1">
                                            <label class="custom-control-label" for="allow_below">{{\App\CPU\translate('Allow selling price below purchase price')}}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12 d-flex">
                                        <a href="{{asset('public/assets/product_bulk_format.xlsx')}}" class="btn btn-secondary mr-3" download>
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
                @endunless
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
