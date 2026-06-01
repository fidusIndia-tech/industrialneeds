@extends('layouts.back-end.app')

@section('title', \App\CPU\translate('Fetched Images'))

@php
    $imgUrl = fn($t) => asset('storage/app/public/product/thumbnail') . '/' . ($t ?: 'def.png');
    $statusColor = ['fetched'=>'success','reused'=>'info','manual_review'=>'danger','failed'=>'danger','queued'=>'secondary','placeholder'=>'secondary'];
    $sourceColor = function($s){ if(!$s) return 'secondary'; if(\Illuminate\Support\Str::startsWith($s,'reused')) return 'info'; return ['digikey'=>'primary','element14'=>'success','import'=>'secondary','mouser'=>'warning'][$s] ?? 'dark'; };
    $filters = [['fetched','Fetched'],['reused','Reused'],['manual_review','Needs review'],['failed','Failed'],['with_image','All with image'],['all','All']];
@endphp

@section('content')
    <div class="content container-fluid">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{route('admin.dashboard')}}">{{\App\CPU\translate('Dashboard')}}</a></li>
                <li class="breadcrumb-item"><a href="{{route('admin.product.image-pipeline')}}">{{\App\CPU\translate('Image Pipeline')}}</a></li>
                <li class="breadcrumb-item">{{\App\CPU\translate('Fetched Images')}}</li>
            </ol>
        </nav>

        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                <h5 class="mb-0"><i class="tio-image"></i> {{\App\CPU\translate('Fetched Images')}}
                    <small class="text-muted">({{ $products->total() }})</small>
                </h5>
                <div class="d-flex flex-wrap align-items-center">
                    @foreach($filters as $f)
                        <a href="{{ route('admin.product.image-pipeline-gallery', ['status'=>$f[0]] + ($source? ['source'=>$source]:[])) }}"
                           class="btn btn-sm mr-1 mb-1 {{ $status===$f[0] ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{\App\CPU\translate($f[1])}}
                            @if(isset($counts[$f[0]])) <span class="badge badge-light">{{ $counts[$f[0]] }}</span> @endif
                        </a>
                    @endforeach
                    <form method="GET" class="form-inline ml-2 mb-1">
                        <input type="hidden" name="status" value="{{ $status }}">
                        <select name="source" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">{{\App\CPU\translate('Any source')}}</option>
                            @foreach(['digikey','element14','reused','import','mouser'] as $s)
                                <option value="{{$s}}" {{ $source===$s?'selected':'' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @if($products->isEmpty())
                    <p class="text-muted text-center my-4">{{\App\CPU\translate('No products match this filter.')}}</p>
                @else
                    <div class="row">
                        @foreach($products as $p)
                            <div class="col-6 col-md-3 col-xl-2 mb-3">
                                <div class="border rounded h-100 p-2 text-center">
                                    <a href="{{ $imgUrl($p->thumbnail) }}" target="_blank" title="{{\App\CPU\translate('Open full image')}}">
                                        <img src="{{ $imgUrl($p->thumbnail) }}" loading="lazy" alt="{{ $p->product_code }}"
                                             style="width:100%;height:120px;object-fit:contain;background:#fff">
                                    </a>
                                    <div class="mt-2 text-truncate font-weight-bold" title="{{ $p->product_code }}">{{ $p->product_code }}</div>
                                    <div>
                                        <span class="badge badge-soft-{{ $statusColor[$p->image_status] ?? 'secondary' }}">{{ $p->image_status }}</span>
                                        @if($p->image_source)
                                            <span class="badge badge-soft-{{ $sourceColor($p->image_source) }}">{{ $p->image_source }}</span>
                                        @endif
                                        @if(!is_null($p->image_confidence))
                                            <span class="badge badge-soft-dark">{{ $p->image_confidence }}%</span>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block text-truncate" title="{{ $p->brand }}">{{ $p->brand }}</small>
                                    @if($p->image_error)
                                        <small class="text-danger d-block text-truncate" title="{{ $p->image_error }}">{{ $p->image_error }}</small>
                                    @endif
                                    <a href="{{ route('admin.product.view', $p->id) }}" class="btn btn-sm btn-outline-primary mt-1" target="_blank">{{\App\CPU\translate('View')}}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 d-flex justify-content-center">
                        {!! $products->links() !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
