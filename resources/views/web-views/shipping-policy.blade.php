@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Shipping Policy') . ' - ' . $web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Shipping Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/shipping-policy">
    <meta property="og:description" content="Shipping Policy for {{$web_config['name']->value}} — IndustrialNeeds.co">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Shipping Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/shipping-policy">
    <meta property="twitter:description" content="Shipping Policy for {{$web_config['name']->value}}">
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

        <h1 class="policy-page-title">Shipping Policy</h1>
        <p class="policy-effective-date">Effective Date: May 2025</p>

        <p class="policy-intro">
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>), we are committed to delivering your industrial
            products safely and on time. Please read this policy before placing an order.
        </p>

        <div class="policy-section">
            <h2 class="policy-section-heading">1. Shipping Locations</h2>
            <hr class="policy-divider">
            <p>We ship to all major locations across India. For remote or restricted pin codes, delivery timelines may be extended and additional freight charges may apply. You will be informed at checkout if your location is outside our standard delivery network.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">2. Order Processing Time</h2>
            <hr class="policy-divider">
            <p>Orders are processed within <strong>1–2 business days</strong> after order confirmation and successful payment. Processing may take longer during public holidays and weekends. Bulk or custom industrial orders may require additional time — you will be notified separately.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">3. Estimated Delivery Time</h2>
            <hr class="policy-divider">
            <p>Estimated delivery timelines after dispatch:</p>
            <h3 class="policy-sub-heading">a) Metro Cities</h3>
            <p>Delhi, Mumbai, Bengaluru, Chennai, Kolkata, Hyderabad — <strong>3–5 business days</strong></p>
            <h3 class="policy-sub-heading">b) Tier 2 &amp; Tier 3 Cities</h3>
            <p><strong>5–8 business days</strong> after dispatch</p>
            <h3 class="policy-sub-heading">c) Remote / Rural Areas</h3>
            <p><strong>8–12 business days</strong> after dispatch</p>
            <p>These are estimates only. IndustrialNeeds.co is not responsible for delays caused by third-party logistics partners, weather conditions, or public holidays.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">4. Shipping Charges</h2>
            <hr class="policy-divider">
            <p>Shipping charges are calculated based on product weight, dimensions, and delivery location. The exact charge is displayed at checkout before you confirm payment. Certain products may qualify for free shipping — this is indicated on the product page.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">5. Order Tracking</h2>
            <hr class="policy-divider">
            <p>Once dispatched, you will receive a confirmation via <strong>email and/or SMS</strong> with the courier tracking number and logistics partner name. Track your order anytime under <strong>My Orders</strong> on our website or directly on the courier's portal.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">6. Packaging</h2>
            <hr class="policy-divider">
            <p>All items are securely packed to prevent damage in transit. Industrial components and fragile equipment receive additional protective packaging. If you receive a visibly damaged or tampered shipment, please <strong>refuse delivery</strong> and contact us immediately.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">7. Partial Shipments</h2>
            <hr class="policy-divider">
            <p>For multi-item orders, products may ship from different warehouses or sellers and arrive separately. You will receive individual tracking details for each shipment at no additional charge.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">8. Failed Delivery &amp; Return to Origin (RTO)</h2>
            <hr class="policy-divider">
            <p>If delivery fails due to an incorrect address, unavailability of the recipient, or refusal to accept the package, the courier will attempt redelivery or hold the shipment for a limited period. If returned to us, we will contact you to arrange re-dispatch. Additional shipping charges may apply.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">9. Damaged or Missing Items</h2>
            <hr class="policy-divider">
            <p>If your order arrives damaged or with missing items, contact us within <strong>48 hours of delivery</strong> with photographs of the product and outer packaging. We will arrange a replacement or refund as per our <a href="{{ route('refund-policy') }}">Return &amp; Refund Policy</a>.</p>
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
