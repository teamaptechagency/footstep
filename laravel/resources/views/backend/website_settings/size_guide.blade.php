@extends('backend.layouts.app')
@section('content')
<div class="mb-3"><h1 class="h3">Size Guide / সাইজ গাইড</h1><p class="text-muted">Products → Size Guide থেকে public menu, footer ও product-এর common Size Guide page পরিচালনা করুন।</p><a href="/size-guide" target="_blank" rel="noopener" class="btn btn-soft-primary">Preview Size Guide ↗</a></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('size-guide.save') }}" class="card">@csrf<div class="card-body" style="max-width:950px">
<label>Page title</label><input name="title" class="form-control mb-3" value="{{ old('title',$guide['title']) }}" maxlength="100" required>
<label>Customer instructions / নির্দেশনা</label><textarea name="intro" class="form-control mb-3" rows="3" maxlength="1500" required>{{ old('intro',$guide['intro']) }}</textarea>
<h2 class="h6 mt-4">পায়ের মাপ নেওয়ার নির্দেশনা</h2>
<label>Instructions heading</label><input name="instructions_title" class="form-control mb-3" value="{{ old('instructions_title',$guide['instructions_title']) }}" maxlength="100" required>
<label>মাপ নেওয়ার ধাপ / প্রতি লাইনে একটি ধাপ</label><textarea name="steps_text" class="form-control mb-2" rows="6" maxlength="4000">{{ old('steps_text',implode("\n",$guide['steps'])) }}</textarea><small class="text-muted d-block mb-3">সর্বোচ্চ ১২টি ধাপ। খালি রাখলে ধাপের তালিকা লুকাবে।</small>
<label>Fit tips / প্রতি লাইনে একটি টিপস (ঐচ্ছিক)</label><textarea name="fit_tips_text" class="form-control mb-3" rows="4" maxlength="2500">{{ old('fit_tips_text',implode("\n",$guide['fit_tips'])) }}</textarea>
<label>অতিরিক্ত তথ্য / Extra information (ঐচ্ছিক)</label><textarea name="extra_info" class="form-control mb-3" rows="5" maxlength="4000">{{ old('extra_info',$guide['extra_info']) }}</textarea>
<h2 class="h6 mt-4">ছবি ও ভিডিও দিয়ে বুঝিয়ে দিন</h2>
<label>Video section title</label><input name="video_title" class="form-control mb-3" value="{{ old('video_title',$guide['video_title']) }}" maxlength="100">
<label>YouTube video link (ঐচ্ছিক)</label><input name="youtube_url" class="form-control mb-2" placeholder="https://www.youtube.com/watch?v=..." value="{{ old('youtube_url',$guide['youtube_url']) }}"><small class="text-muted d-block mb-3">নিজের সঠিক video link দিন। Customer Play চাপলে video চলবে। খালি রাখলে video section দেখাবে না।</small>
<label>Size chart / guide picture (ঐচ্ছিক)</label><div class="mb-3"><div class="input-group" data-toggle="aizuploader" data-type="image" data-multiple="false"><div class="input-group-prepend"><div class="input-group-text">Browse</div></div><div class="form-control file-amount">Choose image</div><input type="hidden" name="image_id" class="selected-files" value="{{ old('image_id',$guide['image_id']) }}"></div><div class="file-preview box sm"></div></div>
<label>Picture caption / ছবির নিচের লেখা</label><input name="image_caption" class="form-control mb-3" value="{{ old('image_caption',$guide['image_caption']) }}" maxlength="160">
<p class="text-muted">ছবি না দিলে পা মাপার illustration থাকবে। Original image convert করা হবে না।</p>
<h2 class="h6 mt-4">39–45 size chart / পায়ের দৈর্ঘ্য (cm)</h2>
<div class="alert alert-info">প্রাথমিক মাপ Nike men's footwear-এর reference; সব brand বা Footstep-এর সব model-এর নিশ্চিত fit নয়। নিজের product-এর মাপ যাচাই করে পরিবর্তন করতে পারবেন। পরিবর্তন করলে chart-কে দোকানের দেওয়া মাপ হিসেবে দেখাবে। CM/JP label বা insole-এর দৈর্ঘ্যের সঙ্গে foot length এক নয়।</div>
<div class="row">@foreach(\App\Services\ProductSizeGuide::measurements() as $size=>$default)<div class="col-6 col-sm-3 mb-3"><label>EU {{ $size }} · Foot length</label><input type="number" class="form-control" name="measurements[{{ $size }}]" value="{{ old('measurements.'.$size,$guide['measurements'][$size]) }}" min="15" max="40" step="0.1" required></div>@endforeach</div>
<button class="btn btn-primary">Save Size Guide / সংরক্ষণ করুন</button><span role="status" class="ml-2" id="guide-save-status"></span>
</div></form>
@endsection
@section('script')<script>document.querySelector('form[action="{{ route('size-guide.save') }}"]').addEventListener('submit',function(){document.getElementById('guide-save-status').textContent='সংরক্ষণ হচ্ছে…';});</script>@endsection
