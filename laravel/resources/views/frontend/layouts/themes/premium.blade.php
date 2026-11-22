<a class="premium-skip" href="#premium-main">Skip to content</a>
<div class="premium-topline"><div class="container"><span>FOOTSTEP <span class="premium-dot">●</span> EVERYDAY MOVEMENT</span><span>Cash on Delivery available</span></div></div>
@include('frontend.inc.nav')
<main id="premium-main" tabindex="-1">
@if(Route::currentRouteName()==='home')
<section class="premium-intro container" aria-labelledby="premium-heading"><div><p class="premium-eyebrow">THE FOOTSTEP EDIT</p><h1 id="premium-heading">Your next step.<br class="d-sm-none"> A better pair.</h1><p class="premium-intro-copy">Discover footwear for your everyday movement.</p></div><a class="premium-browse" href="{{ route('categories.all') }}">Explore collections <span aria-hidden="true">↗</span></a></section>
@endif
@yield('content')
</main>
@if(Route::currentRouteName()==='home')
<section class="container premium-services" aria-label="Shopping with Footstep"><div><i class="las la-box" aria-hidden="true"></i><span><strong>Cash on Delivery</strong><small>Pay when your order arrives</small></span></div><div><i class="las la-shoe-prints" aria-hidden="true"></i><span><strong>Find your next pair</strong><small>Explore styles, sizes and colors</small></span></div><div><i class="las la-receipt" aria-hidden="true"></i><span><strong>Your order, in one place</strong><small>Keep your order details and invoice</small></span></div></section>
@endif
@include('frontend.inc.footer')
