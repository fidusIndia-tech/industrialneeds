@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Refund & Return Policy'))

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Refund & Return Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/refund-policy">
    <meta property="og:description" content="Refund and Return Policy for {{$web_config['name']->value}}">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Refund & Return Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/refund-policy">
    <meta property="twitter:description" content="Refund and Return Policy for {{$web_config['name']->value}}">
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
    <h2 class="text-center mt-3 policy-title">{{ \App\CPU\translate('Return, Exchange & Refund Policy') }}</h2>
    <p class="text-center policy-updated">Last Updated: May 2025</p>

    <div class="policy-body">

        <p>
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>), we are committed to ensuring your satisfaction.
            This policy outlines the conditions and process for returns, exchanges, and refunds.
        </p>

        <h4>1. Eligibility for Return</h4>
        <p>A product is eligible for return under the following conditions:</p>
        <ul>
            <li>The item received is damaged, defective, or not as described.</li>
            <li>The wrong product was delivered.</li>
            <li>The return request is raised within <strong>7 days</strong> of delivery.</li>
            <li>The product is unused, in its original packaging, and with all tags/accessories intact.</li>
        </ul>
        <p>
            Certain categories of products — including custom-manufactured items, sealed industrial components
            once opened, and software or digital goods — are <strong>non-returnable</strong> unless they are
            defective upon arrival.
        </p>

        <h4>2. Non-Returnable Items</h4>
        <ul>
            <li>Products that have been used, installed, or modified.</li>
            <li>Items without original packaging or missing serial numbers.</li>
            <li>Custom or made-to-order products.</li>
            <li>Products damaged due to misuse, mishandling, or improper installation.</li>
            <li>Consumables (filters, lubricants, gaskets, etc.) once used.</li>
        </ul>

        <h4>3. Return Process</h4>
        <p>To initiate a return:</p>
        <ul>
            <li>Log in to your account, go to <strong>My Orders</strong>, and click <strong>Request Return</strong>
                on the relevant order.</li>
            <li>Provide photos of the product and packaging clearly showing the issue.</li>
            <li>Our team will review your request within <strong>2–3 business days</strong>.</li>
            <li>Once approved, you will receive a pickup confirmation or return shipping instructions.</li>
        </ul>
        <p>
            Please do <strong>not</strong> return products without receiving approval from our team, as
            unapproved returns will not be processed.
        </p>

        <h4>4. Exchange Policy</h4>
        <p>
            Exchanges are available for defective or wrongly shipped products, subject to stock availability.
            If the exact product is unavailable, a refund will be issued instead. Exchange requests must be
            raised within <strong>7 days</strong> of delivery.
        </p>

        <h4>5. Refund Policy</h4>
        <p>Refunds are processed under the following conditions:</p>
        <ul>
            <li>The returned item has been received and inspected by our team.</li>
            <li>The return has been approved based on our eligibility criteria.</li>
        </ul>
        <p><strong>Refund timelines:</strong></p>
        <ul>
            <li><strong>Online payments</strong> (UPI, Net Banking, Credit/Debit Card): Refunded to the original
                payment source within <strong>5–7 business days</strong> after return approval.</li>
            <li><strong>Wallet payments:</strong> Credited within <strong>2–3 business days</strong>.</li>
            <li><strong>COD orders:</strong> Refunded via bank transfer / NEFT within
                <strong>7–10 business days</strong>. Bank details will be requested at the time of return approval.</li>
        </ul>

        <h4>6. Shipping Charges for Returns</h4>
        <p>
            If the return is due to a defect, damage, or wrong product delivered by us, the return shipping
            cost will be borne by <strong>IndustrialNeeds.co</strong>. If the return is for any other reason
            (e.g., change of mind), the customer will bear the return shipping charges.
        </p>

        <h4>7. Cancellation & Refund</h4>
        <p>
            Orders cancelled before dispatch are refunded in full. Please refer to our
            <a href="{{ route('cancelation') }}">Cancellation Policy</a> for details.
        </p>

        <h4>8. Contact Us</h4>
        <p>
            For return, exchange, or refund queries, please reach us at:<br>
            <strong>Email:</strong> {{ \App\CPU\Helpers::get_business_settings('company_email') }}<br>
            <strong>Phone:</strong> {{ \App\CPU\Helpers::get_business_settings('company_phone') }}<br>
            <strong>Address:</strong> {{ \App\CPU\Helpers::get_business_settings('shop_address') }}
        </p>

    </div>
</div>
@endsection
