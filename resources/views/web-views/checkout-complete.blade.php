@extends('layouts.front-end.app')

@section('title',\App\CPU\translate('Order Complete'))

@push('css_or_js')
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat&display=swap');

        body {
            font-family: 'Montserrat', sans-serif
        }

        .card {
            border: none
        }

        .totals tr td {
            font-size: 13px
        }

        .footer span {
            font-size: 12px
        }

        .product-qty span {
            font-size: 14px;
            color: #6A6A6A;
        }

        .spanTr {
            color: {{$web_config['primary_color']}};
            font-weight: 700;

        }

        .spandHeadO {
            color: #030303;
            font-weight: 500;
            font-size: 20px;

        }

        .font-name {
            font-weight: 600;
            font-size: 13px;
        }

        .amount {
            font-size: 17px;
            color: {{$web_config['primary_color']}};
        }

        @media (max-width: 600px) {
            .orderId {
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: 91px;
            }

            .p-5 {
                padding: 2% !important;
            }

            .spanTr {

                font-weight: 400 !important;
                font-size: 12px;
            }

            .spandHeadO {

                font-weight: 300;
                font-size: 12px;

            }

            .table th, .table td {
                padding: 5px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container mt-5 mb-5 rtl"
         style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <div class="row d-flex justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="card" style="border: 1px solid #E2E8F0; border-radius: 10px;">
                    @if(auth('customer')->check())
                        <div class="p-4 p-md-5">
                            <div class="text-center mb-4">
                                <i style="font-size: 84px; color: #16A34A;" class="fa fa-check-circle"></i>
                                <h4 class="mt-3" style="color: #082A45; font-weight: 800;">
                                    {{\App\CPU\translate('Order Placed Successfully')}}
                                </h4>
                                <p class="text-muted mb-0">
                                    {{\App\CPU\translate('Hello')}}, {{auth('customer')->user()->f_name}}
                                </p>
                            </div>

                            @if(isset($order) && $order)
                                @php($oMeta = \App\Model\Order::statusColors($order->order_status))
                                <div style="background: #F5F7FA; border: 1px solid #E2E8F0; border-radius: 8px; padding: 18px;">
                                    <div class="row">
                                        <div class="col-6 mb-3">
                                            <small class="text-muted d-block">{{\App\CPU\translate('Order ID')}}</small>
                                            <span style="color: #111827; font-weight: 700;">#{{$order->id}}</span>
                                        </div>
                                        <div class="col-6 mb-3 text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                            <small class="text-muted d-block">{{\App\CPU\translate('Order Date')}}</small>
                                            <span style="color: #111827; font-weight: 700;">{{date('d M, Y', strtotime($order->created_at))}}</span>
                                        </div>
                                        <div class="col-6 mb-3">
                                            <small class="text-muted d-block">{{\App\CPU\translate('Order Status')}}</small>
                                            <span class="badge text-capitalize" style="background: {{$oMeta['bg']}}; color: {{$oMeta['text']}};">
                                                {{\App\CPU\translate($oMeta['label'])}}
                                            </span>
                                        </div>
                                        <div class="col-6 mb-3 text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                            <small class="text-muted d-block">{{\App\CPU\translate('Payment Status')}}</small>
                                            <span class="text-capitalize" style="color: {{$order->payment_status=='paid' ? '#16A34A' : '#64748B'}}; font-weight: 700;">
                                                {{\App\CPU\translate($order->payment_status)}}
                                            </span>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block">{{\App\CPU\translate('Total Amount')}}</small>
                                            <span style="color: #082A45; font-weight: 800; font-size: 18px;">
                                                {{\App\CPU\Helpers::currency_converter($order->order_amount)}}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <p class="mt-4 mb-0 text-center" style="color: #64748B;">
                                {{\App\CPU\translate('We have received your order. Our team will verify product availability and update you shortly.')}}
                            </p>

                            <div class="row mt-4">
                                <div class="col-12 col-md-4 mb-2">
                                    @if(isset($order) && $order)
                                        <a href="{{route('account-order-details', ['id'=>$order->id])}}" class="btn btn-block" style="background: #082A45; color: #fff;">
                                            {{\App\CPU\translate('View Order Details')}}
                                        </a>
                                    @else
                                        <a href="{{route('account-oder')}}" class="btn btn-block" style="background: #082A45; color: #fff;">
                                            {{\App\CPU\translate('check_orders')}}
                                        </a>
                                    @endif
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <a href="{{route('home')}}" class="btn btn-block" style="background: #FFC400; color: #082A45; font-weight: 600;">
                                        {{\App\CPU\translate('Continue Shopping')}}
                                    </a>
                                </div>
                                <div class="col-12 col-md-4 mb-2">
                                    <a href="{{route('contacts')}}" class="btn btn-block btn-outline-secondary">
                                        {{\App\CPU\translate('Contact Support')}}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

@endpush
