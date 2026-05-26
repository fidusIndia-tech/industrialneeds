{{-- Kept for backward compatibility; delegates to the shared product card. --}}
@include('web-views.partials.product-card', ['product' => $product, 'decimal_point_settings' => $decimal_point_settings])
