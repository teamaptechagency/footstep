@extends('backend.layouts.app')
@section('content')
<div class="mb-3"><h1 class="h3">Homepage Banners</h1><p class="text-muted">ছবি, link, ক্রম ও সময়সূচি পরিচালনা করুন। সব সময় Asia/Dhaka (বাংলাদেশ)। Schedule-এর জন্য cron লাগে না; homepage refresh / নতুন visit-এ কার্যকর হয়।</p><a href="#new-banner" class="btn btn-primary">+ Add banner / নতুন ব্যানার</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">সংরক্ষণ হয়নি। @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form class="card" method="POST" action="{{ route('homepage-banners.options') }}">@csrf<div class="card-body d-flex align-items-center flex-wrap" style="gap:18px"><label class="mb-0"><input type="hidden" name="auto_slide" value="0"><input type="checkbox" name="auto_slide" value="1" @if($settings['auto_slide']) checked @endif> Auto slide</label><label class="mb-0">প্রতি <input type="number" class="form-control d-inline-block" style="width:85px" name="seconds" min="2" max="60" value="{{ $settings['seconds'] }}" required> সেকেন্ড</label><button class="btn btn-primary">Save slide settings</button></div></form>
<div class="banner-manager-grid">
@foreach($items->push(['id'=>'','title'=>'','alt'=>'','image_id'=>'','mobile_image_id'=>'','href'=>'/shop','position'=>$items->count()+1,'enabled'=>true,'new_tab'=>false,'starts_at'=>null,'ends_at'=>null]) as $item)
@php
 $new=empty($item['id']); $status=$new?'New':\App\Services\HomepageBanners::status($item);
 $values=old('id')===$item['id']?array_merge($item,old()):$item;
@endphp
<section class="card banner-manager-card" @if($new) id="new-banner" @endif>
<div class="card-header"><h2 class="h6 mb-0">{{ $new?'New banner / নতুন ব্যানার':$item['title'] }}</h2><span class="badge {{ $status==='Live'?'badge-success':'badge-soft-secondary' }}">{{ $status }}</span></div>
<form action="{{ route('homepage-banners.save') }}" method="POST" class="card-body">@csrf<input type="hidden" name="id" value="{{ $item['id'] }}">
<label>Title / নিজের চেনার নাম</label><input class="form-control mb-3" name="title" value="{{ $values['title'] }}" maxlength="100" required>
<label>Desktop banner image</label><div class="mb-3"><div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false"><div class="input-group-prepend"><div class="input-group-text">Browse</div></div><div class="form-control file-amount">Choose image</div><input type="hidden" class="selected-files" name="image_id" value="{{ $values['image_id'] }}"></div><div class="file-preview box sm"></div></div>
<label>Mobile image (ঐচ্ছিক)</label><div class="mb-3"><div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false"><div class="input-group-prepend"><div class="input-group-text">Browse</div></div><div class="form-control file-amount">Same as desktop</div><input type="hidden" class="selected-files" name="mobile_image_id" value="{{ $values['mobile_image_id'] }}"></div><div class="file-preview box sm"></div></div>
<small class="text-muted d-block mb-3">ছবি আগে Media Center-এ upload করুন। নতুন ছবি বাছলে preview দেখাবে। Original image convert হবে না।</small>
<label>Click link</label><input class="form-control mb-3" name="href" value="{{ $values['href'] }}" placeholder="/shop অথবা https://..." maxlength="1000">
<div class="row"><div class="col-8"><label>Image description / Alt text</label><input class="form-control mb-3" name="alt" value="{{ $values['alt'] }}" maxlength="160"></div><div class="col-4"><label>Order / ক্রম</label><input class="form-control mb-3" type="number" min="1" max="999" name="position" value="{{ $values['position'] }}" required></div></div>
<div class="row"><div class="col-sm-6"><label>শুরু (বাংলাদেশ সময়)</label><input class="form-control mb-3" type="datetime-local" name="starts_at" value="{{ $values['starts_at'] }}"></div><div class="col-sm-6"><label>শেষ (বাংলাদেশ সময়)</label><input class="form-control mb-3" type="datetime-local" name="ends_at" value="{{ $values['ends_at'] }}"></div></div>
<small class="text-muted d-block mb-3">সময় খালি রাখলে সীমা নেই। ছোট ক্রম আগে দেখাবে। শেষ সময়ে banner নিজে থেকে লুকাবে; তালিকা থেকে মুছবে না।</small>
<div class="d-flex flex-wrap mb-3" style="gap:18px"><label><input name="enabled" type="hidden" value="0"><input name="enabled" type="checkbox" value="1" @if($values['enabled']) checked @endif> Enabled / চালু</label><label><input name="new_tab" type="hidden" value="0"><input name="new_tab" type="checkbox" value="1" @if($values['new_tab']) checked @endif> Open link in new tab</label></div>
<button class="btn btn-primary">{{ $new?'Add banner':'Save banner / সংরক্ষণ করুন' }}</button><span class="banner-saving ml-2" role="status"></span>
</form>
@unless($new)<form method="POST" action="{{ route('homepage-banners.destroy') }}" class="px-4 pb-3" onsubmit="return confirm('শুধু এই banner তালিকা থেকে সরাবেন? Original image মুছবে না।')">@csrf<input type="hidden" name="id" value="{{ $item['id'] }}"><button class="btn btn-soft-danger btn-sm">Remove banner</button></form>@endunless
</section>
@endforeach
</div>
<style>.banner-manager-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.banner-manager-card{min-width:0}.banner-manager-card .card-header{gap:12px}.banner-manager-preview{height:170px;background:#eff5f2;border-radius:10px;display:flex;align-items:center;justify-content:center;overflow:hidden;margin-bottom:20px}.banner-manager-preview img{width:100%;height:100%;object-fit:contain}.banner-manager-card .dropdown-menu{max-width:100%}.banner-manager-card .dropdown-item{overflow:hidden;text-overflow:ellipsis}.banner-manager-card label{font-size:13px}@media(max-width:991px){.banner-manager-grid{grid-template-columns:1fr}}</style>
@endsection
@section('script')
<script>document.querySelectorAll('[data-banner-image]').forEach(function(select){select.addEventListener('change',function(){var box=select.closest('.card-body').querySelector('.banner-manager-preview'),option=select.selectedOptions[0];box.replaceChildren();if(option&&option.dataset.imageUrl){var img=document.createElement('img');img.src=option.dataset.imageUrl;img.alt='Banner preview';box.appendChild(img);}});});document.querySelectorAll('.banner-manager-card form').forEach(function(form){form.addEventListener('submit',function(){var status=form.querySelector('.banner-saving');if(status)status.textContent='সংরক্ষণ হচ্ছে…';});});</script>
@endsection
