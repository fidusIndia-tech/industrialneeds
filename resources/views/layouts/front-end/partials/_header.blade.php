@php
    // Live products = same status logic as the public product listing (Product::active()).
    $homepage_live_products_count = \Illuminate\Support\Facades\Cache::remember('homepage_live_products_count', now()->addMinutes(10), function () {
        return \App\Model\Product::active()->count();
    });
    $homepage_total_brands_count = \Illuminate\Support\Facades\Cache::remember('homepage_total_brands_count', now()->addMinutes(10), function () {
        return \App\Model\Brand::count();
    });
    $homepage_total_categories_count = \Illuminate\Support\Facades\Cache::remember('homepage_total_categories_count', now()->addMinutes(10), function () {
        return \App\Model\Category::count();
    });
@endphp
<style>
    .card-body.search-result-box {
        overflow: scroll;
        height: 400px;
        overflow-x: hidden;
    }

    .active .seller {
        font-weight: 700;
    }

    .for-count-value {
        position: absolute;

        right: 0.6875rem;;
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        color: {{$web_config['primary_color']}};

        font-size: .75rem;
        font-weight: 500;
        text-align: center;
        line-height: 1.25rem;
    }

    .count-value {
        width: 1.25rem;
        height: 1.25rem;
        border-radius: 50%;
        color: {{$web_config['primary_color']}};

        font-size: .75rem;
        font-weight: 500;
        text-align: center;
        line-height: 1.25rem;
    }

    @media (min-width: 992px) {
        .navbar-sticky.navbar-stuck .navbar-stuck-menu.show {
            display: block;
            height: 55px !important;
        }
    }

    @media (min-width: 768px) {
        .navbar-stuck-menu {
            background-color: {{$web_config['primary_color']}};
            line-height: 15px;
            padding-bottom: 6px;
        }

    }

    @media (max-width: 767px) {
        .search_button {
            background-color: transparent !important;
        }

        .search_button .input-group-text i {
            color: {{$web_config['primary_color']}}                              !important;
        }

        .navbar-expand-md .dropdown-menu > .dropdown > .dropdown-toggle {
            position: relative;
            padding- {{Session::get('direction') === 'rtl' ? 'left' : 'right'}}: 1.95rem;
        }

        .mega-nav1 {
            background: white;
            color: {{$web_config['primary_color']}}                              !important;
            border-radius: 3px;
        }

        .mega-nav1 .nav-link {
            color: {{$web_config['primary_color']}}                              !important;
        }
    }

    @media (max-width: 768px) {
        .tab-logo {
            width: 10rem;
        }
    }

    @media (max-width: 360px) {
        .mobile-head {
            padding: 3px;
        }
    }

    @media (max-width: 471px) {
        .navbar-brand img {

        }

        .mega-nav1 {
            background: white;
            color: {{$web_config['primary_color']}}                              !important;
            border-radius: 3px;
        }

        .mega-nav1 .nav-link {
            color: {{$web_config['primary_color']}} !important;
        }
    }
    #anouncement {
        width: 100%;
        padding: 2px 0;
        text-align: center;
        color:white;
    }

    /* ---- Live products/brands badge — glassmorphism (inside the blue navbar row) ---- */
    .navbar-upload-count {
        display: flex;
        align-items: center;
    }
    .nuc-pill {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: inline-flex;
        align-items: center;
        line-height: 1;
        padding: 6px 14px;
        border-radius: 999px;
        white-space: nowrap;
        color: #f3f7fb;
        font-size: 12.5px;
        font-weight: 500;
        letter-spacing: .2px;
        /* slight transparent navy glass */
        background: linear-gradient(135deg, rgba(11, 79, 138, .38) 0%, rgba(8, 42, 69, .55) 100%);
        -webkit-backdrop-filter: blur(8px) saturate(140%);
        backdrop-filter: blur(8px) saturate(140%);
        /* soft gold border + subtle navy/gold glow */
        border: 1px solid rgba(255, 196, 0, .45);
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, .04) inset,
            0 2px 10px rgba(8, 42, 69, .45),
            0 0 14px rgba(255, 196, 0, .18);
        /* gentle blooming pulse: soft scale + expanding glow, every 4s */
        transform-origin: center;
        will-change: transform;
        animation: nuc-bloom 4s ease-in-out infinite;
    }
    .nuc-pill .nuc-text {
        position: relative;
        z-index: 2;
    }
    .nuc-pill .nuc-num {
        color: var(--ind-accent, #FFC400);
        font-weight: 700;
        text-shadow: 0 0 6px rgba(255, 196, 0, .35);
    }
    .nuc-pill .nuc-dot {
        color: var(--ind-accent, #FFC400);
        padding: 0 3px;
    }
    /* Shimmer sweep — diagonal light passing across the glass */
    .nuc-pill::before {
        content: "";
        position: absolute;
        top: -60%;
        left: -75%;
        width: 50%;
        height: 220%;
        z-index: 1;
        transform: skewX(-20deg);
        background: linear-gradient(90deg,
            rgba(255, 255, 255, 0) 0%,
            rgba(255, 255, 255, .18) 45%,
            rgba(255, 230, 150, .35) 50%,
            rgba(255, 255, 255, .18) 55%,
            rgba(255, 255, 255, 0) 100%);
        animation: nuc-shimmer 6s ease-in-out infinite;
    }
    /* Sparkle / bloom dots — tiny gold stars that twinkle */
    .nuc-pill::after {
        content: "";
        position: absolute;
        inset: 0;
        z-index: 1;
        pointer-events: none;
        background-image:
            radial-gradient(circle, rgba(255, 240, 190, .95) 0%, rgba(255, 196, 0, 0) 60%),
            radial-gradient(circle, rgba(255, 255, 255, .9) 0%, rgba(255, 255, 255, 0) 60%),
            radial-gradient(circle, rgba(255, 214, 90, .85) 0%, rgba(255, 196, 0, 0) 60%);
        background-repeat: no-repeat;
        background-size: 3px 3px, 2px 2px, 2.5px 2.5px;
        background-position: 18% 35%, 62% 68%, 84% 28%;
        animation: nuc-sparkle 4.5s ease-in-out infinite;
    }
    @keyframes nuc-bloom {
        0%, 100% {
            transform: scale(1);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, .04) inset,
                0 2px 10px rgba(8, 42, 69, .45),
                0 0 14px rgba(255, 196, 0, .18);
        }
        50% {
            transform: scale(1.035);
            box-shadow:
                0 0 0 1px rgba(255, 255, 255, .06) inset,
                0 4px 16px rgba(8, 42, 69, .5),
                0 0 26px rgba(255, 196, 0, .42);
        }
    }
    @keyframes nuc-shimmer {
        0%   { left: -75%; }
        55%  { left: 130%; }
        100% { left: 130%; }
    }
    @keyframes nuc-sparkle {
        0%, 100% { opacity: .25; }
        40%      { opacity: 1; }
        70%      { opacity: .45; }
    }
    @media (max-width: 767px) {
        .navbar-upload-count {
            margin-left: 0 !important;
            margin-top: 8px;
            margin-bottom: 4px;
            width: 100%;
            justify-content: center;
        }
        .nuc-pill {
            font-size: 12px;
            padding: 5px 12px;
            /* more opaque navy so light text stays readable over the light mobile menu */
            background: linear-gradient(135deg, rgba(11, 79, 138, .92) 0%, rgba(8, 42, 69, .96) 100%);
        }
    }
    /* Accessibility: kill all motion for reduced-motion users (badge stays fully readable) */
    @media (prefers-reduced-motion: reduce) {
        .nuc-pill { animation: none; transform: none; }
        .nuc-pill::before { animation: none; left: -75%; }
        .nuc-pill::after  { animation: none; opacity: .6; }
    }

    /* ---- Brands dropdown: search box + live counts (scoped to #brandsDropdownMenu) ---- */
    .brands-dropdown-menu {
        min-width: 320px;
        max-width: 380px;
        padding-top: 0;
    }
    .brands-dropdown-header {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #ffffff;
        padding: 12px 14px 10px;
        border-bottom: 1px solid #e3e9ef;
        box-shadow: 0 2px 4px rgba(8, 42, 69, 0.06);
    }
    .brands-dropdown-title {
        font-size: 14px;
        font-weight: 700;
        color: {{$web_config['primary_color']}};
        margin-bottom: 8px;
    }
    .brands-search-wrap {
        position: relative;
    }
    .brands-search-icon {
        position: absolute;
        top: 50%;
        {{Session::get('direction') === 'rtl' ? 'right' : 'left'}}: 11px;
        transform: translateY(-50%);
        font-size: 14px;
        color: #94a3b8;
        pointer-events: none;
    }
    .brands-search-input {
        width: 100%;
        height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #f8fafc;
        color: #082A45;
        font-size: 13px;
        line-height: 38px;
        padding: {{Session::get('direction') === 'rtl' ? '0 34px 0 12px' : '0 12px 0 34px'}};
        outline: none;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .brands-search-input::placeholder { color: #94a3b8; }
    .brands-search-input:focus {
        background: #ffffff;
        border-color: {{$web_config['secondary_color']}};
        box-shadow: 0 0 0 3px rgba(8, 42, 69, 0.12);
    }
    .brands-status {
        margin-top: 8px;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
    }
    .brands-empty-state {
        padding: 16px 14px;
        text-align: center;
        color: #64748b;
        font-size: 13px;
        font-style: italic;
    }
</style>
@php($announcement=\App\CPU\Helpers::get_business_settings('announcement'))
@if (isset($announcement) && $announcement['status']==1)
    <div class="d-flex justify-content-between align-items-center" id="anouncement" style="background-color: #E5E7EB;color:#082A45;border-bottom:1px solid #CBD5E1">
        <span></span>
        <span style="text-align:center; font-size: 14px;font-weight:500;">{{ $announcement['announcement'] }} </span>
        <span class="ml-3 mr-3" style="font-size: 13px;cursor: pointer;color: #64748B"  onclick="myFunction()">&times;</span>
    </div>
@endif

<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WXK9H76W"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->

<header class="box-shadow-sm rtl">
    <!-- Topbar-->
    <div class="topbar">
        <div class="container">

            <div class="ind-topbar-left">
                <a class="ind-topbar-item topbar-link" href="tel:{{$web_config['phone']->value}}">
                    <i class="fa fa-phone"></i>{{$web_config['phone']->value}}
                </a>
                <a class="ind-topbar-item topbar-link d-none d-md-inline-block" href="mailto:{{\App\CPU\Helpers::get_business_settings('company_email')}}">
                    <i class="fa fa-envelope"></i>{{\App\CPU\Helpers::get_business_settings('company_email')}}
                </a>
                <a class="ind-topbar-item topbar-link d-none d-lg-inline-block" href="{{route('contacts')}}">
                    <i class="fa fa-file-text-o"></i>{{\App\CPU\translate('Request a Quotation')}}
                </a>
            </div>

            <div>
                @php($currency_model = \App\CPU\Helpers::get_business_settings('currency_model'))
                @if($currency_model=='multi_currency')
                    {{-- Self-contained currency selector (vanilla-JS toggle) so it never
                         hides behind the sticky header / cart. Uses the existing backend
                         currency_change() (route: currency.change) — no backend changes. --}}
                    <div class="currency-dropdown topbar-text {{Session::get('direction') === 'rtl' ? 'ml-4' : 'mr-4'}}"
                         id="currencyDropdown">
                        <button type="button" class="currency-dropdown-toggle" onclick="toggleCurrencyDropdown(event)">
                            <span>{{session('currency_code')}} {{session('currency_symbol')}}</span>
                            <i class="czi-arrow-down"></i>
                        </button>
                        <div class="currency-dropdown-menu" role="menu">
                            @foreach (\App\Model\Currency::where('status', 1)->get() as $key => $currency)
                                <button type="button" role="menuitem"
                                        class="{{ session('currency_code')==$currency['code'] ? 'is-selected' : '' }}"
                                        onclick="currency_change('{{$currency['code']}}')">
                                    <span class="cur-code">{{ $currency->code }} {{ $currency->symbol }}</span>
                                    <span class="cur-name">{{ $currency->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @php( $local = \App\CPU\Helpers::default_lang())
                {{-- Self-contained language selector (vanilla-JS toggle) so its menu never
                     hides behind the sticky header / cart — same pattern as the currency
                     selector above. Uses the existing lang route — no backend changes. --}}
                <div class="lang-dropdown" id="langDropdown">
                    <button type="button" class="lang-dropdown-toggle" onclick="toggleLangDropdown(event)">
                        @foreach(json_decode($language['value'],true) as $data)
                            @if($data['code']==$local)
                                <img class="lang-flag" width="20"
                                     src="{{asset('public/assets/front-end')}}/img/flags/{{$data['code']}}.png"
                                     alt="{{$data['name']}}">
                                <span class="lang-name">{{$data['name']}}</span>
                            @endif
                        @endforeach
                        <i class="czi-arrow-down"></i>
                    </button>
                    <div class="lang-dropdown-menu" role="menu">
                        @foreach(json_decode($language['value'],true) as $key =>$data)
                            @if($data['status']==1)
                                <a role="menuitem"
                                   class="lang-dropdown-item {{ $data['code']==$local ? 'is-selected' : '' }}"
                                   href="{{route('lang',[$data['code']])}}">
                                    <img class="lang-flag" width="20"
                                         src="{{asset('public/assets/front-end')}}/img/flags/{{$data['code']}}.png"
                                         alt="{{$data['name']}}"/>
                                    <span class="lang-name">{{$data['name']}}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="navbar-sticky bg-light mobile-head">
        <div class="navbar navbar-expand-md navbar-light">
            <div class="container ">
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <a class="navbar-brand d-none d-sm-block {{Session::get('direction') === 'rtl' ? 'ml-3' : 'mr-3'}} flex-shrink-0"
                   href="{{route('home')}}"
                   style="min-width: 7rem;">
                    <img style="height: 40px!important; width:auto;"
                         src="{{asset("storage/app/public/company")."/".$web_config['web_logo']->value}}"
                         onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                         alt="{{$web_config['name']->value}}"/>
                </a>
                <a class="navbar-brand d-sm-none {{Session::get('direction') === 'rtl' ? 'ml-2' : 'mr-2'}}"
                   href="{{route('home')}}">
                    <img style="height: 38px!important;width:auto;" class="mobile-logo-img"
                         src="{{asset("storage/app/public/company")."/".$web_config['mob_logo']->value}}"
                         onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                         alt="{{$web_config['name']->value}}"/>
                </a>
                <!-- Search-->
                <div class="input-group-overlay d-none d-md-block mx-4"
                     style="text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}}">
                    <form action="{{route('products')}}" type="submit" class="search_form">
                        <input class="form-control appended-form-control search-bar-input" type="text"
                               autocomplete="off"
                               placeholder="{{\App\CPU\translate('Search products, brands, categories…')}}"
                               name="name">
                        <button class="input-group-append-overlay search_button" type="submit"
                                style="border-radius: {{Session::get('direction') === 'rtl' ? '7px 0px 0px 7px; right: unset; left: 0' : '0px 7px 7px 0px; left: unset; right: 0'}};top:0">
                                <span class="input-group-text" style="font-size: 20px;">
                                    <i class="czi-search text-white"></i>
                                </span>
                        </button>
                        <input name="data_from" value="search" hidden>
                        <input name="page" value="1" hidden>
                        <diV class="card search-card"
                             style="position: absolute;background: white;z-index: 999;width: 100%;display: none">
                            <div class="card-body search-result-box"
                                 style="overflow:scroll; height:400px;overflow-x: hidden"></div>
                        </diV>
                    </form>
                </div>
                <!-- Toolbar-->
                <div class="navbar-toolbar d-flex flex-shrink-0 align-items-center" style="margin-right: 10px;">
                    <a class="navbar-tool navbar-stuck-toggler" href="#">
                        <span class="navbar-tool-tooltip">{{\App\CPU\translate('Expand menu')}}</span>
                        <div class="navbar-tool-icon-box">
                            <i class="navbar-tool-icon czi-menu"></i>
                        </div>
                    </a>
                    <div class="navbar-tool dropdown {{Session::get('direction') === 'rtl' ? 'mr-3' : 'ml-3'}}">
                        <a class="navbar-tool-icon-box bg-secondary dropdown-toggle" href="{{route('wishlists')}}">
                            <span class="navbar-tool-label">
                                <span
                                    class="countWishlist">{{session()->has('wish_list')?count(session('wish_list')):0}}</span>
                           </span>
                            <i class="navbar-tool-icon czi-heart"></i>
                        </a>
                    </div>
                    @if(auth('customer')->check())
                        <div class="dropdown">
                            <a class="navbar-tool ml-2 mr-2 " type="button" data-toggle="dropdown" aria-haspopup="true"
                               aria-expanded="false">
                                <div class="navbar-tool-icon-box bg-secondary">
                                    <div class="navbar-tool-icon-box bg-secondary">
                                        <img style="width: 40px;height: 40px"
                                             src="{{asset('storage/app/public/profile/'.auth('customer')->user()->image)}}"
                                             onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                             class="img-profile rounded-circle">
                                    </div>
                                </div>
                                <div class="navbar-tool-text">
                                    <small>{{\App\CPU\translate('hello')}}, {{auth('customer')->user()->f_name}}</small>
                                    {{\App\CPU\translate('dashboard')}}
                                </div>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item"
                                   href="{{route('account-oder')}}"> {{ \App\CPU\translate('my_order')}} </a>
                                <a class="dropdown-item"
                                   href="{{route('user-account')}}"> {{ \App\CPU\translate('my_profile')}}</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item"
                                   href="{{route('customer.auth.logout')}}">{{ \App\CPU\translate('logout')}}</a>
                            </div>
                        </div>
                    @else
                        <div class="dropdown">
                            <a class="navbar-tool {{Session::get('direction') === 'rtl' ? 'mr-3' : 'ml-3'}}"
                               type="button" data-toggle="dropdown" aria-haspopup="true"
                               aria-expanded="false">
                                <div class="navbar-tool-icon-box bg-secondary">
                                    <div class="navbar-tool-icon-box bg-secondary">
                                        <i class="navbar-tool-icon czi-user"></i>
                                    </div>
                                </div>
                            </a>
                            <div class="dropdown-menu dropdown-menu-{{Session::get('direction') === 'rtl' ? 'right' : 'left'}}" aria-labelledby="dropdownMenuButton"
                                 style="text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                <a class="dropdown-item" href="{{route('customer.auth.login')}}">
                                    <i class="fa fa-sign-in {{Session::get('direction') === 'rtl' ? 'ml-2' : 'mr-2'}}"></i> {{\App\CPU\translate('sign_in')}}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{route('customer.auth.sign-up')}}">
                                    <i class="fa fa-user-circle {{Session::get('direction') === 'rtl' ? 'ml-2' : 'mr-2'}}"></i>{{\App\CPU\translate('sign_up')}}
                                </a>
                            </div>
                        </div>
                    @endif
                    <a href="{{route('contacts')}}"
                       class="btn btn-accent btn-sm d-none d-lg-inline-flex align-items-center {{Session::get('direction') === 'rtl' ? 'mr-3' : 'ml-3'}}"
                       style="font-weight:600;border-radius:6px;padding:.5rem .9rem;white-space:nowrap;">
                        <i class="czi-message {{Session::get('direction') === 'rtl' ? 'ml-1' : 'mr-1'}}"></i>{{\App\CPU\translate('Get a Quote')}}
                    </a>
                    <div id="cart_items">
                        @include('layouts.front-end.partials.cart')
                    </div>
                </div>
            </div>
        </div>
        <div class="navbar navbar-expand-md navbar-stuck-menu"  >
            <div class="container" style="padding-left: 10px;padding-right: 10px;">
                <div class="collapse navbar-collapse" id="navbarCollapse"
                    style="text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}}; ">

                    <!-- Search-->
                    <div class="input-group-overlay d-md-none my-3">
                        <form action="{{route('products')}}" type="submit" class="search_form">
                            <input class="form-control appended-form-control search-bar-input-mobile" type="text"
                                   autocomplete="off"
                                   placeholder="{{\App\CPU\translate('Search products, brands, categories…')}}" name="name">
                            <input name="data_from" value="search" hidden>
                            <input name="page" value="1" hidden>
                            <button class="input-group-append-overlay search_button" type="submit"
                                    style="border-radius: {{Session::get('direction') === 'rtl' ? '7px 0px 0px 7px; right: unset; left: 0' : '0px 7px 7px 0px; left: unset; right: 0'}};">
                            <span class="input-group-text" style="font-size: 20px;">
                                <i class="czi-search text-white"></i>
                            </span>
                            </button>
                            <diV class="card search-card"
                                 style="position: absolute;background: white;z-index: 999;width: 100%;display: none">
                                <div class="card-body search-result-box" id=""
                                     style="overflow:scroll; height:400px;overflow-x: hidden"></div>
                            </diV>
                        </form>
                    </div>

                    @php($categories=\App\CPU\CategoryManager::nav_tree(11))
                    <ul class="navbar-nav mega-nav pr-2 pl-2 {{Session::get('direction') === 'rtl' ? 'ml-2' : 'mr-2'}} d-none d-xl-block ">
                        <!--web-->
                        {{-- The theme used to pin this panel permanently open on the homepage
                             (no .dropdown class, pointer-events:none on the toggle, display:block
                             on the menu) so it acted as a category sidebar beside the banner. The
                             hero now renders its own .ind-cat-sidebar for that, and the two sat on
                             top of each other — ~95,000px² of overlap, the absolute menu covering
                             the sidebar it duplicated. It is a normal dropdown on every page now. --}}
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{Session::get('direction') === 'rtl' ? 'pr-0' : 'pl-0'}}"
                               href="#" data-toggle="dropdown">
                                <i class="czi-menu align-middle mt-n1 {{Session::get('direction') === 'rtl' ? 'ml-2' : 'mr-2'}}"></i>
                                <span
                                    style="margin-{{Session::get('direction') === 'rtl' ? 'right' : 'left'}}: 40px !important;margin-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}: 50px">
                                    {{ \App\CPU\translate('categories')}}
                                </span>
                            </a>
                            @if(request()->is('/'))
                                <ul class="dropdown-menu" style="right: 0%;
                                    margin-top: 8px; margin-right: 11px;border: 1px solid #ccccccb3;
                                    border-bottom-left-radius: 5px;
                                    border-bottom-right-radius: 5px; box-shadow: none;min-width: 303px !important;{{Session::get('direction') === 'rtl' ? 'margin-right: 1px!important;text-align: right;' : 'margin-left: 1px!important;text-align: left;'}}padding-bottom: 0px!important;">
                                    @foreach($categories as $key=>$category)
                                        @if($key<8)
                                            <li class="dropdown">
                                                <a class="dropdown-item flex-between"
                                                   <?php if ($category->childes->count() > 0) echo "data-toggle='dropdown'"?> href="javascript:"
                                                   onclick="location.href='{{route('products',['id'=> $category['id'],'data_from'=>'category','page'=>1])}}'">
                                                    <div>
                                                        <img
                                                            src="{{asset("storage/app/public/category/$category->icon")}}"
                                                            onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                            style="width: 18px; height: 18px; ">
                                                        <span
                                                            class="{{Session::get('direction') === 'rtl' ? 'pr-3' : 'pl-3'}}">{{$category['name']}}</span>
                                                    </div>
                                                    @if ($category->childes->count() > 0)
                                                        <div>
                                                            <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}" style="font-size: 8px !important;background:none !important;color:#4B5864;"></i>
                                                        </div>
                                                    @endif
                                                </a>
                                                @if($category->childes->count()>0)
                                                    <ul class="dropdown-menu"
                                                        style="right: 100%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                                        @foreach($category['childes'] as $subCategory)
                                                            <li class="dropdown">
                                                                <a class="dropdown-item flex-between"
                                                                   <?php if ($subCategory->childes->count() > 0) echo "data-toggle='dropdown'"?> href="javascript:"
                                                                   onclick="location.href='{{route('products',['id'=> $subCategory['id'],'data_from'=>'category','page'=>1])}}'">
                                                                    <div>
                                                                        <span
                                                                            class="{{Session::get('direction') === 'rtl' ? 'pr-3' : 'pl-3'}}">{{$subCategory['name']}}</span>
                                                                    </div>
                                                                    @if ($subCategory->childes->count() > 0)
                                                                        <div>
                                                                            <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}" style="font-size: 8px !important;background:none !important;color:#4B5864;"></i>
                                                                        </div>
                                                                    @endif
                                                                </a>
                                                                @if($subCategory->childes->count()>0)
                                                                    <ul class="dropdown-menu"
                                                                        style="right: 100%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                                                        @foreach($subCategory['childes'] as $subSubCategory)
                                                                            <li>
                                                                                <a class="dropdown-item"
                                                                                   href="{{route('products',['id'=> $subSubCategory['id'],'data_from'=>'category','page'=>1])}}">{{$subSubCategory['name']}}</a>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @endif
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </li>
                                        @endif
                                    @endforeach
                                    <a class="dropdown-item text-capitalize" href="{{route('categories')}}"
                                       style="color: {{$web_config['primary_color']}} !important;{{Session::get('direction') === 'rtl' ? 'right' : 'left'}}: 29%">
                                        {{\App\CPU\translate('view_more')}}

                                        <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}" style="font-size: 8px !important;background:none !important;color:#4B5864;"></i>
                                    </a>

                                </ul>
                            @else
                                <ul class="dropdown-menu"
                                    style="right: 0; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                    @foreach($categories as $category)
                                        <li class="dropdown">
                                            <a class="dropdown-item flex-between"
                                               <?php if ($category->childes->count() > 0) echo "data-toggle='dropdown'"?> href="javascript:"
                                               onclick="location.href='{{route('products',['id'=> $category['id'],'data_from'=>'category','page'=>1])}}'">
                                                <div>
                                                    <img src="{{asset("storage/app/public/category/$category->icon")}}"
                                                         onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                         style="width: 18px; height: 18px; ">
                                                    <span
                                                        class="{{Session::get('direction') === 'rtl' ? 'pr-3' : 'pl-3'}}">{{$category['name']}}</span>
                                                </div>
                                                @if ($category->childes->count() > 0)
                                                    <div>
                                                        <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}" style="font-size: 8px !important;background:none !important;color:#4B5864;"></i>
                                                    </div>
                                                @endif
                                            </a>
                                            @if($category->childes->count()>0)
                                                <ul class="dropdown-menu"
                                                    style="right: 100%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                                    @foreach($category['childes'] as $subCategory)
                                                        <li class="dropdown">
                                                            <a class="dropdown-item flex-between"
                                                               <?php if ($subCategory->childes->count() > 0) echo "data-toggle='dropdown'"?> href="javascript:"
                                                               onclick="location.href='{{route('products',['id'=> $subCategory['id'],'data_from'=>'category','page'=>1])}}'">
                                                                <div>
                                                                    <span
                                                                        class="{{Session::get('direction') === 'rtl' ? 'pr-3' : 'pl-3'}}">{{$subCategory['name']}}</span>
                                                                </div>
                                                                @if ($subCategory->childes->count() > 0)
                                                                    <div>
                                                                        <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}" style="font-size: 8px !important;background:none !important;color:#4B5864;"></i>
                                                                    </div>
                                                                @endif
                                                            </a>
                                                            @if($subCategory->childes->count()>0)
                                                                <ul class="dropdown-menu"
                                                                    style="right: 100%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                                                    @foreach($subCategory['childes'] as $subSubCategory)
                                                                        <li>
                                                                            <a class="dropdown-item"
                                                                               href="{{route('products',['id'=> $subSubCategory['id'],'data_from'=>'category','page'=>1])}}">{{$subSubCategory['name']}}</a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </li>
                                    @endforeach
                                    <a class="dropdown-item" href="{{route('categories')}}"
                                       style="color: {{$web_config['primary_color']}} !important;{{Session::get('direction') === 'rtl' ? 'right' : 'left'}}: 29%">
                                        {{\App\CPU\translate('view_more')}}

                                        <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}" style="font-size: 8px !important;background:none !important;color:{{$web_config['primary_color']}} !important;"></i>
                                    </a>
                                </ul>
                            @endif
                        </li>
                    </ul>

                    <ul class="navbar-nav mega-nav1 pr-2 pl-2 d-block d-xl-none"><!--mobile-->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{Session::get('direction') === 'rtl' ? 'pr-0' : 'pl-0'}}"
                               href="#" data-toggle="dropdown">
                                <i class="czi-menu align-middle mt-n1 {{Session::get('direction') === 'rtl' ? 'ml-2' : 'mr-2'}}"></i>
                                <span
                                    style="margin-{{Session::get('direction') === 'rtl' ? 'right' : 'left'}}: 20px !important;">{{ \App\CPU\translate('categories')}}</span>
                            </a>
                            <ul class="dropdown-menu"
                                style="right: 0%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                @foreach($categories as $category)
                                    {{-- Below xl there is no hover, so the caret must stay a SEPARATE control:
                                         tapping the name navigates, tapping the caret opens the sub-list. It
                                         previously sat outside the row on a margin-left, which left icon, label
                                         and caret scattered across unstyled inline anchors. .ind-mnav-* lays the
                                         two out as one flex row without merging them. --}}
                                    <li class="dropdown ind-mnav-item">
                                        <a class="ind-mnav-link"
                                           href="{{route('products',['id'=> $category['id'],'data_from'=>'category','page'=>1])}}">
                                            <img src="{{asset("storage/app/public/category/$category->icon")}}"
                                                 onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                 style="width: 18px; height: 18px; ">
                                            <span
                                                class="{{Session::get('direction') === 'rtl' ? 'pr-3' : 'pl-3'}}">{{$category['name']}}</span>
                                        </a>
                                        @if ($category->childes->count() > 0)
                                            <a class="ind-mnav-caret" data-toggle="dropdown" href="javascript:void(0)"
                                               aria-label="{{\App\CPU\translate('Show sub categories')}}">
                                                <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}"
                                                   style="font-size: 10px !important;background:none !important;color:#4B5864;"></i>
                                            </a>
                                        @endif

                                        @if($category->childes->count()>0)
                                            <ul class="dropdown-menu"
                                                style="right: 10%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                                @foreach($category['childes'] as $subCategory)
                                                    <li class="dropdown ind-mnav-item">
                                                        <a class="ind-mnav-link"
                                                           href="{{route('products',['id'=> $subCategory['id'],'data_from'=>'category','page'=>1])}}">
                                                            <span
                                                                class="{{Session::get('direction') === 'rtl' ? 'pr-3' : 'pl-3'}}">{{$subCategory['name']}}</span>
                                                        </a>

                                                        @if($subCategory->childes->count()>0)
                                                            <a class="ind-mnav-caret" data-toggle="dropdown" href="javascript:void(0)"
                                                               aria-label="{{\App\CPU\translate('Show sub categories')}}">
                                                                <i class="czi-arrow-{{Session::get('direction') === 'rtl' ? 'left' : 'right'}}"
                                                                   style="font-size: 10px !important;background:none !important;color:#4B5864;"></i>
                                                            </a>
                                                            <ul class="dropdown-menu"
                                                                style="right: 100%; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                                                @foreach($subCategory['childes'] as $subSubCategory)
                                                                    <li>
                                                                        <a class="dropdown-item"
                                                                           href="{{route('products',['id'=> $subSubCategory['id'],'data_from'=>'category','page'=>1])}}">{{$subSubCategory['name']}}</a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    </ul>
                    <!-- Primary menu-->
                    <ul class="navbar-nav" style="{{Session::get('direction') === 'rtl' ? 'padding-right: 0px' : ''}}">
                        <li class="nav-item dropdown {{request()->is('/')?'active':''}}">
                            <a class="nav-link" href="{{route('home')}}">{{ \App\CPU\translate('Home')}}</a>
                        </li>

                        <li class="nav-item dropdown">
                            @php($header_brands = \App\CPU\BrandManager::get_active_brands())
                            @php($header_brands_total = count($header_brands))
                            <a class="nav-link dropdown-toggle" href="#"
                               data-toggle="dropdown">{{ \App\CPU\translate('brands') }}</a>
                            <ul id="brandsDropdownMenu"
                                class="dropdown-menu dropdown-menu-{{Session::get('direction') === 'rtl' ? 'right' : 'left'}} scroll-bar brands-dropdown-menu"
                                style="text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                {{-- Sticky header: title with total count + search box + live status --}}
                                <li class="brands-dropdown-header" onclick="event.stopPropagation();">
                                    <div class="brands-dropdown-title">
                                        {{ \App\CPU\translate('brands') }} (<span id="brandsTotalCount">{{ $header_brands_total }}</span>)
                                    </div>
                                    <div class="brands-search-wrap">
                                        <i class="czi-search brands-search-icon"></i>
                                        <input type="text" id="brandsSearchInput" class="brands-search-input"
                                               autocomplete="off"
                                               placeholder="{{ \App\CPU\translate('Search brands...') }}"
                                               aria-label="{{ \App\CPU\translate('Search brands...') }}"
                                               onclick="event.stopPropagation();">
                                    </div>
                                    <div class="brands-status" id="brandsSearchStatus"
                                         data-total="{{ $header_brands_total }}"
                                         data-all-tpl="{{ \App\CPU\translate('Showing all') }} :total {{ \App\CPU\translate('brands') }}"
                                         data-filter-tpl="{{ \App\CPU\translate('Showing') }} :shown {{ \App\CPU\translate('of') }} :total {{ \App\CPU\translate('brands') }}">
                                        {{ \App\CPU\translate('Showing all') }} {{ $header_brands_total }} {{ \App\CPU\translate('brands') }}
                                    </div>
                                </li>
                                {{-- Empty state (shown by JS only when no brand matches the search) --}}
                                <li class="brands-empty-state" id="brandsEmptyState" style="display:none;">
                                    {{ \App\CPU\translate('No brands found') }}
                                </li>
                                @foreach($header_brands as $brand)
                                    <li class="brand-item"
                                        data-brand-name="{{ \Illuminate\Support\Str::lower($brand['name']) }}"
                                        style="border-bottom: 1px solid #e3e9ef; display:flex; justify-content:space-between; ">
                                        <div>
                                            <a class="dropdown-item"
                                               href="{{route('products',['id'=> $brand['id'],'data_from'=>'brand','page'=>1])}}">
                                                {{$brand['name']}}
                                            </a>
                                        </div>
                                        <div class="align-baseline">
                                            @if($brand['brand_products_count'] > 0 )
                                                <span class="count-value px-2">( {{ $brand['brand_products_count'] }} )</span>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                                <li class="brands-dropdown-footer" style="border-bottom: 1px solid #e3e9ef; display:flex; justify-content:center;">
                                    <div>
                                        <a class="dropdown-item" href="{{route('brands')}}"
                                        style="color: {{$web_config['primary_color']}} !important;">
                                            {{ \App\CPU\translate('View_more') }}
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                        @php($discount_product = App\Model\Product::with(['reviews'])->active()->where('discount', '!=', 0)->count())
                        @if ($discount_product>0)
                            <li class="nav-item dropdown {{ (request()->routeIs('products') && request('data_from')=='discounted') ? 'active' : '' }}">
                                <a class="nav-link text-capitalize" href="{{route('products',['data_from'=>'discounted','page'=>1])}}">{{ \App\CPU\translate('discounted_products')}}</a>
                            </li>
                        @endif
					 <li class="nav-item dropdown {{ request()->routeIs('customer-feedback') ? 'active' : '' }}">
                                <a class="nav-link" href="{{route('customer-feedback')}}">{{ \App\CPU\translate('customer_feedback')}}</a>
                      </li>
                      <li class="nav-item dropdown {{request()->routeIs('contacts')?'active':''}}">
                                <a class="nav-link" href="{{route('contacts')}}">{{ \App\CPU\translate('Contact')}}</a>
                      </li>
                     <?php /* 
                        @php($business_mode=\App\CPU\Helpers::get_business_settings('business_mode'))
                        @if ($business_mode == 'multi')
                            <!--li class="nav-item dropdown {{request()->is('/')?'active':''}}">
                                <a class="nav-link" href="{{route('sellers')}}">{{ \App\CPU\translate('Sellers')}}</a>
                            </li-->
				
                            @php($seller_registration=\App\Model\BusinessSetting::where(['type'=>'seller_registration'])->first()->value)
                            @if($seller_registration)
                                <li class="nav-item">
                                    <div class="dropdown">
                                        <button class="btn dropdown-toggle" type="button" id="dropdownMenuButton"
                                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                                style="color: white;margin-top: 5px; padding-{{Session::get('direction') === 'rtl' ? 'right' : 'left'}}: 0">
                                            {{ \App\CPU\translate('Seller')}}  {{ \App\CPU\translate('zone')}}
                                        </button>
                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton"
                                            style="min-width: 165px !important; text-align: {{Session::get('direction') === 'rtl' ? 'right' : 'left'}};">
                                            <a class="dropdown-item" href="{{route('shop.apply')}}">
                                                {{ \App\CPU\translate('Become a')}} {{ \App\CPU\translate('Seller')}}
                                            </a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="{{route('seller.auth.login')}}">
                                                {{ \App\CPU\translate('Seller')}}  {{ \App\CPU\translate('login')}}
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endif
                        @endif
                      */ ?>
                    </ul>
                    {{-- Far-right live products/brands badge (glassmorphism, navy/yellow theme) --}}
                    <div class="navbar-upload-count ml-auto">
                        <span class="nuc-pill">
                            <span class="nuc-text">
                                <span class="nuc-num">{{ number_format($homepage_live_products_count) }}</span> {{ \App\CPU\translate('Live Products') }}
                                <span class="nuc-dot">&bull;</span>
                                <span class="nuc-num">{{ number_format($homepage_total_brands_count) }}</span> {{ \App\CPU\translate('Brands') }}
                                <span class="nuc-dot">&bull;</span>
                                <span class="nuc-num">{{ number_format($homepage_total_categories_count) }}</span> {{ \App\CPU\translate('Categories') }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
function myFunction() {
  $('#anouncement').addClass('d-none').removeClass('d-flex')
}

/* Currency dropdown — vanilla JS toggle (independent of Bootstrap) */
function toggleCurrencyDropdown(e) {
    e.preventDefault();
    e.stopPropagation();
    var el = document.getElementById('currencyDropdown');
    if (el) { el.classList.toggle('open'); }
}
document.addEventListener('click', function (e) {
    var el = document.getElementById('currencyDropdown');
    if (el && !el.contains(e.target)) { el.classList.remove('open'); }
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        var el = document.getElementById('currencyDropdown');
        if (el) { el.classList.remove('open'); }
    }
});

/* Language dropdown — vanilla JS toggle (independent of Bootstrap), same pattern
   as the currency dropdown so its menu renders above the sticky header / cart. */
function toggleLangDropdown(e) {
    e.preventDefault();
    e.stopPropagation();
    var el = document.getElementById('langDropdown');
    if (el) { el.classList.toggle('open'); }
}
document.addEventListener('click', function (e) {
    var el = document.getElementById('langDropdown');
    if (el && !el.contains(e.target)) { el.classList.remove('open'); }
});
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        var el = document.getElementById('langDropdown');
        if (el) { el.classList.remove('open'); }
    }
});

/* Brands dropdown — instant client-side filter over the already-rendered list.
   No API calls, no libraries; works for both desktop and the mobile collapse menu. */
(function () {
    var input  = document.getElementById('brandsSearchInput');
    var menu   = document.getElementById('brandsDropdownMenu');
    if (!input || !menu) { return; }

    var items   = menu.querySelectorAll('.brand-item');
    var empty   = document.getElementById('brandsEmptyState');
    var status  = document.getElementById('brandsSearchStatus');
    var total   = status ? parseInt(status.getAttribute('data-total'), 10) || items.length : items.length;
    var allTpl  = status ? status.getAttribute('data-all-tpl')    : '';
    var filtTpl = status ? status.getAttribute('data-filter-tpl') : '';

    function applyFilter() {
        var q = input.value.trim().toLowerCase();
        var shown = 0;

        for (var i = 0; i < items.length; i++) {
            var name = items[i].getAttribute('data-brand-name') || '';
            var match = q === '' || name.indexOf(q) !== -1;
            items[i].style.display = match ? 'flex' : 'none';
            if (match) { shown++; }
        }

        if (empty) { empty.style.display = (shown === 0) ? 'block' : 'none'; }

        if (status) {
            if (q === '') {
                status.textContent = allTpl.replace(':total', total);
            } else {
                status.textContent = filtTpl.replace(':shown', shown).replace(':total', total);
            }
        }
    }

    input.addEventListener('input', applyFilter);
    /* Keep typing/clicks inside the search box from closing the Bootstrap dropdown. */
    input.addEventListener('click', function (e) { e.stopPropagation(); });
})();
</script>
