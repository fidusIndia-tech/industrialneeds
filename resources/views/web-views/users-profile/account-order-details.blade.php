@extends('layouts.front-end.app')

@section('title',\App\CPU\translate('Order Details'))

@push('css_or_js')
    <style>
        .page-item.active .page-link {
            background-color: {{$web_config['primary_color']}}            !important;
        }

        .page-item.active > .page-link {
            box-shadow: 0 0 black !important;
        }

        .widget-categories .accordion-heading > a:hover {
            color: #FFD5A4 !important;
        }

        .widget-categories .accordion-heading > a {
            color: #FFD5A4;
        }

        body {
            font-family: 'Titillium Web', sans-serif
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
            color: #FFFFFF;
            font-weight: 900;
            font-size: 13px;

        }

        .spandHeadO {
            color: #FFFFFF !important;
            font-weight: 400;
            font-size: 13px;

        }

        .font-name {
            font-weight: 600;
            font-size: 12px;
            color: #030303;
        }

        .amount {
            font-size: 15px;
            color: #030303;
            font-weight: 600;
            margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 60px;

        }

        a {
            color: {{$web_config['primary_color']}};
            cursor: pointer;
            text-decoration: none;
            background-color: transparent;
        }

        a:hover {
            cursor: pointer;
        }

        @media (max-width: 600px) {
            .sidebar_heading {
                background: #1B7FED;
            }

            .sidebar_heading h1 {
                text-align: center;
                color: aliceblue;
                padding-bottom: 17px;
                font-size: 19px;
            }
        }

        @media (max-width: 768px) {
            .for-tab-img {
                width: 100% !important;
            }

            .for-glaxy-name {
                display: none;
            }
        }

        @media (max-width: 360px) {
            .for-mobile-glaxy {
                display: flex !important;
            }

            .for-glaxy-mobile {
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: 6px;
            }

            .for-glaxy-name {
                display: none;
            }
        }

        @media (max-width: 600px) {
            .for-mobile-glaxy {
                display: flex !important;
            }

            .for-glaxy-mobile {
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: 6px;
            }

            .for-glaxy-name {
                display: none;
            }

            .order_table_tr {
                display: grid;
            }

            .order_table_td {
                border-bottom: 1px solid #fff !important;
            }

            .order_table_info_div {
                width: 100%;
                display: flex;
            }

            .order_table_info_div_1 {
                width: 50%;
            }

            .order_table_info_div_2 {
                width: 49%;
                text-align: {{Session::get('direction') === "rtl" ? 'left' : 'right'}}        !important;
            }

            .spandHeadO {
                font-size: 16px;
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 16px;
            }

            .spanTr {
                font-size: 16px;
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: 16px;
                margin-top: 10px;
            }

            .amount {
                font-size: 13px;
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 0px;

            }

        }

        /* ---- Order tracking timeline ---- */
        .track-card { background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 8px; }
        .track-card .track-head { background: #082A45; color: #fff; padding: 14px 18px; border-radius: 8px 8px 0 0; font-weight: 700; }
        .track-body { padding: 26px 18px 14px; }
        .track-timeline { display: flex; flex-wrap: nowrap; position: relative; }
        .track-step { flex: 1 1 0; text-align: center; position: relative; padding-top: 36px; min-width: 0; }
        .track-step::before { content: ""; position: absolute; top: 15px; left: -50%; width: 100%; height: 3px; background: #E2E8F0; z-index: 1; }
        .track-step:first-child::before { display: none; }
        .track-icon { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 32px; height: 32px; border-radius: 50%; background: #E2E8F0; color: #94A3B8; display: flex; align-items: center; justify-content: center; font-size: 13px; z-index: 2; border: 3px solid #fff; box-shadow: 0 0 0 1px #E2E8F0; }
        .track-label { font-size: 12px; color: #64748B; margin-top: 8px; line-height: 1.25; padding: 0 2px; }
        .track-done .track-icon { background: #16A34A; color: #fff; box-shadow: 0 0 0 1px #16A34A; }
        .track-done::before { background: #16A34A; }
        .track-current .track-icon { background: #FFC400; color: #082A45; box-shadow: 0 0 0 1px #FFC400; }
        .track-current .track-label { color: #082A45; font-weight: 700; }
        .track-current::before { background: #16A34A; }
        .track-upcoming .track-icon { background: #F5F7FA; color: #94A3B8; }

        .track-terminal { display: flex; align-items: center; gap: 12px; padding: 18px; border-radius: 8px; }
        .track-terminal .t-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex: 0 0 40px; }

        .shipment-card { background: #F5F7FA; border: 1px solid #E2E8F0; border-radius: 8px; padding: 18px; }
        .shipment-card .s-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dashed #E2E8F0; font-size: 13px; }
        .shipment-card .s-row:last-child { border-bottom: none; }
        .shipment-card .s-label { color: #64748B; }
        .shipment-card .s-value { color: #111827; font-weight: 600; text-align: right; }
        .btn-track-ship { background: #082A45; color: #fff; border: none; }
        .btn-track-ship:hover { background: #0B4F8A; color: #fff; }

        @media (max-width: 767px) {
            .track-timeline { flex-direction: column; }
            .track-step { flex: none; text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}}; padding-top: 0; padding-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 44px; min-height: 48px; }
            .track-step::before { top: 0; {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 15px; left: auto; width: 3px; height: 100%; }
            .track-icon { top: 2px; {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 0; left: auto; transform: none; }
            .track-label { margin-top: 6px; }
        }
    </style>
@endpush

@section('content')

    <!-- Page Content-->
    <div class="container pb-5 mb-2 mb-md-4 mt-3 rtl"
         style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <div class="row">
            <!-- Sidebar-->
            @include('web-views.partials._profile-aside')

            {{-- Content --}}
            <section class="col-lg-9 col-md-9">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <a class="page-link" href="{{ route('account-oder') }}">
                            <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'right ml-2' : 'left mr-2'}}"></i>{{\App\CPU\translate('back')}}
                        </a>
                    </div>
                </div>

                @php
                    $statusFlow   = \App\Model\Order::STATUS_FLOW;
                    $isTerminal   = \App\Model\Order::isTerminalStatus($order->order_status);
                    $progress     = \App\Model\Order::timelineProgress($order->order_status);
                    $statusColors = \App\Model\Order::statusColors($order->order_status);
                    $history      = $order->statusHistory;
                    $courier      = $order->delivery_service_name;
                    $trackingNo   = $order->third_party_delivery_tracking_id;
                    $trackingUrl  = optional($history->whereNotNull('tracking_url')->last())->tracking_url;
                    $expectedDate = optional($history->whereNotNull('expected_delivery_date')->last())->expected_delivery_date;
                    $hasShipment  = $courier || $trackingNo || $trackingUrl || $expectedDate;
                @endphp

                {{-- Order tracking timeline --}}
                <div class="track-card mb-4">
                    <div class="track-head">{{\App\CPU\translate('Order Status')}}</div>
                    <div class="track-body">
                        @if($isTerminal)
                            <div class="track-terminal" style="background: {{$statusColors['bg']}};">
                                <div class="t-icon" style="background: {{$statusColors['text']}}; color: #fff;">
                                    <i class="fa fa-info-circle"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: {{$statusColors['text']}}; font-size: 16px;">
                                        {{\App\CPU\translate($statusColors['label'])}}
                                    </div>
                                    <small class="text-muted">
                                        @if($order->order_status == 'canceled')
                                            {{\App\CPU\translate('This order has been cancelled.')}}
                                        @elseif($order->order_status == 'returned')
                                            {{\App\CPU\translate('This order has been returned.')}}
                                        @elseif($order->order_status == 'refunded')
                                            {{\App\CPU\translate('This order has been refunded.')}}
                                        @else
                                            {{\App\CPU\translate('This order could not be completed.')}}
                                        @endif
                                    </small>
                                </div>
                            </div>
                        @else
                            <div class="track-timeline">
                                @foreach($statusFlow as $key => $label)
                                    @php($state = $loop->index < $progress ? 'done' : ($loop->index == $progress ? 'current' : 'upcoming'))
                                    <div class="track-step track-{{$state}}">
                                        <div class="track-icon">
                                            @if($state == 'done')
                                                <i class="fa fa-check"></i>
                                            @else
                                                {{$loop->iteration}}
                                            @endif
                                        </div>
                                        <div class="track-label">{{\App\CPU\translate($label)}}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Shipment details --}}
                <div class="mb-4">
                    <h5 class="mb-2" style="color: #082A45; font-weight: 700;">{{\App\CPU\translate('Shipment Details')}}</h5>
                    @if($hasShipment)
                        <div class="shipment-card">
                            @if($courier)
                                <div class="s-row">
                                    <span class="s-label">{{\App\CPU\translate('Courier Partner')}}</span>
                                    <span class="s-value">{{$courier}}</span>
                                </div>
                            @endif
                            @if($trackingNo)
                                <div class="s-row">
                                    <span class="s-label">{{\App\CPU\translate('Tracking Number')}}</span>
                                    <span class="s-value">{{$trackingNo}}</span>
                                </div>
                            @endif
                            @if($expectedDate)
                                <div class="s-row">
                                    <span class="s-label">{{\App\CPU\translate('Expected Delivery')}}</span>
                                    <span class="s-value">{{\Carbon\Carbon::parse($expectedDate)->format('d M, Y')}}</span>
                                </div>
                            @endif
                            @if($trackingUrl)
                                <div class="mt-3">
                                    <a href="{{$trackingUrl}}" target="_blank" rel="noopener"
                                       class="btn btn-track-ship btn-sm">
                                        <i class="fa fa-truck mr-1"></i> {{\App\CPU\translate('Track Shipment')}}
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="shipment-card text-muted" style="font-size: 13px;">
                            {{\App\CPU\translate('Shipment details will be updated once your order is dispatched.')}}
                        </div>
                    @endif
                </div>

                @if($history && $history->count() > 0)
                    <div class="mb-4">
                        <h5 class="mb-2" style="color: #082A45; font-weight: 700;">{{\App\CPU\translate('Order Updates')}}</h5>
                        <div class="shipment-card">
                            @foreach($history->sortByDesc('id') as $h)
                                @php($hMeta = \App\Model\Order::statusColors($h->status))
                                <div class="s-row" style="align-items: flex-start;">
                                    <span>
                                        <span class="badge text-capitalize" style="background: {{$hMeta['bg']}}; color: {{$hMeta['text']}};">
                                            {{\App\CPU\translate($hMeta['label'])}}
                                        </span>
                                        @if($h->note)
                                            <span class="d-block text-muted mt-1" style="font-size: 12px;">{{$h->note}}</span>
                                        @endif
                                    </span>
                                    <span class="s-label" style="white-space: nowrap;">
                                        {{date('d M, Y', strtotime($h->created_at))}}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif


                <div class="card box-shadow-sm">
                    @if(\App\CPU\Helpers::get_business_settings('order_verification'))
                        <div class="card-header">
                            <h4>{{\App\CPU\translate('order_verification_code')}} : {{$order['verification_code']}}</h4>
                        </div>
                    @endif
                    <div class="payment mb-3  table-responsive">
                        @if(isset($order['seller_id']) != 0)
                            @php($shopName=\App\Model\Shop::where('seller_id', $order['seller_id'])->first())
                        @endif
                        <table class="table table-borderless">
                            <thead>
                            <tr class="order_table_tr" style="background: {{$web_config['primary_color']}}">
                                <td class="order_table_td">
                                    <div class="order_table_info_div">
                                        <div class="order_table_info_div_1 py-2">
                                            <span class="d-block spandHeadO">{{\App\CPU\translate('order_no')}}: </span>
                                        </div>
                                        <div class="order_table_info_div_2">
                                            <span class="spanTr"> {{$order->id}} </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="order_table_td">
                                    <div class="order_table_info_div">
                                        <div class="order_table_info_div_1 py-2">
                                            <span
                                                class="d-block spandHeadO">{{\App\CPU\translate('order_date')}}: </span>
                                        </div>
                                        <div class="order_table_info_div_2">
                                            <span
                                                class="spanTr"> {{date('d M, Y',strtotime($order->created_at))}} </span>
                                        </div>

                                    </div>
                                </td>
                                @if( $order->order_type == 'default_type')
                                <td class="order_table_td">
                                    <div class="order_table_info_div">
                                        <div class="order_table_info_div_1 py-2">
                                            <span
                                                class="d-block spandHeadO">{{\App\CPU\translate('shipping_address')}}: </span>
                                        </div>

                                        @if($order->shippingAddress)
                                            @php($shipping=$order->shippingAddress)
                                        @else
                                            @php($shipping=json_decode($order['shipping_address_data']))
                                        @endif

                                        <div class="order_table_info_div_2">
                                            <span class="spanTr">
                                                @if($shipping)
                                                    {{$shipping->address}},<br>
                                                     {{$shipping->city}}
                                                    , {{$shipping->zip}}
                                                    
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="order_table_td">
                                    <div class="order_table_info_div">
                                        <div class="order_table_info_div_1 py-2">
                                            <span
                                                class="d-block spandHeadO">{{\App\CPU\translate('billing_address')}}: </span>
                                        </div>

                                        @if($order->billingAddress)
                                            @php($billing=$order->billingAddress)
                                        @else
                                            @php($billing=json_decode($order['billing_address_data']))
                                        @endif

                                        <div class="order_table_info_div_2">
                                            <span class="spanTr">
                                                @if($billing)
                                                    {{$billing->address}}, <br>
                                                     {{$billing->city}}
                                                    , {{$billing->zip}}
                                                    
                                                @else
                                                    {{$shipping->address}},<br>
                                                     {{$shipping->city}}
                                                    , {{$shipping->zip}}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                @endif
                            </tr>
                            </thead>
                        </table>

                        <table class="table table-borderless">
                            <tbody>
                            @foreach ($order->details as $key=>$detail)
                                @php($product=json_decode($detail->product_details,true))
                                <tr>
                                    <div class="row">
                                        <div class="col-md-6"
                                             onclick="location.href='{{route('product',$product['slug'])}}'">
                                            <td class="col-2 for-tab-img">
                                                <img class="d-block"
                                                     onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                     src="{{\App\CPU\ProductManager::product_image_path('thumbnail')}}/{{$product['thumbnail']}}"
                                                     alt="VR Collection" width="60">
                                            </td>
                                            <td class="col-10 for-glaxy-name" style="vertical-align:middle;">
                                                
                                                
                                                <a href="{{route('product',[$product['slug']])}}">
                                                    {{isset($product['name']) ? Str::limit($product['name'],40) : ''}}
                                                </a> 
                                                @if($detail->refund_request == 1)
                                                    <small> ({{\App\CPU\translate('refund_pending')}}) </small> <br>
                                                @elseif($detail->refund_request == 2)
                                                    <small> ({{\App\CPU\translate('refund_approved')}}) </small> <br>
                                                @elseif($detail->refund_request == 3)
                                                    <small> ({{\App\CPU\translate('refund_rejected')}}) </small> <br>
                                                @elseif($detail->refund_request == 4)
                                                    <small> ({{\App\CPU\translate('refund_refunded')}}) </small> <br>
                                                @endif<br>
                                                <span>{{\App\CPU\translate('variant')}} : </span>
                                                {{$detail->variant}}
                                            </td>
                                        </div>
                                        <div class="col-md-4">
                                            <td width="100%">
                                                <div
                                                    class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                                    <span
                                                        class="font-weight-bold amount">{{\App\CPU\Helpers::currency_converter($detail->price)}} </span>
                                                    <br>
                                                    <span>{{\App\CPU\translate('qty')}}: {{$detail->qty}}</span>

                                                </div>
                                            </td>
                                        </div>
                                        <?php
                                            $refund_day_limit=\App\CPU\Helpers::get_business_settings('refund_day_limit');
                                            $order_details_date = $detail->created_at;
                                            $current = \Carbon\Carbon::now();
                                            $length = $order_details_date->diffInDays($current);                                          

                                        ?>
                                        <div class="col-md-2">
                                            <td>
                                                @if($order->order_type == 'default_type')
                                                    @if($order->order_status=='delivered')
                                                        <a href="{{route('submit-review',[$detail->id])}}" class="btn btn-primary btn-sm d-inline-block w-100 mb-2">{{\App\CPU\translate('review')}}</a>
                                                        
                                                        @if($detail->refund_request !=0)
                                                            <a href="{{route('refund-details',[$detail->id])}}" class="btn btn-primary btn-sm d-inline-block w-100 mb-2">
                                                                {{\App\CPU\translate('refund_details')}}
                                                            </a>
                                                        @endif
                                                        @if( $length <= $refund_day_limit && $detail->refund_request == 0)
                                                            <a href="{{route('refund-request',[$detail->id])}}"
                                                            class="btn btn-primary btn-sm d-inline-block">{{\App\CPU\translate('refund_request')}}</a>
                                                        @endif
                                                    {{--@else
                                                        <a href="javascript:" onclick="review_message()"
                                                        class="btn btn-primary btn-sm d-inline-block w-100 mb-2">{{\App\CPU\translate('review')}}</a>
                                                        
                                                        @if($length <= $refund_day_limit)
                                                            <a href="javascript:" onclick="refund_message()"
                                                                class="btn btn-primary btn-sm d-inline-block">{{\App\CPU\translate('refund_request')}}</a>
                                                        @endif --}}
                                                    @endif
                                                @else
                                                    <label class="badge badge-secondary">
                                                            <a 
                                                            class="btn btn-primary btn-sm">{{\App\CPU\translate('pos_order')}}</a>
                                                        </label>
                                                @endif
                                            </td>    
                                        </div>
                                    </div>
                                    
                                </tr>
                            @endforeach
                            @php($summary=\App\CPU\OrderManager::order_summary($order))
                            </tbody>
                        </table>
                        @php($extra_discount=0)
                        <?php
                            if ($order['extra_discount_type'] == 'percent') {
                                $extra_discount = ($summary['subtotal'] / 100) * $order['extra_discount'];
                            } else {
                                $extra_discount = $order['extra_discount'];
                            }
                        ?>
                        @if($order->delivery_type !=null)
                        
                            <div class="p-2">
                        
                                <h4 style="color: #130505 !important; margin:0px;text-transform: capitalize;">{{\App\CPU\translate('delivery_info')}} </h4>
                                <hr>
                                <div class="m-2">
                                    @if ($order->delivery_type == 'self_delivery')
                                        <p style="color: #414141 !important ; padding-top:5px;">
                                    
                                            <span style="text-transform: capitalize">
                                                {{\App\CPU\translate('delivery_man_name')}} : {{$order->delivery_man['f_name'].' '.$order->delivery_man['l_name']}}
                                            </span>
                                            {{-- <br>
                                            <span style="text-transform: capitalize">
                                                {{\App\CPU\translate('delivery_man_phone')}} : {{$order->delivery_man['phone']}}
                                            </span> --}}
                                        </p>
                                    @else
                                    <p style="color: #414141 !important ; padding-top:5px;">
                                        <span>
                                            {{\App\CPU\translate('delivery_service_name')}} : {{$order->delivery_service_name}}
                                        </span>
                                        <br>
                                        <span>
                                            {{\App\CPU\translate('tracking_id')}} : {{$order->third_party_delivery_tracking_id}}
                                        </span>
                                    </p>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($order->order_note !=null)
                            <div class="p-2">
                        
                                <h4>{{\App\CPU\translate('order_note')}}</h4>
                                <hr>
                                <div class="m-2">
                                    <p>
                                        {{$order->order_note}} 
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                {{--Calculation--}}
                <div class="row d-flex justify-content-end">
                    <div class="col-md-8 col-lg-5">
                        <table class="table table-borderless">
                            <tbody class="totals">
                            <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('Item')}}</span></div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>{{$order->details->count()}}</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('Subtotal')}}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>{{\App\CPU\Helpers::currency_converter($summary['subtotal'])}}</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('text_fee')}}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>{{\App\CPU\Helpers::currency_converter($summary['total_tax'])}}</span>
                                    </div>
                                </td>
                            </tr>
                            @if($order->order_type == 'default_type')
                            <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('Shipping')}} {{\App\CPU\translate('Fee')}}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>{{\App\CPU\Helpers::currency_converter($summary['total_shipping_cost'])}}</span>
                                    </div>
                                </td>
                            </tr>
                            @endif

                            <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('Discount')}} {{\App\CPU\translate('on_product')}}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>- {{\App\CPU\Helpers::currency_converter($summary['total_discount_on_product'])}}</span>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('Coupon')}} {{\App\CPU\translate('Discount')}}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>- {{\App\CPU\Helpers::currency_converter($order->discount_amount)}}</span>
                                    </div>
                                </td>
                            </tr>

                            @if($order->order_type != 'default_type')
                                <tr>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="product-qty ">{{\App\CPU\translate('extra')}} {{\App\CPU\translate('Discount')}}</span>
                                    </div>
                                </td>
                                
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                                        <span>- {{\App\CPU\Helpers::currency_converter($extra_discount)}}</span>
                                    </div>
                                </td>
                            </tr>
                            @endif

                            <tr class="border-top border-bottom">
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}"><span
                                            class="font-weight-bold">{{\App\CPU\translate('Total')}}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}"><span
                                            class="font-weight-bold amount ">{{\App\CPU\Helpers::currency_converter($order->order_amount)}}</span>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="justify-content mt-4 for-mobile-glaxy ">
                    <a href="{{route('generate-invoice',[$order->id])}}" class="btn btn-primary for-glaxy-mobile"
                       style="width:49%;">
                        {{\App\CPU\translate('generate_invoice')}}
                    </a>
                    <a class="btn btn-secondary" type="button"
                       href="{{route('track-order.result',['order_id'=>$order['id'],'from_order_details'=>1])}}"
                       style="width:50%; color: white">
                        {{\App\CPU\translate('Track')}} {{\App\CPU\translate('Order')}}
                    </a>

                </div>
            </section>
        </div>
    </div>

    

@endsection


@push('script')
    <script>
        function review_message() {
            toastr.info('{{\App\CPU\translate('you_can_review_after_the_product_is_delivered!')}}', {
                CloseButton: true,
                ProgressBar: true
            });
        }

        function refund_message(){
            toastr.info('{{\App\CPU\translate('you_can_refund_request_after_the_product_is_delivered!')}}', {
                CloseButton: true,
                ProgressBar: true
            });
        }
    </script>
@endpush

