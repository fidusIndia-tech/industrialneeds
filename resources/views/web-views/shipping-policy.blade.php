@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Shipping Policy'))

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Shipping Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/shipping-policy">
    <meta property="og:description" content="Shipping Policy for {{$web_config['name']->value}}">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Shipping Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/shipping-policy">
    <meta property="twitter:description" content="Shipping Policy for {{$web_config['name']->value}}">
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
    <h2 class="text-center mt-3 policy-title">{{ \App\CPU\translate('Shipping Policy') }}</h2>
    <p class="text-center policy-updated">Last Updated: May 2025</p>

    <div class="policy-body">

        <p>
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>), we strive to deliver your industrial products
            safely and on time. Please read this Shipping Policy carefully before placing your order.
        </p>

        <h4>1. Serviceable Locations</h4>
        <p>
            We ship across India. For remote or restricted locations, delivery timelines may be longer and
            additional freight charges may apply. You will be notified if your pin code is outside our
            standard delivery network.
        </p>

        <h4>2. Order Processing Time</h4>
        <p>
            Orders are typically processed within <strong>1–3 business days</strong> from the date of order
            confirmation and successful payment. Processing may be delayed on public holidays and weekends.
            For bulk or custom orders, processing time may vary — you will be informed separately.
        </p>

        <h4>3. Estimated Delivery Time</h4>
        <ul>
            <li><strong>Metro cities</strong> (Delhi, Mumbai, Bengaluru, Chennai, Kolkata, Hyderabad):
                3–5 business days after dispatch.</li>
            <li><strong>Tier 2 & Tier 3 cities:</strong> 5–8 business days after dispatch.</li>
            <li><strong>Remote / rural areas:</strong> 8–12 business days after dispatch.</li>
        </ul>
        <p>
            Delivery estimates are indicative and may change due to weather, logistics delays, or
            other unforeseen circumstances. IndustrialNeeds.co is not liable for delays caused by
            third-party logistics partners.
        </p>

        <h4>4. Shipping Charges</h4>
        <p>
            Shipping charges are calculated based on the product weight, dimensions, and delivery location.
            The applicable shipping charge is displayed at checkout before you confirm your order. Some products
            may qualify for free shipping — this will be indicated on the product page.
        </p>

        <h4>5. Dispatch Confirmation</h4>
        <p>
            Once your order is dispatched, you will receive a dispatch confirmation notification (via email
            and/or SMS) along with the courier tracking number and the logistics partner's name. You can
            track your order through our website under <strong>My Orders</strong> or directly on the
            courier's tracking portal.
        </p>

        <h4>6. Packaging</h4>
        <p>
            All products are packed securely to prevent damage during transit. Industrial components and
            fragile items are given additional protective packaging. If you receive a visibly damaged shipment,
            please refuse delivery and contact us immediately.
        </p>

        <h4>7. Partial Shipments</h4>
        <p>
            For orders containing multiple items, products may be shipped separately from different warehouses
            or sellers. You will receive separate tracking details for each shipment. You will not be charged
            additional shipping for split shipments resulting from our logistics decisions.
        </p>

        <h4>8. Failed Delivery / RTO (Return to Origin)</h4>
        <p>
            If delivery fails due to an incorrect address, the recipient being unavailable, or refusal to
            accept the package, the logistics partner will attempt redelivery or hold the shipment at a nearby
            facility for a limited time. If the shipment is returned to us (RTO), we will contact you to
            arrange redelivery. Additional shipping charges may apply for re-dispatch.
        </p>

        <h4>9. Damaged or Missing Items</h4>
        <p>
            If your order arrives damaged or with missing items, please contact us within
            <strong>48 hours of delivery</strong> with photographs of the damaged/missing contents and the
            outer packaging. We will arrange a replacement or refund as appropriate, subject to our
            <a href="{{ route('refund-policy') }}">Return &amp; Refund Policy</a>.
        </p>

        <h4>10. Contact Us</h4>
        <p>
            For shipping-related queries, please reach us at:<br>
            <strong>Email:</strong> {{ \App\CPU\Helpers::get_business_settings('company_email') }}<br>
            <strong>Phone:</strong> {{ \App\CPU\Helpers::get_business_settings('company_phone') }}<br>
            <strong>Address:</strong> {{ \App\CPU\Helpers::get_business_settings('shop_address') }}
        </p>

    </div>
</div>
@endsection
