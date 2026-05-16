@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Cancellation Policy') . ' - ' . $web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Cancellation Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/cancelation">
    <meta property="og:description" content="Cancellation Policy for {{$web_config['name']->value}} — IndustrialNeeds.co">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Cancellation Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/cancelation">
    <meta property="twitter:description" content="Cancellation Policy for {{$web_config['name']->value}}">
    <style>
        .policy-page {
            max-width: 760px;
            margin: 3rem auto 4rem;
            padding: 0 20px;
        }
        @media (max-width: 767px) {
            .policy-page { padding: 0 16px; margin: 2rem auto 3rem; }
        }
        .policy-page-title {
            font-size: 26px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111;
            margin-bottom: 6px;
        }
        .policy-effective-date {
            font-size: 13px;
            color: #666;
            margin-bottom: 28px;
        }
        .policy-intro {
            font-size: 15px;
            color: #333;
            line-height: 1.8;
            margin-bottom: 36px;
        }
        .policy-section { margin-top: 36px; }
        .policy-section-heading {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111;
            margin-bottom: 10px;
        }
        .policy-divider {
            border: none;
            border-top: 1px solid #ddd;
            margin: 0 0 16px;
        }
        .policy-sub-heading {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #333;
            margin-top: 18px;
            margin-bottom: 8px;
        }
        .policy-page p {
            font-size: 15px;
            color: #333;
            line-height: 1.8;
            margin-bottom: 12px;
        }
        .policy-page ul {
            padding-left: 20px;
            margin-bottom: 14px;
        }
        .policy-page ul li {
            font-size: 15px;
            color: #333;
            line-height: 1.75;
            margin-bottom: 6px;
        }
        .policy-contact-section {
            margin-top: 48px;
            border-top: 2px solid #111;
            padding-top: 20px;
        }
        .policy-contact-section p { margin-bottom: 8px; }
    </style>
@endpush

@section('content')
<div class="container">
    <div class="policy-page">

        <h1 class="policy-page-title">Cancellation Policy</h1>
        <p class="policy-effective-date">Effective Date: May 2025</p>

        <p class="policy-intro">
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>), we understand that plans can change.
            This policy explains how and when you can cancel an order placed on our platform.
        </p>

        <div class="policy-section">
            <h2 class="policy-section-heading">1. Cancellation Before Dispatch</h2>
            <hr class="policy-divider">
            <p>You may cancel your order before it has been dispatched using either of the following methods:</p>
            <h3 class="policy-sub-heading">a) Through Your Account</h3>
            <ul>
                <li>Log in and go to <strong>My Orders</strong>.</li>
                <li>Select the order and click <strong>Cancel Order</strong>.</li>
                <li>Choose a cancellation reason and confirm.</li>
            </ul>
            <h3 class="policy-sub-heading">b) Via Customer Support</h3>
            <ul>
                <li>Email or call us with your <strong>Order ID</strong> and cancellation request.</li>
            </ul>
            <p>If the order has not yet been packed and handed over to the courier, a <strong>full refund</strong> will be credited to your original payment method within <strong>5–7 business days</strong>.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">2. Cancellation After Dispatch</h2>
            <hr class="policy-divider">
            <p>Once an order has been dispatched, it <strong>cannot be cancelled</strong>. If you no longer require the product after it has shipped, you may raise a <a href="{{ route('refund-policy') }}">return request</a> after delivery, subject to our Return &amp; Refund Policy.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">3. Bulk &amp; Custom Industrial Orders</h2>
            <hr class="policy-divider">
            <p>Orders for custom-configured equipment, made-to-order products, or large quantities may not be eligible for cancellation once processing has begun. Please contact us <strong>immediately</strong> if you need to cancel such an order. Cancellation charges may apply if procurement or production has already commenced.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">4. Cancellation by IndustrialNeeds.co</h2>
            <hr class="policy-divider">
            <p>We reserve the right to cancel an order under the following circumstances:</p>
            <ul>
                <li>Product is out of stock or has been discontinued.</li>
                <li>A pricing or product information error was present at the time of ordering.</li>
                <li>Payment could not be verified or was flagged as suspicious.</li>
                <li>The delivery address is outside our serviceable area.</li>
            </ul>
            <p>In all such cases, you will be notified immediately and a <strong>full refund</strong> will be issued within 5–7 business days.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">5. Refund on Cancellation</h2>
            <hr class="policy-divider">
            <h3 class="policy-sub-heading">a) Prepaid Orders</h3>
            <p>Refunded to the original payment source — credit/debit card, UPI, net banking, or wallet — within <strong>5–7 business days</strong>.</p>
            <h3 class="policy-sub-heading">b) Cash on Delivery (COD) Orders</h3>
            <p>No refund action is required for COD orders cancelled before dispatch.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">6. How to Contact Us for Cancellation</h2>
            <hr class="policy-divider">
            <ul>
                <li>Visit <a href="https://industrialneeds.co">https://industrialneeds.co</a> and log in.</li>
                <li>Go to <strong>My Orders</strong> → select order → click <strong>Cancel Order</strong>.</li>
                <li>Or email <strong>{{ \App\CPU\Helpers::get_business_settings('company_email') }}</strong> / call <strong>{{ \App\CPU\Helpers::get_business_settings('company_phone') }}</strong> with your Order ID.</li>
            </ul>
        </div>

        <div class="policy-contact-section">
            <h2 class="policy-section-heading">Contact Us</h2>
            <p>📧 <strong>Email:</strong> {{ \App\CPU\Helpers::get_business_settings('company_email') }}</p>
            <p>📞 <strong>Phone:</strong> {{ \App\CPU\Helpers::get_business_settings('company_phone') }}</p>
            <p>📍 <strong>Address:</strong> {{ \App\CPU\Helpers::get_business_settings('shop_address') }}</p>
        </div>

    </div>
</div>
@endsection
