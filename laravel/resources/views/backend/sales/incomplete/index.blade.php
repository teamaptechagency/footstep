@extends('backend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/admin-insights.css') }}?v=2.1.3">
<style>.draft-product{display:flex;align-items:center;gap:12px;min-width:235px;max-width:380px;margin-bottom:12px}.draft-product:last-child{margin-bottom:0}.draft-product-photo{display:block;flex-shrink:0;width:54px;height:72px;border-radius:7px;overflow:hidden;background:#f1f4f2}.draft-product-photo img{display:block;width:100%;height:100%;object-fit:cover}.draft-product>div{min-width:0}.draft-product strong{font-size:12px;line-height:1.6}.draft-product a{color:#236b4f}.draft-product a:hover{text-decoration:underline}.draft-product small{margin-top:5px;font-size:10px}</style>
<div class="store-insights"><div class="insight-page-heading"><div><span class="insight-eyebrow">SALES / CHECKOUT RECOVERY</span><h1>Incomplete Orders <small>/ অসম্পূর্ণ অর্ডার</small></h1><p>Mobile number দেওয়া checkout-গুলো এখানে দেখাবে। Order না হলে ১৫ মিনিট নিষ্ক্রিয় থাকার পর Incomplete হবে।</p></div><a class="insight-secondary" href="{{ route('all_orders.index') }}">Placed orders →</a></div>
@if(!$ready)<div class="insight-notice"><div><strong>Checkout draft storage is not enabled yet.</strong><p>Enable storage to start collecting new incomplete checkout attempts.</p></div>@if(Auth::user()->user_type==='admin')<form action="{{ route('admin.analytics.install') }}" method="POST">@csrf<button class="insight-primary">Enable analytics & checkout storage</button></form>@endif</div>@endif
<div class="insight-stats"><div class="insight-stat"><span>Incomplete checkouts</span><strong>{{ number_format($counts['incomplete']) }}</strong><small>15+ minutes inactive</small></div><div class="insight-stat"><span>COD popup</span><strong>{{ number_format($counts['direct']) }}</strong><small>Incomplete direct checkouts with mobile</small></div><div class="insight-stat"><span>Active checkouts</span><strong>{{ number_format($counts['active']) }}</strong><small>With mobile · active in last 15 minutes</small></div><div class="insight-stat"><span>Completed checkouts</span><strong>{{ number_format($counts['completed']) }}</strong><small>Excluded from incomplete results</small></div></div>
<div class="insight-panel"><form class="insight-acquisition-filters" method="GET" action="{{ route('admin.incomplete-orders') }}"><div><label>Status</label><select name="state" class="form-control">@foreach(['incomplete'=>'Incomplete · 15+ min','active'=>'Active checkout · last 15 min','completed'=>'Order placed','all'=>'All attempts'] as $id=>$label)<option value="{{ $id }}" @if(request('state','incomplete')===$id) selected @endif>{{ $label }}</option>@endforeach</select></div><div><label>Checkout</label><select name="channel" class="form-control"><option value="">All</option><option value="cart" @if(request('channel')==='cart') selected @endif>Cart checkout</option><option value="direct" @if(request('channel')==='direct') selected @endif>COD popup</option></select></div><div><label>Last activity</label><select name="period" class="form-control">@foreach(['all'=>'All time','day'=>'Today','yesterday'=>'Yesterday','week'=>'This week','month'=>'This month','year'=>'This year'] as $id=>$label)<option value="{{ $id }}" @if(request('period','all')===$id) selected @endif>{{ $label }}</option>@endforeach</select></div><button class="insight-primary">Filter</button><a href="{{ route('admin.incomplete-orders') }}" class="insight-secondary">Reset</a></form>
<div class="insight-table-wrap"><table class="insight-table"><thead><tr><th>Customer details</th><th>Products / sizes</th><th>Checkout</th><th>First visit</th><th>Last activity</th><th>Status</th></tr></thead><tbody>
@if($rows)@foreach($rows as $row)@php($details=\App\Services\CheckoutDrafts::read($row))@php($items=json_decode($row->items?:'[]',true)?:[])<tr><td><strong>{{ !empty($details['name'])?$details['name']:($row->has_details?'Details started':'Anonymous visitor') }}</strong>@if(!empty($details['phone']))
    <?php
        $asciiPhone = strtr((string) $details['phone'], ['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
        $whatsapp = preg_replace('/[^0-9]/', '', $asciiPhone);
        if (substr($whatsapp, 0, 2) === '00') {
            $whatsapp = substr($whatsapp, 2);
        }
        if (preg_match('/^01[3-9][0-9]{8}$/D', $whatsapp)) {
            $whatsapp = '88'.$whatsapp;
        }
        $message = ['আসসালামু আলাইকুম, Footstep থেকে বলছি। আপনি একটি অর্ডার করতে চাচ্ছিলেন। এখন কি অর্ডারটি কনফার্ম করতে চান?',''];
        foreach (['name'=>'নাম','phone'=>'মোবাইল','address'=>'ঠিকানা','city'=>'শহর'] as $field => $label) {
            $value = trim((string) ($details[$field] ?? ''));
            if ($value !== '') {
                $message[] = $label.': '.$value;
            }
        }
        foreach ($items as $item) {
            $product = $productMap->get($item['id'] ?? 0);
            $message[] = '';
            $message[] = 'প্রোডাক্ট: '.($product->name ?? $item['name'] ?? 'প্রোডাক্ট');
            $message[] = 'সাইজ: '.($item['variant'] ?? 'Standard').' | পরিমাণ: '.($item['quantity'] ?? 1);
            if ($product && $product->published) {
                $message[] = route('product', $product->slug);
            }
        }
        $message[] = '';
        $message[] = 'কনফার্ম করতে চাইলে এই মেসেজে রিপ্লাই দিন। ধন্যবাদ।';
        $messageQuery = rawurlencode(implode("\n", $message));
    ?>
    <div class="d-flex flex-wrap align-items-center">
        <span class="mr-2">{{ $details['phone'] }}</span>
        @if(preg_match('/^8801[3-9][0-9]{8}$/D', $whatsapp) || (preg_match('/^\s*(?:\+|00)/', $asciiPhone) && preg_match('/^(?!880)[1-9][0-9]{7,14}$/D', $whatsapp)))
            <a class="btn btn-sm btn-outline-success" href="https://wa.me/{{ $whatsapp }}?text={{ $messageQuery }}" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        @endif
    </div>
@endif
 @if(!empty($details['address']))<small class="d-block text-muted" style="max-width:230px;line-height:1.7">{{ $details['address'] }}</small>@endif @if(!empty($details['city']))<small class="d-block text-muted">{{ $details['city'] }}</small>@endif @if(!$row->has_details)<small class="d-block text-muted">Visit only · no details</small>@endif</td><td>@foreach($items as $item)
@php($draftProduct=$productMap->get($item['id']??0))
@php($draftProductUrl=$draftProduct&&$draftProduct->published?route('product',$draftProduct->slug):null)
<div class="draft-product">
 @if($draftProductUrl)<a class="draft-product-photo" href="{{ $draftProductUrl }}" target="_blank" rel="noopener" aria-label="View {{ $draftProduct->name }}">@else<span class="draft-product-photo">@endif
 <img src="{{ $draftProduct?\App\Services\OptimizedImages::asset($draftProduct->thumbnail_img):static_asset('assets/img/placeholder.jpg') }}" alt="{{ $item['name']??($draftProduct->name??'Product') }}" width="54" height="72" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
 @if($draftProductUrl)</a>@else</span>@endif
 <div>@if($draftProductUrl)<a href="{{ $draftProductUrl }}" target="_blank" rel="noopener"><strong>{{ $draftProduct->name }}</strong></a>@else<strong>{{ $item['name']??($draftProduct->name??'Product') }}</strong><small class="d-block text-muted">Product no longer available</small>@endif<small class="d-block text-muted">Size: {{ $item['variant']??'Standard' }} · {{ $item['quantity']??1 }} pair / unit</small></div>
</div>@endforeach</td><td>{{ $row->channel==='direct'?'COD popup':'Cart checkout' }}<small class="d-block text-muted">#{{ $row->id }}</small></td><td>{{ \Carbon\Carbon::parse($row->first_seen,'UTC')->timezone('Asia/Dhaka')->format('d M Y') }}<small class="d-block text-muted">{{ \Carbon\Carbon::parse($row->first_seen,'UTC')->timezone('Asia/Dhaka')->format('g:i:s A') }}</small></td><td>{{ \Carbon\Carbon::parse($row->last_seen,'UTC')->timezone('Asia/Dhaka')->format('d M, g:i A') }}<small class="d-block text-muted">{{ \Carbon\Carbon::parse($row->last_seen,'UTC')->diffForHumans() }}</small></td><td><span class="insight-status {{ $row->status==='completed'?'status-delivered':'status-pending' }}">{{ $row->status==='completed'?'Order placed':(\Carbon\Carbon::parse($row->last_seen,'UTC')->gt(now('UTC')->subMinutes(15))?'Active':'Incomplete') }}</span></td></tr>@endforeach @endif
@if(!$rows||$rows->isEmpty())<tr><td colspan="6"><div class="insight-empty">কোনো matching checkout নেই। Customer mobile number দিলে এখানে আসবে; ১৫ মিনিট নিষ্ক্রিয় থাকলে Incomplete তালিকায় দেখাবে।</div></td></tr>@endif
</tbody></table></div>@if($rows)<div class="p-3">{{ $rows->links() }}</div>@endif</div><div class="insight-footnote"><i class="las la-info-circle"></i><p>Only checkouts with a mobile number are listed. Name-only and visit-only attempts are excluded. A completed order is removed from incomplete results. Admin/staff, known bots and opted-out browsers are excluded. No messages are sent automatically. / এখানে কোনো order, stock reservation বা payment তৈরি হয় না।</p></div></div>
@endsection
