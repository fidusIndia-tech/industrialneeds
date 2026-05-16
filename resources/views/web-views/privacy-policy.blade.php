@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Privacy Policy') . ' - ' . $web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Privacy Policy - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="https://industrialneeds.co/privacy-policy">
    <meta property="og:description" content="Privacy Policy of {{$web_config['name']->value}} — IndustrialNeeds.co">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Privacy Policy - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="https://industrialneeds.co/privacy-policy">
    <meta property="twitter:description" content="Privacy Policy of {{$web_config['name']->value}}">
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

        <h1 class="policy-page-title">Privacy Policy</h1>
        <p class="policy-effective-date">Effective Date: 30-03-2026</p>

        <p class="policy-intro">
            At <strong>{{ \App\CPU\Helpers::get_business_settings('company_name') ?? 'Fidus India Automation Pvt Ltd' }}</strong>
            (operating as <strong>IndustrialNeeds.co</strong>), we value your trust and are committed to
            protecting your personal information. This Privacy Policy explains how we collect, use, share,
            and safeguard your data when you visit or make a purchase on
            <a href="https://industrialneeds.co">https://industrialneeds.co</a>.
            By using our website, you agree to the terms described in this policy.
        </p>

        {{-- Section 1 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">1. Information We Collect</h2>
            <hr class="policy-divider">

            <h3 class="policy-sub-heading">A) Personal Information</h3>
            <p>When you register, place an order, or contact us, we may collect:</p>
            <ul>
                <li>Full name and contact details (email address, phone number)</li>
                <li>Billing and shipping addresses</li>
                <li>Payment information (processed securely via payment gateways; we do not store card details)</li>
                <li>Order history and purchase preferences</li>
                <li>Account login credentials (username and encrypted password)</li>
            </ul>

            <h3 class="policy-sub-heading">B) Non-Personal Information</h3>
            <p>We also automatically collect certain non-identifying information, including:</p>
            <ul>
                <li>IP address and approximate geographic location</li>
                <li>Browser type, version, and operating system</li>
                <li>Device information (desktop, mobile, tablet)</li>
                <li>Pages visited, time spent, and navigation patterns on our website</li>
                <li>Referral source (how you arrived at our website)</li>
            </ul>
        </div>

        {{-- Section 2 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">2. How We Use Your Information</h2>
            <hr class="policy-divider">
            <p>We use the information we collect for the following purposes:</p>
            <ul>
                <li>To process, fulfill, and track your orders</li>
                <li>To send order confirmations, invoices, and shipping updates</li>
                <li>To provide customer support and respond to inquiries</li>
                <li>To send promotional communications, offers, and newsletters (you may opt out at any time)</li>
                <li>To improve our website, product listings, and user experience</li>
                <li>To detect and prevent fraud or unauthorized access</li>
                <li>To comply with applicable legal obligations</li>
            </ul>
        </div>

        {{-- Section 3 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">3. Cookies and Tracking Technologies</h2>
            <hr class="policy-divider">
            <p>
                We use cookies and similar tracking technologies to enhance your browsing experience,
                remember your preferences, analyze website traffic, and improve overall performance.
                Cookies are small text files stored on your device.
            </p>
            <p>
                You may disable cookies through your browser settings; however, doing so may affect the
                functionality of certain features on our website, such as the shopping cart and saved preferences.
            </p>
        </div>

        {{-- Section 4 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">4. Data Security</h2>
            <hr class="policy-divider">
            <p>
                We implement industry-standard security measures — including SSL encryption, secure servers,
                and access controls — to protect your personal information against unauthorized access,
                alteration, disclosure, or destruction.
            </p>
            <p>
                While we take every reasonable precaution, no method of transmission over the internet or
                electronic storage is 100% secure. We cannot guarantee absolute security of your data.
            </p>
        </div>

        {{-- Section 5 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">5. Sharing of Information</h2>
            <hr class="policy-divider">
            <p>We <strong>do not sell or rent your personal data</strong> to third parties. We may share your information only in the following circumstances:</p>
            <ul>
                <li><strong>Logistics partners:</strong> To facilitate delivery of your orders</li>
                <li><strong>Payment gateways:</strong> To process transactions securely (Paytm, Razorpay, UPI, etc.)</li>
                <li><strong>Service providers:</strong> Third parties that assist with platform operations (hosting, analytics, email services) under strict confidentiality agreements</li>
                <li><strong>Legal compliance:</strong> When required by law, court order, or government authority</li>
                <li><strong>Business transfers:</strong> In the event of a merger, acquisition, or sale of assets, your data may be transferred with appropriate notice</li>
            </ul>
        </div>

        {{-- Section 6 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">6. Your Rights and Choices</h2>
            <hr class="policy-divider">
            <p>You have the following rights regarding your personal data:</p>
            <ul>
                <li><strong>Access:</strong> Request a copy of the personal information we hold about you</li>
                <li><strong>Correction:</strong> Update or correct inaccurate information via your account settings</li>
                <li><strong>Deletion:</strong> Request deletion of your personal data, subject to legal retention requirements</li>
                <li><strong>Opt-out:</strong> Unsubscribe from marketing emails at any time using the link in the email footer</li>
                <li><strong>Withdraw consent:</strong> Withdraw your consent to data processing where applicable</li>
            </ul>
            <p>To exercise any of these rights, please contact us at <strong>{{ \App\CPU\Helpers::get_business_settings('company_email') }}</strong>.</p>
        </div>

        {{-- Section 7 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">7. Third-Party Links</h2>
            <hr class="policy-divider">
            <p>
                Our website may contain links to third-party websites, payment portals, or social media platforms.
                We are not responsible for the privacy practices or content of those external sites.
                We encourage you to review their privacy policies before submitting any personal information.
            </p>
        </div>

        {{-- Section 8 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">8. Children's Privacy</h2>
            <hr class="policy-divider">
            <p>
                Our services are intended for adults and business users. We do not knowingly collect or
                solicit personal information from individuals under the age of 18. If we become aware that
                a minor has provided us with personal data, we will delete it promptly.
            </p>
        </div>

        {{-- Section 9 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">9. Changes to This Policy</h2>
            <hr class="policy-divider">
            <p>
                We may update this Privacy Policy from time to time to reflect changes in our practices,
                technology, or legal requirements. Any changes will be posted on this page with an updated
                effective date. We encourage you to review this policy periodically. Continued use of our
                website after changes are posted constitutes your acceptance of the updated policy.
            </p>
        </div>

        {{-- Section 10 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">10. Contact Us</h2>
            <hr class="policy-divider">
            <p>If you have any questions, concerns, or requests regarding this Privacy Policy or how we handle your data, please reach out to us:</p>
            <p>📧 <strong>Email:</strong> {{ \App\CPU\Helpers::get_business_settings('company_email') }}</p>
            <p>📞 <strong>Phone:</strong> {{ \App\CPU\Helpers::get_business_settings('company_phone') }}</p>
            <p>📍 <strong>Address:</strong> {{ \App\CPU\Helpers::get_business_settings('shop_address') }}</p>
            <p>🌐 <strong>Website:</strong> <a href="https://industrialneeds.co">https://industrialneeds.co</a></p>
        </div>

        {{-- Section 11 --}}
        <div class="policy-section">
            <h2 class="policy-section-heading">11. Consent</h2>
            <hr class="policy-divider">
            <p>
                By using our website <a href="https://industrialneeds.co">https://industrialneeds.co</a>,
                registering an account, or placing an order, you acknowledge that you have read, understood,
                and agree to be bound by this Privacy Policy. If you do not agree with any part of this policy,
                please discontinue use of our website.
            </p>
        </div>

    </div>
</div>
@endsection
