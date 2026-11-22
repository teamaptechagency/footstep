@extends('frontend.layouts.app')
@section('content')
@php($bazarCategoryTree=\App\Models\Category::all()->groupBy('parent_id'))
<div class="container bazar-directory"><div class="bazar-directory-heading"><p class="bazar-eyebrow">A PAIR FOR EVERY PLAN</p><h1>All Categories</h1><p>Find your favourite footwear by category.</p><span>{{ count($categories) }} collections to explore</span></div><div class="bazar-directory-grid">
@foreach($categories as $category)
<article><a href="{{ route('products.category',$category->slug) }}" class="bazar-directory-image"><img src="{{ \App\Services\OptimizedImages::asset($category->banner) }}" alt="{{ $category->getTranslation('name') }}" width="600" height="550" loading="lazy" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';" srcset="{{ \App\Services\OptimizedImages::srcset($category->banner) }}" sizes="(max-width:767px) 100vw,960px" decoding="async"><span aria-hidden="true">↗</span></a><div><span class="bazar-eyebrow">FOOTSTEP COLLECTION {{ str_pad($loop->iteration,2,'0',STR_PAD_LEFT) }}</span><h2><a href="{{ route('products.category',$category->slug) }}">{{ $category->getTranslation('name') }}</a></h2><a class="bazar-text-link" href="{{ route('products.category',$category->slug) }}">Explore collection <span aria-hidden="true">↗</span></a>
@if($bazarCategoryTree->has($category->id))
<ul class="bazar-directory-children">@foreach($bazarCategoryTree->get($category->id) as $child)<li><a href="{{ route('products.category',$child->slug) }}">{{ $child->getTranslation('name') }}</a>@if($bazarCategoryTree->has($child->id))<ul>@foreach($bazarCategoryTree->get($child->id) as $grandchild)<li><a href="{{ route('products.category',$grandchild->slug) }}">{{ $grandchild->getTranslation('name') }}</a></li>@endforeach</ul>@endif</li>@endforeach</ul>
@endif
</div></article>
@endforeach
</div></div>
@endsection
