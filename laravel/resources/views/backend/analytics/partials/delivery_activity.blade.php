<link rel="stylesheet" href="{{ static_asset('assets/css/admin-delivery-activity.css') }}?v=20261114">
<section class="delivery-activity" id="delivery-daily" aria-labelledby="delivery-title">
    <div class="delivery-heading"><div><span class="delivery-eyebrow">DAILY COURIER REPORT · বাংলাদেশ সময়</span><h2 id="delivery-title">প্রতিদিনের পার্সেল হিসাব</h2><p>কুরিয়ারে বুকিং, ডেলিভারি ও ক্যানসেল আপডেট এক জায়গায়।</p></div><a class="delivery-link" href="{{ route('admin.dashboard',array_merge(request()->only('period','delivery_from','delivery_to'),['refresh_delivery'=>1])) }}#delivery-daily">↻ রিপোর্ট রিফ্রেশ</a></div>
    <form class="delivery-filter" action="{{ route('admin.dashboard') }}#delivery-daily" method="get">
        <input type="hidden" name="period" value="{{ request('period','day') }}">
        <label>শুরুর তারিখ<input type="date" name="delivery_from" value="{{ $delivery['from'] }}" required></label>
        <label>শেষ তারিখ<input type="date" name="delivery_to" value="{{ $delivery['to'] }}" required></label>
        <button type="submit">রিপোর্ট দেখুন</button><span>সর্বোচ্চ ৯০ দিনের হিসাব</span>
    </form>
    <div class="delivery-metrics">
        <div><span>কুরিয়ারে বুকিং</span><strong>{{ number_format($delivery['totals']['booked']) }}</strong><small>নির্বাচিত তারিখে গৃহীত বুকিং</small></div>
        <div class="delivery-success"><span>ডেলিভারি আপডেট</span><strong>{{ number_format($delivery['totals']['delivered']) }}</strong><small>সফল ডেলিভারি প্রথম জানা গেছে</small></div>
        <div class="delivery-danger"><span>ক্যানসেল আপডেট</span><strong>{{ number_format($delivery['totals']['cancelled']) }}</strong><small>ক্যানসেল প্রথম জানা গেছে</small></div>
        <div><span>এখনও প্রক্রিয়াধীন</span><strong>{{ number_format($delivery['current']['pending']) }}</strong><small>সব তারিখের বুক করা অসমাপ্ত পার্সেল</small></div>
    </div>
    <div class="delivery-table-wrap"><table><thead><tr><th>তারিখ · BD</th><th>বুকিং</th><th>ডেলিভারি</th><th>ক্যানসেল</th></tr></thead><tbody>
    @foreach($delivery['days'] as $day=>$counts)
        <tr><th scope="row">{{ \Carbon\Carbon::parse($day)->format('d M Y') }} @if($day===now('Asia/Dhaka')->format('Y-m-d'))<span class="delivery-today">আজ</span>@endif</th><td>{{ $counts['booked'] }}</td><td class="delivery-success">{{ $counts['delivered'] }}</td><td class="delivery-danger">{{ $counts['cancelled'] }}</td></tr>
    @endforeach
    </tbody></table></div>
    <p class="delivery-note">বুকিং মানে courier API অর্ডার গ্রহণ করেছে; এটি pickup-এর প্রমাণ নয়। ডেলিভারি/ক্যানসেলের তারিখ হলো website-এ আপডেট প্রথম পাওয়ার দিন—কুরিয়ারের প্রকৃত ঘটনার সময় ভিন্ন হতে পারে। রিফ্রেশ শুধু জমা তথ্য দেখায়; courier sync আলাদাভাবে চলে।</p>
    @if(array_sum($delivery['unknown']))<div class="delivery-history"><strong>পুরোনো পার্সেল · তারিখ নিশ্চিত নয়</strong><span>বুকিং {{ number_format($delivery['unknown']['booked']) }} · ডেলিভারি {{ number_format($delivery['unknown']['delivered']) }} · ক্যানসেল {{ number_format($delivery['unknown']['cancelled']) }}</span><small>এগুলো উপরের দৈনিক হিসাবে যোগ করা হয়নি। পুরোনো synced_at-কে বুকিং বা ডেলিভারির তারিখ ধরা হয়নি।</small></div>@endif
    @if(!$delivery['ready'])<p class="alert alert-warning">পার্সেলের রিপোর্টের storage এখনও প্রস্তুত হয়নি।</p>@endif
</section>
