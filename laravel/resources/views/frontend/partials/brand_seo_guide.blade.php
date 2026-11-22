@if(!empty($listingSeo['guide']) && !$query && (int)request('page',1)===1)
<section class="container pb-4" aria-labelledby="brand-buying-heading">
 <div class="bg-white border rounded p-3 p-md-4" style="line-height:1.9">
  <h2 id="brand-buying-heading" class="h5 fw-600">{{ $listingSeo['heading'] }} — কেনার আগে যা দেখবেন</h2>
  <p>{{ $listingSeo['guide']['body'] }}</p>
  @foreach(($listingSeo['guide']['sections']??[]) as $guideSection)
  <h3 class="h6 fw-600 mt-3">{{ $guideSection['title'] }}</h3><p>{{ $guideSection['body'] }}</p>
  @endforeach
  <p><strong>Fit ও size:</strong> {{ $listingSeo['guide']['tip'] }} <a href="/size-guide">Size Guide দেখুন</a>।</p>
  <p class="mb-2"><strong>দাম ও delivery:</strong> বর্তমান দাম ও available size প্রতিটি product page-এ দেখুন। পছন্দের পণ্য ও delivery এলাকা বাছাইয়ের পরে order summary-তে মোট মূল্য মিলিয়ে নিন। <a href="/buying-guide">পুরো Buying Guide পড়ুন</a>।</p>
  <p class="mb-3">ব্র্যান্ডের নাম প্রস্তুতকারকের অনুমোদিত বিক্রেতা হওয়ার দাবি নয়। প্রতিটি listing-এর ঘোষিত মান/গ্রেড ও product details দেখে সিদ্ধান্ত নিন।</p>
  <nav aria-label="Other shoe collections"><strong>আরও brand collection:</strong>
   @foreach(\App\Services\StoreSeo::index()['brands'] as $seoBrand)
    <a class="d-inline-block mr-3 mb-1" href="{{ route('products.brand',$seoBrand['slug']) }}">{{ $seoBrand['name'] }}</a>
   @endforeach
  </nav>
 </div>
</section>
<script type="application/ld+json">{!! json_encode(['@context'=>'https://schema.org','@type'=>'CollectionPage','name'=>$listingSeo['heading'],'description'=>$listingSeo['description'],'url'=>\App\Services\StoreSeo::canonical()],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) !!}</script>
@endif
