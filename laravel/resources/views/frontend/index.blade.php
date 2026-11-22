@extends('frontend.layouts.app')





@push('preload')

    @php $firstSlider = (json_decode(get_setting('home_slider_images'), true) ?: [])[0] ?? null; @endphp

    @if($firstSlider)

        <link rel="preload" as="image" href="{{ \App\Services\OptimizedImages::asset($firstSlider) }}" fetchpriority="high" imagesrcset="{{ \App\Services\OptimizedImages::srcset($firstSlider) }}" imagesizes="(max-width:767px) 100vw,960px">

    @endif

@endpush

@section('content')

    <link rel="stylesheet" href="{{ static_asset('assets/css/home-refresh.css') }}?v=2">

    <section class="home-banner-area home-refresh">

      <div class="container"><div class="home-refresh-grid {{ $todays_deal_products->isEmpty() ? 'without-today' : '' }}">

        <aside class="home-refresh-categories d-none d-lg-block">@include('frontend.partials.category_menu')</aside>

        <div class="home-refresh-banner">

          @php

            $sliderImages=json_decode(get_setting('home_slider_images'),true) ?: [];

            $sliderLinks=json_decode(get_setting('home_slider_links'),true) ?: [];

          @endphp

          @if(count($sliderImages))

          <div class="aiz-carousel dots-inside-bottom" data-arrows="true" data-dots="true" data-autoplay="true">

            @foreach($sliderImages as $image)

              <div class="carousel-box"><a href="{{ $sliderLinks[$loop->index] ?? route('search') }}"><img src="{{ \App\Services\OptimizedImages::asset($image) }}" srcset="{{ \App\Services\OptimizedImages::srcset($image) }}" sizes="(max-width:767px) 100vw,800px" alt="Footstep collection" loading="{{ $loop->first?'eager':'lazy' }}" fetchpriority="{{ $loop->first?'high':'low' }}" decoding="async"></a></div>

            @endforeach

          </div>

          @else

          <a class="home-refresh-fallback" href="{{ route('search') }}"><span>FOOTSTEP ONLINE</span><h1>আপনার পরের পছন্দের জুতা</h1><strong>Explore products →</strong></a>

          @endif

        </div>

        @include('frontend.partials.today_deal_rotation',['todayProducts'=>$todays_deal_products,'todayCompact'=>true])

      </div></div>

    </section>



    {{-- Banner section 1 --}}

  @if (get_setting('home_banner1_images') != null)

    <div class="mb-4">

        <div class="container">

            <div class="row gutters-10">

                @php $banner_1_imags = json_decode(get_setting('home_banner1_images')); @endphp

                @foreach ($banner_1_imags as $key => $value)

                    <div class="col-xl col-md-3 col-sm-12">

                    

                        <div class="mb-3 mb-lg-0">

                            <a href="{{ json_decode(get_setting('home_banner1_links'), true)[$key] }}" class="d-block text-reset">

                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}" data-src="{{ \App\Services\OptimizedImages::asset($banner_1_imags[$key]) }}" alt="{{ env('APP_NAME') }} promo" class="img-fluid lazyload w-100" data-srcset="{{ \App\Services\OptimizedImages::srcset($banner_1_imags[$key]) }}" sizes="(max-width:767px) 100vw,960px" decoding="async">

                            </a>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

    @endif 







     {{-- Featured Product --}}

    <div id="section_best_sellers" style="min-height: 240px" aria-busy="true">



    </div>

    

      {{-- Banner Section 2 --}}

    @if (get_setting('home_banner2_images') != null)

    <div class="mb-4">

        <div class="container">

            <div class="row gutters-10">

                @php $banner_2_imags = json_decode(get_setting('home_banner2_images')); @endphp

                @foreach ($banner_2_imags as $key => $value)

                    <div class="col-xl col-12 col-md-6">

                        <div class="mb-3 mb-lg-0">

                            <a href="{{ json_decode(get_setting('home_banner2_links'), true)[$key] }}" class="d-block text-reset">

                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}" data-src="{{ \App\Services\OptimizedImages::asset($banner_2_imags[$key]) }}" alt="{{ env('APP_NAME') }} promo" class="img-fluid lazyload w-100" data-srcset="{{ \App\Services\OptimizedImages::srcset($banner_2_imags[$key]) }}" sizes="(max-width:767px) 100vw,960px" decoding="async">

                            </a>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

    @endif

        {{-- Category wise Products --}}

    <div id="section_home_categories" style="min-height: 240px" aria-busy="true">



    </div>

 {{-- Best Selling  --}}

    <div id="section_best_selling" style="min-height: 240px" aria-busy="true">



    </div>

    

   



    {{--All Products --}}

    <div id="section_featured" style="min-height: 240px" aria-busy="true">



    </div>

    

    



   



    

    @if(addon_is_activated('auction'))

        <div id="auction_products" style="min-height: 240px" aria-busy="true">



        </div>

    @endif

    

   





  



 

    

        {{-- Best sellers --}}

    <div id="section_feature_product" style="min-height: 240px" aria-busy="true">



    </div>



    



    {{-- Classified Product --}}

    @if(get_setting('classified_product') == 1)

        @php

            $classified_products = \App\Models\CustomerProduct::where('status', '1')->where('published', '1')->take(10)->get();

        @endphp

           @if (count($classified_products) > 0)

               <section class="mb-4">

                   <div class="container">

                       <div class="px-2 py-4 px-md-4 py-md-3 bg-white shadow-sm rounded">

                            <div class="d-flex mb-3 align-items-baseline border-bottom">

                                <h3 class="h5 fw-700 mb-0">-->

                                    <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ translate('Classified Ads') }}</span>-->

                                    

                                </h3>

                                <a href="{{ route('customer.products') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">{{ translate('View More') }}</a>

                            </div>

                           <div class="aiz-carousel gutters-10 half-outside-arrow" data-items="6" data-xl-items="5" data-lg-items="4"  data-md-items="3" data-sm-items="2" data-xs-items="2" data-arrows='true'>

                               @foreach ($classified_products as $key => $classified_product)

                                   <div class="carousel-box">

                                        <div class="aiz-card-box border border-light rounded hov-shadow-md my-2 has-transition">

                                            <div class="position-relative">

                                                <a href="{{ route('customer.product', $classified_product->slug) }}" class="d-block">

                                                    <img

                                                        class="img-fit lazyload mx-auto h-140px h-md-210px"

                                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"

                                                        data-src="{{ \App\Services\OptimizedImages::asset($classified_product->thumbnail_img) }}"

                                                        alt="{{ $classified_product->getTranslation('name') }}"

                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"

                                                     data-srcset="{{ \App\Services\OptimizedImages::srcset($classified_product->thumbnail_img) }}" sizes="(max-width:767px) 100vw,960px" decoding="async">

                                                </a>

                                                <div class="absolute-top-left pt-2 pl-2">

                                                    @if($classified_product->conditon == 'new')

                                                       <span class="badge badge-inline badge-success">{{translate('new')}}</span>

                                                    @elseif($classified_product->conditon == 'used')

                                                       <span class="badge badge-inline badge-danger">{{translate('Used')}}</span>

                                                    @endif

                                                </div>

                                            </div>

                                            <div class="p-md-3 p-2 text-left">

                                                <div class="fs-15 mb-1">

                                                    <span class="fw-700 text-primary">{{ single_price($classified_product->unit_price) }}</span>

                                                </div>

                                                <h3 class="fw-600 fs-13 text-truncate-2 lh-1-4 mb-0 h-35px">-->

                                                    <a href="{{ route('customer.product', $classified_product->slug) }}" class="d-block text-reset">{{ $classified_product->getTranslation('name') }}</a>-->

                                                </h3>

                                            </div>

                                       </div>

                                   </div>

                               @endforeach

                           </div>

                       </div>

                   </div>

               </section>

           @endif

       @endif



    {{-- Banner Section 2 --}}

    @if (get_setting('home_banner3_images') != null)

    <div class="mb-4">

        <div class="container">

            <div class="row gutters-10">

                @php $banner_3_imags = json_decode(get_setting('home_banner3_images')); @endphp

                @foreach ($banner_3_imags as $key => $value)

                    <div class="col-xl col-md-6">

                        <div class="mb-3 mb-lg-0">

                            <a href="{{ json_decode(get_setting('home_banner3_links'), true)[$key] }}" class="d-block text-reset">

                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}" data-src="{{ \App\Services\OptimizedImages::asset($banner_3_imags[$key]) }}" alt="{{ env('APP_NAME') }} promo" class="img-fluid lazyload w-100" data-srcset="{{ \App\Services\OptimizedImages::srcset($banner_3_imags[$key]) }}" sizes="(max-width:767px) 100vw,960px" decoding="async">

                            </a>

                        </div>

                    </div>

                @endforeach

            </div>

        </div>

    </div>

    @endif

    

    

    



   

    

    

   



    {{-- Top 10 categories and Brands --}}

    @if (get_setting('top10_categories') != null && get_setting('top10_brands') != null)

    <section class="mb-4">

        <div class="container">

            <div class="row gutters-10">

                @if (get_setting('top10_categories') != null)

                    <div class="col-lg-6">

                        <div class="d-flex mb-3 align-items-baseline border-bottom">

                            <h3 class="h5 fw-700 mb-0">

                                <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ translate('Top 10 Categories') }}</span>

                            </h3>

                            <a href="{{ route('categories.all') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">{{ translate('View All Categories') }}</a>

                        </div>

                        <div class="row gutters-5">

                            @php $top10_categories = json_decode(get_setting('top10_categories')); @endphp

                            @foreach ($top10_categories as $key => $value)

                                @php $category = \App\Services\StorefrontData::categories()->get($value); @endphp

                                @if ($category != null)

                                    <div class="col-sm-6">

                                        <a href="{{ route('products.category', $category->slug) }}" class="bg-white border d-block text-reset rounded p-2 hov-shadow-md mb-2">

                                            <div class="row align-items-center no-gutters">

                                                <div class="col-3 text-center">

                                                    <img

                                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"

                                                        data-src="{{ \App\Services\OptimizedImages::asset($category->banner) }}"

                                                        alt="{{ $category->getTranslation('name') }}"

                                                        class="img-fluid img lazyload h-60px"

                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"

                                                     data-srcset="{{ \App\Services\OptimizedImages::srcset($category->banner) }}" sizes="(max-width:767px) 100vw,960px" decoding="async">

                                                </div>

                                                <div class="col-7">

                                                    <div class="text-truncat-2 pl-3 fs-14 fw-600 text-left">{{ $category->getTranslation('name') }}</div>

                                                </div>

                                                <div class="col-2 text-center">

                                                    <i class="la la-angle-right text-primary"></i>

                                                </div>

                                            </div>

                                        </a>

                                    </div>

                                @endif

                            @endforeach

                        </div>

                    </div>

                @endif

                @if (get_setting('top10_brands') != null)

                    <div class="col-lg-6">

                        <div class="d-flex mb-3 align-items-baseline border-bottom">

                            <h3 class="h5 fw-700 mb-0">

                                <span class="border-bottom border-primary border-width-2 pb-3 d-inline-block">{{ translate('Top 10 Brands') }}</span>

                            </h3>

                            <a href="{{ route('brands.all') }}" class="ml-auto mr-0 btn btn-primary btn-sm shadow-md">{{ translate('View All Brands') }}</a>

                        </div>

                        <div class="row gutters-5">

                            @php $top10_brands = json_decode(get_setting('top10_brands')); @endphp

                            @foreach ($top10_brands as $key => $value)

                                @php $brand = \App\Services\StorefrontData::brands()->get($value); @endphp

                                @if ($brand != null)

                                    <div class="col-sm-6">

                                        <a href="{{ route('products.brand', $brand->slug) }}" class="bg-white border d-block text-reset rounded p-2 hov-shadow-md mb-2">

                                            <div class="row align-items-center no-gutters">

                                                <div class="col-4 text-center">

                                                    <img

                                                        src="{{ static_asset('assets/img/placeholder.jpg') }}"

                                                        data-src="{{ \App\Services\OptimizedImages::asset($brand->logo) }}"

                                                        alt="{{ $brand->getTranslation('name') }}"

                                                        class="img-fluid img lazyload h-60px"

                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"

                                                     data-srcset="{{ \App\Services\OptimizedImages::srcset($brand->logo) }}" sizes="(max-width:767px) 100vw,960px" decoding="async">

                                                </div>

                                                <div class="col-6">

                                                    <div class="text-truncate-2 pl-3 fs-14 fw-600 text-left">{{ $brand->getTranslation('name') }}</div>

                                                </div>

                                                <div class="col-2 text-center">

                                                    <i class="la la-angle-right text-primary"></i>

                                                </div>

                                            </div>

                                        </a>

                                    </div>

                                @endif

                            @endforeach

                        </div>

                    </div>

                @endif

            </div>

        </div>

    </section>

    @endif



@endsection



@section('script')

    <script>

        $(function () {

            var sections = {

                section_featured: '{{ route('home.section.featured') }}',

                section_best_selling: '{{ route('home.section.best_selling') }}',

                auction_products: '{{ route('home.section.auction_products') }}',

                section_home_categories: '{{ route('home.section.home_categories') }}',

                section_best_sellers: '{{ route('home.section.featuredss') }}',

                section_feature_product: '{{ route('home.section.best_sellers') }}'

            };

            function loadSection(el) {

                $.post(sections[el.id], {_token: '{{ csrf_token() }}'})

                    .done(function (html) {

                        $(el).html(html);

                        AIZ.plugins.slickCarousel();

                    }).always(function () {

                        $(el).css('min-height', '').attr('aria-busy', 'false');

                    });

            }

            var observer = 'IntersectionObserver' in window ? new IntersectionObserver(function (entries) {

                entries.forEach(function (entry) {

                    if (entry.isIntersecting) {

                        observer.unobserve(entry.target);

                        loadSection(entry.target);

                    }

                });

            }, {rootMargin: '250px'}) : null;

            Object.keys(sections).forEach(function (id) {

                var el = document.getElementById(id);

                if (el) { if (observer) observer.observe(el); else loadSection(el); }

            });

        });

    </script>

@endsection

