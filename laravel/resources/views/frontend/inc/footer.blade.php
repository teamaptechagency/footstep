@include('frontend.partials.return_policy_summary')
@php
    $footerPhone=trim((string)get_setting('contact_phone'));
    if(preg_match('~(?:wa\.me/|phone=)([+0-9]+)~',$footerPhone,$match))$footerPhone=$match[1];
    $footerPhone=preg_replace('/[^0-9]/','',$footerPhone);
    if(substr($footerPhone,0,2)==='00')$footerPhone=substr($footerPhone,2);
    if(preg_match('/^01[3-9][0-9]{8}$/D',$footerPhone))$footerPhone='88'.$footerPhone;
    $footerWhatsApp=preg_match('/^[1-9][0-9]{6,14}$/D',$footerPhone)?'https://wa.me/'.$footerPhone:null;
@endphp
<style>
.footstep-footer-brand img{height:82px;width:auto;max-width:100%;object-fit:contain}.footstep-footer-heading{font-size:12px;font-weight:650;text-transform:uppercase;letter-spacing:1px;margin-bottom:22px}.footstep-footer-whatsapp{display:inline-flex!important;align-items:center;justify-content:center;width:40px;height:40px;border:0;border-radius:50%;color:#fff!important;font-size:21px;background:#25d366}.footstep-footer-whatsapp:hover{background:#1fba58}.footstep-footer-whatsapp i,.footstep-footer-social a i{font-size:21px;line-height:1}.footstep-footer-social{display:flex;gap:12px;list-style:none;padding:0;margin:0}.footstep-footer-social a{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:50%;color:white!important;font-size:21px}.footstep-footer-social .facebook{background:#315da4}.footstep-footer-social .instagram{background:#b72b85}.footstep-footer-copyright{font-size:12px;text-align:center!important}.footstep-footer-copyright p{margin:0}.footstep-footer-contact{display:flex;justify-content:center;align-items:center;flex-direction:column;padding-top:0}.footstep-footer-main{padding-top:40px!important;padding-bottom:36px!important}@media(max-width:767.98px){.footstep-footer-brand{text-align:center;margin-bottom:26px}.footstep-footer-contact{padding-top:0;justify-content:flex-start;margin-bottom:24px}.footstep-footer-social{justify-content:center}.footstep-footer-heading{text-align:center}.footstep-footer-contact{justify-content:center}.footstep-footer-account{text-align:center}.footstep-footer-main{padding-top:32px!important;padding-bottom:24px!important}.footstep-footer-brand img{height:76px}}
</style>
<section class="bg-dark text-light footer-widget footstep-footer-main">
<div class="container"><div class="row align-items-start">
    <div class="col-md-4 footstep-footer-brand">
        <a href="{{ route('home') }}" class="d-inline-block">
            <img src="{{ get_setting('footer_logo') ? \App\Services\OptimizedImages::asset(get_setting('footer_logo')) : static_asset('assets/img/logo.png') }}" alt="{{ env('APP_NAME') }}" height="82" decoding="async">
        </a>
        @if(get_setting('about_us_description',null,App::getLocale()))<div class="mt-3">{!! get_setting('about_us_description',null,App::getLocale()) !!}</div>@endif
    </div>
    <div class="col-12 col-md-2 footstep-footer-contact">
        <h4 class="footstep-footer-heading">Contact Info</h4>
        @if($footerWhatsApp)<a class="footstep-footer-whatsapp" href="{{ $footerWhatsApp }}" target="_blank" rel="noopener noreferrer" aria-label="Contact Footstep on WhatsApp"><i class="lab la-whatsapp" aria-hidden="true"></i></a>@endif
    </div>
    <div class="col-6 col-md-3">
        <h4 class="footstep-footer-heading">Social Links</h4>
        <ul class="footstep-footer-social">
        @if(get_setting('show_social_links') && get_setting('facebook_link'))<li><a href="{{ get_setting('facebook_link') }}" class="facebook" target="_blank" rel="noopener noreferrer" aria-label="Footstep on Facebook"><i class="lab la-facebook-f" aria-hidden="true"></i></a></li>@endif
        @if(get_setting('show_social_links') && get_setting('instagram_link'))<li><a href="{{ get_setting('instagram_link') }}" class="instagram" target="_blank" rel="noopener noreferrer" aria-label="Footstep on Instagram"><i class="lab la-instagram" aria-hidden="true"></i></a></li>@endif
        </ul>
    </div>
    <div class="col-6 col-md-3 footstep-footer-account">
        <h4 class="footstep-footer-heading">{{ translate('My Account') }}</h4>
        <ul class="list-unstyled mb-0">
        @if(Auth::check())<li class="mb-2"><a href="{{ route('logout') }}" class="opacity-50 text-reset">{{ translate('Logout') }}</a></li>
        @else<li class="mb-2"><a href="{{ route('user.login') }}" class="opacity-50 text-reset">{{ translate('Login') }}</a></li>@endif
        <li class="mb-2"><a href="{{ route('purchase_history.index') }}" class="opacity-50 text-reset">{{ translate('Order History') }}</a></li>
        <li class="mb-2"><a href="{{ route('wishlists.index') }}" class="opacity-50 text-reset">{{ translate('My Wishlist') }}</a></li>
        <li class="mb-2"><a href="{{ route('orders.track') }}" class="opacity-50 text-reset">{{ translate('Track Order') }}</a></li>
        @if(addon_is_activated('affiliate_system'))<li class="mb-2"><a href="{{ route('affiliate.apply') }}" class="opacity-50 text-reset">{{ translate('Be an affiliate partner') }}</a></li>@endif
        </ul>
    </div>
</div></div>
</section>
<footer class="pt-3 pb-7 pb-xl-3 bg-black text-light"><div class="container"><div class="footstep-footer-copyright" current-verison="{{ get_setting('current_version') }}">{!! get_setting('frontend_copyright_text',null,App::getLocale()) !!}</div></div><div class="container pb-4">@if(\App\Services\WebsiteLiveChat::enabled())<button type="button" class="btn btn-outline-primary" data-website-live-chat=""><i class="las la-comments" aria-hidden="true"></i> Contact Live Chat / সরাসরি কথা বলুন</button>@endif</div></footer>
<div class="aiz-mobile-bottom-nav d-xl-none fixed-bottom bg-white shadow-lg border-top rounded-top" style="box-shadow: 0px -1px 10px rgb(0 0 0 / 15%)!important; ">
    <div class="row align-items-center gutters-5">
        <div class="col">
            <a href="{{ route('home') }}" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-home fs-20 opacity-60 {{ areActiveRoutes(['home'],'opacity-100 text-primary')}}"></i>
                <span class="d-block fs-10 fw-600 opacity-60 {{ areActiveRoutes(['home'],'opacity-100 fw-600')}}">{{ translate('Home') }}</span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('categories.all') }}" class="text-reset d-block text-center pb-2 pt-3">
                <i class="las la-list-ul fs-20 opacity-60 {{ areActiveRoutes(['categories.all'],'opacity-100 text-primary')}}"></i>
                <span class="d-block fs-10 fw-600 opacity-60 {{ areActiveRoutes(['categories.all'],'opacity-100 fw-600')}}">{{ translate('Categories') }}</span>
            </a>
        </div>
        @php
            if(auth()->user() != null) {
                $user_id = Auth::user()->id;
                $cart = \App\Models\Cart::where('user_id', $user_id)->get();
            } else {
                $temp_user_id = Session()->get('temp_user_id');
                if($temp_user_id) {
                    $cart = \App\Models\Cart::where('temp_user_id', $temp_user_id)->get();
                }
            }
        @endphp
        <div class="col-auto">
            <a href="{{ route('cart') }}" class="text-reset d-block text-center pb-2 pt-3">
                <span class="align-items-center bg-primary border border-white border-width-4 d-flex justify-content-center position-relative rounded-circle size-50px" style="margin-top: -33px;box-shadow: 0px -5px 10px rgb(0 0 0 / 15%);border-color: #fff !important;">
                    <i class="las la-shopping-bag la-2x text-white"></i>
                </span>
                <span class="d-block mt-1 fs-10 fw-600 opacity-60 {{ areActiveRoutes(['cart'],'opacity-100 fw-600')}}">
                    {{ translate('Cart') }}
                    @php
                        $count = (isset($cart) && count($cart)) ? count($cart) : 0;
                    @endphp
                    (<span class="cart-count">{{$count}}</span>)
                </span>
            </a>
        </div>
        <div class="col">
            <a href="{{ route('flash-deals') }}" class="text-reset d-block text-center pb-2 pt-3"><span class="d-inline-block position-relative px-2"><i class="las la-bolt fs-20 opacity-60 {{ areActiveRoutes(['flash-deals','flash-deal-details'],'opacity-100 text-primary') }}" aria-hidden="true"></i></span><span class="d-block fs-10 fw-600 opacity-60 {{ areActiveRoutes(['flash-deals','flash-deal-details'],'opacity-100 fw-600') }}">Flash Deals</span></a>
        </div>
        <div class="col">
        @if (Auth::check())
            @if(isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="text-reset d-block text-center pb-2 pt-3">
                    <span class="d-block mx-auto">
                        @if(Auth::user()->photo != null)
                            <img src="{{ custom_asset(Auth::user()->avatar_original)}}" class="rounded-circle size-20px">
                        @else
                            <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="rounded-circle size-20px">
                        @endif
                    </span>
                    <span class="d-block fs-10 fw-600 opacity-60">{{ translate('Account') }}</span>
                </a>
            @else
                <a href="javascript:void(0)" class="text-reset d-block text-center pb-2 pt-3 mobile-side-nav-thumb" data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav">
                    <span class="d-block mx-auto">
                        @if(Auth::user()->photo != null)
                            <img src="{{ custom_asset(Auth::user()->avatar_original)}}" class="rounded-circle size-20px">
                        @else
                            <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="rounded-circle size-20px">
                        @endif
                    </span>
                    <span class="d-block fs-10 fw-600 opacity-60">{{ translate('Account') }}</span>
                </a>
            @endif
        @else
            <a href="{{ route('user.login') }}" class="text-reset d-block text-center pb-2 pt-3">
                <span class="d-block mx-auto">
                    <img src="{{ static_asset('assets/img/avatar-place.png') }}" class="rounded-circle size-20px">
                </span>
                <span class="d-block fs-10 fw-600 opacity-60">{{ translate('Account') }}</span>
            </a>
        @endif
        </div>
    </div>
</div>
@if (Auth::check() && !isAdmin())
    <div class="aiz-mobile-side-nav collapse-sidebar-wrap sidebar-xl d-xl-none z-1035">
        <div class="overlay dark c-pointer overlay-fixed" data-toggle="class-toggle" data-backdrop="static" data-target=".aiz-mobile-side-nav" data-same=".mobile-side-nav-thumb"></div>
        <div class="collapse-sidebar bg-white">
            @include('frontend.inc.user_side_nav')
        </div>
    </div>
@endif

    <div class="modal fade" id="guest-modal">
        <div class="modal-dialog modal-lg modal-dialog-zoom">
            <div class="modal-content">


                <div class="modal-header">
                    <h6 class="modal-title fw-600">Order confirmation</h6>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true"></span>
                    </button>
                </div>

        <form action="{{route('order_store')}}" method="post">
            @csrf
                <div class="modal-body">

    <div class="mb-4">
        <div class="row gutters-5 d-none d-lg-flex border-bottom mb-3 pb-3">
            <div class="col-md-5 fw-600">Product</div>
            <div class="col fw-600">Price</div>
            <!--<div class="col fw-600">Tax</div>-->
            <div class="col fw-600">Quantity</div>
            <div class="col fw-600">Total</div>
            <div class="col-auto fw-600">Remove</div>
        </div>
        <ul class="list-group list-group-flush" id="itemadd"></ul>
    </div>
    
        <div class="form-group">
            <label><strong>আপনার নাম (Name)*</strong></label>
            <input class="form-control" type="text" placeholder="আপনার নাম*" name="name" value="" required>
            
            <input class="form-control" type="hidden" name="email" value="@if(Auth()->check()){{Auth()->user()->email}} @else customer@gmail.com @endif">
        </div>
        
        <div class="form-group">
            <label><strong>আপনার সম্পূর্ণ ঠিকানা (Address)*</strong></label>
            <input class="form-control" type="text" placeholder="আপনার সম্পূর্ণ ঠিকানা*" name="address" value="" required>
        </div>
        
        <div class="form-group">
            <label><strong>আপনার মোবাইল নাম্বার (Number)* </strong></label>
            <input type="tel" id="phone" name="phone" class="form-control" placeholder="আপনার মোবাইল নাম্বার *" pattern="[0-9]{11}" required title="Example: 01xxxxxxxxxx">
            
        </div>
        
        <div class="form-group">
            <label><strong>এলাকা (Area)* </strong></label>
            <select name="city" class="form-control" required>
                <option value="">এলাকা (Area)* </option>
                <option value="In Dhaka City">ঢাকার ভিতরে (১০০) টাকা (Inside Dhaka)</option>
                <option value="Out Of Dhaka City">ঢাকার বাইরে (১৫০) টাকা (Outside Dhaka)</option>
            </select>
        </div>
        
        </div>
        
        <div class="modal-footer">
            <input type="submit" class="btn btn-danger btn-block btn-sm" value="অর্ডার কনফার্ম করুন">
        </div>
        
    </form>
            </div>
        </div>
    </div>

    
    
{{-- jQuery is loaded once by the layout. --}}
<script type="text/javascript">

    function guest_cart_update(cardid, qty){

        event.preventDefault();

        if(qty > 0){
            $.ajax({
                url : "{{Route('cartupdate.cart_update')}}",
                method : "get",
                data : {
                    cardid : cardid,
                    qty : qty
                },
                success : function(data){
                    if (data.status == 1) {
                        $("#guest_qty_"+cardid).val(data.stock);
                    }else{
                        $("#guest_price_"+cardid).text("৳"+data.price);
                        $("#guest_total_"+cardid).text("৳"+data.total);
                    }
                }
            });
        }
    }

    function removeguest(id){
        event.preventDefault();
        $.ajax({
                url : "{{Route('cartdelete.cart_delete')}}",
                method : "get",
                data : {
                    id : id
                }
        });

        $("#guest_remove_"+id).remove();
        $(".cart-count").text($(".addItem").length);
        if($(".addItem").length == 0){
            window.location = "{{url('/')}}";
        }
    }

    function guest_card(){
        event.preventDefault();
        $('#guest-modal').modal();

        $.ajax({
                url : "{{Route('getguestcard.getguest_card')}}",
                method : "get",
                success : function(data){



                if(data.guest_data.length > 0){
                    $("#itemadd").html("");

                    $.each(data.guest_data, function(key, values){

                        $("#itemadd").append('<li class="list-group-item addItem px-0 px-lg-3" id="guest_remove_'+values.cart_id+'"><div class="row gutters-5"><div class="col-lg-5 d-flex"><span class="mr-2 ml-0"><img src="'+values.icon+'" class="img-fit size-60px rounded"></span><span class="fs-14 opacity-60">'+values.title+'</span></div><div class="col-lg col-4 order-1 order-lg-0 my-3 my-lg-0"><span class="fw-600 fs-16" id="guest_price_2">৳'+values.price+'</span></div><div class="col-lg col-6 order-4 order-lg-0"><div class="row no-gutters align-items-center aiz-plus-minus mr-2 ml-0"><input type="number" name="quantity['+values.cart_id+']" id="guest_qty_'+values.cart_id+'" class="form-control" placeholder="1" value="'+values.qty+'" min="1" max="50" onchange="guest_cart_update('+values.cart_id+', this.value)"></div></div><div class="col-lg col-4 order-3 order-lg-0 my-3 my-lg-0"><span class="fw-600 fs-16 text-primary" id="guest_total_'+values.cart_id+'">৳'+values.total+'</span></div><div class="col-lg-auto col-6 order-5 order-lg-0 text-right"><a href="" onclick="removeguest('+values.cart_id+')" class="btn btn-icon btn-sm btn-soft-primary btn-circle"><i class="las la-trash"></i></a></div></div></li>');

                        

                    });
                }

                }
        });





    }

</script>
