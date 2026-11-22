<div class="form-group row">
 <label class="col-lg-3 col-form-label" for="product-made-in">Made in / উৎপাদনের দেশ</label>
 <div class="col-lg-8"><select class="form-control" name="made_in" id="product-made-in" required>
 @foreach(\App\Services\ProductOrigin::COUNTRIES as $country)<option value="{{ $country }}" @if(old('made_in',\App\Services\ProductOrigin::value($product->made_in??null))===$country) selected @endif>{{ $country }}</option>@endforeach
 </select><small class="text-muted">Default: Vietnam. Product-এর সঠিক উৎপাদনের দেশ বাছুন।</small></div>
</div>
