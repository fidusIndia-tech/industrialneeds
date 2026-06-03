{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ $base.'/' }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
@foreach($categories as $category)
    <url>
        <loc>{{ $base.'/products?id='.$category->id.'&data_from=category&page=1' }}</loc>
@if($category->updated_at)
        <lastmod>{{ $category->updated_at->format('Y-m-d') }}</lastmod>
@endif
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
@endforeach
@foreach($brands as $brand)
    <url>
        <loc>{{ $base.'/products?id='.$brand->id.'&data_from=brand&page=1' }}</loc>
@if($brand->updated_at)
        <lastmod>{{ $brand->updated_at->format('Y-m-d') }}</lastmod>
@endif
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
@endforeach
@foreach($products as $product)
    <url>
        <loc>{{ $base.'/product/'.$product->slug }}</loc>
@if($product->updated_at)
        <lastmod>{{ $product->updated_at->format('Y-m-d') }}</lastmod>
@endif
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
@endforeach
</urlset>
