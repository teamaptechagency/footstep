<?php
return [
 'tiktok'=>[
  'summary'=>'TikTok বিজ্ঞাপনের website activity মাপার browser Pixel settings।',
  'id_label'=>'TikTok Pixel ID',
  'steps'=>['TikTok Ads Manager → Tools → Events / Events Manager খুলুন।','Website data source খুলে Pixel ID copy করুন। এখানে ID দিয়ে Enable ও Save করুন।','Events Manager → Test Events ও TikTok Pixel Helper দিয়ে আসল receipt পরীক্ষা করুন।'],
  'fields'=>['account_id'=>['label'=>'Ads account ID (ঐচ্ছিক reference)','pattern'=>'/^[0-9]{1,30}$/','max'=>30,'hint'=>'Ads Manager-এর account ID; Pixel ID থেকে আলাদা।']],
  'help'=>'https://ads.tiktok.com/help/article/get-started-pixel?lang=en',
  'test'=>'https://ads.tiktok.com/',
  'scope'=>'এটি browser Pixel setup। Events API token বা server-side Purchase connector এই form চালু করে না।',
 ],
 'ga4'=>[
  'summary'=>'Website visitors ও activity দেখার জন্য Google Analytics 4।',
  'id_label'=>'Measurement ID',
  'steps'=>['Google Analytics → Admin → Property তৈরি/নির্বাচন করুন।','Data streams → Web → footsteponline.com stream খুলে G- দিয়ে শুরু Measurement ID copy করুন।','এখানে ID save করার পরে Analytics Realtime ও Google Tag Assistant দিয়ে receipt পরীক্ষা করুন।'],
  'fields'=>['property_id'=>['label'=>'Property ID (ঐচ্ছিক reference)','pattern'=>'/^[0-9]{1,30}$/','max'=>30,'hint'=>'Admin → Property details-এর numeric ID; এটি Measurement ID নয়।'],'stream_id'=>['label'=>'Web stream ID (ঐচ্ছিক reference)','pattern'=>'/^[0-9]{1,30}$/','max'=>30,'hint'=>'Data stream-এর numeric ID; G- ID-এর বিকল্প নয়।']],
  'help'=>'https://support.google.com/analytics/answer/12270356',
  'test'=>'https://analytics.google.com/',
  'scope'=>'Next.js storefront-এ consent-aware page-view adapter আছে। Measurement Protocol API secret বা server-side ecommerce mapping এই form-এর অংশ নয়।',
 ],
 'gtm'=>[
  'summary'=>'একটি Web container থেকে tags ও triggers পরিচালনা করুন।',
  'id_label'=>'Web Container ID',
  'steps'=>['Google Tag Manager → Account / Web container তৈরি বা নির্বাচন করুন।','Workspace-এর GTM- দিয়ে শুরু Container ID copy করুন।','Tags ও triggers তৈরি করে Preview পরীক্ষা করুন, তারপর Submit / Publish করুন। শুধু container save করলে tags publish হয় না।'],
  'fields'=>['container_name'=>['label'=>'Container name (ঐচ্ছিক reference)','pattern'=>null,'max'=>100,'hint'=>'আপনার বোঝার সুবিধার জন্য নাম; যেমন Footstep Website।']],
  'help'=>'https://support.google.com/tagmanager/answer/14847097',
  'test'=>'https://tagassistant.google.com/',
  'scope'=>'GTM-এ একই GA4/Ads/Pixel tag থাকলে direct integration-ও একসঙ্গে চালু করে duplicate events তৈরি করবেন না। Custom HTML বা raw JavaScript এখানে paste করবেন না।',
 ],
 'google_ads'=>[
  'summary'=>'Google Ads website tag ও conversion setup-এর প্রয়োজনীয় তথ্য।',
  'id_label'=>'Google Ads tag / Conversion ID',
  'steps'=>['Google Ads → Goals → Conversions-এ website conversion action খুলুন/তৈরি করুন।','Tag setup → Use Google Tag Manager থেকে Conversion ID ও Conversion Label নিন। ID এখানে AW- সহ লিখুন।','Conversion Label থাকলে নিচে রাখুন এবং GTM-এ conversion tag/trigger map ও publish করুন। Tag Assistant দিয়ে পরীক্ষা করুন।'],
  'fields'=>['conversion_label'=>['label'=>'Purchase Conversion Label (ঐচ্ছিক)','pattern'=>'/^[A-Za-z0-9_-]{1,100}$/','max'=>100,'hint'=>'Conversion action-এর label। Reference হিসেবে save হয়; এটি save করলেই automatic Purchase event চালু হয় না।']],
  'help'=>'https://support.google.com/tagmanager/answer/6105160',
  'test'=>'https://tagassistant.google.com/',
  'scope'=>'Base tag আর Purchase conversion আলাদা। Label/trigger mapping ও actual receipt যাচাই ছাড়া purchase tracking connected বলা যাবে না।',
 ],
];
