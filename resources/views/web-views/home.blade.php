@extends('layouts.front-end.app')

@section('title',\App\CPU\translate('Welcome To').' '.$web_config['name']->value)

@push('css_or_js')
    <meta property="og:image" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="og:title" content="Welcome To {{$web_config['name']->value}} Home"/>
    <meta property="og:url" content="{{env('APP_URL')}}">
    <meta property="og:description" content="{!! substr($web_config['about']->value,0,100) !!}">

    <meta property="twitter:card" content="{{asset('storage/app/public/company')}}/{{$web_config['web_logo']->value}}"/>
    <meta property="twitter:title" content="Welcome To {{$web_config['name']->value}} Home"/>
    <meta property="twitter:url" content="{{env('APP_URL')}}">
    <meta property="twitter:description" content="{!! substr($web_config['about']->value,0,100) !!}">

    <link rel="stylesheet" href="{{asset('public/assets/front-end')}}/css/home.css"/>
    <style>
        .media {
            background: white;
        }

        /* Floating WhatsApp chat button (homepage only, temporary test number) */
        .ind-whatsapp-float {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 1030; /* above page content, below Bootstrap modals (1050) */
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 18px;
            background-color: #25D366;
            color: #ffffff;
            font-weight: 600;
            line-height: 1;
            border-radius: 50px; /* pill on desktop */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.18);
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        .ind-whatsapp-float:hover,
        .ind-whatsapp-float:focus {
            color: #ffffff;
            background-color: #1ebe57;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .ind-whatsapp-float__icon {
            flex: 0 0 auto;
        }

        /* Mobile: compact circular icon only */
        @media (max-width: 767.98px) {
            .ind-whatsapp-float {
                right: 16px;
                bottom: 16px;
                padding: 0;
                width: 52px;
                height: 52px;
                justify-content: center;
                border-radius: 50%;
            }

            .ind-whatsapp-float__text {
                display: none;
            }
        }

        .section-header {
            display: flex;
            justify-content: space-between;
        }

        .cz-countdown-days {
            color: white !important;
            background-color: #ffffff30;
            border: .5px solid{{$web_config['primary_color']}};
            padding: 0px 6px;
            border-radius: 3px;
            margin-right: 0px !important;
            display: flex;
	        flex-direction: column;
            -ms-flex: .4;  /* IE 10 */
            flex: 1;

        }

        .cz-countdown-hours {
            color: white !important;
            background-color: #ffffff30;
            border: .5px solid{{$web_config['primary_color']}};
            padding: 0px 6px;
            border-radius: 3px;
            margin-right: 0px !important;
            display: flex;
	        flex-direction: column;
            -ms-flex: .4;  /* IE 10 */
            flex: 1;
        }

        .cz-countdown-minutes {
            color: white !important;
            background-color: #ffffff30;
            border: .5px solid{{$web_config['primary_color']}};
            padding: 0px 6px;
            border-radius: 3px;
            margin-right: 0px !important;
            display: flex;
	        flex-direction: column;
            -ms-flex: .4;  /* IE 10 */
            flex: 1;
        }

        .cz-countdown-seconds {
            color: white !important;
            background-color: #ffffff30;
            border: .5px solid{{$web_config['primary_color']}};
            padding: 0px 6px;
            border-radius: 3px;
            display: flex;
	        flex-direction: column;
            -ms-flex: .4;  /* IE 10 */
            flex: 1;
        }

        .flash_deal_product_details .flash-product-price {
            font-weight: 700;
            font-size: 18px;
            color: {{$web_config['primary_color']}};
        }

        .featured_deal_left {
            height: 130px;
            background: {{$web_config['primary_color']}} 0% 0% no-repeat padding-box;
            padding: 10px 13px;
            text-align: center;
        }

        .category_div:hover {
            color: {{$web_config['secondary_color']}};
        }

        .deal_of_the_day {
            /* filter: grayscale(0.5); */
            /* opacity: .8; */
            background: {{$web_config['secondary_color']}};
            border-radius: 3px;
        }

        .deal-title {
            font-size: 12px;

        }

        .for-flash-deal-img img {
            max-width: none;
        }
        .best-selleing-image {
            background:{{$web_config['primary_color']}}10;
            width:30%;
            display:flex;
            align-items:center;
            border-radius: 5px;
        }
        .best-selling-details {
            padding:10px;
            width:50%;
        }
        .top-rated-image{
            background:{{$web_config['primary_color']}}10;
            width:30%;
            display:flex;
            align-items:center;
            border-radius: 5px;
        }
        .top-rated-details {
            padding:10px;width:70%;
        }

        @media (max-width: 375px) {
            .cz-countdown {
                display: flex !important;

            }

            .cz-countdown .cz-countdown-seconds {

                margin-top: -5px !important;
            }

            .for-feature-title {
                font-size: 20px !important;
            }
        }

        @media (max-width: 600px) {
            .flash_deal_title {
                /*font-weight: 600;*/
                /*font-size: 18px;*/
                /*text-transform: uppercase;*/

                font-weight: 700;
                font-size: 25px;
                text-transform: uppercase;
            }

            .cz-countdown .cz-countdown-value {
                /* font-family: "Roboto", sans-serif; */
                font-size: 11px !important;
                font-weight: 700 !important;

            }

            .featured_deal {
                opacity: 1 !important;
            }

            .cz-countdown {
                display: inline-block;
                flex-wrap: wrap;
                font-weight: normal;
                margin-top: 4px;
                font-size: smaller;
            }

            .view-btn-div-f {

                margin-top: 6px;
                float: right;
            }

            .view-btn-div {
                float: right;
            }

            .viw-btn-a {
                font-size: 10px;
                font-weight: 600;
            }


            .for-mobile {
                display: none;
            }

            .featured_for_mobile {
                max-width: 100%;
                margin-top: 20px;
                margin-bottom: 20px;
            }
            .best-selleing-image {
                width: 50%;
                border-radius: 5px;
            }
            .best-selling-details {
                width:50%;
            }
            .top-rated-image {
                width: 50%;
            }
            .top-rated-details {
            width:50%;
        }
        }


        @media (max-width: 360px) {
            .featured_for_mobile {
                max-width: 100%;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .featured_deal {
                opacity: 1 !important;
            }
        }

        @media (max-width: 375px) {
            .featured_for_mobile {
                max-width: 100%;
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .featured_deal {
                opacity: 1 !important;
            }

        }

        @media (min-width: 768px) {
            .displayTab {
                display: block !important;
            }

        }

        @media (max-width: 800px) {

            .latest-product-margin {
                margin-left: 0px !important;
                }
            .for-tab-view-img {
                width: 40%;
            }

            .for-tab-view-img {
                width: 105px;
            }

            .widget-title {
                font-size: 19px !important;
            }
            .flash-deal-view-all-web {
                display: none !important;
            }
            .categories-view-all {
                {{session('direction') === "rtl" ? 'margin-left: 10px;' : 'margin-right: 6px;'}}
            }
            .categories-title {
                {{Session::get('direction') === "rtl" ? 'margin-right: 0px;' : 'margin-left: 6px;'}}
            }
            .seller-list-title{
                {{Session::get('direction') === "rtl" ? 'margin-right: 0px;' : 'margin-left: 10px;'}}
            }
            .seller-list-view-all {
                {{Session::get('direction') === "rtl" ? 'margin-left: 20px;' : 'margin-right: 10px;'}}
            }
            .seller-card {
                padding-left: 0px !important;
            }
            .category-product-view-title {
                {{Session::get('direction') === "rtl" ? 'margin-right: 16px;' : 'margin-left: -8px;'}}
            }
            .category-product-view-all {
                {{Session::get('direction') === "rtl" ? 'margin-left: -7px;' : 'margin-right: 5px;'}}
            }
            .recomanded-product-card {
                background: #F8FBFD;margin:20px;height: 535px; border-radius: 5px;
            }
            .recomanded-buy-button {
                text-align: center;
                margin-top: 30px;
            }
        }
        @media(min-width:801px){
            .flash-deal-view-all-mobile{
                display: none !important;
            }
            .categories-view-all {
                {{session('direction') === "rtl" ? 'margin-left: 30px;' : 'margin-right: 27px;'}}
            }
            .categories-title {
                {{Session::get('direction') === "rtl" ? 'margin-right: 25px;' : 'margin-left: 25px;'}}
            }
            .seller-list-title{
                {{Session::get('direction') === "rtl" ? 'margin-right: 6px;' : 'margin-left: 10px;'}}
            }
            .seller-list-view-all {
                {{Session::get('direction') === "rtl" ? 'margin-left: 12px;' : 'margin-right: 10px;'}}
            }
            .seller-card {
                {{Session::get('direction') === "rtl" ? 'padding-left:0px !important;' : 'padding-right:0px !important;'}}
            }
            .category-product-view-title {
                {{Session::get('direction') === "rtl" ? 'margin-right: 10px;' : 'margin-left: -12px;'}}
            }
            .category-product-view-all {
                {{Session::get('direction') === "rtl" ? 'margin-left: -20px;' : 'margin-right: 0px;'}}
            }
            .recomanded-product-card {
                background: #F8FBFD;margin:20px;height: 475px; border-radius: 5px;
            }
            .recomanded-buy-button {
                text-align: center;
                margin-top: 63px;
            }

        }

        .featured_deal_carosel .carousel-inner {
            width: 100% !important;
        }

        .badge-style2 {
            color: black !important;
            background: transparent !important;
            font-size: 11px;
        }
        .countdown-card{
            background:{{$web_config['primary_color']}}10;
            height: 150px!important;
            border-radius:5px;

        }
        .flash-deal-text{
            color: {{$web_config['primary_color']}};
            text-transform: uppercase;
            text-align:center;
            font-weight:700;
            font-size:20px;
            border-radius:5px;
            margin-top:25px;
        }
        .countdown-background{
            background: {{$web_config['primary_color']}};
            padding: 5px 5px;
            border-radius:5px;
            margin-top:15px;
        }
        .carousel-wrap{
            position: relative;
        }
        .owl-nav{
            top: 40%;
            position: absolute;
            display: flex;
            justify-content: space-between;
            width: 100%;
        }
     }
     .owl-prev{
         float: left;

     }
     .owl-next{
         float: right;
     }
     .czi-arrow-left{
        color: {{$web_config['primary_color']}};
        background: {{$web_config['primary_color']}}10;
        padding: 5px;
        border-radius: 50%;
        margin-left: -12px;
        font-weight: bold;
        font-size: 12px;
     }
     .czi-arrow-right{
        color: {{$web_config['primary_color']}};
        background: {{$web_config['primary_color']}}10;
        padding: 5px;
        border-radius: 50%;
        margin-right: -15px;
        font-weight: bold;
        font-size: 12px;
     }
    .owl-carousel .nav-btn .czi-arrow-left{
      height: 47px;
      position: absolute;
      width: 26px;
      cursor: pointer;
      top: 100px !important;
  }
  .flash-deals-background-image{
    background: {{$web_config['primary_color']}}10;
    border-radius:5px;
    width:125px;
    height:125px;
  }
  .view-all-text{
    color:{{$web_config['secondary_color']}} !important;
    font-size:14px;
  }
  .feature-product-title {
    text-align: center;
    font-size: 22px;
    margin-top: 15px;
    font-style: normal;
    font-weight: 700;
  }
  .feature-product .czi-arrow-left{
        color: {{$web_config['primary_color']}};
        background: {{$web_config['primary_color']}}10;
        padding: 5px;
        border-radius: 50%;
        margin-left: -80px;
        font-weight: bold;
        font-size: 12px;
     }

     .feature-product .owl-nav{
        top: 40%;
        position: absolute;
        display: flex;
        justify-content: space-between;
        /* width: 100%; */
        z-index: -999;
    }
     .feature-product .czi-arrow-right{
        color: {{$web_config['primary_color']}};
        background: {{$web_config['primary_color']}}10;
        padding: 5px;
        border-radius: 50%;
        margin-right: -80px;
        font-weight: bold;
        font-size: 12px;
     }
     .shipping-policy-web{
        background: #ffffff;width:100%; border-radius:5px;
     }
     .shipping-method-system{
        height: 130px;width: 70%;margin-top: 15px;
     }

     .flex-between {
         display: flex;
         justify-content: space-between;
     }
     .new_arrival_product .czi-arrow-left{
         margin-left: -28px;
     }
     .new_arrival_product .owl-nav{
         z-index: -999;
     }
    </style>

    <link rel="stylesheet" href="{{asset('public/assets/front-end')}}/css/owl.carousel.min.css"/>
    <link rel="stylesheet" href="{{asset('public/assets/front-end')}}/css/owl.theme.default.min.css"/>
@endpush

@section('content')

@php($decimal_point_settings = \App\CPU\Helpers::get_business_settings('decimal_point_settings'))
    <!-- Hero (Banners + Slider)-->
    <section class="bg-transparent mb-3"> 
        <div class="container">
            <div class="row ">
                <div class="col-12">
                    @include('web-views.partials._home-top-slider')
                </div>
            </div>
        </div>
    </section>

    {{--flash deal--}}
    @php($flash_deals=\App\Model\FlashDeal::with(['products'=>function($query){
                $query->with('product')->whereHas('product',function($q){
                    $q->active();
                });
            }])->where(['status'=>1])->where(['deal_type'=>'flash_deal'])->whereDate('start_date','<=',date('Y-m-d'))->whereDate('end_date','>=',date('Y-m-d'))->first())

    @if (isset($flash_deals))
    <div class="container">
        <div class="flash-deal-view-all-web row d-flex justify-content-{{Session::get('direction') === "rtl" ? 'start' : 'end'}}" style="{{Session::get('direction') === "rtl" ? 'margin-left: 2px;' : 'margin-right:2px;'}}">
            @if (count($flash_deals->products)>0)
                <a class="text-capitalize view-all-text" href="{{route('flash-deals',[isset($flash_deals)?$flash_deals['id']:0])}}">
                    {{ \App\CPU\translate('view_all')}}
                    <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left-circle mr-1 ml-n1 mt-1 float-left' : 'right-circle ml-1 mr-n1'}}"></i>
                </a>
            @endif
        </div>
        <div class="row d-flex {{Session::get('direction') === "rtl" ? 'flex-row-reverse' : 'flex-row'}}">


            <div class="col-md-3 mt-2 countdown-card" >
                <div class="m-2">
                    <div class="flash-deal-text">
                        <span>{{ \App\CPU\translate('flash deal')}}</span>
                    </div>
                    <div style=" text-align: center;color: #ffffff !important;">
                        <div class="countdown-background">
                            <span class="cz-countdown d-flex justify-content-center align-items-center"
                                data-countdown="{{isset($flash_deals)?date('m/d/Y',strtotime($flash_deals['end_date'])):''}} 11:59:00 PM">
                                <span class="cz-countdown-days">
                                    <span class="cz-countdown-value"></span>
                                    <span>{{ \App\CPU\translate('day')}}</span>
                                </span>
                                <span class="cz-countdown-value p-1">:</span>
                                <span class="cz-countdown-hours">
                                    <span class="cz-countdown-value"></span>
                                    <span>{{ \App\CPU\translate('hrs')}}</span>
                                </span>
                                <span class="cz-countdown-value p-1">:</span>
                                <span class="cz-countdown-minutes">
                                    <span class="cz-countdown-value"></span>
                                    <span>{{ \App\CPU\translate('min')}}</span>
                                </span>
                                <span class="cz-countdown-value p-1">:</span>
                                <span class="cz-countdown-seconds">
                                    <span class="cz-countdown-value"></span>
                                    <span>{{ \App\CPU\translate('sec')}}</span>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flash-deal-view-all-mobile col-md-12" style="{{Session::get('direction') === "rtl" ? 'margin-left: 2px;' : 'margin-right:2px;'}}">
                <a class="{{Session::get('direction') === "rtl" ? 'float-left' : 'float-right'}} mt-2 text-capitalize view-all-text" href="{{route('flash-deals',[isset($flash_deals)?$flash_deals['id']:0])}}">
                    {{ \App\CPU\translate('view_all')}}
                    <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left-circle mr-1 ml-n1 mt-1 float-left' : 'right-circle ml-1 mr-n1'}}"></i>
                </a>
            </div>
            <div class="col-md-9 {{Session::get('direction') === "rtl" ? 'pr-md-4' : 'pl-md-4'}}">
                <div class="carousel-wrap">
                    <div class="owl-carousel owl-theme mt-2" id="flash-deal-slider">
                        @foreach($flash_deals->products as $key=>$deal)
                            @if( $deal->product)
                                @include('web-views.partials._product-card-1',['product'=>$deal->product,'decimal_point_settings'=>$decimal_point_settings])
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{--brands--}}
    <section class="container rtl mt-3">
        <!-- Heading-->
        <div class="section-header section-header--stacked">
            <div>
                <h2 class="section-title">{{\App\CPU\translate('brands')}}</h2>
                <p class="section-subtitle">{{\App\CPU\translate('Trusted industrial brands we supply')}}</p>
            </div>
            <a class="view-all-link text-capitalize" href="{{route('brands')}}">
                {{ \App\CPU\translate('view_all')}}
                <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
            </a>
        </div>
    <!-- Grid-->

        <div class="mt-2 mb-3 brand-slider">
            <div class="owl-carousel owl-theme p-2" id="brands-slider">
                @foreach($brands as $brand)
                    <div class="text-center">
                        <a class="brand-card" href="{{route('products',['id'=> $brand['id'],'data_from'=>'brand','page'=>1])}}">
                            <img onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                 src="{{asset("storage/app/public/brand/$brand->image")}}" alt="{{$brand->name}}">
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Products grid (featured products) — static grid -->
    @if ($featured_products->count() > 0 )
    <div class="container rtl mb-4">
        <div class="section-header section-header--stacked">
            <div>
                <h2 class="section-title">{{ \App\CPU\translate('featured_products')}}</h2>
                <p class="section-subtitle">{{ \App\CPU\translate('Popular industrial products selected for quick sourcing')}}</p>
            </div>
            <a class="view-all-link text-capitalize" href="{{route('products',['data_from'=>'latest','page'=>1])}}">
                {{ \App\CPU\translate('View All Products')}}
                <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
            </a>
        </div>
        <div class="featured-products-grid">
            @foreach($featured_products as $product)
                @include('web-views.partials._feature-product',['product'=>$product, 'decimal_point_settings'=>$decimal_point_settings])
            @endforeach
        </div>
    </div>
    @endif

    {{--request a quote CTA band--}}
    <div class="container rtl mb-4">
        <div class="ind-quote-band row align-items-center justify-content-between">
            <div class="col-md-8 mb-3 mb-md-0">
                <h3>{{\App\CPU\translate('Need a bulk or custom quote?')}}</h3>
                <p>{{\App\CPU\translate('Tell us what you need and our team will get back to you with the best B2B pricing.')}}</p>
            </div>
            <div class="col-md-4 text-md-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}">
                <a href="{{route('contacts')}}" class="btn btn-accent btn-lg">
                    <i class="czi-message mr-1"></i>{{\App\CPU\translate('Request a Quote')}}
                </a>
            </div>
        </div>
    </div>

    {{--featured deal--}}
    @php($featured_deals=\App\Model\FlashDeal::with(['products'=>function($query_one){
        $query_one->with('product.reviews')->whereHas('product',function($query_two){
            $query_two->active();
        });
    }])
    ->whereDate('start_date', '<=', date('Y-m-d'))->whereDate('end_date', '>=', date('Y-m-d'))
    ->where(['status'=>1])->where(['deal_type'=>'feature_deal'])
    ->first())

    @if(isset($featured_deals))
        <section class="container featured_deal rtl mb-2">
            <div class="row" style="background: {{$web_config['primary_color']}};padding:5px;padding-bottom: 25px; border-radius:5px;">
                <div class="col-12 pb-2" >
                    @if (count($featured_deals->products)>0)
                        <a class="text-capitalize mt-2 mt-md-0 {{Session::get('direction') === "rtl" ? 'float-left' : 'float-right'}}" href="{{route('products',['data_from'=>'featured_deal'])}}"
                            style="color: white !important;{{Session::get('direction') === "rtl" ? 'margin-left: 21px;' : 'margin-right: 21px;'}}">
                            {{ \App\CPU\translate('view_all')}}
                            <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left-circle mr-1 ml-n1 mt-1 float-left' : 'right-circle ml-1 mr-n1'}}"></i>
                        </a>
                    @endif
                </div>
                <div class="col-xl-3 col-md-4 d-flex align-items-center justify-content-center right">
                    <div class="m-4">
                        <span class="featured_deal_title"
                            style="padding-top: 12px">{{ \App\CPU\translate('featured_deal')}}</span>
                        <br>

                        <span style="color: white;text-align: left !important;">{{ \App\CPU\translate('See the latest deals and exciting new offers ')}}!</span>

                    </div>

                </div>

                <div class="col-xl-9 col-md-8 d-flex align-items-center justify-content-center {{Session::get('direction') === "rtl" ? 'pl-4' : 'pr-4'}}">
                    <div class="owl-carousel owl-theme" id="web-feature-deal-slider">
                        @foreach($featured_deals->products as $key=>$product)
                            @include('web-views.partials._feature-deal-product',['product'=>$product->product, 'decimal_point_settings'=>$decimal_point_settings])
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    {{--Recommended product spotlight--}}
    @php($rec = (isset($deal_of_the_day) && isset($deal_of_the_day->product)) ? $deal_of_the_day->product : \App\Model\Product::active()->inRandomOrder()->first())
    @if(isset($rec))
        @php($rec_rating = \App\CPU\ProductManager::get_overall_rating($rec['reviews']))
        <section class="container rtl mb-4">
            <div class="section-header section-header--stacked">
                <div>
                    <h2 class="section-title">{{ \App\CPU\translate('Recommended') }}</h2>
                    <p class="section-subtitle">{{ \App\CPU\translate('Our top industrial pick for you') }}</p>
                </div>
            </div>
            <div class="ind-rec-card">
                {{-- Left: product image --}}
                <a href="{{route('product',$rec->slug)}}" class="ind-rec-media">
                    @if($rec->discount > 0)
                        <span class="ind-feature-badge">@if($rec->discount_type == 'percent'){{round($rec->discount)}}%@elseif($rec->discount_type=='flat'){{\App\CPU\Helpers::currency_converter($rec->discount)}}@endif {{\App\CPU\translate('off')}}</span>
                    @endif
                    <img src="{{\App\CPU\ProductManager::product_image_path('thumbnail')}}/{{$rec['thumbnail']}}"
                         onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'" alt="{{$rec['name']}}">
                </a>

                {{-- Middle: product info + actions --}}
                <div class="ind-rec-info">
                    <span class="ind-recommended-badge">{{ \App\CPU\translate('Top Pick') }}</span>
                    <a href="{{route('product',$rec->slug)}}" class="ind-rec-title">{{ Str::limit($rec['name'],70) }}</a>
                    @if(optional($rec->brand)->name)
                        <div class="ind-rec-brand">{{ $rec->brand->name }}</div>
                    @endif
                    <div class="rating-show mt-2">
                        @if($rec->reviews_count > 0)
                            <span class="d-inline-block font-size-sm text-body">
                                @for($inc=0;$inc<5;$inc++)
                                    @if($inc<$rec_rating[0])<i class="sr-star czi-star-filled active"></i>@else<i class="sr-star czi-star" style="color:#cbd5e1 !important"></i>@endif
                                @endfor
                                <span class="text-muted" style="font-size:12px;">( {{$rec->reviews_count}} )</span>
                            </span>
                        @else
                            <span class="ind-no-reviews">{{\App\CPU\translate('No reviews yet')}}</span>
                        @endif
                    </div>
                    <div class="ind-rec-price mt-2">
                        @if($rec->unit_price > 0)
                            @if($rec->discount > 0)<strike>{{\App\CPU\Helpers::currency_converter($rec->unit_price)}}</strike>@endif
                            <span class="ind-feature-price">{{\App\CPU\Helpers::currency_converter($rec->unit_price-(\App\CPU\Helpers::get_product_discount($rec,$rec->unit_price)))}}</span>
                        @else
                            <span class="ind-feature-quote">{{\App\CPU\translate('Request Quote')}}</span>
                        @endif
                    </div>
                    <div class="ind-rec-actions mt-3">
                        <a href="{{route('product',$rec->slug)}}" class="btn btn-accent">{{\App\CPU\translate('View Details')}}</a>
                        <a href="{{route('contacts')}}" class="btn btn-outline-primary">{{\App\CPU\translate('Request a Quote')}}</a>
                    </div>
                </div>

                {{-- Right: company trust panel --}}
                <div class="ind-rec-trust">
                    <h6 class="ind-rec-trust-title"><i class="czi-security-check"></i>{{\App\CPU\translate('Why source this from FIAPL?')}}</h6>
                    <ul class="ind-rec-trust-list">
                        <li><i class="czi-check"></i><span>{{\App\CPU\translate('Genuine industrial product sourcing')}}</span></li>
                        <li><i class="czi-check"></i><span>{{\App\CPU\translate('Quick quotation response')}}</span></li>
                        <li><i class="czi-check"></i><span>{{\App\CPU\translate('Support for bulk requirements')}}</span></li>
                        <li><i class="czi-check"></i><span>{{\App\CPU\translate('Suitable for B2B procurement')}}</span></li>
                        <li><i class="czi-check"></i><span>{{\App\CPU\translate('Pan-India delivery assistance')}}</span></li>
                    </ul>
                </div>
            </div>
        </section>
    @endif

    {{--Latest products--}}
    @if(count($latest_products) > 0)
    <section class="container rtl mb-4">
        <div class="section-header section-header--stacked">
            <div>
                <h2 class="section-title">{{ \App\CPU\translate('latest_products') }}</h2>
                <p class="section-subtitle">{{ \App\CPU\translate('Newly added industrial products for quick sourcing') }}</p>
            </div>
            <a class="view-all-link text-capitalize" href="{{route('products',['data_from'=>'latest','page'=>1])}}">
                {{ \App\CPU\translate('View All Products') }}
                <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
            </a>
        </div>
        <div class="featured-products-grid">
            @foreach($latest_products as $product)
                @include('web-views.partials.product-card',['product'=>$product,'decimal_point_settings'=>$decimal_point_settings])
            @endforeach
        </div>
    </section>
    @endif


@php($main_section_banner = \App\Model\Banner::where('banner_type','Main Section Banner')->where('published',1)->orderBy('id','desc')->latest()->first())
    @if (isset($main_section_banner))
    <div class="container rtl mb-3">
        <div class="row" >
            <div class="col-12 pl-0 pr-0">
                <a href="{{$main_section_banner->url}}"
                    style="cursor: pointer;">
                    <img class="d-block footer_banner_img" style="width: 100%;border-radius: 5px;height: auto !important;"
                            onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                            src="{{asset('storage/app/public/banner')}}/{{$main_section_banner['photo']}}">
                </a>
            </div>
        </div>
    </div>
    @endif

    @php($business_mode=\App\CPU\Helpers::get_business_settings('business_mode'))
    {{--categories--}}
    <section class="container rtl mb-4">
        <div class="section-header section-header--stacked">
            <div>
                <h2 class="section-title">{{ \App\CPU\translate('categories') }}</h2>
                <p class="section-subtitle">{{ \App\CPU\translate('Browse products by industrial category') }}</p>
            </div>
            <a class="view-all-link text-capitalize" href="{{route('categories')}}">
                {{ \App\CPU\translate('View All') }}
                <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
            </a>
        </div>
        <div class="ind-cat-grid-wrap">
            @foreach($categories as $key=>$category)
                @if($key<12)
                    <a class="ind-cat-tile" title="{{$category->name}}"
                       href="{{route('products',['id'=> $category['id'],'data_from'=>'category','page'=>1])}}">
                        <span class="ind-cat-tile-img">
                            <img onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                 src="{{asset("storage/app/public/category/$category->icon")}}" alt="{{$category->name}}">
                        </span>
                        <span class="ind-cat-tile-name">{{$category->name}}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </section>

    {{--top sellers--}}
    @if ($business_mode == 'multi' && count($top_sellers) > 0)
    <section class="container rtl mb-4">
        <div class="section-header section-header--stacked">
            <div>
                <h2 class="section-title">{{ \App\CPU\translate('sellers') }}</h2>
                <p class="section-subtitle">{{ \App\CPU\translate('Trusted suppliers on our platform') }}</p>
            </div>
            <a class="view-all-link text-capitalize" href="{{route('sellers')}}">
                {{ \App\CPU\translate('view_all') }}
                <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
            </a>
        </div>
        <div class="ind-seller-grid">
            @foreach($top_sellers as $key=>$seller)
                @if ($key<12 && $seller->shop)
                    <a class="ind-seller-tile" title="{{$seller->shop->name}}" href="{{route('shopView',['id'=>$seller['id']])}}">
                        <span class="ind-seller-img">
                            <img onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                 src="{{asset("storage/app/public/shop")}}/{{$seller->shop->image}}" alt="{{$seller->shop->name}}">
                        </span>
                        <span class="ind-seller-name">{{$seller->shop->name}}</span>
                    </a>
                @endif
            @endforeach
        </div>
    </section>
    @endif


    <div class="container rtl mt-3">
        <div class="row d-flex justify-content-center">
            <div style="height: 90px;width:90px;">
                <img  src="{{asset("public/assets/front-end/png/new-arrivals.png")}}"
                                 alt="">

            </div>
            <div style="margin-top:24px;font-weight: 700;font-size: 26px;">
                <p style="float: right">{{ \App\CPU\translate('ARRIVALS')}}</p>
            </div>
        </div>
    </div>
    <div class="container rtl mb-3" style="">
        <div class="col-md-12" style="background-color:white;padding:20px;border-radius:10px;">
            <div class="new_arrival_product" style="margin-left:-5px;">
                <div class="carousel-wrap" >
                    <div class="owl-carousel owl-theme p-2" id="new-arrivals-product">
                        @foreach($latest_products as $key=>$product)

                                @include('web-views.partials._product-card-1',['product'=>$product,'decimal_point_settings'=>$decimal_point_settings])

                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="container rtl mb-4">
        <div class="row">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="ind-card ind-product-panel">
                    <div class="ind-panel-header">
                        <div class="ind-panel-title">
                            <img src="{{asset("public/assets/front-end/png/best sellings.png")}}" alt="">
                            <span>{{ \App\CPU\translate('best sellings')}}</span>
                        </div>
                        <a class="view-all-link text-capitalize" href="{{route('products',['data_from'=>'best-selling','page'=>1])}}">
                            {{ \App\CPU\translate('view_all')}} <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
                        </a>
                    </div>
                    <div class="ind-product-row-list">
                        @foreach($bestSellProduct as $key=>$bestSell)
                            @if($bestSell->product && $key<3)
                                @include('web-views.partials.product-row',['product'=>$bestSell->product,'decimal_point_settings'=>$decimal_point_settings])
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="ind-card ind-product-panel">
                    <div class="ind-panel-header">
                        <div class="ind-panel-title">
                            <img src="{{asset("public/assets/front-end/png/top-rated.png")}}" alt="">
                            <span>{{ \App\CPU\translate('top rated')}}</span>
                        </div>
                        <a class="view-all-link text-capitalize" href="{{route('products',['data_from'=>'top-rated','page'=>1])}}">
                            {{ \App\CPU\translate('view_all')}} <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
                        </a>
                    </div>
                    <div class="ind-product-row-list">
                        @foreach($topRated as $key=>$top)
                            @if($top->product && $key<3)
                                @include('web-views.partials.product-row',['product'=>$top->product,'decimal_point_settings'=>$decimal_point_settings])
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Banner  --}}
    <div class="container rtl mt-3 mb-3">
        <div class="row">
            @foreach(\App\Model\Banner::where('banner_type','Footer Banner')->where('published',1)->orderBy('id','desc')->take(2)->get() as $banner)
                <div class="col-md-6 mt-2 mt-md-0">
                    <a href="{{$banner->url}}"
                        style="cursor: pointer;">
                         <img class="" style="width: 100%; border-radius:5px;height:auto;"
                              onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                              src="{{asset('storage/app/public/banner')}}/{{$banner['photo']}}">
                     </a>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Categorized product (capped to keep the homepage short) --}}
    @foreach($home_categories->take(4) as $category)
        <section class="container rtl mb-4">
            <div class="ind-card ind-catprod">
                <div class="ind-panel-header">
                    <div class="ind-panel-title">{{ $category['name'] }}</div>
                    <a class="view-all-link text-capitalize" href="{{route('products',['id'=> $category['id'],'data_from'=>'category','page'=>1])}}">
                        {{ \App\CPU\translate('view_all')}} <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ml-1"></i>
                    </a>
                </div>
                <div class="row mt-3">
                    @foreach($category['products'] as $key=>$product)
                        @if ($key<4)
                            <div class="col-xl-3 col-lg-3 col-md-4 col-6 mb-3">
                                @include('web-views.partials.product-card',['product'=>$product,'decimal_point_settings'=>$decimal_point_settings])
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endforeach

        {{--delivery type --}}

    <div class="container rtl mb-4">
        <div class="row ind-trust-strip">
            <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                <div class="ind-trust-card">
                    <span class="ind-trust-ic"><i class="czi-delivery"></i></span>
                    <div class="ind-trust-text">
                        <h6 class="ind-trust-title">{{ \App\CPU\translate('Fast Delivery Across India')}}</h6>
                        <p class="ind-trust-sub">{{ \App\CPU\translate('Quick dispatch for industrial orders')}}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                <div class="ind-trust-card">
                    <span class="ind-trust-ic"><i class="czi-security-check"></i></span>
                    <div class="ind-trust-text">
                        <h6 class="ind-trust-title">{{ \App\CPU\translate('Secure Payment')}}</h6>
                        <p class="ind-trust-sub">{{ \App\CPU\translate('Safe and protected checkout')}}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                <div class="ind-trust-card">
                    <span class="ind-trust-ic"><i class="czi-package"></i></span>
                    <div class="ind-trust-text">
                        <h6 class="ind-trust-title">{{ \App\CPU\translate('Easy Returns on Eligible Products')}}</h6>
                        <p class="ind-trust-sub">{{ \App\CPU\translate('Hassle-free support')}}</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-sm-6">
                <div class="ind-trust-card">
                    <span class="ind-trust-ic"><i class="czi-check-circle"></i></span>
                    <div class="ind-trust-text">
                        <h6 class="ind-trust-title">{{ \App\CPU\translate('100% Authentic Products')}}</h6>
                        <p class="ind-trust-sub">{{ \App\CPU\translate('Trusted industrial brands')}}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Floating WhatsApp chat button (homepage only) --}}
    {{-- TEMPORARY testing number — replace later with the official company WhatsApp Business number. --}}
    {{-- International format only: country code + 10-digit number, no +, spaces, brackets or dashes. --}}
    @php($whatsappNumber = '918744999901')
    @php($whatsappMessage = rawurlencode('Hello Industrial Needs, I am interested in your industrial products. Please assist me with pricing and availability.'))
    @php($whatsappLink = "https://wa.me/{$whatsappNumber}?text={$whatsappMessage}")

    <a href="{{ $whatsappLink }}"
       class="ind-whatsapp-float"
       target="_blank"
       rel="noopener noreferrer"
       aria-label="Chat with Industrial Needs on WhatsApp">
        <svg class="ind-whatsapp-float__icon" viewBox="0 0 32 32" width="28" height="28" aria-hidden="true" focusable="false">
            <path fill="currentColor" d="M16.001 3.2c-7.06 0-12.8 5.74-12.8 12.8 0 2.257.59 4.46 1.71 6.4L3.2 28.8l6.56-1.72a12.74 12.74 0 0 0 6.24 1.62h.005c7.06 0 12.8-5.74 12.8-12.8s-5.74-12.7-12.8-12.7zm0 23.04h-.004a10.62 10.62 0 0 1-5.41-1.48l-.388-.23-4.02 1.054 1.072-3.92-.253-.402a10.6 10.6 0 0 1-1.626-5.662c0-5.867 4.776-10.64 10.65-10.64 2.842 0 5.513 1.108 7.524 3.12a10.56 10.56 0 0 1 3.116 7.526c0 5.867-4.776 10.638-10.64 10.638zm5.835-7.97c-.32-.16-1.892-.933-2.185-1.04-.293-.107-.507-.16-.72.16-.213.32-.826 1.04-1.013 1.253-.187.213-.373.24-.693.08-.32-.16-1.35-.498-2.572-1.587-.95-.848-1.592-1.895-1.779-2.215-.187-.32-.02-.493.14-.652.144-.144.32-.373.48-.56.16-.187.213-.32.32-.533.107-.213.053-.4-.027-.56-.08-.16-.72-1.735-.987-2.375-.26-.624-.524-.54-.72-.55l-.613-.01c-.213 0-.56.08-.853.4-.293.32-1.12 1.094-1.12 2.668 0 1.574 1.146 3.094 1.306 3.307.16.213 2.256 3.444 5.466 4.83.764.33 1.36.527 1.825.674.767.244 1.464.21 2.016.127.615-.092 1.892-.773 2.158-1.52.267-.747.267-1.387.187-1.52-.08-.133-.293-.213-.613-.373z"/>
        </svg>
        <span class="ind-whatsapp-float__text">{{ \App\CPU\translate('Chat on WhatsApp') }}</span>
    </a>

@endsection

@push('script')
    {{-- Owl Carousel --}}

    <script src="{{asset('public/assets/front-end')}}/js/owl.carousel.min.js"></script>
    <script>
        $('#flash-deal-slider').owlCarousel({
            loop: false,
            autoplay: false,
            margin: 5,
            nav: true,
            navText: ["<i class='czi-arrow-left'></i>", "<i class='czi-arrow-right'></i>"],
            dots: false,
            autoplayHoverPause: true,
            '{{session('direction')}}': false,
            // center: true,
            responsive: {
                //X-Small
                0: {
                    items: 1
                },
                360: {
                    items: 1
                },
                375: {
                    items: 1
                },
                540: {
                    items: 2
                },
                //Small
                576: {
                    items: 2
                },
                //Medium
                768: {
                    items: 2
                },
                //Large
                992: {
                    items: 2
                },
                //Extra large
                1200: {
                    items: 2
                },
                //Extra extra large
                1400: {
                    items: 3
                }
            }
        })

        $('#web-feature-deal-slider').owlCarousel({
            loop: false,
            autoplay: true,
            margin: 5,
            nav: false,
            //navText: ["<i class='czi-arrow-left'></i>", "<i class='czi-arrow-right'></i>"],
            dots: false,
            autoplayHoverPause: true,
            '{{session('direction')}}': true,
            // center: true,
            responsive: {
                //X-Small
                0: {
                    items: 1
                },
                360: {
                    items: 1
                },
                375: {
                    items: 1
                },
                540: {
                    items: 2
                },
                //Small
                576: {
                    items: 2
                },
                //Medium
                768: {
                    items: 2
                },
                //Large
                992: {
                    items: 2
                },
                //Extra large
                1200: {
                    items: 2
                },
                //Extra extra large
                1400: {
                    items: 2
                }
            }
        })

        $('#new-arrivals-product').owlCarousel({
            loop: true,
            autoplay: false,
            margin: 5,
            nav: true,
            navText: ["<i class='czi-arrow-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}'></i>", "<i class='czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}'></i>"],
            dots: false,
            autoplayHoverPause: true,
            '{{session('direction')}}': true,
            // center: true,
            responsive: {
                //X-Small
                0: {
                    items: 1
                },
                360: {
                    items: 1
                },
                375: {
                    items: 1
                },
                540: {
                    items: 2
                },
                //Small
                576: {
                    items: 2
                },
                //Medium
                768: {
                    items: 2
                },
                //Large
                992: {
                    items: 2
                },
                //Extra large
                1200: {
                    items: 4
                },
                //Extra extra large
                1400: {
                    items: 4
                }
            }
        })
    </script>
<script>
    /* Featured products is now a static Bootstrap grid (no carousel). */
</script>
    <script>
        $('#brands-slider').owlCarousel({
            loop: true,
            autoplay: true,
            margin: 10,
            nav: false,
            '{{session('direction')}}': true,
            //navText: ["<i class='czi-arrow-left'></i>","<i class='czi-arrow-right'></i>"],
            dots: false,
            autoplayHoverPause: true,
            center: true,
            responsive: {
                //X-Small
                0: {
                    items: 2
                },
                360: {
                    items: 3
                },
                375: {
                    items: 3
                },
                540: {
                    items: 4
                },
                //Small
                576: {
                    slideBy: 1,
                    items: 5
                },
                //Medium
                768: {
                    slideBy: 1,
                    items: 5
                },
                //Large
                992: {
                    slideBy: 1,
                    items: 6
                },
                //Extra large
                1200: {
                    slideBy: 1,
                    items: 7
                },
                //Extra extra large
                1400: {
                    slideBy: 1,
                    items: 8
                }
            }
        })
    </script>

    <script>
        $('#category-slider, #top-seller-slider').owlCarousel({
            loop: false,
            autoplay: false,
            margin: 5,
            nav: false,
            // navText: ["<i class='czi-arrow-left'></i>","<i class='czi-arrow-right'></i>"],
            dots: true,
            autoplayHoverPause: true,
            '{{session('direction')}}': true,
            // center: true,
            responsive: {
                //X-Small
                0: {
                    items: 2
                },
                360: {
                    items: 3
                },
                375: {
                    items: 3
                },
                540: {
                    items: 4
                },
                //Small
                576: {
                    items: 5
                },
                //Medium
                768: {
                    items: 6
                },
                //Large
                992: {
                    items: 8
                },
                //Extra large
                1200: {
                    items: 10
                },
                //Extra extra large
                1400: {
                    items: 11
                }
            }
        })
    </script>
@endpush

