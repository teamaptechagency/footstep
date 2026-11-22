@extends('backend.layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4">Layout & Appearance</h1><a class="btn btn-light" href="{{ route('home') }}" target="_blank" rel="noopener">View storefront</a></div>
@if(session('platform_success'))<div class="alert alert-success">{{ session('platform_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="card"><div class="card-body"><h2 class="h5">Public developer credit / Footer promotion</h2><p class="text-muted small">ছোট developer credit public page-এর নিচে দেখাবে; দুটি link নতুন tab-এ খুলবে।</p><form method="POST" action="{{ route('platform.developer-credit.save') }}">@csrf<input type="hidden" name="developer_credit" value="0"><label><input type="checkbox" name="developer_credit" value="1" {{ \App\Services\AdminBranding::publicCredit()?'checked':'' }}> Show Developer of AP Tech Agency (Product: Astral Labs)</label><div><button class="btn btn-primary btn-sm" type="submit">Save footer setting</button></div></form></div></div>
<div class="card"><div class="card-body">
<div class="d-flex justify-content-between flex-wrap mb-3"><div><h2 class="h5 mb-1">Backend theme / অ্যাডমিন থিম</h2><p class="small text-muted mb-0">পুরো admin panel-এর sidebar, header, forms, lists ও buttons। Storefront-এর theme আলাদা থাকবে।</p></div></div>
<form method="POST" action="{{ route('platform.backend-theme.save') }}">@csrf
<div class="row">
@foreach(['classic'=>['Classic Admin','আগের backend design। Dashboard আলাদাভাবে বেছে নেওয়া যাবে।'],'insights'=>['Footstep Insights Admin','Dashboard-এর teal style-এ পুরো backend; Insights dashboard একসঙ্গে চালু হবে।']] as $id=>$theme)
<div class="col-md-6 mb-3"><label for="backend-theme-{{ $id }}" class="d-block border rounded p-3 h-100" style="cursor:pointer">
<div style="height:100px;border-radius:9px;background:{{ $id==='insights'?'#f2f5f8':'#f1f2f8' }};display:flex;overflow:hidden;margin-bottom:14px;border:1px solid #e1e8ed" aria-hidden="true">
<div style="width:23%;background:{{ $id==='insights'?'#193b40':'#171725' }};padding:14px 7px"><span style="display:block;height:5px;background:#ffffff70;margin-bottom:12px"></span>@for($i=0;$i<4;$i++)<span style="display:block;height:3px;background:#ffffff30;margin:9px 0"></span>@endfor</div>
<div style="flex:1;padding:10px"><div style="height:13px;background:#fff;margin-bottom:8px;border-radius:3px"></div><div style="height:24px;background:{{ $id==='insights'?'#193b40':'#fff' }};border-radius:5px;margin-bottom:8px"></div><div style="display:flex;gap:5px">@for($i=0;$i<3;$i++)<span style="height:22px;flex:1;background:#fff;border-radius:4px"></span>@endfor</div></div></div>
<input id="backend-theme-{{ $id }}" type="radio" name="backend_theme" value="{{ $id }}" {{ \App\Services\BackendTheme::current()===$id?'checked':'' }}> <strong>{{ $theme[0] }}</strong><small class="d-block text-muted mt-2">{{ $theme[1] }}</small>
</label><a href="{{ route('admin.dashboard',['backend_theme'=>$id,'dashboard_layout'=>'modern']) }}" target="_blank" rel="noopener" class="btn btn-link btn-sm">Preview / দেখে নিন ↗</a></div>
@endforeach
</div><button class="btn btn-primary" type="submit">Apply backend theme / থিম সেভ করুন</button>
</form></div></div>

<div class="card"><div class="card-body"><div class="d-flex align-items-center justify-content-between mb-3"><div><h2 class="h5 mb-1">Admin dashboard design / ড্যাশবোর্ড ডিজাইন</h2><p class="small text-muted mb-0">Choose the admin overview independently from your storefront.</p></div><a href="{{ route('admin.dashboard') }}" class="btn btn-light btn-sm">Open dashboard</a></div>
<form action="{{ route('platform.dashboard-appearance.save') }}" method="POST">@csrf<div class="row">
@foreach(['classic'=>['Classic dashboard','আগের dashboard design, charts ও top-selling products।'],'modern'=>['Footstep Insights','নতুন overview, sales, visitor analytics ও top-selling products।']] as $id=>$theme)
<div class="col-md-6 mb-3"><label class="dashboard-layout-choice" for="dashboard-layout-{{ $id }}"><div class="dashboard-layout-swatch {{ $id }}" aria-hidden="true">@for($i=0;$i<7;$i++)<i></i>@endfor</div><input id="dashboard-layout-{{ $id }}" type="radio" name="dashboard_layout" value="{{ $id }}" @if(\App\Services\AdminDashboardAppearance::current()===$id) checked @endif> <strong>{{ $theme[0] }}</strong><small>{{ $theme[1] }}</small></label><a href="{{ route('admin.dashboard',['dashboard_layout'=>$id]) }}" target="_blank" rel="noopener" class="btn btn-link btn-sm px-0">Preview {{ $theme[0] }} ↗</a></div>
@endforeach
</div><button class="btn btn-primary">Apply dashboard design / সেভ করুন</button></form></div></div>
<link rel="stylesheet" href="{{ static_asset('assets/css/admin-insights.css') }}?v=2.1.0">
<p class="text-muted">Choose a storefront layout and color pack. The existing design is included; new layout themes can be added in future updates.</p>
<div class="card"><div class="card-body"><h2 class="h5">Order number format / অর্ডার নম্বরের ধরন</h2><p class="text-muted">Confirmation page এবং invoice-এ যেভাবে অর্ডার নম্বর দেখাবে। সংরক্ষিত order code পরিবর্তন হবে না।</p><form method="POST" action="{{ route('platform.order-display.save') }}">@csrf<label for="order-display-format">Display format</label><select id="order-display-format" name="order_display_format" class="form-control mb-3"><option value="last_phone" {{ \App\Services\OrderDisplay::mode()==='last_phone'?'selected':'' }}>Last part - Customer mobile / শেষ অংশ - কাস্টমারের মোবাইল</option><option value="full" {{ \App\Services\OrderDisplay::mode()==='full'?'selected':'' }}>Full original order number / আগের সম্পূর্ণ অর্ডার নম্বর</option></select><p class="small text-muted">Example: 15022038 - 017XXXXXXXX</p><button type="submit" class="btn btn-primary">Save order format / সেভ করুন</button></form></div></div>
<form method="POST" action="{{ route('platform.appearance.save') }}">@csrf
<div class="card"><div class="card-body"><h2 class="h5">Layout themes</h2><div class="row">
@foreach($layouts as $id=>$theme)
<div class="col-md-6 col-xl-4 mb-3"><label class="border rounded p-3 d-block h-100" for="layout-{{ $id }}">
<div aria-hidden="true" class="border rounded mb-3 p-2" style="background:#f3f5f7"><div class="bg-white p-2 mb-2 d-flex justify-content-between"><strong>FOOTSTEP</strong><span>⌕</span></div><div class="d-flex mb-2" style="gap:8px"><div class="bg-white p-2" style="width:25%">☰<br>—<br>—</div><div class="rounded p-3 text-white" style="background:{{ $id==='bazar'?'#F58A1F':($id==='studio'?'#20241e':($id==='premium'?'#173b2a':'#008A45')) }};flex:1">Step into style<br><small>{{ $theme['name'] }}</small></div></div><div class="d-flex" style="gap:6px">@for($i=0;$i<4;$i++)<div class="bg-white rounded p-3" style="flex:1">▱</div>@endfor</div></div>
<input id="layout-{{ $id }}" type="radio" name="layout" value="{{ $id }}" {{ old('layout',$options['layout'])===$id?'checked':'' }}> <strong>{{ $theme['name'] }}</strong>
@if($options['layout']===$id)<span class="badge badge-success ml-1">Active</span>@endif
<small class="d-block text-muted mt-2">{{ $theme['description'] }}</small></label></div>
@endforeach
</div></div></div>
<div class="card"><div class="card-body"><h2 class="h5">Color packs</h2><p class="text-muted">Changes the storefront brand accents, buttons and links. Product images and admin colors stay as they are.</p><div class="row">
@foreach($packs as $id=>$pack)
@php($palette=\App\Services\StorefrontAppearance::palette($id))
<div class="col-md-6 col-xl-4 mb-3"><label for="pack-{{ $id }}" class="border rounded p-3 d-block h-100"><span class="d-flex mb-3" aria-hidden="true" style="gap:6px"><span style="background:{{ $palette['primary'] }};height:34px;flex:1;border-radius:4px"></span><span style="background:{{ $palette['hover'] }};height:34px;flex:1;border-radius:4px"></span><span style="background:#f3f5f7;height:34px;flex:1;border-radius:4px"></span></span>
<input id="pack-{{ $id }}" type="radio" name="color" value="{{ $id }}" data-primary="{{ $palette['primary'] }}" data-hover="{{ $palette['hover'] }}" {{ old('color',$options['color'])===$id?'checked':'' }}> <strong>{{ $pack['name'] }}</strong>
@if($options['color']===$id)<span class="badge badge-success ml-1">Active</span>@endif
<small class="d-block text-muted mt-2">{{ $pack['description'] }}</small></label></div>
@endforeach
</div><div class="border rounded p-3" id="appearance-preview"><strong>Color preview</strong><p class="mb-2">Footstep · Product details · ৳1,580</p><span id="appearance-preview-button" class="btn text-white">Add to cart</span> <span id="appearance-preview-link">View product</span><small class="d-block text-muted mt-2">Preview only. Apply to update the storefront.</small></div></div></div>
<button class="btn btn-primary" name="action" value="save" type="submit">Apply appearance</button>
<button class="btn btn-light" name="action" value="reset" type="submit">Restore existing design</button>
<a href="{{ route('website.appearance') }}" class="btn btn-link">Logo, site details & existing settings</a>
</form>
@endsection
@section('script')
<script>document.addEventListener('DOMContentLoaded',function(){function preview(){var selected=document.querySelector('input[name="color"]:checked');if(!selected)return;document.getElementById('appearance-preview-button').style.backgroundColor=selected.dataset.primary;document.getElementById('appearance-preview-link').style.color=selected.dataset.primary;}document.querySelectorAll('input[name="color"]').forEach(function(input){input.addEventListener('change',preview);});preview();});</script>
@endsection
