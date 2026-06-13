@extends('layouts.back-end.app')

@section('title', 'Chat Lead')

@section('content')
<div class="content container-fluid">
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title">{{ \App\CPU\translate('Chat Lead') }} #{{ $lead->id }}
            <span class="badge badge-soft-info text-capitalize ml-2">{{ $lead->status }}</span>
        </h1>
        <a href="{{ route('admin.chat-leads.list') }}" class="btn btn-primary">{{ \App\CPU\translate('Back to List') }}</a>
    </div>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-3 mb-lg-5">
                <div class="card-header"><h5 class="mb-0">{{ \App\CPU\translate('Details') }}</h5></div>
                <div class="card-body">
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Name') }}</label><div class="col-sm-8">{{ $lead->name ?? '—' }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Company') }}</label><div class="col-sm-8">{{ $lead->company_name ?? '—' }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Location') }}</label><div class="col-sm-8">{{ $lead->location ?? '—' }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Phone') }}</label>
                        <div class="col-sm-8">@if($lead->phone)<a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>@else — @endif</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Email') }}</label><div class="col-sm-8">{{ $lead->email ?? '—' }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Intent') }}</label><div class="col-sm-8">{{ $lead->intent ?? '—' }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Product') }}</label><div class="col-sm-8">{{ $lead->product ?? '—' }}@if($lead->quantity) (×{{ $lead->quantity }})@endif</div></div>
                    @if($lead->message)<div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Note') }}</label><div class="col-sm-8" style="white-space:pre-wrap;">{{ $lead->message }}</div></div>@endif
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('Page') }}</label><div class="col-sm-8">{{ $lead->page_url ?? '—' }}</div></div>
                    <div class="row"><label class="col-sm-4 text-dark">{{ \App\CPU\translate('When') }}</label><div class="col-sm-8">{{ optional($lead->created_at)->format('d M Y, h:i A') }}</div></div>
                </div>
            </div>

            <div class="card mb-3 mb-lg-5">
                <div class="card-header"><h5 class="mb-0">{{ \App\CPU\translate('Conversation') }}</h5></div>
                <div class="card-body">
                    @forelse($lead->transcript ?? [] as $t)
                        <div class="mb-2"><small class="text-muted">{{ $t['key'] ?? 'msg' }}:</small> {{ $t['value'] ?: '(skipped)' }}</div>
                    @empty
                        <p class="text-muted mb-0">{{ \App\CPU\translate('No messages recorded.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ \App\CPU\translate('Update Status') }}</h5></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.chat-leads.status', $lead->id) }}">
                        @csrf
                        <div class="form-group">
                            <select name="status" class="form-control">
                                @foreach(['lead','contacted','closed'] as $s)
                                    <option value="{{ $s }}" {{ $lead->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">{{ \App\CPU\translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
