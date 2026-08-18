<style>
    .just-padding{
        padding: 15px;
        border: 1px solid #ccccccb3;
        border-bottom-left-radius: 5px;
        border-bottom-right-radius: 5px;
        height: 100%;
        background-color: white;
    }
    .carousel-control-prev, .carousel-control-next{
        width: 7% !important;
    }
</style>

<div class="row rtl">
    <div class="col-lg-3 ind-hero-side d-none d-lg-block">
        <nav class="ind-cat-sidebar">
            <div class="ind-cat-sidebar-head">
                <i class="czi-menu {{Session::get('direction') === "rtl" ? 'ml-2' : 'mr-2'}}"></i>{{\App\CPU\translate('Search by Category')}}
            </div>
            <ul class="ind-cat-sidebar-list">
                @foreach($categories as $category)
                    <li>
                        <a href="{{route('products',['id'=> $category['id'],'data_from'=>'category','page'=>1])}}" title="{{$category['name']}}">
                            <img class="ind-cat-sidebar-ic"
                                 onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                 src="{{asset("storage/app/public/category/$category->icon")}}" alt="{{$category['name']}}">
                            <span>{{$category['name']}}</span>
                            <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ind-cat-sidebar-arrow"></i>
                        </a>
                    </li>
                @endforeach
                <li>
                    <a href="{{route('categories')}}" class="ind-cat-sidebar-all" title="{{\App\CPU\translate('view_all')}}">
                        <span>{{\App\CPU\translate('View All Categories')}}</span>
                        <i class="czi-arrow-{{Session::get('direction') === "rtl" ? 'left' : 'right'}} ind-cat-sidebar-arrow"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <div class="col-lg-9 ind-hero-main col-md-12" style="margin-top: 3px;{{Session::get('direction') === "rtl" ? 'padding-right:10px;' : 'padding-left:10px;'}}">
        @php
            $main_banner = \App\Model\Banner::where('banner_type','Main Banner')->where('published',1)->orderBy('id','desc')->get();
            /* Every slide shares one container, so the container can only take the taller
               mobile ratio once EVERY published banner has mobile artwork. Until then the
               carousel stays 3:1 and any mobile image already uploaded simply waits. */
            $mobile_art = $main_banner->isNotEmpty() && $main_banner->every(function ($b) {
                return !empty($b->photo_mobile);
            });
        @endphp
        <div id="carouselExampleIndicators"
             class="carousel slide home-banner-carousel {{ $mobile_art ? 'has-mobile-art' : '' }}"
             data-ride="carousel">
            <ol class="carousel-indicators">
                @foreach($main_banner as $key=>$banner)
                    <li data-target="#carouselExampleIndicators" data-slide-to="{{$key}}"
                        class="{{$key==0?'active':''}}">
                    </li>
                @endforeach
            </ol>
            <div class="carousel-inner">
                @foreach($main_banner as $key=>$banner)
                    <div class="carousel-item {{$key==0?'active':''}}">
                        <a href="{{$banner['url']}}">
                            <picture>
                                @if(!empty($banner['photo_mobile']))
                                    <source media="(max-width: 767.98px)"
                                            srcset="{{asset('storage/app/public/banner')}}/{{$banner['photo_mobile']}}">
                                @endif
                                <img class="d-block ind-hero-banner-img"
                                     onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                                     src="{{asset('storage/app/public/banner')}}/{{$banner['photo']}}"
                                     alt="">
                            </picture>
                        </a>
                    </div>
                @endforeach
            </div>
            <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button"
               data-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true" ></span>
                <span class="sr-only">{{\App\CPU\translate('Previous')}}</span>
            </a>
            <a class="carousel-control-next" href="#carouselExampleIndicators" role="button"
               data-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="sr-only">{{\App\CPU\translate('Next')}}</span>
            </a>
        </div>

        {{-- B2B support strip — fills the space under the banner, beside the category sidebar --}}
        <div class="ind-hero-support">
            {{-- Page h1. The homepage had none at all, so search engines had no stated
                 subject for the site's most-linked page. Styling is carried entirely by
                 .ind-hero-support-title, so the tag change is not a visual change. --}}
            <h1 class="ind-hero-support-title">{{\App\CPU\translate('Industrial, Electrical & Automation Supplies for Business')}}</h1>
            <div class="ind-hero-support-grid">
                <div class="ind-hero-support-card">
                    <span class="ind-hero-support-ic"><i class="czi-package"></i></span>
                    <div class="ind-hero-support-text">
                        <h3>{{\App\CPU\translate('Bulk Orders')}}</h3>
                        <p>{{\App\CPU\translate('Best pricing for volume requirements')}}</p>
                    </div>
                </div>
                <a href="{{route('contacts')}}" class="ind-hero-support-card">
                    <span class="ind-hero-support-ic"><i class="czi-message"></i></span>
                    <div class="ind-hero-support-text">
                        <h3>{{\App\CPU\translate('Quick Quote')}}</h3>
                        <p>{{\App\CPU\translate('Fast response from our sourcing team')}}</p>
                    </div>
                </a>
                <div class="ind-hero-support-card">
                    <span class="ind-hero-support-ic"><i class="czi-search"></i></span>
                    <div class="ind-hero-support-text">
                        <h3>{{\App\CPU\translate('Hard-to-Find Products')}}</h3>
                        <p>{{\App\CPU\translate('We help source specific industrial items')}}</p>
                    </div>
                </div>
                <div class="ind-hero-support-card">
                    <span class="ind-hero-support-ic"><i class="czi-delivery"></i></span>
                    <div class="ind-hero-support-text">
                        <h3>{{\App\CPU\translate('Global Supply')}}</h3>
                        <p>{{\App\CPU\translate('Reliable delivery support worldwide')}}</p>
                    </div>
                </div>
            </div>
            <div class="ind-hero-support-cta">
                <div class="ind-hero-support-cta-text">
                    <h3>{{\App\CPU\translate('Have a bulk requirement or specific part number?')}}</h3>
                    <p>{{\App\CPU\translate('Send your product list and get quick sourcing support.')}}</p>
                </div>
                <a href="{{route('contacts')}}" class="btn btn-accent">
                    <i class="czi-message {{Session::get('direction') === "rtl" ? 'ml-1' : 'mr-1'}}"></i>{{\App\CPU\translate('Request a Quote')}}
                </a>
            </div>
        </div>
    </div>
    <!-- Banner group-->
</div>


<script>
    $(function () {
        $('.list-group-item').on('click', function () {
            $('.glyphicon', this)
                .toggleClass('glyphicon-chevron-right')
                .toggleClass('glyphicon-chevron-down');
        });
    });
</script>
