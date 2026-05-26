@php($decimal_point_settings = \App\CPU\Helpers::get_business_settings('decimal_point_settings'))
@foreach($products as $product)
    @if(!empty($product['product_id']))
        @php($product=$product->product)
    @endif
    <div class="{{Request::is('products*')?'col-xl-3 col-lg-4 col-md-4 col-6':'col-lg-3 col-md-4 col-sm-4 col-6'}} {{Request::is('shopView*')?'col-xl-3 col-lg-4 col-md-4 col-6':''}} mb-4">
        @if(!empty($product))
            @include('web-views.partials.product-card',['product'=>$product,'decimal_point_settings'=>$decimal_point_settings])
        @endif
    </div>
@endforeach

<div class="col-12">
    <nav class="d-flex justify-content-center pt-2" aria-label="Page navigation"
         id="paginator-ajax">
        {!! $products->links() !!}
    </nav>
</div>
