<?php
namespace App\Services;
class PerformanceInsights {
 public static function label($row){$r=$row['route']??'';$labels=['home'=>'Homepage / হোমপেজ','product'=>'Product / প্রোডাক্ট','platform.images.batch'=>'Image optimization / ছবি optimize','analytics.collect'=>'Visitor analytics / ভিজিটর তথ্য','checkout.draft'=>'Checkout draft / checkout তথ্য','orders.track'=>'Order tracking / অর্ডার ট্র্যাকিং','admin.dashboard'=>'Admin dashboard','flash-deal-details'=>'Flash deal','all_orders.index'=>'All orders'];return $labels[$r]??str_replace(['.','_'],[' / ',' '],$r?:($row['pattern']??'Unknown request'));}
 public static function explain($r){$out=[];$status=(int)$r['status'];$total=(float)$r['duration_ms'];$sql=(float)$r['sql_ms'];$p=$r['phases']??[];
 if($status===419)$out[]=['level'=>'danger','text'=>'Session/CSRF যাচাই ব্যর্থ; এই request-এর কাজ save হয়নি।','next'=>'পুরোনো tab বন্ধ করে নতুন করে login/reload করুন; বারবার হলে cookie/CSRF configuration পরীক্ষা করুন।'];
 elseif($status===404)$out[]=['level'=>'warning','text'=>'এই page/endpoint পাওয়া যায়নি।','next'=>'Button/link এবং route মিলিয়ে ঠিক করতে হবে।'];
 elseif($status>=500)$out[]=['level'=>'danger','text'=>'Server error হয়েছে; শুধু timing দিয়ে error-এর কারণ জানা যায় না।','next'=>'এই সময়ের Laravel error log দেখুন; Request ID সঙ্গে দিন।'];
 if($total>=500&&$sql/max(1,$total)>=.5)$out[]=['level'=>'warning','text'=>'মোট সময়ের বড় অংশ database query-তে গেছে।','next'=>'Query plan, index ও বারবার query পরীক্ষা করুন।'];
 if(($p['http_wait']['ms']??0)>=300)$out[]=['level'=>'warning','text'=>'বাইরের API-এর response-এর জন্য অপেক্ষা হয়েছে—সরাসরি মাপা।','next'=>'API timeout/cache ও worker দিয়ে কাজটি পরে করা সম্ভব কি না পরীক্ষা করুন।'];
 if(($p['controller']['ms']??0)>=500)$out[]=['level'=>'info','text'=>'Backend controller-এর কাজ করতে সময় গেছে—সরাসরি মাপা। এর মধ্যে SQL/API/image-এর সময়ও থাকতে পারে।','next'=>'নিচের আলাদা SQL/API/image সময়ের সঙ্গে মিলিয়ে পড়ুন; এগুলো যোগ করবেন না।'];
 if(($p['image_processing']['ms']??0)>=300)$out[]=['level'=>'warning','text'=>'ছবি তৈরি/optimize করতে সময় গেছে—এটি সরাসরি মাপা হয়েছে।','next'=>'Bulk কাজ কম traffic-এর সময় চালান; worker-এ নেওয়া যায় কি না দেখুন।'];
 if(($p['image_metadata']['ms']??0)>=200)$out[]=['level'=>'warning','text'=>'ছবির dimensions/EXIF/file metadata পড়তে সময় গেছে—সরাসরি মাপা।','next'=>'Upload/optimization-এর সময় metadata সংরক্ষণ করে public page-এ সেই তথ্য ব্যবহার করুন।'];
 if(($r['unmeasured_ms']??0)>=500)$out[]=['level'=>'warning','text'=>'কিছু সময় এখনও আলাদাভাবে ধরা যায়নি; CPU, disk বা external wait—কোনটি তা নিশ্চিত নয়।','next'=>'Hosting CPU/I/O ও PHP worker status মিলিয়ে দেখুন; অনুমানকে নিশ্চিত কারণ ধরবেন না।'];
 if(!array_key_exists('phases',$r))$out[]=['level'=>'info','text'=>'পুরোনো log: SQL ছাড়া আলাদা কাজের সময় মাপা হয়নি।','next'=>'Update-এর পরে page আবার ব্যবহার করে নতুন log নিন।'];
 if(!$out)$out[]=['level'=>'info','text'=>'এই request-এ বড় সমস্যা চিহ্নিত হয়নি।','next'=>'Browser/network সময় এই report-এ নেই; ব্যবহারকারীর দেরি থাকলে সেটিও পরীক্ষা করুন।'];return $out;}
}
