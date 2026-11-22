@if(auth()->check()&&auth()->user()->user_type==='admin')
<section class="card no-print" data-admin-delivery-note>
<div class="card-body">
<h2 class="h5">Delivery man note</h2>
<form method="POST" action="{{ route('admin.delivery-note.save',$order->id) }}">
@csrf
<label class="sr-only" for="admin-delivery-note-{{ $order->id }}">Delivery man note</label>
<textarea class="form-control mb-3" id="admin-delivery-note-{{ $order->id }}" name="delivery_note" rows="4" maxlength="254">{{ old('delivery_note',\App\Services\AdminDeliveryNote::text($order->id)) }}</textarea>
<button class="btn btn-primary" type="submit">Save note</button>
</form>
</div>
</section>
@endif
