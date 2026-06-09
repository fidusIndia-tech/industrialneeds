@extends('layouts.front-end.app')

@section('title',$product['name'])

@php
    // ---- SEO data: real meta description, canonical and JSON-LD structured data ----
    $companyName = $web_config['name']->value ?? config('app.name');
    $brandName = optional($product->brand)->name;
    $productUrl = route('product', [$product->slug]);

    // SEO keywords: name + MPN + brand. Built in PHP (not inline Blade @if) because
    // adjacent directives like @endif@if don't compile (Blade's \B@ word-boundary rule).
    $keywordParts = [$product->name];
    if (!empty($product->product_code)) { $keywordParts[] = $product->product_code; }
    if ($brandName) { $keywordParts[] = $brandName; }
    $metaKeywords = implode(', ', $keywordParts);

    // Templated meta description (<=160 chars) — never the slug.
    if (!empty($product->meta_description)) {
        $metaDescription = \Illuminate\Support\Str::limit(trim(strip_tags($product->meta_description)), 158);
    } else {
        $descParts = [$product->name];
        if (!empty($product->product_code)) { $descParts[] = 'MPN ' . $product->product_code; }
        if ($brandName) { $descParts[] = 'by ' . $brandName; }
        $metaDescription = \Illuminate\Support\Str::limit(trim(implode(' ', $descParts)) . ' — buy online at ' . $companyName . '.', 158);
    }

    // Offer + rating figures.
    $ldImage = $product->meta_image
        ? asset('storage/app/public/product/meta') . '/' . $product->meta_image
        : asset('storage/app/public/product/thumbnail') . '/' . $product->thumbnail;
    $ldPrice = round((float) $product->unit_price, 2);
    try { $ldCurrency = \App\CPU\Helpers::currency_code(); } catch (\Throwable $e) { $ldCurrency = 'INR'; }
    if (empty($ldCurrency)) { $ldCurrency = 'INR'; }
    $ldAvailability = ($product->current_stock > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
    $ldRating = \App\CPU\ProductManager::get_overall_rating($product->reviews); // [avg, count]
    $ldRatingAvg = isset($ldRating[0]) ? round((float) $ldRating[0], 1) : 0;
    $ldRatingCount = isset($ldRating[1]) ? (int) $ldRating[1] : 0;

    $productLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'description' => $metaDescription,
        'image' => $ldImage,
        'url' => $productUrl,
    ];
    if (!empty($product->product_code)) {
        $productLd['sku'] = $product->product_code;
        $productLd['mpn'] = $product->product_code;
    }
    if ($brandName) {
        $productLd['brand'] = ['@type' => 'Brand', 'name' => $brandName];
    }
    if ($ldPrice > 0) {
        $productLd['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => $ldCurrency,
            'price' => $ldPrice,
            'availability' => $ldAvailability,
            'url' => $productUrl,
        ];
    }
    if ($ldRatingCount > 0 && $ldRatingAvg > 0) {
        $productLd['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $ldRatingAvg,
            'reviewCount' => $ldRatingCount,
        ];
    }

    // Breadcrumb: Home › [top-level category] › product (category_ids is JSON, so guard it).
    $crumbs = [['name' => \App\CPU\translate('Home'), 'item' => route('home')]];
    foreach ((json_decode($product->category_ids, true) ?: []) as $c) {
        if (isset($c['position'], $c['id']) && $c['position'] == 1) {
            $catModel = \App\Model\Category::select('id', 'name')->find($c['id']);
            if ($catModel) {
                $crumbs[] = ['name' => $catModel->name, 'item' => url('/products?id=' . $catModel->id . '&data_from=category&page=1')];
            }
            break;
        }
    }
    $crumbs[] = ['name' => $product->name, 'item' => $productUrl];
    $breadcrumbItems = [];
    foreach ($crumbs as $i => $crumb) {
        $breadcrumbItems[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $crumb['name'], 'item' => $crumb['item']];
    }
    $breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $breadcrumbItems];
@endphp
@section('meta_description', $metaDescription)
@section('canonical', $productUrl)

@push('css_or_js')
    {{-- SEO: structured data for rich results (price, availability, brand, MPN, rating + breadcrumb). json_encode keeps slashes escaped so product text can't break out of the script tag. --}}
    <script type="application/ld+json">{!! json_encode($productLd, JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_UNICODE) !!}</script>
    <meta name="keywords" content="{{ $metaKeywords }}">
    @if($product->added_by=='seller')
        <meta name="author" content="{{ $product->seller->shop?$product->seller->shop->name:$product->seller->f_name}}">
    @elseif($product->added_by=='admin')
        <meta name="author" content="{{$web_config['name']->value}}">
    @endif
    <!-- Viewport-->

    @if($product['meta_image']!=null)
        <meta property="og:image" content="{{asset("storage/app/public/product/meta")}}/{{$product->meta_image}}"/>
        <meta property="twitter:card"
              content="{{asset("storage/app/public/product/meta")}}/{{$product->meta_image}}"/>
    @else
        <meta property="og:image" content="{{asset("storage/app/public/product/thumbnail")}}/{{$product->thumbnail}}"/>
        <meta property="twitter:card"
              content="{{asset("storage/app/public/product/thumbnail/")}}/{{$product->thumbnail}}"/>
    @endif

    @if($product['meta_title']!=null)
        <meta property="og:title" content="{{$product->meta_title}}"/>
        <meta property="twitter:title" content="{{$product->meta_title}}"/>
    @else
        <meta property="og:title" content="{{$product->name}}"/>
        <meta property="twitter:title" content="{{$product->name}}"/>
    @endif
    <meta property="og:url" content="{{route('product',[$product->slug])}}">

    @if($product['meta_description']!=null)
        <meta property="twitter:description" content="{!! $product['meta_description'] !!}">
        <meta property="og:description" content="{!! $product['meta_description'] !!}">
    @else
        <meta property="og:description"
              content="@foreach(explode(' ',$product['name']) as $keyword) {{$keyword.' , '}} @endforeach">
        <meta property="twitter:description"
              content="@foreach(explode(' ',$product['name']) as $keyword) {{$keyword.' , '}} @endforeach">
    @endif
    <meta property="twitter:url" content="{{route('product',[$product->slug])}}">

    <link rel="stylesheet" href="{{asset('public/assets/front-end/css/product-details.css')}}"/>
    <style>
        .msg-option {
            display: none;
        }

        .chatInputBox {
            width: 100%;
        }

        .go-to-chatbox {
            width: 100%;
            text-align: center;
            padding: 5px 0px;
            display: none;
        }

        .feature_header {
            display: flex;
            justify-content: center;
        }

        .btn-number:hover {
            color: {{$web_config['secondary_color']}};

        }

        .for-total-price {
            margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: -30%;
        }

        .feature_header span {
            padding- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 15px;
            font-weight: 700;
            font-size: 25px;
            background-color: #ffffff;
            text-transform: uppercase;
        }

        .flash-deals-background-image{
            background: {{$web_config['primary_color']}}10;
            border-radius:5px;
            width:125px;
            height:125px;
        }

        @media (max-width: 768px) {
            .feature_header span {
                margin-bottom: -40px;
            }

            .for-total-price {
                padding- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 30%;
            }

            .product-quantity {
                padding- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 4%;
            }

            .for-margin-bnt-mobile {
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: 7px;
            }

            .font-for-tab {
                font-size: 11px !important;
            }

            .pro {
                font-size: 13px;
            }
        }

        @media (max-width: 375px) {
            .for-margin-bnt-mobile {
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: 3px;
            }

            .for-discount {
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 10% !important;
            }

            .for-dicount-div {
                margin-top: -5%;
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: -7%;
            }

            .product-quantity {
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 4%;
            }

        }

        @media (max-width: 500px) {
            .for-dicount-div {
                margin-top: -4%;
                margin- {{Session::get('direction') === "rtl" ? 'left' : 'right'}}: -5%;
            }

            .for-total-price {
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: -20%;
            }

            .view-btn-div {

                margin-top: -9%;
                float: {{Session::get('direction') === "rtl" ? 'left' : 'right'}};
            }

            .for-discount {
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 7%;
            }

            .viw-btn-a {
                font-size: 10px;
                font-weight: 600;
            }

            .feature_header span {
                margin-bottom: -7px;
            }

            .for-mobile-capacity {
                margin- {{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 7%;
            }
        }
    </style>
    <style>
        th, td {
            border-bottom: 1px solid #ddd;
            padding: 5px;
        }

        thead {
            background: {{$web_config['primary_color']}} !important;
            color: white;
        }
        .product-details-shipping-details{
            background: #ffffff;
            border-radius: 5px;
            font-size: 14;
            font-weight: 400;
            color: #212629;
        }
        .shipping-details-bottom-border{
            border-bottom: 1px #F9F9F9 solid;
        }
    </style>
@endpush

@section('content')
    <?php
    $overallRating = \App\CPU\ProductManager::get_overall_rating($product->reviews);
    $rating = \App\CPU\ProductManager::get_rating($product->reviews);
    $decimal_point_settings = \App\CPU\Helpers::get_business_settings('decimal_point_settings');

    // --- Product detail fallback data (safe, dynamic only) ---
    $detail_brand_name = $product->brand->name ?? null;
    $detail_category_name = null;
    $detail_cats = json_decode($product['category_ids']);
    if (isset($detail_cats[0]) && isset($detail_cats[0]->id)) {
        $detail_category = \App\Model\Category::find($detail_cats[0]->id);
        $detail_category_name = $detail_category->name ?? null;
    }
    $detail_has_description = trim(strip_tags((string) ($product['details'] ?? ''))) !== '';
    $detail_review_count = count($product->reviews);

    // Build the professional fallback overview from available dynamic data only.
    $detail_fallback = '<strong>' . e($product->name) . '</strong>';
    if ($detail_brand_name) {
        $detail_fallback .= ' ' . \App\CPU\translate('is an industrial product from') . ' <strong>' . e($detail_brand_name) . '</strong>';
    } else {
        $detail_fallback .= ' ' . \App\CPU\translate('is an industrial product');
    }
    if ($detail_category_name) {
        $detail_fallback .= ', ' . \App\CPU\translate('listed under') . ' <strong>' . e($detail_category_name) . '</strong>';
    }
    if ($product->product_code) {
        $detail_fallback .= ' (' . \App\CPU\translate('Part No.') . ' ' . e($product->product_code) . ')';
    }
    $detail_fallback .= '. ' . \App\CPU\translate('It is available through FIAPL for B2B procurement, industrial maintenance, automation, electrical, and MRO sourcing requirements. For technical specifications, availability, and bulk pricing, customers can request a quotation.');

    // Build a technical-specs table from genuinely-available structured data only (no invented specs).
    $detail_specs = [];
    if (!empty($product->product_code)) {
        $detail_specs[\App\CPU\translate('Part Number')] = $product->product_code;
    }
    if ($detail_brand_name) {
        $detail_specs[\App\CPU\translate('Brand')] = $detail_brand_name;
    }
    if (!empty($product->unit)) {
        $detail_specs[\App\CPU\translate('Unit')] = $product->unit;
    }
    if (!empty($product->minimum_order_qty) && $product->minimum_order_qty > 0) {
        $detail_specs[\App\CPU\translate('Minimum Order Quantity')] = $product->minimum_order_qty;
    }
    $detail_choices = json_decode($product->choice_options);
    if (is_array($detail_choices)) {
        foreach ($detail_choices as $detail_choice) {
            if (isset($detail_choice->title) && !empty($detail_choice->options)) {
                $detail_specs[$detail_choice->title] = implode(', ', $detail_choice->options);
            }
        }
    }
    $detail_has_specs = count($detail_specs) > 0;
    ?>
    <!-- Page Content-->
    <div class="container mt-4 rtl" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <?php $breadcrumb_cat = json_decode($product['category_ids']); ?>
        <nav class="ind-detail-breadcrumb">
            <a href="{{route('home')}}">{{\App\CPU\translate('Home')}}</a>
            <span class="mx-1">/</span>
            @if(isset($breadcrumb_cat[0]) && isset($breadcrumb_cat[0]->id))
                <a href="{{route('products',['id'=> $breadcrumb_cat[0]->id,'data_from'=>'category','page'=>1])}}">{{\App\CPU\translate('Products')}}</a>
                <span class="mx-1">/</span>
            @endif
            <span>{{ Str::limit($product->name, 60) }}</span>
        </nav>
        <!-- General info tab-->
        <div class="row ind-top-row" style="direction: ltr">
            <!-- LEFT: Product image gallery -->
            <div class="col-lg-4 col-md-5 col-12 ind-col-image">
                        @php
                            // Build the list of displayable image URLs for the detail gallery.
                            // Imported products often only have the thumbnail saved (product/thumbnail/<file>)
                            // while the gallery copy (product/<file>) may be missing on the live server,
                            // which is why the listing image worked but the detail image was broken.
                            // Resolve each gallery file to a path that actually exists on disk, and always
                            // guarantee the proven-working listing thumbnail is available as a fallback.
                            $thumbDir    = \App\CPU\ProductManager::product_image_path('thumbnail');
                            $productDir  = \App\CPU\ProductManager::product_image_path('product');
                            $placeholder = asset('public/assets/front-end/img/image-place-holder.png');

                            $detailImages = [];
                            $gallery = $product->images ? json_decode($product->images, true) : [];
                            if (is_array($gallery)) {
                                foreach ($gallery as $photo) {
                                    if (!$photo || $photo === 'def.png') { continue; }
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists('product/'.$photo)) {
                                        $detailImages[] = $productDir.'/'.$photo;            // real gallery image
                                    } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists('product/thumbnail/'.$photo)) {
                                        $detailImages[] = $thumbDir.'/'.$photo;              // gallery copy missing -> reuse the listing thumbnail
                                    }
                                }
                            }
                            // No gallery resolved: fall back to the same thumbnail the listing/search card uses.
                            if (empty($detailImages) && !empty($product->thumbnail) && $product->thumbnail !== 'def.png') {
                                $detailImages[] = $thumbDir.'/'.$product->thumbnail;
                            }
                            // Genuinely no image anywhere: show the placeholder.
                            if (empty($detailImages)) {
                                $detailImages[] = $placeholder;
                            }
                            $detailImages = array_values(array_unique($detailImages));
                        @endphp
                        <div class="ind-gallery-card">
                        <div class="cz-product-gallery">
                            <div class="cz-preview">
                                @foreach ($detailImages as $key => $photoUrl)
                                    <div
                                        class="cz-preview-item d-flex align-items-center justify-content-center {{$key==0?'active':''}}"
                                        id="image{{$key}}">
                                        <img class="cz-image-zoom img-responsive" style="width:100%;max-height:323px;"
                                            onerror="this.src='{{$placeholder}}'"
                                            src="{{$photoUrl}}"
                                            data-zoom="{{$photoUrl}}"
                                            alt="Product image" width="">
                                        <div class="cz-image-zoom-pane"></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="cz">
                                <div>
                                    <div class="row">
                                        <div class="table-responsive" data-simplebar style="max-height: 515px; padding: 1px;">
                                            <div class="d-flex" style="padding-left: 3px;">
                                                @if(count($detailImages) > 1)
                                                    @foreach ($detailImages as $key => $photoUrl)
                                                        <div class="cz-thumblist">
                                                            <a class="cz-thumblist-item  {{$key==0?'active':''}} d-flex align-items-center justify-content-center "
                                                            href="#image{{$key}}">
                                                                <img
                                                                    onerror="this.src='{{$placeholder}}'"
                                                                    src="{{$photoUrl}}"
                                                                    alt="Product thumb">
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <!-- Trust / assurance badges under the image -->
                        <div class="ind-assurance">
                            <div class="ind-assurance-item">
                                <img src="{{asset('public/assets/front-end/png/Payment.png')}}" alt="">
                                <span>{{\App\CPU\translate('Safe Payment')}}</span>
                            </div>
                            <div class="ind-assurance-item">
                                <img src="{{asset('public/assets/front-end/png/money.png')}}" alt="">
                                <span>{{\App\CPU\translate('7 Days Return Policy')}}</span>
                            </div>
                            <div class="ind-assurance-item">
                                <img src="{{asset('public/assets/front-end/png/Genuine.png')}}" alt="">
                                <span>{{\App\CPU\translate('100% Authentic Products')}}</span>
                            </div>
                        </div>
            </div>
            <!-- MIDDLE: Product information -->
            <div class="col-lg-5 col-md-7 col-12 ind-col-info" style="direction: {{ Session::get('direction') }}">
                <div class="details">
                            <h1 class="ind-pd-title mb-2">{{$product->name}}</h1>
                            @if($product->product_code)
                                <div class="ind-pd-partno mb-2">
                                    <span>{{\App\CPU\translate('Part Number')}}:</span> {{ $product->product_code }}
                                </div>
                            @endif
                            <div class="d-flex align-items-center mb-2 pro ind-meta-row">
                                @if($detail_review_count > 0)
                                <span
                                    class="d-inline-block  align-middle mt-1 {{Session::get('direction') === "rtl" ? 'ml-md-2 ml-sm-0 pl-2' : 'mr-md-2 mr-sm-0 pr-2'}}"
                                    style="color: #FE961C">{{$overallRating[0]}}</span>
                                <div class="star-rating" style="{{Session::get('direction') === "rtl" ? 'margin-left: 25px;' : 'margin-right: 25px;'}}">
                                    @for($inc=0;$inc<5;$inc++)
                                        @if($inc<$overallRating[0])
                                            <i class="sr-star czi-star-filled active"></i>
                                        @else
                                            <i class="sr-star czi-star"></i>
                                        @endif
                                    @endfor
                                </div>
                                <span style="font-weight: 400;"
                                    class="font-for-tab d-inline-block font-size-sm text-body align-middle mt-1 {{Session::get('direction') === "rtl" ? 'mr-1 ml-md-2 ml-1 pr-md-2 pr-sm-1 pl-md-2 pl-sm-1' : 'ml-1 mr-md-2 mr-1 pl-md-2 pl-sm-1 pr-md-2 pr-sm-1'}}">{{$overallRating[1]}} {{\App\CPU\translate('Reviews')}}</span>
                                @else
                                <span class="ind-no-review-inline align-middle mt-1 {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}">{{\App\CPU\translate('No reviews yet')}}</span>
                                @endif
                                <span style="width: 0px;height: 10px;border: 0.5px solid #707070; margin-top: 6px;font-weight: 400 !important;"></span>
                                <span style="font-weight: 400;"
                                    class="font-for-tab d-inline-block font-size-sm text-body align-middle mt-1 {{Session::get('direction') === "rtl" ? 'mr-1 ml-md-2 ml-1 pr-md-2 pr-sm-1 pl-md-2 pl-sm-1' : 'ml-1 mr-md-2 mr-1 pl-md-2 pl-sm-1 pr-md-2 pr-sm-1'}}">{{$countOrder}} {{\App\CPU\translate('orders')}}   </span>
                                <span style="width: 0px;height: 10px;border: 0.5px solid #707070; margin-top: 6px;font-weight: 400;">    </span>
                                <span style="font-weight: 400;"
                                    class=" font-for-tab d-inline-block font-size-sm text-body align-middle mt-1 {{Session::get('direction') === "rtl" ? 'mr-1 ml-md-2 ml-0 pr-md-2 pr-sm-1 pl-md-2 pl-sm-1' : 'ml-1 mr-md-2 mr-0 pl-md-2 pl-sm-1 pr-md-2 pr-sm-1'}} text-capitalize">  {{$countWishlist}} {{\App\CPU\translate('wish_listed')}} </span>

                            </div>
                </div>
                <div class="ind-pd-stock mb-3">
                    @if($product->current_stock > 0)
                        <span class="ind-stock-in">&#10003; {{\App\CPU\translate('In Stock')}}</span>
                    @else
                        <span class="ind-stock-out">{{\App\CPU\translate('Out of Stock')}}</span>
                    @endif
                    @if(!empty($product->minimum_order_qty) && $product->minimum_order_qty > 0)
                        <span class="ind-moq">{{\App\CPU\translate('Min. order')}}: {{ $product->minimum_order_qty }}</span>
                    @endif
                </div>

                <!-- Key Features (dynamic attributes only) -->
                <div class="ind-keyfeatures">
                    <div class="ind-section-subtitle">{{\App\CPU\translate('Key Features')}}</div>
                    @if($detail_has_specs)
                        <table class="ind-keyfeatures-table">
                            @foreach($detail_specs as $kf_name => $kf_value)
                                <tr>
                                    <td>{{ $kf_name }}</td>
                                    <td>{{ $kf_value }}</td>
                                </tr>
                            @endforeach
                        </table>
                    @else
                        <p class="text-muted mb-0">{{\App\CPU\translate('Detailed specifications are available on request.')}}</p>
                    @endif
                </div>

                <!-- Business Benefits -->
                <div class="ind-benefits">
                    <div class="ind-section-subtitle">{{\App\CPU\translate('Business Benefits')}}</div>
                    <ul class="ind-benefits-list">
                        <li>{{\App\CPU\translate('GST Invoice Available')}}</li>
                        <li>{{\App\CPU\translate('Bulk Order Support')}}</li>
                        <li>{{\App\CPU\translate('Genuine Industrial Products')}}</li>
                        <li>{{\App\CPU\translate('Secure Payment')}}</li>
                        <li>{{\App\CPU\translate('Fast Dispatch')}}</li>
                        <li>{{\App\CPU\translate('Quote Assistance')}}</li>
                    </ul>
                </div>
            </div>
            <!-- RIGHT: Buy / Quote card -->
            <div class="col-lg-3 col-md-12 col-12 ind-col-buy">
                <div class="ind-buy-card">
                        @if($product->unit_price > 0)
                            <div class="mb-3">
                                @if($product->discount > 0)
                                    <strike style="color: #E96A6A;" class="{{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-3'}}">
                                        {{\App\CPU\Helpers::currency_converter($product->unit_price)}}
                                    </strike>
                                @endif
                                <span class="ind-pd-price">
                                    {{\App\CPU\Helpers::get_price_range($product) }}
                                </span> <small class="ind-pd-unit">/ per {{$product->unit}}</small>
                                <span class="{{Session::get('direction') === "rtl" ? 'mr-2' : 'ml-2'}}"
                                    style="font-size: 12px;font-weight:400">
                                    (<span>{{\App\CPU\translate('tax')}} : </span>
                                    <span id="set-tax-amount"></span>)
                                </span>
                            </div>
                            <form id="add-to-cart-form" class="mb-2">
                                @csrf
                                <input type="hidden" name="id" value="{{ $product->id }}">
                                <div class="position-relative {{Session::get('direction') === "rtl" ? 'ml-n4' : 'mr-n4'}} mb-2">
                                    @if (count(json_decode($product->colors)) > 0)
                                        <div class="flex-start">
                                            <div class="product-description-label mt-2 text-body">{{\App\CPU\translate('color')}}:
                                            </div>
                                            <div>
                                                <ul class="list-inline checkbox-color mb-1 flex-start {{Session::get('direction') === "rtl" ? 'mr-2' : 'ml-2'}}"
                                                    style="padding-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 0;">
                                                    @foreach (json_decode($product->colors) as $key => $color)
                                                        <div>
                                                            <li>
                                                                <input type="radio"
                                                                    id="{{ $product->id }}-color-{{ $key }}"
                                                                    name="color" value="{{ $color }}"
                                                                    @if($key == 0) checked @endif>
                                                                <label style="background: {{ $color }};"
                                                                    for="{{ $product->id }}-color-{{ $key }}"
                                                                    data-toggle="tooltip"></label>
                                                            </li>
                                                        </div>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>
                                    @endif
                                    @php
                                        $qty = 0;
                                        if(!empty($product->variation)){
                                        foreach (json_decode($product->variation) as $key => $variation) {
                                                $qty += $variation->qty;
                                            }
                                        }
                                    @endphp
                                </div>
                                @foreach (json_decode($product->choice_options) as $key => $choice)
                                    <div class="row flex-start mx-0">
                                        <div
                                            class="product-description-label text-body mt-2 {{Session::get('direction') === "rtl" ? 'pl-2' : 'pr-2'}}">{{ $choice->title }}
                                            :
                                        </div>
                                        <div>
                                            <ul class="list-inline checkbox-alphanumeric checkbox-alphanumeric--style-1 mb-2 mx-1 flex-start row"
                                                style="padding-{{Session::get('direction') === "rtl" ? 'right' : 'left'}}: 0;">
                                                @foreach ($choice->options as $key => $option)
                                                    <div>
                                                        <li class="for-mobile-capacity">
                                                            <input type="radio"
                                                                id="{{ $choice->name }}-{{ $option }}"
                                                                name="{{ $choice->name }}" value="{{ $option }}"
                                                                @if($key == 0) checked @endif >
                                                            <label style="font-size: 12px;"
                                                                for="{{ $choice->name }}-{{ $option }}">{{ $option }}</label>
                                                        </li>
                                                    </div>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                            @endforeach

                            <!-- Quantity + Add to cart -->
                                <div class="row no-gutters">
                                    <div>
                                        <div class="product-description-label text-body" style="margin-top: 10px;">{{\App\CPU\translate('Quantity')}}:</div>
                                    </div>
                                    <div >
                                        <div class="product-quantity d-flex justify-content-between align-items-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center"
                                                style="width: 160px;color: {{$web_config['primary_color']}}">
                                                <span class="input-group-btn" style="">
                                                    <button class="btn btn-number" type="button"
                                                            data-type="minus" data-field="quantity"
                                                            disabled="disabled" style="padding: 10px;color: {{$web_config['primary_color']}}">
                                                        -
                                                    </button>
                                                </span>
                                                <input type="text" name="quantity"
                                                    class="form-control input-number text-center cart-qty-field"
                                                    placeholder="1" value="{{ $product->minimum_order_qty ?? 1 }}" min="{{ $product->minimum_order_qty ?? 1 }}" max="100"
                                                    style="padding: 0px !important;width: 40%;height: 25px;">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-number" type="button" data-type="plus"
                                                            data-field="quantity" style="padding: 10px;color: {{$web_config['primary_color']}}">
                                                    +
                                                    </button>
                                                </span>
                                            </div>
                                            <div class="float-right"  id="chosen_price_div">
                                                <div class="d-flex justify-content-center align-items-center {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}">
                                                    <div class="product-description-label"><strong>{{\App\CPU\translate('total_price')}}</strong> : </div>
                                                    &nbsp; <strong id="chosen_price"></strong>
                                                </div>

                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="row flex-start no-gutters d-none mt-2">


                                    <div class="col-12">
                                        @if($product['current_stock']<=0)
                                            <h5 class="mt-3 text-body" style="color: red">{{\App\CPU\translate('out_of_stock')}}</h5>
                                        @endif
                                    </div>
                                </div>

                                <div class="d-flex justify-content-start mt-2 mb-3">
                                    <button
                                        class="btn element-center btn-gap-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}"
                                        onclick="buy_now()"
                                        type="button"
                                        style="width:37%; height: 46px; background: #FFC400 !important; color: #082A45; font-weight:700; border-radius:8px;">
                                        <span class="string-limit">{{\App\CPU\translate('buy_now')}}</span>
                                    </button>
                                    <button
                                        class="btn element-center btn-gap-{{Session::get('direction') === "rtl" ? 'left' : 'right'}}"
                                        onclick="addToCart()"
                                        type="button"
                                        style="width:37%; height: 46px; background: #082A45 !important; color: #ffffff; font-weight:700; border-radius:8px;{{Session::get('direction') === "rtl" ? 'margin-right: 16px;' : 'margin-left: 16px;'}}">
                                        <span class="string-limit">{{\App\CPU\translate('add_to_cart')}}</span>
                                    </button>
                                    <button type="button" onclick="addWishlist('{{$product['id']}}')"
                                            class="btn for-hover-bg"
                                            style="color:{{$web_config['secondary_color']}};font-size: 18px;">
                                        <i class="fa fa-heart-o "
                                        aria-hidden="true"></i>
                                        <span class="countWishlist-{{$product['id']}}">{{$countWishlist}}</span>
                                    </button>
                                </div>
                            </form>
                        @else
                            {{-- Enquiry-only product: no published price, so no cart/checkout. --}}
                            <div class="mb-3">
                                <span class="ind-pd-price">{{\App\CPU\translate('Price on Request')}}</span>
                                <small class="ind-pd-unit d-block mt-1" style="font-weight:400;">{{\App\CPU\translate('Contact us for a quotation and bulk pricing.')}}</small>
                            </div>
                        @endif
                            <!-- INQUIRY BUTTON -->
                            <div class="mt-3 mb-4">
                                <button type="button" class="btn btn-block ind-pd-quote-btn" data-toggle="modal" data-target="#inquiryModal">
                                    <i class="fa fa-envelope {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}"></i> {{\App\CPU\translate('Request a Quote / Inquire')}}
                                </button>
                                @if($product->unit_price <= 0 || is_null($product->unit_price))
                                    @php($wa_number = preg_replace('/\D/', '', \App\CPU\Helpers::get_business_settings('company_phone') ?? ''))
                                    @if($wa_number !== '')
                                        @php($wa_text = rawurlencode(\App\CPU\translate('Hello, I would like a quote for').': '.$product->name.($product->product_code ? ' ('.\App\CPU\translate('Part No').': '.$product->product_code.')' : '')))
                                        <a href="https://wa.me/{{ $wa_number }}?text={{ $wa_text }}" target="_blank" rel="noopener"
                                           class="btn btn-block mt-2" style="background:#25D366;color:#ffffff;font-weight:700;">
                                            <i class="fa fa-whatsapp {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}"></i>{{\App\CPU\translate('WhatsApp Enquiry')}}
                                        </a>
                                    @endif
                                @endif
                            </div>
                    <div class="ind-buy-expert">
                        <i class="fa fa-headphones {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}"></i>
                        {{\App\CPU\translate('Need bulk pricing?')}}
                        <a href="{{route('contacts')}}">{{\App\CPU\translate('Call a product expert')}}</a>
                    </div>
                            <div style="text-align:{{Session::get('direction') === "rtl" ? 'right' : 'left'}};"
                                class="sharethis-inline-share-buttons"></div>
                </div>
            </div>
            <!-- FULL-WIDTH: Trust badges -->
            <div class="col-12 ind-trust-row">
                <div class="ind-trust-badges">
                    <div class="ind-trust-badge"><span class="ind-trust-ic">&#10003;</span> {{\App\CPU\translate('Genuine Products')}}</div>
                    <div class="ind-trust-badge"><span class="ind-trust-ic">&#10003;</span> {{\App\CPU\translate('GST Invoice')}}</div>
                    <div class="ind-trust-badge"><span class="ind-trust-ic">&#10003;</span> {{\App\CPU\translate('Secure Payments')}}</div>
                    <div class="ind-trust-badge"><span class="ind-trust-ic">&#10003;</span> {{\App\CPU\translate('Bulk Pricing')}}</div>
                    <div class="ind-trust-badge"><span class="ind-trust-ic">&#10003;</span> {{\App\CPU\translate('Fast Dispatch')}}</div>
                    <div class="ind-trust-badge"><span class="ind-trust-ic">&#10003;</span> {{\App\CPU\translate('Support Available')}}</div>
                </div>
            </div>
            <!-- Store / seller info -->
            <div class="col-12 ind-col-store">
                <div style="background: #ffffff; padding: 25px;border-radius: 5px;
                    font-weight: 400;color: #212629;margin-top: 0;">
                    {{--seller section--}}
                    @if($product->added_by=='seller')
                        @if(isset($product->seller->shop))
                            <div class="row d-flex justify-content-between">
                                <div class="col-8">
                                    <div class="row d-flex ">
                                        <div>
                                            <img style="height: 65px; width: 65px; border-radius: 50%"
                                                src="{{asset('storage/app/public/shop')}}/{{$product->seller->shop->image}}"
                                                onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                                alt="">
                                        </div>
                                        <div class="{{Session::get('direction') === "rtl" ? 'right' : 'ml-3'}}">
                                            <span style="font-weight: 700;font-size: 16px;">
                                                {{$product->seller->shop->name}}
                                            </span><br>
                                            <span>{{\App\CPU\translate('Seller_info')}}</span>
                                        </div>
                                    </div>

                                </div>
                                <div class="col-4">
                                    @if (auth('customer')->id() == '')
                                    <a href="{{route('customer.auth.login')}}">
                                        <div class="float-left" style="color:{{$web_config['primary_color']}};background: {{$web_config['primary_color']}}10;padding: 6px 15px 6px 15px;font-size:12px;">
                                            <i class="fa fa-envelope"></i>
                                        <span>{{\App\CPU\translate('chat')}}</span>
                                        </div>
                                        </a>
                                    @else
                                        <div id="contact-seller" style="color:{{$web_config['primary_color']}};background: {{$web_config['primary_color']}}10;padding: 6px 15px 6px 15px;font-size:12px;">
                                                <i class="fa fa-envelope"></i>
                                            <span>{{\App\CPU\translate('chat')}}</span>
                                            </div>
                                    @endif

                                </div>
                                <div class="col-12 msg-option mt-2" id="msg-option">

                                        <form action="">
                                        <input type="text" class="seller_id" hidden seller-id="{{$product->seller->id }}">
                                        <textarea shop-id="{{$product->seller->shop->id}}" class="chatInputBox"
                                                id="chatInputBox" rows="5"> </textarea>


                                        <div class="row">
                                            <button class="btn btn-secondary" style="color: white;display: block;width: 47%;margin: 3px;"
                                                id="cancelBtn">{{\App\CPU\translate('cancel')}}
                                            </button>
                                            <button class="btn btn-success " style="color: white;display: block;width: 47%;margin: 3px;"
                                                id="sendBtn">{{\App\CPU\translate('send')}}</button>
                                        </div>

                                    </form>

                                </div>

                                @php($products_for_review = App\Model\Product::where('added_by',$product->added_by)->where('user_id',$product->user_id)->withCount('reviews')->get())

                                <?php
                                $total_reviews = 0;
                                    foreach ($products_for_review as $item)
                                       { $total_reviews += $item->reviews_count;
                                       }
                                ?>
                                <div class="col-12 mt-2">
                                    <div class="row d-flex justify-content-between">
                                        <div class="col-6 ">
                                            <div class="d-flex justify-content-center align-items-center" style="height: 79px;background:{{$web_config['primary_color']}}10;border-radius:5px;">
                                                <div class="text-center">
                                                    <span style="color: {{$web_config['primary_color']}};font-weight: 700;
                                                    font-size: 26px;">
                                                    {{$total_reviews}}
                                                    </span><br>
                                                    <span style="font-size: 12px;">
                                                        {{\App\CPU\translate('reviews')}}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="d-flex justify-content-center align-items-center" style="height: 79px;background:{{$web_config['primary_color']}}10;border-radius:5px;">
                                                <div class="text-center">
                                                    <span style="color: {{$web_config['primary_color']}};font-weight: 700;
                                                    font-size: 26px;">
                                                        {{$products_for_review->count()}}
                                                    </span><br>
                                                    <span style="font-size: 12px;">
                                                        {{\App\CPU\translate('products')}}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div>
                                        <a href="{{ route('shopView',[$product->seller->id]) }}" style="display: block;width:100%;text-align: center">
                                            <button class="btn" style="display: block;width:100%;text-align: center;background: {{$web_config['primary_color']}};color:#ffffff">
                                                <i class="fa fa-shopping-bag" aria-hidden="true"></i>
                                                {{\App\CPU\translate('Visit Store')}}
                                            </button>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="row d-flex justify-content-between">
                            <div class="col-9 ">
                                <div class="row d-flex ">
                                    <div>
                                        <img style="height: 65px; width: 65px; border-radius: 50%"
                                            src="{{asset("storage/app/public/company")}}/{{$web_config['fav_icon']->value}}"
                                            onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                            alt="">
                                    </div>
                                    <div class="{{Session::get('direction') === "rtl" ? 'right' : 'ml-3'}}">
                                        <span style="font-weight: 700;font-size: 16px;">
                                            {{$web_config['name']->value}}
                                        </span><br>
                                    </div>
                                </div>

                            </div>

                            @php($products_for_review = App\Model\Product::where('added_by','admin')->where('user_id',$product->user_id)->withCount('reviews')->get())

                            <?php
                            $total_reviews = 0;
                                foreach ($products_for_review as $item)
                                   { $total_reviews += $item->reviews_count;
                                   }
                            ?>
                            <div class="col-12 mt-2">
                                <div class="row d-flex justify-content-between">
                                    <div class="col-6 ">
                                        <div class="d-flex justify-content-center align-items-center" style="height: 79px;background:{{$web_config['primary_color']}}10;border-radius:5px;">
                                            <div class="text-center">
                                                <span style="color: {{$web_config['primary_color']}};font-weight: 700;
                                                font-size: 26px;">
                                                    {{$total_reviews}}
                                                </span><br>
                                                <span style="font-size: 12px;">
                                                    {{\App\CPU\translate('reviews')}}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex justify-content-center align-items-center" style="height: 79px;background:{{$web_config['primary_color']}}10;border-radius:5px;">
                                            <div class="text-center">
                                                <span style="color: {{$web_config['primary_color']}};font-weight: 700;
                                                font-size: 26px;">
                                                    {{$products_for_review->count()}}
                                                </span><br>
                                                <span style="font-size: 12px;">
                                                    {{\App\CPU\translate('products')}}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div class="row">
                                    <a href="{{ route('shopView',[0]) }}" style="display: block;width:100%;text-align: center">
                                    <button class="btn" style="display: block;width:100%;text-align: center;background: {{$web_config['primary_color']}};color:#ffffff">
                                        <i class="fa fa-shopping-bag" aria-hidden="true"></i>
                                        {{\App\CPU\translate('Visit Store')}}
                                    </button>
                                </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="row ind-overview-row">
                <!-- FULL-WIDTH ROW (below top section): Overview / Reviews tabs -->
                <div class="col-12 ind-col-tabs">
                    <div class="mt-4 rtl col-12" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                        <div class="row" >
                            <div class="col-12">
                                <div class=" mt-1">
                                    <!-- Tabs-->
                                    <ul class="nav nav-tabs ind-detail-tabs d-flex justify-content-center" role="tablist" style="margin-top:35px;">
                                        <li class="nav-item">
                                            <a class="nav-link active " href="#overview" data-toggle="tab" role="tab"
                                            style="color: black !important;font-weight: 400;font-size: 24px;">
                                                {{\App\CPU\translate('overview')}}
                                            </a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="#reviews" data-toggle="tab" role="tab"
                                            style="color: black !important;font-weight: 400;font-size: 24px;">
                                                {{\App\CPU\translate('reviews')}}
                                            </a>
                                        </li>
                                    </ul>
                                    <div class="px-4 pt-lg-3 pb-3 mb-3 mr-0 mr-md-2 ind-detail-tab-card" style="background: #ffffff;border-radius:10px;">
                                        <div class="tab-content px-lg-3">
                                            <!-- Tech specs tab-->
                                            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                                @if($product->video_url!=null)
                                                    <div class="row pt-2">
                                                        <div class="col-12 mb-4">
                                                            <iframe width="420" height="315"
                                                                    src="{{$product->video_url}}">
                                                            </iframe>
                                                        </div>
                                                    </div>
                                                @endif
                                                <div class="row pt-2 specification">
                                                    <!-- Overview + Technical specifications -->
                                                    <div class="col-lg-8 col-md-7 col-12">
                                                        <h5 class="ind-section-title">{{\App\CPU\translate('Product Overview')}}</h5>
                                                        @if($detail_has_description)
                                                            <div class="text-body ind-overview-content">
                                                                {!! $product['details'] !!}
                                                            </div>
                                                        @else
                                                            <p class="text-body ind-overview-fallback">
                                                                {!! $detail_fallback !!}
                                                            </p>
                                                        @endif

                                                        <h5 class="ind-section-title mt-4">{{\App\CPU\translate('Technical Specifications')}}</h5>
                                                        @if($detail_has_specs)
                                                            <table class="ind-specs-table">
                                                                <thead>
                                                                    <tr>
                                                                        <th>{{\App\CPU\translate('Specification')}}</th>
                                                                        <th>{{\App\CPU\translate('Value')}}</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($detail_specs as $detail_spec_name => $detail_spec_value)
                                                                        <tr>
                                                                            <td>{{ $detail_spec_name }}</td>
                                                                            <td>{{ $detail_spec_value }}</td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                            <p class="ind-specs-note">{{\App\CPU\translate('For complete datasheets and additional specifications, please request a quotation.')}}</p>
                                                        @else
                                                            <p class="text-body mb-2">{{\App\CPU\translate('Technical specifications are available on request.')}}</p>
                                                        @endif
                                                        <button type="button" class="btn ind-req-quote-btn" data-toggle="modal" data-target="#inquiryModal">
                                                            <i class="fa fa-file-text-o {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}"></i>{{\App\CPU\translate('Request Quote')}}
                                                        </button>

                                                        <h5 class="ind-section-title mt-4">{{\App\CPU\translate('Shipping & Returns')}}</h5>
                                                        <p class="text-body mb-0">{{\App\CPU\translate('Delivery time and return eligibility may vary by product. Contact support for confirmation.')}}</p>
                                                    </div>

                                                    <!-- Key Information + Why FIAPL -->
                                                    <div class="col-lg-4 col-md-5 col-12 mt-4 mt-md-0">
                                                        <div class="ind-info-card">
                                                            <div class="ind-info-card-head">{{\App\CPU\translate('Key Information')}}</div>
                                                            <table class="ind-info-table">
                                                                @if($detail_brand_name)
                                                                    <tr><td>{{\App\CPU\translate('Brand')}}</td><td>{{ $detail_brand_name }}</td></tr>
                                                                @endif
                                                                @if($detail_category_name)
                                                                    <tr><td>{{\App\CPU\translate('Category')}}</td><td>{{ $detail_category_name }}</td></tr>
                                                                @endif
                                                                @if($product->product_code)
                                                                    <tr><td>{{\App\CPU\translate('Part Number')}}</td><td>{{ $product->product_code }}</td></tr>
                                                                @endif
                                                                <tr><td>{{\App\CPU\translate('Availability')}}</td><td>{{\App\CPU\translate('Request confirmation')}}</td></tr>
                                                                <tr><td>{{\App\CPU\translate('Support')}}</td><td>{{\App\CPU\translate('Bulk order & quotation support available')}}</td></tr>
                                                            </table>
                                                        </div>

                                                        <div class="ind-info-card ind-why-card mt-3">
                                                            <div class="ind-info-card-head">{{\App\CPU\translate('Why source from FIAPL?')}}</div>
                                                            <ul class="ind-why-list">
                                                                <li>{{\App\CPU\translate('Genuine industrial product sourcing')}}</li>
                                                                <li>{{\App\CPU\translate('Quick quotation response')}}</li>
                                                                <li>{{\App\CPU\translate('Support for bulk requirements')}}</li>
                                                                <li>{{\App\CPU\translate('Suitable for B2B procurement')}}</li>
                                                                <li>{{\App\CPU\translate('Pan-India delivery assistance')}}</li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @php($reviews_of_product = App\Model\Review::where('product_id',$product->id)->paginate(2))
                                            <!-- Reviews tab-->
                                            <div class="tab-pane fade" id="reviews" role="tabpanel">
                                                @if($detail_review_count > 0)
                                                <div class="row pt-2 pb-3">
                                                    <div class="col-lg-4 col-md-5 ">
                                                        <div class=" row d-flex justify-content-center align-items-center">
                                                            <div class="col-12 d-flex justify-content-center align-items-center">
                                                                <h2 class="overall_review mb-2" style="font-weight: 500;font-size: 50px;">
                                                                    {{$overallRating[1]}}
                                                                </h2>
                                                            </div>
                                                            <div
                                                                class="d-flex justify-content-center align-items-center star-rating ">
                                                                @if (round($overallRating[0])==5)
                                                                    @for ($i = 0; $i < 5; $i++)
                                                                        <i class="czi-star-filled font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                @endif
                                                                @if (round($overallRating[0])==4)
                                                                    @for ($i = 0; $i < 4; $i++)
                                                                        <i class="czi-star-filled font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                    <i class="czi-star font-size-sm text-muted {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                @endif
                                                                @if (round($overallRating[0])==3)
                                                                    @for ($i = 0; $i < 3; $i++)
                                                                        <i class="czi-star-filled font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                    @for ($j = 0; $j < 2; $j++)
                                                                        <i class="czi-star font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                @endif
                                                                @if (round($overallRating[0])==2)
                                                                    @for ($i = 0; $i < 2; $i++)
                                                                        <i class="czi-star-filled font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                    @for ($j = 0; $j < 3; $j++)
                                                                        <i class="czi-star font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                @endif
                                                                @if (round($overallRating[0])==1)
                                                                    @for ($i = 0; $i < 4; $i++)
                                                                        <i class="czi-star font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                    <i class="czi-star-filled font-size-sm text-accent {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                @endif
                                                                @if (round($overallRating[0])==0)
                                                                    @for ($i = 0; $i < 5; $i++)
                                                                        <i class="czi-star font-size-sm text-muted {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>
                                                                    @endfor
                                                                @endif
                                                            </div>
                                                            <div class="col-12 d-flex justify-content-center align-items-center mt-2">
                                                                <span class="text-center">
                                                                    {{$reviews_of_product->total()}} {{\App\CPU\translate('ratings')}}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-8 col-md-7 pt-sm-3 pt-md-0" >
                                                        <div class="row d-flex align-items-center mb-2 font-size-sm">
                                                            <div
                                                                class="col-3 text-nowrap "><span
                                                                    class="d-inline-block align-middle text-body">{{\App\CPU\translate('Excellent')}}</span>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="progress text-body" style="height: 5px;">
                                                                    <div class="progress-bar " role="progressbar"
                                                                        style="background-color: {{$web_config['primary_color']}} !important;width: <?php echo $widthRating = ($rating[0] != 0) ? ($rating[0] / $overallRating[1]) * 100 : (0); ?>%;"
                                                                        aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1 text-body">
                                                                <span
                                                                    class=" {{Session::get('direction') === "rtl" ? 'mr-3 float-left' : 'ml-3 float-right'}} ">
                                                                    {{$rating[0]}}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="row d-flex align-items-center mb-2 text-body font-size-sm">
                                                            <div
                                                                class="col-3 text-nowrap "><span
                                                                    class="d-inline-block align-middle ">{{\App\CPU\translate('Good')}}</span>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="progress" style="height: 5px;">
                                                                    <div class="progress-bar" role="progressbar"
                                                                        style="background-color: {{$web_config['primary_color']}} !important;width: <?php echo $widthRating = ($rating[1] != 0) ? ($rating[1] / $overallRating[1]) * 100 : (0); ?>%; background-color: #a7e453;"
                                                                        aria-valuenow="27" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'mr-3 float-left' : 'ml-3 float-right'}}">
                                                                        {{$rating[1]}}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="row d-flex align-items-center mb-2 text-body font-size-sm">
                                                            <div
                                                                class="col-3 text-nowrap"><span
                                                                    class="d-inline-block align-middle ">{{\App\CPU\translate('Average')}}</span>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="progress" style="height: 5px;">
                                                                    <div class="progress-bar" role="progressbar"
                                                                        style="background-color: {{$web_config['primary_color']}} !important;width: <?php echo $widthRating = ($rating[2] != 0) ? ($rating[2] / $overallRating[1]) * 100 : (0); ?>%; background-color: #ffda75;"
                                                                        aria-valuenow="17" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'mr-3 float-left' : 'ml-3 float-right'}}">
                                                                    {{$rating[2]}}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="row d-flex align-items-center mb-2 text-body font-size-sm">
                                                            <div
                                                                class="col-3 text-nowrap "><span
                                                                    class="d-inline-block align-middle">{{\App\CPU\translate('Below Average')}}</span>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="progress" style="height: 5px;">
                                                                    <div class="progress-bar" role="progressbar"
                                                                        style="background-color: {{$web_config['primary_color']}} !important;width: <?php echo $widthRating = ($rating[3] != 0) ? ($rating[3] / $overallRating[1]) * 100 : (0); ?>%; background-color: #fea569;"
                                                                        aria-valuenow="9" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                        class="{{Session::get('direction') === "rtl" ? 'mr-3 float-left' : 'ml-3 float-right'}}">
                                                                    {{$rating[3]}}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="row d-flex align-items-center text-body font-size-sm">
                                                            <div
                                                                class="col-3 text-nowrap"><span
                                                                    class="d-inline-block align-middle ">{{\App\CPU\translate('Poor')}}</span>
                                                            </div>
                                                            <div class="col-8">
                                                                <div class="progress" style="height: 5px;">
                                                                    <div class="progress-bar" role="progressbar"
                                                                        style="background-color: {{$web_config['primary_color']}} !important;backbround-color:{{$web_config['primary_color']}};width: <?php echo $widthRating = ($rating[4] != 0) ? ($rating[4] / $overallRating[1]) * 100 : (0); ?>%;"
                                                                        aria-valuenow="4" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </div>
                                                            <div class="col-1">
                                                                <span
                                                                    class="{{Session::get('direction') === "rtl" ? 'mr-3 float-left' : 'ml-3 float-right'}}">
                                                                        {{$rating[4]}}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row pb-4 mb-3">
                                                    <div style="display: block;width:100%;text-align: center;background: #F3F4F5;border-radius: 5px;padding:5px;">
                                                        <span class="text-capitalize">{{\App\CPU\translate('Product Review')}}</span>
                                                    </div>
                                                </div>
                                                @endif
                                                <div class="row pb-4">
                                                    <div class="col-12" id="product-review-list">
                                                        {{-- @foreach($reviews_of_product as $productReview) --}}
                                                            {{-- @include('web-views.partials.product-reviews',['productRevie'=>$productRevie]) --}}
                                                        {{-- @endforeach --}}
                                                        @if($detail_review_count == 0)
                                                            <div class="ind-no-reviews text-center">
                                                                <i class="czi-star" style="font-size:28px;color:#c7ced6;"></i>
                                                                <p class="mb-0 mt-2" style="color:#6b7785;font-weight:500;">{{\App\CPU\translate('No reviews yet')}}</p>
                                                            </div>
                                                        @endif

                                                    </div>
                                                    @if(count($product->reviews) > 0)
                                                    <div class="col-12">
                                                        <div class="card-footer d-flex justify-content-center align-items-center">
                                                            <button class="btn" style="background: {{$web_config['primary_color']}}; color: #ffffff" onclick="load_review()">{{\App\CPU\translate('view more')}}</button>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </div>

    <!-- Product carousel (You may also like)-->
    <div class="container ind-similar-wrap mb-3 rtl" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <div class="row flex-between">
            <div class="text-capitalize" style="font-weight: 700; font-size: 30px;{{Session::get('direction') === "rtl" ? 'margin-right: 5px;' : 'margin-left: 5px;'}}">
                <span>{{ \App\CPU\translate('similar_products')}}</span>
            </div>

            <div class="view_all d-flex justify-content-center align-items-center">
                <div>
                    @php($category=json_decode($product['category_ids']))
                    <a class="text-capitalize view-all-text" style="color:{{$web_config['primary_color']}} !important;{{Session::get('direction') === "rtl" ? 'margin-left:10px;' : 'margin-right: 8px;'}}"
                       href="{{route('products',['id'=> $category[0]->id,'data_from'=>'category','page'=>1])}}">{{ \App\CPU\translate('view_all')}}
                       <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left-circle mr-1 ml-n1 mt-1 ' : 'right-circle ml-1 mr-n1'}}"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- Grid-->

        <!-- Product-->
        <div class="row mt-4">
            @if (count($relatedProducts)>0)
                @foreach($relatedProducts as $key => $relatedProduct)
                    <div class="col-xl-2 col-sm-3 col-6" style="margin-bottom: 20px;">
                        @include('web-views.partials._single-product',['product'=>$relatedProduct,'decimal_point_settings'=>$decimal_point_settings])
                    </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-danger text-center">{{\App\CPU\translate('similar')}} {{\App\CPU\translate('product_not_available')}}</h6>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

                
    <div class="modal fade rtl" id="show-modal-view" tabindex="-1" role="dialog" aria-labelledby="show-modal-image"
         aria-hidden="true" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-body" style="display: flex;justify-content: center">
                    <button class="btn btn-default"
                            style="border-radius: 50%;margin-top: -25px;position: absolute;{{Session::get('direction') === "rtl" ? 'left' : 'right'}}: -7px;"
                            data-dismiss="modal">
                        <i class="fa fa-close"></i>
                    </button>
                    <img class="element-center" id="attachment-view" src="">
                </div>
            </div>
        </div>
    </div>
    <!-- Inquiry Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1" role="dialog" aria-labelledby="inquiryModalLabel" aria-hidden="true" style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background: {{$web_config['primary_color']}}; color: white;">
                    <h5 class="modal-title" id="inquiryModalLabel" style="color: white;">{{\App\CPU\translate('Inquire about')}}: {{$product->name}}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white; opacity: 1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('product.quote.submit') }}" method="POST">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">

                        <div class="form-group">
                            <label for="quantity">{{\App\CPU\translate('Quantity Required')}} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="quantity"
                                   value="{{ $product->minimum_order_qty ?? 1 }}"
                                   min="{{ $product->minimum_order_qty ?? 1 }}" required>
                            @if(!empty($product->minimum_order_qty) && $product->minimum_order_qty > 1)
                                <small class="text-muted">{{\App\CPU\translate('Minimum order quantity')}}: {{ $product->minimum_order_qty }}</small>
                            @endif
                        </div>
                        <div class="form-group">
                            <label for="customer_name">{{\App\CPU\translate('Your Name')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="customer_name" required placeholder="{{\App\CPU\translate('Enter your full name')}}">
                        </div>
                        <div class="form-group">
                            <label for="phone_number">{{\App\CPU\translate('Phone Number')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone_number" required placeholder="{{\App\CPU\translate('Enter your phone number')}}">
                        </div>
                        <div class="form-group">
                            <label for="email">{{\App\CPU\translate('Email Address')}} <small class="text-muted">({{\App\CPU\translate('Optional')}})</small></label>
                            <input type="email" class="form-control" name="email" placeholder="{{\App\CPU\translate('Enter your email address')}}">
                        </div>
                        <div class="form-group">
                            <label for="message">{{\App\CPU\translate('Message / Requirements')}} <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="message" rows="4" required placeholder="{{\App\CPU\translate('Please specify your quantity, location, or any technical questions...')}}"></textarea>
                        </div>
                        <button type="submit" class="btn btn-block" style="background: {{$web_config['primary_color']}}; color: white; font-weight: bold;">
                            {{\App\CPU\translate('Send Inquiry')}} <i class="fa fa-paper-plane {{Session::get('direction') === "rtl" ? 'mr-2' : 'ml-2'}}"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')

    <script type="text/javascript">
        @if($product->unit_price > 0)
        cartQuantityInitialize();
        getVariantPrice();
        $('#add-to-cart-form input').on('change', function () {
            getVariantPrice();
        });
        @endif

        function showInstaImage(link) {
            $("#attachment-view").attr("src", link);
            $('#show-modal-view').modal('toggle')
        }
    </script>
    <script>
        $( document ).ready(function() {
            load_review();
        });
        let load_review_count = 1;
        function load_review()
        {

            $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });
            $.ajax({
                    type: "post",
                    url: '{{route('review-list-product')}}',
                    data:{
                        product_id:{{$product->id}},
                        offset:load_review_count
                    },
                    success: function (data) {
                        $('#product-review-list').append(data.productReview)
                        if(data.not_empty == 0 && load_review_count>2){
                            toastr.info('{{\App\CPU\translate('no more review remain to load')}}', {
                                CloseButton: true,
                                ProgressBar: true
                            });
                            console.log('iff');
                        }
                    }
                });
                load_review_count++
        }
    </script>

    {{-- Messaging with shop seller --}}
    <script>
        $('#contact-seller').on('click', function (e) {
            // $('#seller_details').css('height', '200px');
            $('#seller_details').animate({'height': '276px'});
            $('#msg-option').css('display', 'block');
        });
        $('#sendBtn').on('click', function (e) {
            e.preventDefault();
            let msgValue = $('#msg-option').find('textarea').val();
            let data = {
                message: msgValue,
                shop_id: $('#msg-option').find('textarea').attr('shop-id'),
                seller_id: $('.msg-option').find('.seller_id').attr('seller-id'),
            }
            if (msgValue != '') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    }
                });

                $.ajax({
                    type: "post",
                    url: '{{route('messages_store')}}',
                    data: data,
                    success: function (respons) {
                        console.log('send successfully');
                    }
                });
                $('#chatInputBox').val('');
                $('#msg-option').css('display', 'none');
                $('#contact-seller').find('.contact').attr('disabled', '');
                $('#seller_details').animate({'height': '125px'});
                $('#go_to_chatbox').css('display', 'block');
            } else {
                console.log('say something');
            }
        });
        $('#cancelBtn').on('click', function (e) {
            e.preventDefault();
            $('#seller_details').animate({'height': '114px'});
            $('#msg-option').css('display', 'none');
        });
    </script>

    <script type="text/javascript"
            src="https://platform-api.sharethis.com/js/sharethis.js#property=5f55f75bde227f0012147049&product=sticky-share-buttons"
            async="async"></script>
@endpush
