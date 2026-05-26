{{-- Reusable product card (Featured / Latest / Discounted etc.)
     Expects: $product, $decimal_point_settings
     Price formatting is intentionally left untouched. --}}
@php($pc_rating = \App\CPU\ProductManager::get_overall_rating($product->reviews))

<div class="ind-feature-card">
    <a href="{{route('product',$product->slug)}}" class="product-image-box">
        @if($product->discount > 0)
            <span class="ind-feature-badge">
                @if ($product->discount_type == 'percent')
                    {{round($product->discount,$decimal_point_settings)}}%
                @elseif($product->discount_type =='flat')
                    {{\App\CPU\Helpers::currency_converter($product->discount)}}
                @endif
                {{\App\CPU\translate('off')}}
            </span>
        @endif
        <img src="{{\App\CPU\ProductManager::product_image_path('thumbnail')}}/{{$product['thumbnail']}}"
             onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
             alt="{{ $product['name'] }}">
    </a>

    <div class="product-info">
        <a href="{{route('product',$product->slug)}}" class="product-title">
            {{ Str::limit($product['name'], 45) }}
        </a>

        @if(optional($product->brand)->name)
            <div class="product-meta">{{ $product->brand->name }}</div>
        @endif

        @php($pc_review_count = is_countable($product->reviews) ? count($product->reviews) : ($product->reviews_count ?? 0))
        <div class="rating-show mt-1">
            @if($pc_review_count > 0)
                <span class="d-inline-block font-size-sm text-body">
                    @for($inc=0;$inc<5;$inc++)
                        @if($inc<$pc_rating[0])
                            <i class="sr-star czi-star-filled active"></i>
                        @else
                            <i class="sr-star czi-star" style="color:#cbd5e1 !important"></i>
                        @endif
                    @endfor
                    <span class="text-muted" style="font-size:12px;">( {{$pc_review_count}} )</span>
                </span>
            @else
                <span class="ind-no-reviews">{{\App\CPU\translate('No reviews yet')}}</span>
            @endif
        </div>

        <div class="mt-2">
            @if($product->unit_price > 0)
                <span class="ind-feature-price">
                    @if($product->discount > 0)
                        <strike>{{\App\CPU\Helpers::currency_converter($product->unit_price)}}</strike>
                    @endif
                    {{\App\CPU\Helpers::currency_converter(
                        $product->unit_price-(\App\CPU\Helpers::get_product_discount($product,$product->unit_price))
                    )}}
                </span>
            @else
                <span class="ind-feature-quote">{{\App\CPU\translate('Request Quote')}}</span>
            @endif
        </div>

        <div class="product-action mt-auto">
            <a href="{{route('product',$product->slug)}}">{{\App\CPU\translate('View Details')}}</a>
        </div>
    </div>
</div>
