@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Your Quote'))

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">{{ \App\CPU\translate('Quote') }} {{ $quote->reference_no ?? ('#' . $quote->id) }}</h4>
                    <span class="badge badge-secondary text-capitalize">{{ $quote->status }}</span>
                </div>
                <div class="card-body">
                    <p class="text-muted">{{ \App\CPU\translate('Hi') }} {{ $quote->customer_name }},</p>

                    <table class="table table-bordered">
                        <tr>
                            <th>{{ \App\CPU\translate('Product') }}</th>
                            <td>{{ optional($quote->product)->name ?? ('Product #' . $quote->product_id) }}</td>
                        </tr>
                        <tr>
                            <th>{{ \App\CPU\translate('Quantity') }}</th>
                            <td>{{ $quote->quoted_qty ?? $quote->requested_qty }}</td>
                        </tr>
                        <tr>
                            <th>{{ \App\CPU\translate('Unit Price') }}</th>
                            <td>
                                @if($quote->quoted_unit_price !== null)
                                    {{ \App\CPU\Helpers::currency_converter($quote->quoted_unit_price) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                        @if($quote->quote_valid_until)
                        <tr>
                            <th>{{ \App\CPU\translate('Valid Until') }}</th>
                            <td>{{ \Illuminate\Support\Carbon::parse($quote->quote_valid_until)->format('d M Y') }}</td>
                        </tr>
                        @endif
                    </table>

                    @if($quote->admin_note)
                        <div class="alert alert-light border">
                            <strong>{{ \App\CPU\translate('Note') }}:</strong> {{ $quote->admin_note }}
                        </div>
                    @endif

                    @if($quote->status === 'quoted')
                        <div class="d-flex" style="gap: 10px;">
                            <form method="POST" action="{{ route('quote.accept', $quote->accept_token) }}" class="flex-fill">
                                @csrf
                                <button type="submit" class="btn btn-primary btn-block">{{ \App\CPU\translate('Accept & Order') }}</button>
                            </form>
                            <form method="POST" action="{{ route('quote.reject', $quote->accept_token) }}" class="flex-fill"
                                  onsubmit="return confirm('{{ \App\CPU\translate('Decline this quote?') }}');">
                                @csrf
                                <button type="submit" class="btn btn-outline-secondary btn-block">{{ \App\CPU\translate('Decline') }}</button>
                            </form>
                        </div>
                    @elseif($quote->status === 'requested')
                        <div class="alert alert-info mb-0">{{ \App\CPU\translate('Our team is preparing your price. You will receive it shortly.') }}</div>
                    @elseif($quote->status === 'accepted')
                        <div class="alert alert-success mb-0">{{ \App\CPU\translate('You have accepted this quote. Please complete your order at checkout.') }}</div>
                    @elseif($quote->status === 'ordered')
                        <div class="alert alert-success mb-0">{{ \App\CPU\translate('This quote has been converted to an order. Thank you!') }}</div>
                    @elseif($quote->status === 'rejected')
                        <div class="alert alert-secondary mb-0">{{ \App\CPU\translate('You have declined this quote.') }}</div>
                    @elseif($quote->status === 'expired')
                        <div class="alert alert-warning mb-0">{{ \App\CPU\translate('This quote has expired. Please submit a new request.') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
