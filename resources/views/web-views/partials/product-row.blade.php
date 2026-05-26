{{-- Reusable horizontal product row (Best Sellings / Top Rated).
     Expects: $product, $decimal_point_settings. Price formatting untouched. --}}
@php($pr_rating = \App\CPU\ProductManager::get_overall_rating($product->reviews))

<a href="{{route('product',$product->slug)}}" class="ind-product-row">
    <div class="ind-product-row-img">
        @if($product->discount > 0)
            <span class="ind-row-badge">@if($product->discount_type == 'percent'){{round($product->discount)}}%@elseif($product->discount_type=='flat'){{\App\CPU\Helpers::currency_converter($product->discount)}}@endif {{\App\CPU\translate('off')}}</span>
        @endif
        <img src="{{\App\CPU\ProductManager::product_image_path('thumbnail')}}/{{$product['thumbnail']}}"
             onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'" alt="{{$product['name']}}">
    </div>
    <div class="ind-product-row-body">
        <div class="ind-product-row-title">{{Str::limit($product['name'],55)}}</div>
        <div class="rating-show mt-1">
            @if($product->reviews_count > 0)
                <span class="d-inline-block font-size-sm text-body">
                    @for($inc=0;$inc<5;$inc++)@if($inc<$pr_rating[0])<i class="sr-star czi-star-filled active"></i>@else<i class="sr-star czi-star" style="color:#cbd5e1 !important"></i>@endif @endfor
                    <span class="text-muted" style="font-size:12px;">( {{$product->reviews_count}} )</span>
                </span>
            @else
                <span class="ind-no-reviews">{{\App\CPU\translate('No reviews yet')}}</span>
            @endif
        </div>
        <div class="ind-product-row-price mt-1">
            @if($product->unit_price > 0)
                @if($product->discount > 0)<strike>{{\App\CPU\Helpers::currency_converter($product->unit_price)}}</strike>@endif
                <span class="ind-feature-price">{{\App\CPU\Helpers::currency_converter($product->unit_price-(\App\CPU\Helpers::get_product_discount($product,$product->unit_price)))}}</span>
            @else
                <span class="ind-feature-quote">{{\App\CPU\translate('Request Quote')}}</span>
            @endif
        </div>
    </div>
</a>
