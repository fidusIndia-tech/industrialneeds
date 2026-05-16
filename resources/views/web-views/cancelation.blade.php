@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Cancellation Policy'))

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Cancellation Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/cancelation">
    <meta property="og:description" content="Cancellation Policy for {{$web_config['name']->value}}">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Cancellation Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/cancelation">
    <meta property="twitter:description" content="Cancellation Policy for {{$web_config['name']->value}}">
    <style>
        .policy-title {
            font-size: 28px;
            font-weight: 700;
            margin-top: 2rem;
        }
        .policy-container {
            width: 91%;
            border: 1px solid #D8D8D8;
            margin-top: 3%;
            margin-bottom: 3%;
            border-radius: 4px;
        }
        .policy-body {
            padding: 3%;
            line-height: 1.8;
        }
        .policy-body h4 {
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .policy-body p, .policy-body li {
            font-size: 15px;
            color: #444;
        }
        .policy-body ul {
            padding-left: 1.5rem;
        }
        .policy-updated {
            font-size: 13px;
            color: #888;
            margin-bottom: 1rem;
        }
    </style>
@endpush

@section('content')
<div class="container policy-container rtl" style="text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
    <h2 class="text-center mt-3 policy-title">{{ \App\CPU\translate('Cancellation Policy') }}</h2>
    <p class="text-center policy-updated">Last Updated: May 2025</p>

    <div class="policy-body">

        <p>
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>), we understand that circumstances can change.
            This Cancellation Policy explains your rights and our process for order cancellations.
        </p>

        <h4>1. Cancellation by Customer — Before Dispatch</h4>
        <p>
            You may cancel your order <strong>before it has been dispatched</strong> by contacting us through any of the following:
        </p>
        <ul>
            <li>Logging into your account and using the "Cancel Order" option on your order page.</li>
            <li>Contacting our customer support team via email or phone with your Order ID.</li>
        </ul>
        <p>
            If the cancellation request is received before the order is packed and handed over to the logistics partner,
            the full order amount will be refunded to the original payment method within <strong>5–7 business days</strong>.
        </p>

        <h4>2. Cancellation After Dispatch</h4>
        <p>
            Once an order has been dispatched (shipped), it <strong>cannot be cancelled</strong>. If you no longer want the
            product after it has been shipped, you may initiate a <a href="{{ route('refund-policy') }}">return request</a>
            after delivery, subject to our Return &amp; Refund Policy.
        </p>

        <h4>3. Cancellation of Bulk / Custom Orders</h4>
        <p>
            Orders placed for industrial products, custom configurations, or large quantities may be subject to
            <strong>partial or no cancellation</strong>, depending on the stage of processing. Please contact us
            immediately if you wish to cancel such an order. Cancellation charges may apply if production or procurement
            has already commenced.
        </p>

        <h4>4. Cancellation by IndustrialNeeds.co</h4>
        <p>
            We reserve the right to cancel any order at our discretion under the following circumstances:
        </p>
        <ul>
            <li>The product is out of stock or discontinued.</li>
            <li>Pricing or product information errors were present at the time of ordering.</li>
            <li>Payment could not be verified or was flagged as fraudulent.</li>
            <li>The delivery location is outside our serviceable area.</li>
        </ul>
        <p>
            In any such case, you will be notified promptly and a <strong>full refund</strong> will be issued
            within 5–7 business days.
        </p>

        <h4>5. Prepaid Orders</h4>
        <p>
            For prepaid orders cancelled before dispatch, refunds will be credited back to the original payment source
            (bank account, UPI, credit/debit card, or wallet). For Cash on Delivery (COD) orders cancelled before
            dispatch, no refund action is required.
        </p>

        <h4>6. How to Request Cancellation</h4>
        <p>
            To cancel an order, please:
        </p>
        <ul>
            <li>Log in to your account at <a href="{{ route('home') }}">{{ env('APP_URL') }}</a></li>
            <li>Navigate to <strong>My Orders</strong> and select the order you wish to cancel.</li>
            <li>Click <strong>Cancel Order</strong> and provide a reason.</li>
            <li>Alternatively, contact us by email at
                <strong>{{ \App\CPU\Helpers::get_business_settings('company_email') }}</strong>
                or call <strong>{{ \App\CPU\Helpers::get_business_settings('company_phone') }}</strong>
                with your Order ID.
            </li>
        </ul>

        <h4>7. Contact Us</h4>
        <p>
            For any cancellation-related queries, please reach us at:<br>
            <strong>Email:</strong> {{ \App\CPU\Helpers::get_business_settings('company_email') }}<br>
            <strong>Phone:</strong> {{ \App\CPU\Helpers::get_business_settings('company_phone') }}<br>
            <strong>Address:</strong> {{ \App\CPU\Helpers::get_business_settings('shop_address') }}
        </p>

    </div>
</div>
@endsection
