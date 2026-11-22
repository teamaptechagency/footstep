<div class="form-group row">
 <label class="col-lg-3 col-form-label" for="product-comfort-note">Product highlight / বিশেষত্ব</label>
 <div class="col-lg-8"><input class="form-control" name="comfort_note" id="product-comfort-note" maxlength="160" value="{{ old('comfort_note',\App\Services\ProductComfort::value($product->comfort_note??null)) }}" placeholder="Super lightweight and Super comfortable"><small class="text-muted">Total Price-এর পাশে এই লেখা দেখাবে। প্রতি product-এ বদলাতে পারবেন; খালি রাখলে দেখাবে না।</small></div>
</div>
