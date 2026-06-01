@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Product Image Pipeline'))

@section('content')
    <div class="content container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a></li>
                <li class="breadcrumb-item"><a href="{{route('admin.product.list', ['in_house',''])}}">{{\App\CPU\translate('Product')}}</a></li>
                <li class="breadcrumb-item">{{\App\CPU\translate('Image Pipeline')}}</li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="tio-image"></i> {{\App\CPU\translate('Product Image Pipeline')}}</h4>
                        <div>
                            <a href="{{route('admin.product.image-pipeline-review-export')}}" class="btn btn-sm btn-outline-danger">
                                <i class="tio-download-to"></i> {{\App\CPU\translate('Export review list')}}
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1">
                            <strong><span id="processed">0</span> / <span id="total">0</span> {{\App\CPU\translate('processed')}}</strong>
                            <strong><span id="percentage">0</span>%</strong>
                        </div>
                        <div class="progress mb-3" style="height: 22px;">
                            <div id="bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width:0%">0%</div>
                        </div>

                        <div class="row text-center">
                            @php($cards = [
                                ['fetched','Fetched','success'], ['reused','Reused','info'],
                                ['queued','Queued','secondary'], ['placeholder','Placeholder','secondary'],
                                ['manual_review','Manual review','danger'], ['failed','Failed','danger'],
                            ])
                            @foreach($cards as $c)
                                <div class="col-6 col-md-2 mb-2">
                                    <div class="border rounded p-2">
                                        <div class="h4 mb-0 text-{{$c[2]}}"><span id="{{$c[0]}}">0</span></div>
                                        <small class="text-muted">{{\App\CPU\translate($c[1])}}</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <small class="text-muted">{{\App\CPU\translate('Auto-refreshing')}} · {{\App\CPU\translate('last updated')}}: <span id="updated_at">—</span></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        (function () {
            const url = "{{ route('admin.product.image-pipeline-status') }}";
            const $ = (id) => document.getElementById(id);
            async function tick() {
                try {
                    const d = await (await fetch(url, { headers: { 'Accept': 'application/json' } })).json();
                    ['total','processed','fetched','reused','queued','placeholder','manual_review','failed','percentage'].forEach(k => { if ($(k)) $(k).textContent = d[k]; });
                    $('updated_at').textContent = d.updated_at || '';
                    const bar = $('bar'); bar.style.width = d.percentage + '%'; bar.textContent = d.percentage + '%';
                } catch (e) { /* keep trying */ }
                setTimeout(tick, 3000);
            }
            tick();
        })();
    </script>
@endpush
