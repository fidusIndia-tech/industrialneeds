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
                        <p> 1. {{\App\CPU\translate('This matches existing products by product_code (SKU / manufacturer part number) and updates ONLY the prices.')}}</p>
                        <p> 2. {{\App\CPU\translate('It NEVER changes name, brand, category, description, images, stock, slug or live status — and never creates new products.')}}</p>
                        <p> 3. {{\App\CPU\translate('Provide unit_price and/or purchase_price (or supplier_price + the defaults below). Blank price fields are left untouched.')}}</p>
                        <p> 4. {{\App\CPU\translate('You always see an old → new preview before anything is written. Codes not found in the catalogue are listed and downloadable.')}}</p>
                        <p> 5. {{\App\CPU\translate('Every apply writes a reversible JSON backup to storage/app/backups/ so prices can be restored.')}}</p>
                    </div>
                </div>

                {{-- ===================== PREVIEW (after upload, before confirm) ===================== --}}
                @isset($summary)
                    <div class="card mt-2 border-info">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-info"><i class="tio-visible-outlined"></i> {{\App\CPU\translate('Preview')}} — {{\App\CPU\translate('nothing has been changed yet')}}</h4>
                            @if($summary['not_found'] > 0)
                                <a href="{{route('admin.product.price-update-not-found')}}" class="btn btn-sm btn-outline-danger">
                                    <i class="tio-download-to"></i> {{\App\CPU\translate('Download not-found codes')}} ({{ $summary['not_found'] }})
                                </a>
                            @endif
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-bordered mb-0">
                                <tbody>
                                    <tr><th>{{\App\CPU\translate('Rows read')}}</th><td>{{ $summary['processed'] }}</td>
                                        <th>{{\App\CPU\translate('Matched (found by code)')}}</th><td>{{ $summary['matched'] }}</td></tr>
                                    <tr class="text-primary"><th>{{\App\CPU\translate('Will change')}}</th><td>{{ $summary['changed'] }}</td>
                                        <th class="text-danger">{{\App\CPU\translate('Not found')}}</th><td class="text-danger">{{ $summary['not_found'] }}</td></tr>
                                    <tr><th>{{\App\CPU\translate('Skipped (no price / already same)')}}</th><td>{{ $summary['skipped'] }}</td>
                                        <th>{{\App\CPU\translate('Invalid price (e.g. selling < purchase)')}}</th><td>{{ $summary['invalid'] }}</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(!empty($sample))
                        <div class="card mt-2 border-success">
                            <div class="card-header bg-light">
                                <h4 class="mb-0 text-success"><i class="tio-file-text-outlined"></i> {{\App\CPU\translate('Price changes')}} ({{ $summary['changed'] }})</h4>
                                @if($summary['changed'] > count($sample))
                                    <small class="text-muted">{{\App\CPU\translate('Showing the first')}} {{ count($sample) }} {{\App\CPU\translate('as a sample. All')}} {{ $summary['changed'] }} {{\App\CPU\translate('will be applied on confirm.')}}</small>
                                @endif
                                <small class="text-muted d-block">{{\App\CPU\translate('Values shown are the stored amounts (USD), as saved on the product.')}}</small>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover text-center">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>{{\App\CPU\translate('Product ID')}}</th>
                                                <th>{{\App\CPU\translate('Code')}}</th>
                                                <th>{{\App\CPU\translate('Unit Price')}} ({{\App\CPU\translate('old → new')}})</th>
                                                <th>{{\App\CPU\translate('Purchase Price')}} ({{\App\CPU\translate('old → new')}})</th>
                                                <th>{{\App\CPU\translate('Discount')}}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($sample as $c)
                                                <tr>
                                                    <td>{{ $c['id'] }}</td>
                                                    <td class="font-weight-bold">{{ $c['product_code'] }}</td>
                                                    <td>
                                                        @if(isset($c['new']['unit_price']))
                                                            <span class="text-muted">{{ number_format($c['old']['unit_price'], 2) }}</span>
                                                            &rarr; <span class="text-success font-weight-bold">{{ number_format($c['new']['unit_price'], 2) }}</span>
                                                        @else <span class="text-muted">—</span> @endif
                                                    </td>
                                                    <td>
                                                        @if(isset($c['new']['purchase_price']))
                                                            <span class="text-muted">{{ number_format($c['old']['purchase_price'], 2) }}</span>
                                                            &rarr; <span class="text-success font-weight-bold">{{ number_format($c['new']['purchase_price'], 2) }}</span>
                                                        @else <span class="text-muted">—</span> @endif
                                                    </td>
                                                    <td>
                                                        @if(isset($c['new']['discount']))
                                                            {{ number_format($c['new']['discount'], 2) }}{{ ($c['new']['discount_type'] ?? '') === 'percent' ? '%' : '' }}
                                                        @else <span class="text-muted">—</span> @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <form action="{{route('admin.product.price-update-apply')}}" method="POST" class="mt-4 d-flex justify-content-end" id="confirm-price-form">
                                    @csrf
                                    <a href="{{route('admin.product.price-update')}}" class="btn btn-secondary mr-3">{{\App\CPU\translate('Cancel')}}</a>
                                    <button type="submit" class="btn btn-success" id="confirm-price-btn" {{ $summary['changed'] === 0 ? 'disabled' : '' }}>
                                        <i class="tio-save"></i> {{\App\CPU\translate('Confirm & Update')}} {{ $summary['changed'] }} {{\App\CPU\translate('Price(s)')}}
                                    </button>
                                </form>
                                <script>
                                    document.getElementById('confirm-price-form').addEventListener('submit', function () {
                                        var btn = document.getElementById('confirm-price-btn');
                                        btn.disabled = true;
                                        btn.innerHTML = '<i class="tio-sync"></i> {{\App\CPU\translate('Updating prices...')}}';
                                    });
                                </script>
                            </div>
                        </div>
                    @else
                        <div class="card mt-2">
                            <div class="card-body text-center text-muted">
                                {{\App\CPU\translate('No price changes were found in this file (every matched product already has these prices).')}}
                                <div class="mt-3"><a href="{{route('admin.product.price-update')}}" class="btn btn-primary">{{\App\CPU\translate('Upload Another File')}}</a></div>
                            </div>
                        </div>
                    @endif
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
                                            <small class="text-muted">{{\App\CPU\translate('Must contain a product_code (SKU / MPN) column plus unit_price and/or purchase_price (or supplier_price).')}}</small>
                                        </div>
                                    </div>
                                </div>

                                <hr>
                                <h6 class="text-primary mb-3"><i class="tio-settings"></i> {{\App\CPU\translate('Optional Defaults')}}
                                    <small class="text-muted">({{\App\CPU\translate('used only when a price cell needs conversion / margin; row values always win')}})</small>
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
