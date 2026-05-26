<!-- Footer -->
<footer class="page-footer ind-footer">
    <div class="ind-footer-main">
        <div class="container">
            <div class="row">

                {{-- Company Info --}}
                <div class="col-lg-3 col-md-6 ind-footer-col">
                    <a class="d-inline-block mb-3" href="{{route('home')}}">
                        <img class="ind-footer-logo"
                             src="{{asset("storage/app/public/company")}}/{{ $web_config['footer_logo']->value }}"
                             onerror="this.src='{{asset('public/assets/front-end/img/image-place-holder.png')}}'"
                             alt="{{ $web_config['name']->value }}"/>
                    </a>
                    <p class="ind-footer-desc">Fidus India Automation Pvt. Ltd. is an industrial automation and MRO sourcing company, helping businesses procure trusted industrial products and solutions.</p>
                    <ul class="ind-footer-contact">
                        <li>
                            <i class="czi-location"></i>
                            <span>{{ \App\CPU\Helpers::get_business_settings('shop_address') }}</span>
                        </li>
                        <li>
                            <i class="czi-phone"></i>
                            <a href="tel:{{\App\CPU\Helpers::get_business_settings('company_phone')}}">{{\App\CPU\Helpers::get_business_settings('company_phone')}}</a>
                        </li>
                        <li>
                            <i class="czi-mail"></i>
                            <a href="mailto:{{\App\CPU\Helpers::get_business_settings('company_email')}}">{{\App\CPU\Helpers::get_business_settings('company_email')}}</a>
                        </li>
                    </ul>
                </div>

                {{-- Quick Links --}}
                <div class="col-lg-2 col-md-6 col-6 ind-footer-col">
                    <h6 class="footer-heder">{{\App\CPU\translate('Quick Links')}}</h6>
                    <ul class="ind-footer-list">
                        <li><a class="ind-footer-link" href="{{route('products',['data_from'=>'latest','page'=>1])}}">{{\App\CPU\translate('latest_products')}}</a></li>
                        <li><a class="ind-footer-link" href="{{route('products',['data_from'=>'best-selling','page'=>1])}}">{{\App\CPU\translate('best_selling_product')}}</a></li>
                        <li><a class="ind-footer-link" href="{{route('products',['data_from'=>'top-rated','page'=>1])}}">{{\App\CPU\translate('top_rated_product')}}</a></li>
                        <li><a class="ind-footer-link" href="{{route('shipping-policy')}}">{{\App\CPU\translate('shipping & Return')}}</a></li>
                    </ul>
                </div>

                {{-- Account & Orders --}}
                <div class="col-lg-2 col-md-6 col-6 ind-footer-col">
                    <h6 class="footer-heder">{{\App\CPU\translate('Account & Orders')}}</h6>
                    <ul class="ind-footer-list">
                        @if(auth('customer')->check())
                            <li><a class="ind-footer-link" href="{{route('user-account')}}">{{\App\CPU\translate('profile_info')}}</a></li>
                            <li><a class="ind-footer-link" href="{{route('wishlists')}}">{{\App\CPU\translate('wish_list')}}</a></li>
                            <li><a class="ind-footer-link" href="{{route('track-order.index')}}">{{\App\CPU\translate('track_order')}}</a></li>
                            <li><a class="ind-footer-link" href="{{ route('account-address') }}">{{\App\CPU\translate('address')}}</a></li>
                        @else
                            <li><a class="ind-footer-link" href="{{route('customer.auth.login')}}">{{\App\CPU\translate('profile_info')}}</a></li>
                            <li><a class="ind-footer-link" href="{{route('customer.auth.login')}}">{{\App\CPU\translate('wish_list')}}</a></li>
                            <li><a class="ind-footer-link" href="{{route('track-order.index')}}">{{\App\CPU\translate('track_order')}}</a></li>
                            <li><a class="ind-footer-link" href="{{route('customer.auth.login')}}">{{\App\CPU\translate('address')}}</a></li>
                        @endif
                    </ul>
                </div>

                {{-- Customer Support --}}
                <div class="col-lg-2 col-md-6 col-6 ind-footer-col">
                    <h6 class="footer-heder">{{\App\CPU\translate('Customer Support')}}</h6>
                    <ul class="ind-footer-list">
                        <li><a class="ind-footer-link" href="{{route('contacts')}}">{{\App\CPU\translate('Contact Us')}}</a></li>
                        <li><a class="ind-footer-link" href="{{route('helpTopic')}}">{{\App\CPU\translate('FAQ')}}</a></li>
                        @if(auth('customer')->check())
                            <li><a class="ind-footer-link" href="{{route('account-tickets')}}">{{\App\CPU\translate('Support Ticket')}}</a></li>
                        @else
                            <li><a class="ind-footer-link" href="{{route('customer.auth.login')}}">{{\App\CPU\translate('Support Ticket')}}</a></li>
                        @endif
                        <li><a class="ind-footer-link" href="{{route('contacts')}}">{{\App\CPU\translate('Request a Quotation')}}</a></li>
                        <li><a class="ind-footer-link" href="{{route('about-us')}}">{{\App\CPU\translate('About Company')}}</a></li>
                    </ul>
                </div>

                {{-- Newsletter --}}
                <div class="col-lg-3 col-md-6 ind-footer-col">
                    <h6 class="footer-heder">{{\App\CPU\translate('Newsletter')}}</h6>
                    <p class="ind-footer-desc">{{\App\CPU\translate('Get product updates, offers, and sourcing support.')}}</p>
                    <form action="{{ route('subscription') }}" method="post" class="ind-newsletter-form">
                        @csrf
                        <input type="email" name="subscription_email" class="form-control" required
                               placeholder="{{\App\CPU\translate('Your Email Address')}}"
                               style="text-align: {{Session::get('direction') === "rtl" ? 'right' : 'left'}};">
                        <button class="btn btn-accent btn-block mt-2" type="submit">{{\App\CPU\translate('subscribe')}}</button>
                    </form>

                    @php($ios = \App\CPU\Helpers::get_business_settings('download_app_apple_stroe'))
                    @php($android = \App\CPU\Helpers::get_business_settings('download_app_google_stroe'))
                    @if($ios['status'] || $android['status'])
                        <div class="ind-footer-apps mt-3">
                            @if($ios['status'])
                                <a href="{{ $ios['link'] }}"><img src="{{asset("public/assets/front-end/png/apple_app.png")}}" alt=""></a>
                            @endif
                            @if($android['status'])
                                <a href="{{ $android['link'] }}"><img src="{{asset("public/assets/front-end/png/google_app.png")}}" alt=""></a>
                            @endif
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="ind-footer-bottom">
        <div class="container">
            <div class="end-footer">
                <div class="ind-footer-copy">
                    <p class="mb-0">{!! $web_config['copyright_text']->value !!}</p>
                </div>
                <div class="ind-footer-social">
                    @php($social_media = \App\Model\SocialMedia::where('active_status', 1)->get())
                    @foreach ($social_media as $item)
                        <span class="social-media">
                            <a class="social-btn sb-{{$item->name}}" target="_blank" href="{{$item->link}}">
                                <i class="{{$item->icon}}" aria-hidden="true"></i>
                            </a>
                        </span>
                    @endforeach
                </div>
                <div class="ind-footer-legal">
                    <a class="ind-footer-link" href="{{route('terms')}}">{{\App\CPU\translate('terms_&_conditions')}}</a>
                    <a class="ind-footer-link" href="{{route('privacy-policy')}}">{{\App\CPU\translate('privacy_policy')}}</a>
                </div>
            </div>
        </div>
    </div>
</footer>
