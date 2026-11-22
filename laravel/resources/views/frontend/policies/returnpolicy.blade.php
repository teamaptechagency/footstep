@extends('frontend.layouts.app')
@php($return_policy=\App\Models\Page::where('type','return_policy_page')->first())
@section('meta_title'){{ optional($return_policy)->meta_title ?: 'Return & Exchange Policy | Footstep Online' }}@stop
@section('meta_description'){{ optional($return_policy)->meta_description }}@stop
@section('content')
<section class="py-4"><div class="container"><div class="mb-3"><a href="/">Home</a> / Return & Exchange</div><div class="p-3 p-md-4 bg-white rounded border"><h1 class="h4 fw-700 mb-4">{{ $return_policy?$return_policy->getTranslation('title'):'Return & Exchange Policy' }}</h1>@if($return_policy){!! $return_policy->getTranslation('content') !!}@endif</div></div></section>
@endsection
