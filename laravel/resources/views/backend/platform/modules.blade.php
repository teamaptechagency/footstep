@extends('backend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/backend-modules.css') }}?v=1">
@php($moduleFlags=old('flags',$moduleState['flags']))
<div class="modules-workspace" id="modules-workspace">
    <header class="modules-hero">
        <div><span class="modules-eyebrow">YOUR WORKSPACE, YOUR WAY</span><h1>Modules and Extension</h1><p>প্রয়োজনীয় menu ও feature রাখুন। বাকিগুলো এক জায়গা থেকে লুকিয়ে দিন।</p></div>
        <div class="modules-scope"><i class="las la-lock" aria-hidden="true"></i><span>Backend only<small>Public website একই থাকবে</small></span></div>
    </header>
    @if(session('module_success'))<div class="alert alert-success" role="status">{{ session('module_success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>@endif
    <div class="modules-summary">
        <div><i class="las la-layer-group" aria-hidden="true"></i><span>Modules<strong>{{ count($modules) }}</strong></span></div>
        <div><i class="las la-toggle-on" aria-hidden="true"></i><span>Enabled modules<strong id="modules-enabled-count">—</strong></span></div>
        <div><i class="las la-puzzle-piece" aria-hidden="true"></i><span>Visible features<strong id="features-enabled-count">—</strong></span></div>
        <div class="modules-help"><strong>কীভাবে কাজ করে?</strong><p>Module বন্ধ করলে তার সব feature লুকাবে। আবার চালু করলে আগের feature নির্বাচন ফিরবে। Data মুছবে না; staff permissions একই থাকবে।</p></div>
    </div>
    <form action="{{ route('admin.modules.save') }}" method="POST" id="modules-form">
        @csrf
        <input type="hidden" name="revision" value="{{ $moduleState['revision'] }}">
        <div class="modules-toolbar">
            <label class="modules-search"><i class="las la-search" aria-hidden="true"></i><span class="sr-only">Search modules or features</span><input id="modules-search" type="search" placeholder="Search menu or feature / খুঁজুন" autocomplete="off"></label>
            <label class="modules-status-filter"><span class="sr-only">Filter visibility</span><select id="modules-status"><option value="all">All modules</option><option value="on">Enabled</option><option value="off">Disabled</option></select></label>
            <button type="button" class="modules-text-button" id="modules-enable-all">Enable all</button>
            <button type="button" class="modules-text-button danger" id="modules-disable-all">Disable all</button>
        </div>
        <p class="modules-result-count" id="modules-result-count" aria-live="polite"></p>
        <div class="modules-grid">
            @foreach($modules as $moduleKey=>$module)
            <section class="module-card" data-module-card="{{ $moduleKey }}">
                <header class="module-card-header">
                    <div class="module-symbol"><i class="las {{ $module['icon'] }}" aria-hidden="true"></i></div>
                    <div class="module-heading"><h2>{{ $module['label'] }}</h2><span class="module-count">{{ count($module['features']) }} features</span><span class="module-state-text"></span></div>
                    <label class="module-switch" title="Enable {{ $module['label'] }}">
                        <input type="hidden" name="flags[{{ $moduleKey }}]" value="0">
                        <input type="checkbox" name="flags[{{ $moduleKey }}]" value="1" data-module-master="{{ $moduleKey }}" aria-label="Enable {{ $module['label'] }}" {{ !empty($moduleFlags[$moduleKey])?'checked':'' }}>
                        <span aria-hidden="true"></span>
                    </label>
                </header>
                <div class="module-features">
                    @foreach($module['features'] as $featureKey=>$feature)
                    @php($flagKey=$moduleKey.'.'.$featureKey)
                    <label class="module-feature-row" data-feature-label="{{ mb_strtolower($feature['label']) }}">
                        <span>{{ $feature['label'] }}</span>
                        <span class="module-switch small">
                            <input type="hidden" name="flags[{{ $flagKey }}]" value="0">
                            <input type="checkbox" name="flags[{{ $flagKey }}]" value="1" data-feature-switch="{{ $moduleKey }}" aria-label="Enable {{ $feature['label'] }}" {{ !empty($moduleFlags[$flagKey])?'checked':'' }}>
                            <span aria-hidden="true"></span>
                        </span>
                    </label>
                    @endforeach
                </div>
                <footer class="module-disabled-note">Module বন্ধ—feature নির্বাচন রাখা আছে; চালু করলে কার্যকর হবে।</footer>
            </section>
            @endforeach
        </div>
        <div class="modules-empty" id="modules-empty" hidden><i class="las la-search" aria-hidden="true"></i><h2>No matching modules</h2><p>অন্য নাম দিয়ে খুঁজুন অথবা filter পরিবর্তন করুন।</p></div>
        <div class="modules-savebar"><div><strong id="modules-changes">No unsaved changes</strong><span>শুধু সেভ করার পর পরিবর্তন কার্যকর হবে।</span></div><button type="submit" class="modules-save" id="modules-save"><i class="las la-check" aria-hidden="true"></i> Save changes / সেভ করুন</button></div>
    </form>
    <aside class="modules-footnote"><i class="las la-shield-alt" aria-hidden="true"></i><p><strong>Modules and Extension সবসময় চালু থাকবে।</strong> Profile ও logout বন্ধ হবে না। বন্ধ feature-এর backend page/action সরাসরি URL দিয়েও খুলবে না। Public product, checkout, Pixel/CAPI delivery, courier workers ও আগে শুরু হওয়া background কাজ এই menu visibility switch দিয়ে বন্ধ হবে না। Retired Inbox/multivendor এখানে আবার চালু করা হয় না।</p></aside>
</div>
<script src="{{ static_asset('assets/js/backend-modules.js') }}?v=1" defer></script>
@endsection
