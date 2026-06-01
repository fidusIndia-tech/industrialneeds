@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Product Import Progress'))

@section('content')
    <div class="content container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a></li>
                <li class="breadcrumb-item"><a href="{{route('admin.product.list', ['in_house',''])}}">{{\App\CPU\translate('Product')}}</a></li>
                <li class="breadcrumb-item">{{\App\CPU\translate('Import Progress')}}</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="tio-download-to"></i> {{\App\CPU\translate('Importing products')}}
                            <span id="status-badge" class="badge badge-soft-info ml-2">{{ ucfirst($job->status) }}</span>
                        </h4>
                        @if($job->original_file_name)
                            <small class="text-muted">{{ $job->original_file_name }}</small>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1">
                            <strong><span id="processed">{{ $job->processed_rows }}</span> / <span id="total">{{ $job->total_rows }}</span> {{\App\CPU\translate('completed')}}</strong>
                            <strong><span id="percentage">{{ $job->percentage() }}</span>%</strong>
                        </div>
                        <div class="progress mb-2" style="height: 22px;">
                            <div id="bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                 style="width: {{ $job->percentage() }}%;">{{ $job->percentage() }}%</div>
                        </div>
                        <p id="working-note" class="text-muted"><i class="tio-sync spin"></i> {{\App\CPU\translate('Working... keep this tab open. Progress is saved, so you can refresh safely.')}}</p>

                        <div id="error-box" class="alert alert-danger d-none">
                            <strong>{{\App\CPU\translate('Import failed')}}:</strong> <span id="error-message"></span>
                        </div>
                        <div id="done-box" class="alert alert-success d-none">
                            <i class="tio-checkmark-circle-outlined"></i> {{\App\CPU\translate('Import completed.')}}
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr class="text-success"><th>{{\App\CPU\translate('Created')}}</th><td id="created_count">{{ $job->created_count }}</td></tr>
                                        <tr class="text-primary"><th>{{\App\CPU\translate('Updated')}}</th><td id="updated_count">{{ $job->updated_count }}</td></tr>
                                        <tr class="text-danger"><th>{{\App\CPU\translate('Skipped')}}</th><td id="skipped_count">{{ $job->skipped_count }}</td></tr>
                                        <tr class="text-danger"><th>{{\App\CPU\translate('Failed')}}</th><td id="failed_count">{{ $job->failed_count }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr><th>{{\App\CPU\translate('New brands')}}</th><td id="new_brands_count">{{ $job->new_brands_count }}</td></tr>
                                        <tr><th>{{\App\CPU\translate('New categories / sub / sub-sub')}}</th>
                                            <td><span id="new_categories_count">{{ $job->new_categories_count }}</span> /
                                                <span id="new_sub_categories_count">{{ $job->new_sub_categories_count }}</span> /
                                                <span id="new_sub_sub_categories_count">{{ $job->new_sub_sub_categories_count }}</span></td></tr>
                                        <tr><th>{{\App\CPU\translate('Calculated purchase / selling price')}}</th>
                                            <td><span id="calculated_purchase_price_count">{{ $job->calculated_purchase_price_count }}</span> /
                                                <span id="calculated_selling_price_count">{{ $job->calculated_selling_price_count }}</span></td></tr>
                                        <tr><th>{{\App\CPU\translate('Default unit / MOQ / stock used')}}</th>
                                            <td><span id="default_unit_used_count">{{ $job->default_unit_used_count }}</span> /
                                                <span id="default_moq_used_count">{{ $job->default_moq_used_count }}</span> /
                                                <span id="default_stock_used_count">{{ $job->default_stock_used_count }}</span></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if(!empty(($job->import_options['process_images'] ?? false)))
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-muted mb-1"><i class="tio-image"></i> {{\App\CPU\translate('Images')}}</h6>
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>{{\App\CPU\translate('With images')}}</th><td id="with_images_count">{{ $job->with_images_count }}</td>
                                            <th>{{\App\CPU\translate('Without images')}}</th><td id="without_images_count">{{ $job->without_images_count }}</td>
                                        </tr>
                                        <tr>
                                            <th>{{\App\CPU\translate('Matched from ZIP')}}</th><td id="images_from_zip_count">{{ $job->images_from_zip_count }}</td>
                                            <th>{{\App\CPU\translate('Downloaded')}}</th><td id="images_downloaded_count">{{ $job->images_downloaded_count }}</td>
                                        </tr>
                                        <tr class="text-danger">
                                            <th>{{\App\CPU\translate('Failed downloads')}}</th><td id="failed_image_downloads_count">{{ $job->failed_image_downloads_count }}</td>
                                            <th>{{\App\CPU\translate('Invalid files skipped')}}</th><td id="invalid_images_count">{{ $job->invalid_images_count }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        <small class="text-muted">{{\App\CPU\translate('Last updated')}}: <span id="updated_at">{{ optional($job->updated_at)->toDateTimeString() }}</span></small>

                        <div id="failed-rows-wrap" class="mt-3 d-none">
                            <h6 class="text-danger"><i class="tio-warning"></i> {{\App\CPU\translate('Failed Rows')}} (<span id="failed-rows-count">0</span>)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light"><tr><th style="width:90px;">{{\App\CPU\translate('Row')}} #</th><th>{{\App\CPU\translate('Reason')}}</th></tr></thead>
                                    <tbody id="failed-rows-body"></tbody>
                                </table>
                            </div>
                        </div>

                        <div id="done-actions" class="mt-3 d-none">
                            <a href="{{route('admin.product.list', ['in_house',''])}}" class="btn btn-secondary mr-2">{{\App\CPU\translate('View Products')}}</a>
                            <a href="{{route('admin.product.bulk-import')}}" class="btn btn-primary">{{\App\CPU\translate('Import Another File')}}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const processUrl = "{{ route('admin.product.bulk-import-process', ['job' => $job->id]) }}";
            const csrf = "{{ csrf_token() }}";
            let finished = {{ $job->isFinished() ? 'true' : 'false' }};
            let lastProcessed = {{ $job->processed_rows }};

            const $ = (id) => document.getElementById(id);
            const setText = (id, v) => { const el = $(id); if (el) el.textContent = v; };

            function render(d) {
                setText('processed', d.processed_rows);
                setText('total', d.total_rows);
                setText('percentage', d.percentage);
                setText('created_count', d.created_count);
                setText('updated_count', d.updated_count);
                setText('skipped_count', d.skipped_count);
                setText('failed_count', d.failed_count);
                setText('new_brands_count', d.new_brands_count);
                setText('new_categories_count', d.new_categories_count);
                setText('new_sub_categories_count', d.new_sub_categories_count);
                setText('new_sub_sub_categories_count', d.new_sub_sub_categories_count);
                setText('calculated_purchase_price_count', d.calculated_purchase_price_count);
                setText('calculated_selling_price_count', d.calculated_selling_price_count);
                setText('default_unit_used_count', d.default_unit_used_count);
                setText('default_moq_used_count', d.default_moq_used_count);
                setText('default_stock_used_count', d.default_stock_used_count);
                setText('with_images_count', d.with_images_count);
                setText('without_images_count', d.without_images_count);
                setText('images_from_zip_count', d.images_from_zip_count);
                setText('images_downloaded_count', d.images_downloaded_count);
                setText('failed_image_downloads_count', d.failed_image_downloads_count);
                setText('invalid_images_count', d.invalid_images_count);
                setText('updated_at', d.updated_at || '');

                const bar = $('bar');
                bar.style.width = d.percentage + '%';
                bar.textContent = d.percentage + '%';

                const badge = $('status-badge');
                badge.textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
                badge.className = 'badge ml-2 ' + (d.status === 'completed' ? 'badge-soft-success'
                    : d.status === 'failed' ? 'badge-soft-danger' : 'badge-soft-info');
            }

            function showFailedRows(rows) {
                if (!rows || !rows.length) return;
                $('failed-rows-wrap').classList.remove('d-none');
                setText('failed-rows-count', rows.length);
                const body = $('failed-rows-body');
                body.innerHTML = '';
                rows.forEach(r => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = '<td>' + (r.row ?? '?') + '</td><td class="text-danger">' +
                        String(r.reason ?? '').replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c])) + '</td>';
                    body.appendChild(tr);
                });
            }

            function finish(d) {
                finished = true;
                $('bar').classList.remove('progress-bar-animated', 'progress-bar-striped');
                $('working-note').classList.add('d-none');
                $('done-actions').classList.remove('d-none');
                if (d.status === 'failed') {
                    $('bar').classList.add('bg-danger');
                    $('error-message').textContent = d.error_message || 'Unknown error.';
                    $('error-box').classList.remove('d-none');
                } else {
                    $('done-box').classList.remove('d-none');
                }
                showFailedRows(d.failed_rows);
            }

            let errorRetries = 0;
            async function tick() {
                try {
                    const res = await fetch(processUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const d = await res.json();
                    errorRetries = 0;
                    render(d);
                    if (d.status === 'completed' || d.status === 'failed') { finish(d); return; }
                    // If another tab is driving (no progress this call), poll slower; else continue immediately.
                    const delay = (d.processed_rows === lastProcessed) ? 2500 : 50;
                    lastProcessed = d.processed_rows;
                    setTimeout(tick, delay);
                } catch (e) {
                    if (++errorRetries <= 5) {
                        setTimeout(tick, 3000); // transient error — retry a few times
                    } else {
                        $('working-note').classList.add('d-none');
                        $('error-message').textContent = 'Lost connection to the server while importing. Reopen this page to resume.';
                        $('error-box').classList.remove('d-none');
                    }
                }
            }

            if (finished) {
                // Already done — just fetch the final snapshot once.
                fetch("{{ route('admin.product.bulk-import-progress-status', ['job' => $job->id]) }}", { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json()).then(d => { render(d); finish(d); });
            } else {
                tick();
            }
        })();
    </script>
    <style>.spin{animation:spin 1.2s linear infinite;display:inline-block}@keyframes spin{to{transform:rotate(360deg)}}</style>
@endpush
