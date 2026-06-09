@extends('layouts.back-end.app')

@section('title', 'Quote Requests')

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">{{ translate('Quote Requests') }}</h1>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                    <h5 class="mb-0">{{ translate('Quotes') }}
                        <span class="badge badge-soft-dark ml-2">{{ $quotes->total() }}</span>
                    </h5>
                    <form method="GET" action="{{ route('admin.quotes.list') }}" class="d-flex">
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="">{{ translate('All statuses') }}</option>
                            @foreach(['requested','quoted','accepted','rejected','expired','ordered'] as $s)
                                <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                                <tr>
                                    <th>{{ translate('Ref') }}</th>
                                    <th>{{ translate('Product') }}</th>
                                    <th>{{ translate('Customer') }}</th>
                                    <th>{{ translate('Qty') }}</th>
                                    <th>{{ translate('Status') }}</th>
                                    <th>{{ translate('Date') }}</th>
                                    <th>{{ translate('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quotes as $quote)
                                <tr>
                                    <td>{{ $quote->reference_no ?? ('#' . $quote->id) }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit(optional($quote->product)->name ?? ('#' . $quote->product_id), 30) }}</td>
                                    <td>
                                        {{ $quote->customer_name }}<br>
                                        <small><a href="tel:{{ $quote->phone_number }}">{{ $quote->phone_number }}</a></small>
                                    </td>
                                    <td>{{ $quote->requested_qty }}</td>
                                    <td><span class="badge badge-soft-info text-capitalize">{{ $quote->status }}</span></td>
                                    <td>{{ $quote->created_at->format('d M Y') }}</td>
                                    <td>
                                        <a class="btn btn-primary btn-sm" href="{{ route('admin.quotes.show', $quote->id) }}">
                                            <i class="tio-visible"></i> {{ translate('View') }}
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">{{ translate('No quote requests yet.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    {!! $quotes->links() !!}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
