@php
    $studioRoute=Route::currentRouteName();
    $studioTypes=['home'=>'home','search'=>'catalog','products.category'=>'catalog','products.brand'=>'catalog','product'=>'product','categories.all'=>'collections','brands.all'=>'brands','flash-deal-details'=>'sale','cart'=>'cart','checkout.shipping_info'=>'checkout','checkout.store_shipping_infostore'=>'checkout','checkout.store_delivery_info'=>'checkout','checkout.payment_info'=>'checkout','order_confirmed'=>'confirmation','orders.track'=>'tracking','user.login'=>'login','user.registration'=>'registration','dashboard'=>'account','profile'=>'account','purchase_history.index'=>'account','wishlists.index'=>'account','compare'=>'compare'];
    $studioPage=$studioTypes[$studioRoute]??($__env->hasSection('panel_content')?'account':(request()->is('checkout/*')?'checkout':'content'));
    $studioTitles=['cart'=>['YOUR NEXT PAIR IS ALMOST YOURS','Your bag.','Review your picks and take the next step.'],'checkout'=>['THE FINAL FEW STEPS','Make it yours.','Your details, delivery and order in one place.'],'confirmation'=>['THE NEXT STEP','Your order.','Your order details and what comes next.'],'tracking'=>['FROM OUR DOOR TO YOURS','Where is my pair?','Enter your order code to follow its progress.'],'account'=>['YOUR FOOTSTEP SPACE','Your account.','Your details, favourite pairs and order history.'],'compare'=>['SIDE BY SIDE','Find your perfect match.','A closer look at the pairs on your shortlist.'],'content'=>['THE FOOTSTEP EDIT','Footstep.','Explore the details.']];
@endphp
<a class="studio-skip" href="#studio-main">Skip to content</a>
<div class="studio-announcement"><div class="container"><span>GOOD SHOES. GREAT DAYS.</span><span>Cash on Delivery available <i class="las la-arrow-right" aria-hidden="true"></i></span></div></div>
@include('frontend.themes.studio.nav')
<main id="studio-main" tabindex="-1" class="studio-page studio-page--{{ $studioPage }}">
@if(isset($studioTitles[$studioPage]) && $studioPage !== 'cart')
<div class="container studio-page-heading"><div><p class="studio-eyebrow">{{ $studioTitles[$studioPage][0] }}</p><h1>{{ $studioTitles[$studioPage][1] }}</h1><p>{{ $studioTitles[$studioPage][2] }}</p></div><a href="{{ route('search') }}" class="studio-text-link">Continue exploring <span aria-hidden="true">↗</span></a></div>
@endif
@if(in_array($studioPage,['login','registration'],true))
<aside class="studio-auth-rail"><p class="studio-eyebrow">YOUR STYLE. YOUR PACE.</p><h2>{{ $studioPage==='login'?'Good to see you.':'Your next chapter.' }}<br><em>Keep moving.</em></h2><p>A home for your favourite pairs,<br>your orders, and your next discoveries.</p><span class="studio-auth-mark" aria-hidden="true">↗</span><a href="{{ route('search') }}" class="studio-text-link">Explore Footstep <span aria-hidden="true">↗</span></a></aside>
@endif
@yield('content')
</main>
@unless(in_array($studioPage,['login','registration'],true))
<section class="studio-benefits container" aria-label="Shopping with Footstep"><div><i class="las la-box" aria-hidden="true"></i><span><strong>Cash on Delivery</strong><small>Pay when your pair arrives.</small></span></div><div><i class="las la-shoe-prints" aria-hidden="true"></i><span><strong>A style for every step</strong><small>From everyday wear to your next run.</small></span></div><a href="{{ route('orders.track') }}"><i class="las la-route" aria-hidden="true"></i><span><strong>Follow your order</strong><small>Keep your order details close.</small></span><span aria-hidden="true">↗</span></a></section>
@endunless
@include('frontend.inc.footer')
