@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Update Product Prices'))

@section('content')
    <div class="content container-fluid">

        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a></li>
                <li class="breadcrumb-item" aria-current="page"><a href="{{route('admin.product.list', ['in_house',''])}}">{{\App\CPU\translate('Product')}}</a></li>
                <li class="breadcrumb-item">{{\App\CPU\translate('Update Prices')}}</li>
            </ol>
        </nav>

        <div class="row" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
            <div class="col-12">

                <div class="card border-primary">
                    <div class="card-body">
                        <h5 class="mb-4 text-primary"><i class="tio-info-outined"></i> {{\App\CPU\translate('Safe price-only update')}} :</h5>
                        <p> 1. {{\App\CPU\translate('Matches existing products by product_code (SKU / MPN / Manufacturer Part Number) and updates ONLY the prices.')}}</p>
                        <p> 2. {{\App\CPU\translate('It NEVER changes name, brand, category, description, images, stock, slug or live status — and never creates new products.')}}</p>
                        <p> 3. {{\App\CPU\translate('Provide unit_price and/or purchase_price (or supplier_price / Price [EUR] / price + the defaults below). Blank price fields are left untouched.')}}</p>
                        <p> 4. {{\App\CPU\translate('The preview is instant — it shows only the first 40 rows. The whole file is matched and applied in the background with a live progress bar.')}}</p>
                        <p> 5. {{\App\CPU\translate('Every apply writes a reversible JSON backup to storage/app/backups/ so prices can be restored.')}}</p>
                    </div>
                </div>

                {{-- ===================== PREVIEW (after upload, before apply) ===================== --}}
                @isset($summary)
                    <div class="card mt-2 border-info">
                        <div class="card-header bg-light">
                            <h4 class="mb-0 text-info"><i class="tio-visible-outlined"></i> {{\App\CPU\translate('Preview')}} — {{\App\CPU\translate('nothing has been changed yet')}}</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <table class="table table-sm table-bordered mb-2">
                                        <tbody>
                                            <tr><th>{{\App\CPU\translate('Total rows read')}}</th><td>{{ number_format($summary['total_rows']) }}</td></tr>
                                            <tr><th>{{\App\CPU\translate('Preview rows shown')}}</th><td>{{ $summary['preview_rows'] }}</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-8">
                                    <label class="input-label mb-1">{{\App\CPU\translate('Detected columns')}}</label>
                                    <div>
                                        @foreach($summary['columns'] as $col)
                                            <span class="badge badge-soft-secondary mb-1">{{ $col }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mb-0">
                                <i class="tio-info-outined"></i>
                                {{\App\CPU\translate('Showing the first')}} {{ $summary['preview_rows'] }} {{\App\CPU\translate('rows. The full file will be matched and applied during apply — exact matched / not-found / updated counts are calculated then.')}}
                            </p>
                        </div>
                    </div>

                    <div class="card mt-2 border-success">
                        <div class="card-header bg-light">
                            <h4 class="mb-0 text-success"><i class="tio-file-text-outlined"></i> {{\App\CPU\translate('Sample of changes')}}</h4>
                            <small class="text-muted d-block">{{\App\CPU\translate('Prices shown are the stored amounts (USD), as saved on the product.')}}</small>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{\App\CPU\translate('Row')}} #</th>
                                            <th>{{\App\CPU\translate('Code')}}</th>
                                            <th class="text-left">{{\App\CPU\translate('Product Name')}}</th>
                                            <th>{{\App\CPU\translate('Purchase')}} ({{\App\CPU\translate('old → new')}})</th>
                                            <th>{{\App\CPU\translate('Selling')}} ({{\App\CPU\translate('old → new')}})</th>
                                            <th>{{\App\CPU\translate('Status')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($sample as $row)
                                            <tr>
                                                <td>{{ $row['row'] }}</td>
                                                <td class="font-weight-bold">{{ $row['product_code'] }}</td>
                                                <td class="text-left">{{ \Illuminate\Support\Str::limit($row['name'], 60) }}</td>
                                                <td>
                                                    @if($row['old_purchase'] !== null)
                                                        <span class="text-muted">{{ number_format($row['old_purchase'], 2) }}</span>
                                                        @if($row['new_purchase'] !== null && $row['status'] === 'will_update')
                                                            &rarr; <span class="text-success font-weight-bold">{{ number_format($row['new_purchase'], 2) }}</span>
                                                        @endif
                                                    @else <span class="text-muted">—</span> @endif
                                                </td>
                                                <td>
                                                    @if($row['old_unit'] !== null)
                                                        <span class="text-muted">{{ number_format($row['old_unit'], 2) }}</span>
                                                        @if($row['new_unit'] !== null && $row['status'] === 'will_update')
                                                            &rarr; <span class="text-success font-weight-bold">{{ number_format($row['new_unit'], 2) }}</span>
                                                        @endif
                                                    @else <span class="text-muted">—</span> @endif
                                                </td>
                                                <td>
                                                    @if($row['status'] === 'will_update')
                                                        <span class="badge badge-soft-success">{{\App\CPU\translate('will update')}}</span>
                                                    @elseif($row['status'] === 'not_found')
                                                        <span class="badge badge-soft-danger">{{\App\CPU\translate('not found')}}</span>
                                                    @else
                                                        <span class="badge badge-soft-secondary">{{\App\CPU\translate('skipped')}}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="text-muted">{{\App\CPU\translate('No rows with a product code were found to preview.')}}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <form action="{{route('admin.product.price-update-apply')}}" method="POST" class="mt-4 d-flex justify-content-end" id="confirm-price-form">
                                @csrf
                                <a href="{{route('admin.product.price-update')}}" class="btn btn-secondary mr-3">{{\App\CPU\translate('Cancel')}}</a>
                                <button type="submit" class="btn btn-success" id="confirm-price-btn">
                                    <i class="tio-save"></i> {{\App\CPU\translate('Apply Prices for all')}} {{ number_format($summary['total_rows']) }} {{\App\CPU\translate('rows')}}
                                </button>
                            </form>
                            <script>
                                document.getElementById('confirm-price-form').addEventListener('submit', function () {
                                    var btn = document.getElementById('confirm-price-btn');
                                    btn.disabled = true;
                                    btn.innerHTML = '<i class="tio-sync"></i> {{\App\CPU\translate('Starting...')}}';
                                });
                            </script>
                        </div>
                    </div>
                @endisset

                {{-- ===================== UPLOAD FORM ===================== --}}
                @unless(isset($summary))
                    <div class="card mt-2">
                        <div class="card-body">
                            <form class="product-form" action="{{route('admin.product.price-update-preview')}}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="input-label" for="products_file">{{\App\CPU\translate('Upload_Excel/CSV_File')}}</label>
                                            <div class="custom-file">
                                                <input type="file" name="products_file" class="custom-file-input" id="products_file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                                                <label class="custom-file-label" for="products_file">{{\App\CPU\translate('Choose_File')}}</label>
                                            </div>
                                            <small class="text-muted">{{\App\CPU\translate('Must contain a product_code (or SKU / Part# / MPN / Manufacturer Part Number) column plus unit_price and/or purchase_price (or supplier_price / Price [EUR] / price).')}}</small>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-primary mb-3"><i class="tio-settings"></i> {{\App\CPU\translate('Optional Defaults')}}
                                    <small class="text-muted">({{\App\CPU\translate('used only when a supplier/cost price needs conversion or margin; row values always win')}})</small>
                                </h6>
                                <div class="row">
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Exchange Rate')}}</label>
                                        <input type="number" step="any" min="0" name="exchange_rate" class="form-control" placeholder="1">
                                        <small class="text-muted">{{\App\CPU\translate('Supplier currency → site currency')}}</small>
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Landed Cost %')}}</label>
                                        <input type="number" step="any" min="0" name="landed_cost_percent" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Margin %')}}</label>
                                        <input type="number" step="any" min="0" name="margin_percent" class="form-control" placeholder="0">
                                    </div>
                                    <div class="col-md-3 form-group">
                                        <label class="input-label">{{\App\CPU\translate('Price Rounding')}}</label>
                                        <select name="rounding" class="form-control">
                                            <option value="whole">{{\App\CPU\translate('Nearest whole number')}}</option>
                                            <option value="none">{{\App\CPU\translate('No rounding (2 decimals)')}}</option>
                                            <option value="5">{{\App\CPU\translate('Round up to nearest 5')}}</option>
                                            <option value="10">{{\App\CPU\translate('Round up to nearest 10')}}</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 form-group d-flex align-items-end">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="allow_below" name="allow_below" value="1">
                                            <label class="custom-control-label" for="allow_below">{{\App\CPU\translate('Allow selling price below purchase price')}}</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12 d-flex">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="tio-visible"></i> {{\App\CPU\translate('Preview Changes')}}
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
