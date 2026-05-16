@extends('layouts.front-end.app')

@section('title', \App\CPU\translate('Terms & Conditions') . ' - ' . $web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Terms & Conditions - {{$web_config['name']->value}}"/>
    <meta property="og:url" content="{{env('APP_URL')}}/terms">
    <meta property="og:description" content="Terms & Conditions of {{$web_config['name']->value}} — IndustrialNeeds.co">
    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Terms & Conditions - {{$web_config['name']->value}}"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}/terms">
    <meta property="twitter:description" content="Terms & Conditions of {{$web_config['name']->value}}">
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
        .policy-cms-content h2,
        .policy-cms-content h3 {
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #111;
            margin-top: 36px;
            margin-bottom: 10px;
        }
        .policy-cms-content h2 + hr,
        .policy-cms-content h3 + hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 0 0 16px;
        }
        .policy-cms-content h4 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: #333;
            margin-top: 20px;
            margin-bottom: 8px;
        }
        .policy-cms-content p {
            font-size: 15px;
            color: #333;
            line-height: 1.8;
            margin-bottom: 12px;
        }
        .policy-cms-content ul,
        .policy-cms-content ol {
            padding-left: 20px;
            margin-bottom: 14px;
        }
        .policy-cms-content ul li,
        .policy-cms-content ol li {
            font-size: 15px;
            color: #333;
            line-height: 1.75;
            margin-bottom: 6px;
        }
        .policy-cms-content strong { color: #111; }
        .policy-cms-content hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 8px 0 16px;
        }
    </style>
@endpush

@section('content')
<div class="container">
    <div class="policy-page">
        <h1 class="policy-page-title">Terms &amp; Conditions</h1>
        <p class="policy-effective-date">Effective Date: May 2025</p>
        <div class="policy-cms-content">
            {!! $terms_condition['value'] !!}
        </div>
    </div>
</div>
@endsection
