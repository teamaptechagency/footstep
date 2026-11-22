<p class="text-muted">Drag ☰ up or down, or use the arrow buttons. Top product appears first. Click Save to keep this order.</p><div class="table-responsive">@if(count($product_ids) > 0)
<table class="table table-bordered">
    <thead>
      <tr><th style="width:110px">Position</th>
        <td width="50%">
            <span>Order / Product</span>
        </td>
        <td data-breakpoints="lg" width="20%">
            <span>{{translate('Base Price')}}</span>
        </td>
        <td data-breakpoints="lg" width="20%">
            <span>{{translate('Discount')}}</span>
        </td>
        <td data-breakpoints="lg" width="10%">
            <span>{{translate('Discount Type')}}</span>
        </td><th style="min-width:115px">Actions</th>
      </tr>
    </thead>
    <tbody>
        @foreach ($product_ids as $key => $id)
            @php
              $product = \App\Models\Product::findOrFail($id);
              $flash_deal_product = \App\Models\FlashDealProduct::where('flash_deal_id', $flash_deal_id)->where('product_id', $product->id)->first();
            @endphp
            <tr data-product-id="{{ $id }}">
<td style="width:110px"><input type="hidden" name="ordered_products[]" value="{{ $id }}"><button type="button" class="btn btn-sm btn-light flash-drag-handle" style="cursor:grab;touch-action:none" aria-label="Drag product {{ $id }}">☰</button> <strong class="flash-position">{{ $key+1 }}</strong><div class="mt-1"><button type="button" class="btn btn-sm btn-light" data-flash-move="up" aria-label="Move product {{ $id }} up">↑</button> <button type="button" class="btn btn-sm btn-light" data-flash-move="down" aria-label="Move product {{ $id }} down">↓</button></div></td>
                <td>
                  <div class="form-group row">
                      <div class="col-auto">
                          <img src="{{ uploaded_asset($product->thumbnail_img)}}" class="size-60px img-fit" >
                      </div>
                      <div class="col">
                          <span>{{  $product->getTranslation('name')  }}</span>
                      </div>
                  </div>
                </td>
                <td>
                    <span>{{ $product->unit_price }}</span>
                </td>
                <td>
                    <input type="number" lang="en" name="discount_{{ $id }}" value="{{ $product->discount }}" min="0" step="1" class="form-control" required>
                </td>
                <td>
                    <select class="aiz-selectpicker" name="discount_type_{{ $id }}">
                        <option value="amount" <?php if($product->discount_type == 'amount') echo "selected";?> >{{ translate('Flat') }}</option>
                        <option value="percent" <?php if($product->discount_type == 'percent') echo "selected";?> >{{ translate('Percent') }}</option>
                    </select>
                </td><td><button type="button" class="btn btn-sm btn-soft-danger" data-flash-remove aria-label="Remove product {{ $id }}" style="white-space:nowrap"><i class="las la-times" aria-hidden="true"></i> Remove / বাদ দিন</button></td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
</div>