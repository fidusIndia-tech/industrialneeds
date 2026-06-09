@extends('layouts.back-end.app')

@section('title', 'Quote Details')

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <h1 class="page-header-title">{{ translate('Quote') }} {{ $quote->reference_no ?? ('#' . $quote->id) }}
            <span class="badge badge-soft-info text-capitalize ml-2">{{ $quote->status }}</span>
        </h1>
        <a href="{{ route('admin.quotes.list') }}" class="btn btn-primary">{{ translate('Back to List') }}</a>
    </div>

    <div class="row">
        <div class="col-md-7">
            <!-- Request details -->
            <div class="card mb-3 mb-lg-5">
                <div class="card-header"><h5 class="mb-0">{{ translate('Request Details') }}</h5></div>
                <div class="card-body">
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ translate('Product') }}</label>
                        <div class="col-sm-8">{{ optional($quote->product)->name ?? ('Product #' . $quote->product_id) }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ translate('Requested Qty') }}</label>
                        <div class="col-sm-8">{{ $quote->requested_qty }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ translate('Customer') }}</label>
                        <div class="col-sm-8">{{ $quote->customer_name }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ translate('Phone') }}</label>
                        <div class="col-sm-8"><a href="tel:{{ $quote->phone_number }}">{{ $quote->phone_number }}</a></div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ translate('Email') }}</label>
                        <div class="col-sm-8">{{ $quote->email ?? translate('Not provided') }}</div></div>
                    <div class="row mb-2"><label class="col-sm-4 text-dark">{{ translate('Received On') }}</label>
                        <div class="col-sm-8">{{ $quote->created_at->format('d M Y, h:i A') }}</div></div>
                    <div class="row"><label class="col-sm-4 text-dark">{{ translate('Message') }}</label>
                        <div class="col-sm-8" style="white-space: pre-wrap;">{{ $quote->message ?? '—' }}</div></div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <!-- Respond with a price -->
            <div class="card mb-3 mb-lg-5">
                <div class="card-header"><h5 class="mb-0">{{ translate('Send a Quote') }}</h5></div>
                <div class="card-body">
                    @if($quote->email == null)
                        <div class="alert alert-warning">{{ translate('This request has no email address — the customer cannot be sent a quote link. Contact them by phone.') }}</div>
                    @endif
                    @if(in_array($quote->status, ['accepted','rejected','expired','ordered']))
                        <div class="alert alert-info">{{ translate('This quote is') }} <strong>{{ $quote->status }}</strong> {{ translate('and can no longer be changed.') }}</div>
                    @else
                    <form method="POST" action="{{ route('admin.quotes.respond', $quote->id) }}">
                        @csrf
                        <div class="form-group">
                            <label class="text-dark">{{ translate('Unit Price') }} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="quoted_unit_price" class="form-control"
                                   value="{{ $quote->quoted_unit_price }}" required>
                        </div>
                        <div class="form-group">
                            <label class="text-dark">{{ translate('Quantity') }}</label>
                            <input type="number" min="1" name="quoted_qty" class="form-control"
                                   value="{{ $quote->quoted_qty ?? $quote->requested_qty }}">
                            <small class="text-muted">{{ translate('Defaults to the requested quantity.') }}</small>
                        </div>
                        <div class="form-group">
                            <label class="text-dark">{{ translate('Valid Until') }}</label>
                            <input type="date" name="quote_valid_until" class="form-control"
                                   value="{{ $quote->quote_valid_until ? \Illuminate\Support\Carbon::parse($quote->quote_valid_until)->format('Y-m-d') : '' }}">
                        </div>
                        <div class="form-group">
                            <label class="text-dark">{{ translate('Note / Terms') }}</label>
                            <textarea name="admin_note" rows="3" class="form-control">{{ $quote->admin_note }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            {{ $quote->status === 'quoted' ? translate('Re-send Quote') : translate('Send Quote') }}
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
