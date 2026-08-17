@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Return, Exchange & Refund Policy') . ' - ' . $web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Return, Exchange & Refund Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/refund-policy">
    <meta property="og:description" content="Return, Exchange and Refund Policy for {{$web_config['name']->value}} — IndustrialSupply.in">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Return, Exchange & Refund Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/refund-policy">
    <meta property="twitter:description" content="Return, Exchange and Refund Policy for {{$web_config['name']->value}}">
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

        <h1 class="policy-page-title">Return, Exchange &amp; Refund Policy</h1>
        <p class="policy-effective-date">Effective Date: May 2025</p>

        <p class="policy-intro">
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialSupply.in</strong>), customer satisfaction is our priority.
            This policy outlines the terms and process for returns, exchanges, and refunds.
        </p>

        <div class="policy-section">
            <h2 class="policy-section-heading">1. Eligibility for Return</h2>
            <hr class="policy-divider">
            <p>A product is eligible for return if the following conditions are met:</p>
            <h3 class="policy-sub-heading">a) Accepted Reasons</h3>
            <ul>
                <li>Item received is damaged, defective, or not as described.</li>
                <li>Wrong product was delivered.</li>
                <li>Return request is raised within <strong>7 days of delivery</strong>.</li>
            </ul>
            <h3 class="policy-sub-heading">b) Condition of Item</h3>
            <ul>
                <li>Must be unused and in original, unaltered packaging.</li>
                <li>All original tags, manuals, and accessories must be intact.</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">2. Non-Returnable Items</h2>
            <hr class="policy-divider">
            <p>The following products are <strong>not eligible for return</strong>:</p>
            <ul>
                <li>Products that have been used, installed, or modified.</li>
                <li>Items missing original packaging, labels, or serial numbers.</li>
                <li>Custom-manufactured or made-to-order products.</li>
                <li>Products damaged due to misuse or improper installation by the customer.</li>
                <li>Consumables (filters, lubricants, gaskets, seals) once opened or used.</li>
                <li>Items purchased during a sale or special offer (unless defective).</li>
            </ul>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">3. Exchange Policy</h2>
            <hr class="policy-divider">
            <p>Exchanges are available for <strong>defective or wrongly delivered products only</strong>, subject to stock availability. Exchange requests must be raised within <strong>7 days of delivery</strong>. If the exact item is unavailable, a full refund will be issued instead.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">4. Return Process</h2>
            <hr class="policy-divider">
            <p>To initiate a return or exchange:</p>
            <ul>
                <li>Log in to your account and go to <strong>My Orders</strong>.</li>
                <li>Click <strong>Request Return</strong> on the relevant order.</li>
                <li>Upload clear photographs of the product and packaging showing the issue.</li>
                <li>Our team will review the request within <strong>2–3 business days</strong>.</li>
                <li>Once approved, you will receive pickup instructions or a prepaid return label.</li>
            </ul>
            <p><strong>Do not ship items back without prior written approval.</strong> Unapproved returns will not be accepted or processed.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">5. Refunds</h2>
            <hr class="policy-divider">
            <p>Refunds are processed after the returned item is received and inspected. Refund timelines after approval:</p>
            <h3 class="policy-sub-heading">a) Online Payments</h3>
            <p>Credit/Debit Card, Net Banking, UPI — refunded to the original payment source within <strong>5–7 business days</strong>.</p>
            <h3 class="policy-sub-heading">b) Wallet Payments</h3>
            <p>Credited within <strong>2–3 business days</strong>.</p>
            <h3 class="policy-sub-heading">c) Cash on Delivery (COD)</h3>
            <p>Refunded via NEFT to your bank account within <strong>7–10 business days</strong>. Bank details will be requested at approval.</p>
            <p><strong>Note:</strong> Shipping charges are non-refundable unless the return is due to a defect or delivery error on our part.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">6. Return Shipping Charges</h2>
            <hr class="policy-divider">
            <p>If the return is due to a defective, damaged, or wrongly delivered product, <strong>IndustrialSupply.in bears the return shipping cost</strong>. For all other return reasons, return shipping charges are the customer's responsibility.</p>
        </div>

        <div class="policy-section">
            <h2 class="policy-section-heading">7. Cancellation &amp; Refund</h2>
            <hr class="policy-divider">
            <p>Orders cancelled before dispatch are refunded in full. See our <a href="{{ route('cancelation') }}">Cancellation Policy</a> for complete details.</p>
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
