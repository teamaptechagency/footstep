@extends('backend.layouts.app')
@section('content')
<div class="card"><div class="card-body">
<h1 class="h3">CarryBee Courier</h1>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if(!$order)
<p>API v2.0 · {{ $configured?'Credentials stored':'Credentials required' }}</p>
<form method="POST" action="{{ route('carrybee.save') }}">@csrf
<label class="d-block"><input type="checkbox" name="enabled" value="1" @if($settings['enabled']) checked @endif> Enable CarryBee</label>
<label class="d-block">Environment<select class="form-control" name="mode"><option value="sandbox" @if($settings['mode']==='sandbox') selected @endif>Sandbox</option><option value="production" @if($settings['mode']==='production') selected @endif>Production</option></select></label>
@foreach(['client_id'=>'Client ID','client_secret'=>'Client Secret','client_context'=>'Client Context'] as $field=>$label)
<label class="d-block">{{ $label }}<input type="password" autocomplete="new-password" name="{{ $field }}" class="form-control" placeholder="Leave empty to retain stored value"></label>
@endforeach
<label class="d-block">Pickup store ID<input class="form-control" name="store_id" value="{{ $settings['store_id'] }}"></label>
<label class="d-block">Default weight (grams)<input type="number" class="form-control" name="weight" min="1" max="25000" value="{{ $settings['weight'] }}" required></label>
<button class="btn btn-primary">Save settings</button>
</form>
<hr><h2 class="h5">Connection and pickup stores</h2>
<button type="button" class="btn btn-outline-primary" id="carrybee-test">Test connection / Fetch stores</button><pre id="carrybee-result" class="mt-2" style="white-space:pre-wrap" aria-live="polite"></pre>
<h2 class="h5">Webhook</h2><p>URL <code>{{ route('carrybee.webhook') }}</code></p>
@if($settings['webhook_secret'])<p>Copy this secret to CarryBee Webhook Integration.</p><input class="form-control" readonly value="{{ $settings['webhook_secret'] }}" aria-label="Webhook secret">@else<p>Save settings to generate the webhook secret.</p>@endif
@else
<h2 class="h5">Order {{ $order->code }}</h2>
<a href="{{ route('carrybee.index') }}">CarryBee settings</a>
<p class="mt-2">CarryBee collectable amount: ৳{{ $order->payment_status==='paid'?0:number_format(ceil((float)$order->grand_total),0) }} · COD rounded up to whole Taka.</p>
@if(($parcel['provider']??'')==='carrybee')
<dl class="mt-3">@foreach(['state','status','consignment_id','invoice','synced_at'] as $field)<dt>{{ ucwords(str_replace('_',' ',$field)) }}</dt><dd>{{ $parcel[$field]??'—' }}</dd>@endforeach</dl>
<a class="btn btn-outline-primary" href="{{ route('store-ops.label',$order->id) }}" target="_blank" rel="noopener">Print / Share courier invoice</a>
<form class="d-inline" method="POST" action="{{ route('carrybee.action',$order->id) }}">@csrf<input type="hidden" name="action" value="sync"><button class="btn btn-outline-primary">Sync / Reconcile</button></form>
<form class="mt-3" method="POST" action="{{ route('carrybee.action',$order->id) }}">@csrf<input type="hidden" name="action" value="cancel"><label>Cancellation reason<input class="form-control" name="reason" maxlength="199" required></label><button class="btn btn-outline-danger">Request parcel cancellation</button></form>
@else
<form class="mt-3" method="POST" action="{{ route('carrybee.action',$order->id) }}">@csrf<input type="hidden" name="action" value="book">
<label class="d-block">Weight (grams)<input name="item_weight" type="number" min="1" max="25000" value="{{ $settings['weight'] }}" class="form-control" required></label>
<label class="d-block">Delivery<select class="form-control" name="delivery_type"><option value="1">Normal</option><option value="2">Express</option></select></label>
@foreach(['city_id'=>'City','zone_id'=>'Zone','area_id'=>'Area'] as $field=>$label)
<label class="d-block">{{ $label }}<select class="form-control" name="{{ $field }}" id="carrybee-{{ $field }}"><option value="">Select {{ $label }}</option></select></label>
@endforeach
<p id="carrybee-address-status" role="status" aria-live="polite">Matching the delivery location…</p>
<label class="d-block">Special instruction<input class="form-control" name="special_instruction" maxlength="254"></label>
<button class="btn btn-success">Send parcel to CarryBee</button></form>
@endif
@endif
</div></div>
@endsection
@section('script')
<script>
@if($order && ($parcel['provider']??'')!=='carrybee')
(async function(){
 const city=document.getElementById('carrybee-city_id'),zone=document.getElementById('carrybee-zone_id'),area=document.getElementById('carrybee-area_id');
 const status=document.getElementById('carrybee-address-status'),lookup=@json(route('carrybee.lookup'));
 const address=@json(json_decode($order->shipping_address,true) ?: []);
 let revision=0;
 function normalize(value){return String(value||'').normalize('NFKC').toLocaleLowerCase().trim();}
 function match(items,text){
  const matches=items.filter(item=>{
   const name=normalize(item.name);if(!name)return false;
   const escaped=name.replace(/[.*+?^$\{\}()|[\]\\]/g,'\\$&');
   return new RegExp('(^|[^\\p{L}\\p{N}])'+escaped+'($|[^\\p{L}\\p{N}])','u').test(normalize(text));
  });
  return matches.length===1?matches[0].id:null;
 }
 function fill(select,items,label,selected){
  select.replaceChildren(new Option('Select '+label,''));
  for(const item of items)select.add(new Option(item.name,String(item.id)));
  if(selected!=null)select.value=String(selected);
 }
 async function locations(kind,cityId,zoneId){
  const url=new URL(lookup);url.searchParams.set('kind',kind);
  if(cityId)url.searchParams.set('city',cityId);if(zoneId)url.searchParams.set('zone',zoneId);
  const response=await fetch(url,{headers:{Accept:'application/json'},cache:'no-store'}),data=await response.json();
  if(!response.ok)throw new Error(data.message||'CarryBee location lookup failed');return data[kind]||[];
 }
 const text=[address.address,address.city].filter(Boolean).join(', ');
 async function loadAreas(version){
  const items=await locations('areas',city.value,zone.value);if(version!==revision)return;
  const selected=match(items,text)??(items.length===1?items[0].id:null);
  fill(area,items,'area',selected);
  status.textContent=selected?'Location filled. Review before booking.':'Choose the delivery area.';
 }
 async function loadZones(version){
  const items=await locations('zones',city.value);if(version!==revision)return;
  fill(zone,items,'zone',match(items,text));
  if(zone.value)await loadAreas(version);else status.textContent='Choose the delivery zone.';
 }
 city.addEventListener('change',async()=>{
  const version=++revision;fill(zone,[],'zone');fill(area,[],'area');if(!city.value)return;
  try{await loadZones(version);}catch(error){if(version===revision)status.textContent=error.message;}
 });
 zone.addEventListener('change',async()=>{
  const version=++revision;fill(area,[],'area');if(!city.value||!zone.value)return;
  try{await loadAreas(version);}catch(error){if(version===revision)status.textContent=error.message;}
 });
 try{
  const version=revision,items=await locations('cities');if(version!==revision)return;
  const exact=items.filter(item=>normalize(item.name)===normalize(address.city));
  fill(city,items,'city',exact.length===1?exact[0].id:match(items,text));
  if(city.value)await loadZones(version);else status.textContent='Choose the delivery city.';
 }catch(error){status.textContent=error.message;}
})();
@endif
document.getElementById('carrybee-test')?.addEventListener('click',async function(){
 const output=document.getElementById('carrybee-result');this.disabled=true;output.textContent='Checking…';
 try {const r=await fetch(@json(route('carrybee.lookup',['kind'=>'stores'])),{headers:{Accept:'application/json'}});const data=await r.json();output.textContent=r.ok?JSON.stringify(data,null,2):(data.message||'Request failed');}
 catch {output.textContent='Connection failed. Please retry.';}finally{this.disabled=false;}
});
</script>
@endsection
