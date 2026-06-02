@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Price Update Progress'))

@section('content')
    <div class="content container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a></li>
                <li class="breadcrumb-item"><a href="{{route('admin.product.list', ['in_house',''])}}">{{\App\CPU\translate('Product')}}</a></li>
                <li class="breadcrumb-item">{{\App\CPU\translate('Price Update Progress')}}</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="tio-dollar"></i> {{\App\CPU\translate('Updating prices')}}
                            <span id="status-badge" class="badge badge-soft-info ml-2">{{ ucfirst($job->status) }}</span>
                        </h4>
                        @if($job->original_file_name)
                            <small class="text-muted">{{ $job->original_file_name }}</small>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1">
                            <strong><span id="processed">{{ $job->processed_rows }}</span> / <span id="total">{{ $job->total_rows }}</span> {{\App\CPU\translate('processed')}}</strong>
                            <strong><span id="percentage">{{ $job->percentage() }}</span>%</strong>
                        </div>
                        <div class="progress mb-2" style="height: 22px;">
                            <div id="bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                                 style="width: {{ $job->percentage() }}%;">{{ $job->percentage() }}%</div>
                        </div>
                        <p id="working-note" class="text-muted"><i class="tio-sync spin"></i> {{\App\CPU\translate('Working... keep this tab open. Progress is saved, so you can refresh safely.')}}</p>

                        <div id="error-box" class="alert alert-danger d-none">
                            <strong>{{\App\CPU\translate('Price update failed')}}:</strong> <span id="error-message"></span>
                        </div>
                        <div id="done-box" class="alert alert-success d-none">
                            <i class="tio-checkmark-circle-outlined"></i> {{\App\CPU\translate('Price update completed.')}}
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <table class="table table-sm table-bordered">
                                    <tbody>
                                        <tr class="text-success"><th>{{\App\CPU\translate('Updated')}}</th><td id="updated_count">{{ $job->updated_count }}</td></tr>
                                        <tr class="text-secondary"><th>{{\App\CPU\translate('Skipped (no price / already same)')}}</th><td id="skipped_count">{{ $job->skipped_count }}</td></tr>
                                        <tr class="text-danger"><th>{{\App\CPU\translate('Not found')}}</th><td id="not_found_count">{{ $job->not_found_count }}</td></tr>
                                        <tr class="text-danger"><th>{{\App\CPU\translate('Failed')}}</th><td id="failed_count">{{ $job->failed_count }}</td></tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div id="backup-note" class="alert alert-light border d-none">
                                    <i class="tio-archive"></i> {{\App\CPU\translate('A reversible backup was saved to')}}
                                    <code id="backup-file"></code>.
                                    <small class="d-block text-muted mt-1">{{\App\CPU\translate('Restore with')}}: <code>php artisan products:update-prices --restore="storage/app/&lt;path&gt;"</code></small>
                                </div>
                            </div>
                        </div>

                        <small class="text-muted">{{\App\CPU\translate('Last updated')}}: <span id="updated_at">{{ optional($job->updated_at)->toDateTimeString() }}</span></small>

                        <div id="done-actions" class="mt-3 d-none">
                            <a id="not-found-btn" href="{{route('admin.product.price-update-not-found', ['job' => $job->id])}}" class="btn btn-outline-danger mr-2 d-none">
                                <i class="tio-download-to"></i> {{\App\CPU\translate('Download not-found codes')}}
                            </a>
                            <a href="{{route('admin.product.list', ['in_house',''])}}" class="btn btn-secondary mr-2">{{\App\CPU\translate('View Products')}}</a>
                            <a href="{{route('admin.product.price-update')}}" class="btn btn-primary">{{\App\CPU\translate('Update Another File')}}</a>
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
            const processUrl = "{{ route('admin.product.price-update-process', ['job' => $job->id]) }}";
            const csrf = "{{ csrf_token() }}";
            let finished = {{ $job->isFinished() ? 'true' : 'false' }};
            let lastProcessed = {{ $job->processed_rows }};

            const $ = (id) => document.getElementById(id);
            const setText = (id, v) => { const el = $(id); if (el) el.textContent = v; };

            function render(d) {
                setText('processed', d.processed_rows);
                setText('total', d.total_rows);
                setText('percentage', d.percentage);
                setText('updated_count', d.updated_count);
                setText('skipped_count', d.skipped_count);
                setText('not_found_count', d.not_found_count);
                setText('failed_count', d.failed_count);
                setText('updated_at', d.updated_at || '');

                const bar = $('bar');
                bar.style.width = d.percentage + '%';
                bar.textContent = d.percentage + '%';

                const badge = $('status-badge');
                badge.textContent = d.status.charAt(0).toUpperCase() + d.status.slice(1);
                badge.className = 'badge ml-2 ' + (d.status === 'completed' ? 'badge-soft-success'
                    : d.status === 'failed' ? 'badge-soft-danger' : 'badge-soft-info');
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
                if (d.has_not_found) { $('not-found-btn').classList.remove('d-none'); }
                if (d.backup_file) {
                    $('backup-file').textContent = 'storage/app/' + d.backup_file;
                    $('backup-note').classList.remove('d-none');
                }
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
                    const delay = (d.processed_rows === lastProcessed) ? 2500 : 50;
                    lastProcessed = d.processed_rows;
                    setTimeout(tick, delay);
                } catch (e) {
                    if (++errorRetries <= 5) {
                        setTimeout(tick, 3000);
                    } else {
                        $('working-note').classList.add('d-none');
                        $('error-message').textContent = 'Lost connection to the server while updating. Reopen this page to resume.';
                        $('error-box').classList.remove('d-none');
                    }
                }
            }

            if (finished) {
                fetch("{{ route('admin.product.price-update-progress-status', ['job' => $job->id]) }}", { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json()).then(d => { render(d); finish(d); });
            } else {
                tick();
            }
        })();
    </script>
    <style>.spin{animation:spin 1.2s linear infinite;display:inline-block}@keyframes spin{to{transform:rotate(360deg)}}</style>
@endpush
