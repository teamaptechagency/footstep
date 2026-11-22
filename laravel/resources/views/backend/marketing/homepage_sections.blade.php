@extends('backend.layouts.app')
@section('content')
<form method="POST" action="{{ route('homepage-sections.save') }}" id="homepage-sections-form">@csrf
<div class="d-flex justify-content-between align-items-center flex-wrap mb-3"><div><h1 class="h3">Homepage Product Sections</h1><p class="text-muted">Product, brand, ক্রম ও auto-slide নিয়ন্ত্রণ করুন। Public design অপরিবর্তিত থাকবে।</p></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="home-section-editor">
@foreach($sections as $key=>$saved)
@php
 $section=old('sections.'.$key,$saved);
 $chosen=array_values(array_map('intval',$section['product_ids']??[]));
@endphp
<section class="card home-section-card" data-home-section="{{ $key }}" data-selected="{{ json_encode($chosen) }}">
<div class="card-header"><h2 class="h6 mb-0">{{ $saved['title'] }}</h2><label class="mb-0"><input type="hidden" name="sections[{{ $key }}][enabled]" value="0"><input type="checkbox" name="sections[{{ $key }}][enabled]" value="1" @if($section['enabled']) checked @endif> Show / দেখান</label></div>
<div class="card-body">
<label>Section title</label><input class="form-control mb-3" name="sections[{{ $key }}][title]" value="{{ $section['title'] }}" required maxlength="80">
<div class="row"><div class="col-6"><label>Section order / ক্রম</label><input class="form-control mb-3" type="number" min="1" max="4" name="sections[{{ $key }}][position]" value="{{ $section['position'] }}" required></div><div class="col-6"><label>Product limit</label><input class="form-control mb-3" type="number" min="1" max="40" name="sections[{{ $key }}][limit]" value="{{ $section['limit'] }}" required></div></div>
<label>Products নেবেন যেখান থেকে</label><select class="form-control mb-3" name="sections[{{ $key }}][source]" data-source>@foreach(['latest'=>'Latest uploads / নতুন product','best'=>'Best selling / বেশি বিক্রি','category'=>'Category','brand'=>'Brand','manual'=>'নিজে product বাছব / Manual'] as $value=>$label)<option value="{{ $value }}" @if($section['source']===$value) selected @endif>{{ $label }}</option>@endforeach</select>
<label>Brand / চাইলে নির্দিষ্ট brand</label><select class="form-control aiz-selectpicker mb-3" data-live-search="true" name="sections[{{ $key }}][brand_id]"><option value="">All brands</option>@foreach($brands as $brand)<option value="{{ $brand->id }}" @if((int)$section['brand_id']===$brand->id) selected @endif>{{ $brand->name }}</option>@endforeach</select>
<div data-category-fields><label>Category</label><select class="form-control aiz-selectpicker mb-3" data-live-search="true" name="sections[{{ $key }}][category_id]"><option value="">Choose category</option>@foreach($categories as $category)<option value="{{ $category->id }}" @if((int)$section['category_id']===$category->id) selected @endif>{{ $category->name }}</option>@endforeach</select></div>
<div data-manual-fields><label>Product বাছুন / ছবি দেখে খুঁজুন</label><select class="form-control aiz-selectpicker mb-2" multiple data-max-options="40" data-live-search="true" data-selected-text-format="count > 1" data-products title="Product বাছুন">@foreach($products as $product)<option value="{{ $product->id }}" data-content="{{ $product->picker_html }}" @if(in_array((int)$product->id,$chosen,true)) selected @endif>#{{ $product->id }} · {{ $product->name }}</option>@endforeach</select><small class="text-muted">নিচের ↑ ↓ দিয়ে ১, ২, ৩ ক্রমে সাজান। শুধু Manual mode-এ এই তালিকা ব্যবহার হবে। সর্বোচ্চ ৪০টি।</small><ol class="home-product-order" data-product-order></ol><div data-product-inputs>@foreach($chosen as $id)<input type="hidden" name="sections[{{ $key }}][product_ids][]" value="{{ $id }}">@endforeach</div></div>
<div class="row mt-3 align-items-center"><div class="col-6"><label><input type="hidden" name="sections[{{ $key }}][auto_slide]" value="0"><input type="checkbox" name="sections[{{ $key }}][auto_slide]" value="1" @if($section['auto_slide']) checked @endif> Auto slide</label></div><div class="col-6"><label>প্রতি কত সেকেন্ড?</label><input class="form-control" type="number" min="2" max="60" required name="sections[{{ $key }}][seconds]" value="{{ $section['seconds'] }}"></div></div>
<div class="home-section-save"><button type="button" class="btn btn-primary" data-save-section="{{ $key }}">Save section / এই অংশ সংরক্ষণ করুন</button><span data-save-status role="status" aria-live="polite"></span></div>
</div></section>
@endforeach
</div>
</form>
<style>.home-section-save{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:22px;padding-top:18px;border-top:1px solid #e2ebe6}.home-section-save [data-save-status]{font-size:12px;line-height:1.7}.home-section-save button{min-height:44px}.home-section-editor{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.home-section-card{min-width:0}.home-section-card .card-header{gap:10px;flex-wrap:wrap}.home-product-option{display:flex;align-items:center;gap:9px;min-width:0}.home-product-option img{width:30px;height:38px;object-fit:cover;border-radius:4px;flex-shrink:0}.home-product-option>span{white-space:normal;overflow-wrap:anywhere}.home-product-order{padding:0;list-style:none;max-height:290px;overflow:auto;margin:12px 0}.home-product-order li{display:flex;gap:8px;align-items:center;border:1px solid #dfebe5;border-radius:8px;padding:8px;margin-bottom:7px}.home-product-order .home-product-option{flex:1;font-size:12px}.home-product-order button{border:1px solid #d4e2da;background:white;border-radius:5px;padding:4px 8px;cursor:pointer}.home-product-order button:disabled{opacity:.35}.home-section-card [hidden]{display:none!important}@media(max-width:991px){.home-section-editor{grid-template-columns:1fr}}</style>
@endsection
@section('script')
<script>
const homeSectionsForm=document.getElementById('homepage-sections-form');
homeSectionsForm.querySelectorAll('[data-save-section]').forEach(function(button){
 button.addEventListener('click',async function(){
  const card=button.closest('[data-home-section]'),key=button.dataset.saveSection,status=card.querySelector('[data-save-status]');
  for(const field of card.querySelectorAll('input,select,textarea')){if(!field.disabled&&!field.checkValidity()){field.reportValidity();return;}}
  const data=new FormData();for(const [name,value] of new FormData(homeSectionsForm)){if(name==='_token'||name.startsWith('sections['+key+']'))data.append(name,value);}data.set('section_key',key);
  const label=button.textContent;button.disabled=true;button.textContent='Saving…';status.textContent='সংরক্ষণ হচ্ছে…';status.style.color='#64786d';
  homeSectionsForm.dataset.pending=String(Number(homeSectionsForm.dataset.pending||0)+1);homeSectionsForm.querySelectorAll('button[type=submit]').forEach(b=>b.disabled=true);
  try{
   const response=await fetch(homeSectionsForm.action,{method:'POST',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},body:data});
   if(response.redirected||response.status===401||response.status===419)throw Error('Session শেষ হয়েছে। পেজ refresh করে আবার চেষ্টা করুন।');
   const result=await response.json();if(!response.ok||!result.success)throw Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'সংরক্ষণ হয়নি। আবার চেষ্টা করুন।');
   status.textContent=result.message;status.style.color='#087c46';
   Object.entries(result.positions||{}).forEach(function([section,position]){const field=homeSectionsForm.elements.namedItem('sections['+section+'][position]');if(field)field.value=position;});
  }catch(error){status.textContent=error.message||'সংযোগে সমস্যা হয়েছে। আবার চেষ্টা করুন।';status.style.color='#b12828';}
  finally{button.disabled=false;button.textContent=label;homeSectionsForm.dataset.pending=String(Math.max(0,Number(homeSectionsForm.dataset.pending||1)-1));homeSectionsForm.querySelectorAll('button[type=submit]').forEach(b=>b.disabled=Number(homeSectionsForm.dataset.pending)>0);}
 });
});
document.querySelectorAll('[data-home-section]').forEach(function(card){
 const key=card.dataset.homeSection,picker=card.querySelector('[data-products]'),list=card.querySelector('[data-product-order]'),inputs=card.querySelector('[data-product-inputs]'),source=card.querySelector('[data-source]');
 let ids=JSON.parse(card.dataset.selected);
 function render(){list.replaceChildren();inputs.replaceChildren();ids.forEach(function(id,index){const option=Array.from(picker.options).find(o=>Number(o.value)===id);if(!option)return;const li=document.createElement('li'),position=document.createElement('strong'),label=document.createElement('span');position.textContent=String(index+1);label.className='home-product-option';const parsed=new DOMParser().parseFromString(option.dataset.content,'text/html');label.append(...Array.from(parsed.body.firstElementChild.childNodes));li.append(position,label);[-1,1].forEach(function(delta){const button=document.createElement('button');button.type='button';button.textContent=delta===-1?'↑':'↓';button.setAttribute('aria-label',delta===-1?'Move product up':'Move product down');button.disabled=index+delta<0||index+delta>=ids.length;button.addEventListener('click',function(){[ids[index],ids[index+delta]]=[ids[index+delta],ids[index]];render();});li.append(button);});list.append(li);const input=document.createElement('input');input.type='hidden';input.name='sections['+key+'][product_ids][]';input.value=id;inputs.append(input);});}
 function changed(){const selected=Array.from(picker.selectedOptions).map(o=>Number(o.value));ids=ids.filter(id=>selected.includes(id));selected.forEach(id=>{if(!ids.includes(id))ids.push(id);});render();}
 if(window.jQuery)jQuery(picker).on('changed.bs.select',changed);else picker.addEventListener('change',changed);
 function mode(){card.querySelector('[data-manual-fields]').hidden=source.value!=='manual';card.querySelector('[data-category-fields]').hidden=source.value!=='category';}source.addEventListener('change',mode);mode();render();
});
</script>
@endsection
