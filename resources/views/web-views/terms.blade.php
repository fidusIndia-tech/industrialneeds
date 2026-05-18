@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Terms & Conditions') . ' - ' . $web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Terms & Conditions - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="https://industrialneeds.co/terms">
    <meta property="og:description" content="Terms & Conditions of {{$web_config['name']->value}} — IndustrialNeeds.co"/>
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Terms & Conditions - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="https://industrialneeds.co/terms">
    <meta property="twitter:description" content="Terms & Conditions of {{$web_config['name']->value}}"/>
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

        <h1 class="policy-page-title">Terms &amp; Conditions</h1>
        <p class="policy-effective-date">Effective Date: 18-05-2026</p>

        <p class="policy-intro">
            Welcome to <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt. Ltd.' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>). These Terms &amp; Conditions govern your access
            to and use of our website <a href="https://industrialneeds.co">https://industrialneeds.co</a>,
            and the purchase of any products or services made available through it. By browsing, registering,
            or placing an order on our website, you confirm that you have read, understood, and agreed to be
            bound by these Terms. If you do not agree with any part of these Terms, please discontinue use
            of our website.
        </p>

        {{-- Section 1 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">1. Introduction</h2>
            <hr class="policy-divider">
            <p>
                IndustrialNeeds.co is a B2B ecommerce platform owned and operated by
                <strong>Fidus India Automation Pvt. Ltd.</strong>, based in Gurgaon, Haryana, India.
                We specialise in industrial automation, electrical, and industrial products, catering primarily
                to Indian businesses, industries, buyers, and purchase teams.
            </p>
            <p>
                These Terms apply to all visitors, registered users, customers, and anyone else who accesses or
                uses our website or services. We may update these Terms from time to time, and the most current
                version will always be available on this page.
            </p>
        </div>

        {{-- Section 2 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">2. Eligibility</h2>
            <hr class="policy-divider">
            <p>To use our website and place an order, you must:</p>
            <ul>
                <li>Be at least 18 years of age and legally capable of entering into a binding contract under Indian law.</li>
                <li>Be a business entity, sole proprietor, professional buyer, or authorised purchase representative.</li>
                <li>Provide accurate, current, and complete information during registration and checkout.</li>
                <li>Not be barred from receiving services under the laws of India or any other applicable jurisdiction.</li>
            </ul>
            <p>
                We reserve the right to refuse service, terminate accounts, or cancel orders at our sole discretion
                if eligibility cannot be verified.
            </p>
        </div>

        {{-- Section 3 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">3. Account &amp; Registration</h2>
            <hr class="policy-divider">
            <p>
                Certain features of our website — including placing orders, viewing pricing, and accessing order
                history — require account registration. When you register, you agree to:
            </p>
            <ul>
                <li>Provide accurate and complete information, including your business name, GST details (where applicable), and contact information.</li>
                <li>Maintain the confidentiality of your login credentials and not share them with any third party.</li>
                <li>Be solely responsible for all activities that occur under your account.</li>
                <li>Notify us immediately of any unauthorised access or suspected security breach.</li>
            </ul>
            <p>
                We reserve the right to suspend or terminate accounts that contain false information, are inactive
                for extended periods, or are used in violation of these Terms.
            </p>
        </div>

        {{-- Section 4 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">4. Products &amp; Services</h2>
            <hr class="policy-divider">
            <p>
                IndustrialNeeds.co offers a curated catalogue of industrial automation, electrical components,
                instrumentation, control systems, and related industrial products sourced from leading brands
                and verified vendors.
            </p>
            <ul>
                <li>Product listings include technical specifications, brand details, and manufacturer part numbers wherever available.</li>
                <li>We strive to display product images and information as accurately as possible; however, slight variations may occur due to manufacturer revisions, lighting, or screen calibration.</li>
                <li>Certain products may be subject to minimum order quantities, lead times, or vendor-specific terms, which will be communicated at the time of order.</li>
                <li>Availability of products is not guaranteed and may change without prior notice.</li>
            </ul>
        </div>

        {{-- Section 5 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">5. Pricing &amp; Product Information</h2>
            <hr class="policy-divider">
            <p>
                All prices listed on our website are in Indian Rupees (INR) and, unless otherwise stated, are
                exclusive of applicable taxes, shipping charges, and other duties.
            </p>
            <ul>
                <li><strong>Prices, availability, product specifications, and images may change without prior notice.</strong></li>
                <li>Despite our best efforts, occasional typographical errors, mispricing, or inaccuracies may occur. We reserve the right to correct any such errors and to refuse or cancel orders placed with incorrect information, even after the order has been submitted or confirmed.</li>
                <li>Promotional offers, discounts, and coupon codes are subject to specific terms and may be modified or withdrawn at any time.</li>
                <li>Bulk pricing, project-based quotations, or negotiated rates may be offered separately on request and are not reflected on the standard product listing.</li>
            </ul>
        </div>

        {{-- Section 6 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">6. Orders &amp; Acceptance</h2>
            <hr class="policy-divider">
            <p>
                Placing an order on our website constitutes an offer to purchase the selected products from us.
                <strong>All orders are subject to acceptance, availability, and verification by IndustrialNeeds.co.</strong>
            </p>
            <ul>
                <li>An order confirmation email or message acknowledges receipt of your order but does not constitute acceptance.</li>
                <li>An order is considered accepted only when payment has been successfully verified and the product has been dispatched, or when a separate acceptance communication is sent.</li>
                <li>We reserve the right to refuse, limit, or cancel any order at our sole discretion — including for reasons such as stock unavailability, pricing errors, suspected fraud, incomplete shipping information, or restricted delivery locations.</li>
                <li>In the event of cancellation by us, any amount already paid will be refunded in full through the original mode of payment.</li>
            </ul>
        </div>

        {{-- Section 7 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">7. Payments</h2>
            <hr class="policy-divider">
            <p>
                We accept payments through multiple secure methods to make B2B purchasing convenient for our customers:
            </p>
            <ul>
                <li>Credit and debit cards (Visa, MasterCard, RuPay, American Express)</li>
                <li>UPI, Net Banking, and wallets</li>
                <li>NEFT / RTGS / IMPS bank transfers (against pro forma invoice)</li>
                <li>Authorised purchase orders for select corporate customers, subject to approval</li>
            </ul>
            <p>
                <strong>All online payments are processed through secure third-party payment gateway providers such as Paytm</strong>
                and other RBI-authorised gateways. We do not store your full card details, CVV, or banking credentials on our servers.
                All transactions are encrypted and protected using industry-standard security protocols.
            </p>
            <p>
                You agree to provide accurate and complete payment information and authorise us (or our payment partners)
                to charge the applicable amount for your order. If a payment fails or is declined, your order may be cancelled
                or placed on hold until payment is successfully received.
            </p>
        </div>

        {{-- Section 8 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">8. Shipping &amp; Delivery</h2>
            <hr class="policy-divider">
            <p>
                We ship products across India through trusted logistics and courier partners.
                Delivery timelines depend on the product, vendor location, destination pin code,
                and prevailing logistics conditions.
            </p>
            <ul>
                <li>Estimated delivery dates are indicative and not guaranteed.</li>
                <li>Shipping charges, if applicable, will be displayed at checkout or communicated separately for bulk orders.</li>
                <li>Risk of loss and title for purchased products pass to you upon delivery to the courier partner.</li>
                <li>You are responsible for inspecting the package at the time of delivery and reporting any visible damage, tampering, or shortage immediately.</li>
                <li>For full details, please refer to our <a href="{{ route('shipping-policy') }}">Shipping Policy</a>.</li>
            </ul>
            <p>
                <strong>IndustrialNeeds.co / Fidus India Automation Pvt. Ltd. shall not be held liable for delays,
                non-delivery, or damages caused by courier partners, vendors, technical issues, or force majeure events</strong>
                — including but not limited to natural calamities, strikes, transport disruptions, government restrictions,
                or any circumstances beyond our reasonable control.
            </p>
        </div>

        {{-- Section 9 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">9. Cancellation</h2>
            <hr class="policy-divider">
            <p>Orders may be cancelled subject to the following conditions:</p>
            <ul>
                <li>Cancellation requests must be submitted in writing (via email) before the product is dispatched.</li>
                <li>Once an order has been dispatched, it cannot be cancelled; the standard returns process will apply if eligible.</li>
                <li>Custom-built, made-to-order, vendor-imported, or specially procured products are non-cancellable once the order has been accepted.</li>
                <li>We reserve the right to cancel orders at our discretion in cases of stock unavailability, pricing errors, payment failure, suspected fraud, or non-deliverable addresses.</li>
                <li>Refunds for cancelled prepaid orders will be processed through the original payment method within the standard refund timelines.</li>
            </ul>
        </div>

        {{-- Section 10 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">10. Returns, Replacement &amp; Refunds</h2>
            <hr class="policy-divider">
            <p>
                We aim to ensure that every order is delivered correctly and in good condition. If you receive
                a defective, damaged, incorrect, or short-shipped product, you may request a return, replacement,
                or refund subject to our policy.
            </p>
            <ul>
                <li>Return or replacement requests must be raised within the timeframe specified in our Refund Policy from the date of delivery.</li>
                <li>The product must be unused, in its original packaging, with all accessories, manuals, warranty cards, and invoices intact.</li>
                <li>Certain product categories — including consumables, customised items, software/licensed products, and items marked non-returnable — are not eligible for return.</li>
                <li>Refunds, when approved, will be processed to the original payment method within 7–14 business days, depending on your bank or payment provider.</li>
                <li>Detailed terms are described in our <a href="{{ route('refund-policy') }}">Return, Exchange &amp; Refund Policy</a>.</li>
            </ul>
        </div>

        {{-- Section 11 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">11. Warranty</h2>
            <hr class="policy-divider">
            <p>
                Products sold on IndustrialNeeds.co are covered by the warranty offered by their respective
                manufacturers or brands. Warranty terms — including duration, coverage, and claim process —
                vary by product and brand.
            </p>
            <ul>
                <li>Warranty claims must be supported by the original invoice and any warranty documentation supplied with the product.</li>
                <li>Warranty does not cover damage caused by misuse, incorrect installation, unauthorised repair, exposure to unsuitable operating conditions, or normal wear and tear.</li>
                <li>We act as a facilitator for warranty claims and will assist you in coordinating with the relevant manufacturer or authorised service partner.</li>
                <li>No additional warranty, express or implied, is provided by IndustrialNeeds.co beyond what is offered by the original manufacturer.</li>
            </ul>
        </div>

        {{-- Section 12 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">12. User Responsibilities</h2>
            <hr class="policy-divider">
            <p>By using our website, you agree to:</p>
            <ul>
                <li>Use the website only for lawful business purposes and in accordance with these Terms.</li>
                <li>Provide accurate and truthful information at all times during registration, ordering, and communication.</li>
                <li>Not interfere with or disrupt the security, performance, or availability of the website.</li>
                <li>Verify product specifications, suitability for your application, and compliance requirements before placing an order.</li>
                <li>Be responsible for the safe handling, installation, and use of products purchased from us, in accordance with the manufacturer's guidelines and applicable safety standards.</li>
            </ul>
        </div>

        {{-- Section 13 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">13. Prohibited Activities</h2>
            <hr class="policy-divider">
            <p>You agree not to engage in any of the following while using our website:</p>
            <ul>
                <li>Submitting false, misleading, or fraudulent information.</li>
                <li>Attempting to gain unauthorised access to other users' accounts, our servers, or any related systems.</li>
                <li>Uploading or transmitting viruses, malware, or any other malicious code.</li>
                <li>Using automated tools (bots, scrapers, crawlers) to access or extract content from the website without our prior written consent.</li>
                <li>Reselling or redistributing content, product data, or pricing information from our website without authorisation.</li>
                <li>Using the website in any way that violates applicable laws, infringes third-party rights, or harms our reputation or business interests.</li>
            </ul>
            <p>
                Violation of these provisions may result in immediate account suspension, order cancellation,
                and legal action where appropriate.
            </p>
        </div>

        {{-- Section 14 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">14. Limitation of Liability</h2>
            <hr class="policy-divider">
            <p>
                To the maximum extent permitted by applicable law,
                <strong>Fidus India Automation Pvt. Ltd. (IndustrialNeeds.co)</strong> shall not be liable for:
            </p>
            <ul>
                <li>Any indirect, incidental, special, consequential, or punitive damages, including loss of profits, revenue, business, or data.</li>
                <li>Delays, non-delivery, or interruptions caused by courier partners, vendors, payment gateways, technical issues, server downtime, or force majeure events.</li>
                <li>Any damages arising out of incorrect product selection, improper installation, misuse, or use of the product outside of its specified application.</li>
                <li>Errors, inaccuracies, or omissions in product descriptions, images, pricing, or other content displayed on the website.</li>
            </ul>
            <p>
                In no event shall our total aggregate liability arising out of or related to any order exceed
                the amount actually paid by you for that specific order.
            </p>
        </div>

        {{-- Section 15 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">15. Intellectual Property</h2>
            <hr class="policy-divider">
            <p>
                All content available on IndustrialNeeds.co — including but not limited to text, graphics, logos,
                icons, product images, technical drawings, software, layouts, and design elements — is the property
                of Fidus India Automation Pvt. Ltd. or its licensors and is protected under applicable copyright,
                trademark, and other intellectual property laws.
            </p>
            <p>
                Brand names, manufacturer logos, and product trademarks displayed on the website remain the property
                of their respective owners and are used solely to identify the products offered for sale.
            </p>
            <p>
                You may not reproduce, distribute, modify, transmit, or otherwise exploit any content from this
                website for commercial purposes without our prior written permission.
            </p>
        </div>

        {{-- Section 16 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">16. Privacy</h2>
            <hr class="policy-divider">
            <p>
                Your privacy is important to us. The collection, use, and protection of your personal information
                is governed by our <a href="{{ route('privacy-policy') }}">Privacy Policy</a>, which forms an
                integral part of these Terms &amp; Conditions.
            </p>
            <p>
                By using our website, you acknowledge that you have read and agree to the practices described
                in our Privacy Policy.
            </p>
        </div>

        {{-- Section 17 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">17. Changes to Terms &amp; Conditions</h2>
            <hr class="policy-divider">
            <p>
                We reserve the right to revise, update, or modify these Terms &amp; Conditions at any time
                without prior notice, to reflect changes in our services, business practices, technology,
                or legal requirements.
            </p>
            <p>
                Any changes will be posted on this page with an updated effective date. Your continued use of
                our website after such changes are posted will be considered as your acceptance of the revised
                Terms. We encourage you to review this page periodically.
            </p>
        </div>

        {{-- Section 18 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">18. Governing Law &amp; Jurisdiction</h2>
            <hr class="policy-divider">
            <p>
                These Terms &amp; Conditions and any disputes or claims arising out of or in connection with them,
                their subject matter, or formation, shall be governed by and construed in accordance with the laws
                of India.
            </p>
            <p>
                <strong>All disputes are subject to the exclusive jurisdiction of the competent courts in Gurgaon, Haryana, India.</strong>
            </p>
        </div>

        {{-- Section 19 --}}
        <div class="policy-section policy-contact-section">
            <h2 class="policy-section-heading">19. Contact Information</h2>
            <hr class="policy-divider">
            <p>
                For any questions, clarifications, or concerns regarding these Terms &amp; Conditions, please get in touch with us:
            </p>
            <p><strong>Business Name:</strong> Fidus India Automation Pvt. Ltd.</p>
            <p><strong>Website:</strong> <a href="https://industrialneeds.co">https://industrialneeds.co</a></p>
            <p><strong>Email:</strong> {{ \App\CPU\Helpers::get_business_settings('company_email') ?? 'support@industrialneeds.co' }}</p>
            <p><strong>Phone:</strong> {{ \App\CPU\Helpers::get_business_settings('company_phone') ?? '+91-XXXXXXXXXX' }}</p>
            <p><strong>Address:</strong> {{ \App\CPU\Helpers::get_business_settings('shop_address') ?? 'Gurgaon, Haryana, India' }}</p>
        </div>

    </div>
</div>
@endsection
