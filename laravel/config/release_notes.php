<?php

return array (
 '2.4.11'=>['title'=>"Cleaner & Media Center — ক্লিনার ও মিডিয়া",'changes'=>["Separate rate-limit counters for update upload/install/rollback, image optimization and Admin search. — Update ও অন্য কাজের request limit আলাদা।","Header search finds products and available Admin menu pages. — Top bar থেকে product ও Admin page search।", "Performance logs have search/filters and plain Bengali explanations. — Log search/filter ও সহজ diagnosis।", "Measure controller, HTML rendering, image metadata/processing, Laravel HTTP wait and uncovered time. — Backend/HTML/image/API সময় আলাদা মাপা।", "Cleaner groups Cache Management, Unused Images and Product Cleaner. — Cleaner menu-এ তিনটি tool।", "Filter old products by 3/6/12 months or custom added/updated dates; single/bulk delete or unpublish. — পুরোনো product filter ও single/bulk action।", "Products with orders are retained; use Unpublish to preserve invoices. — Order থাকা product ও invoice অক্ষত।", "Media Center lists uploaded images, video, audio and files with filters, preview and link copying. — Filter/preview সহ Media Center।", "Single/bulk Trash, Restore and permanent unused-media deletion with progress and stop. — Progress/stop সহ Trash/restore/permanent delete।", "Recheck references before removal; keep logos, settings and original image versions protected. — ব্যবহৃত ছবি ও original versions সুরক্ষিত।", "Uploaded/restored/optimized public files use readable permissions. — Upload/restore/optimized file-এর public permission।"]],

 '2.4.10'=>['title'=>'Image access repair — ছবির permission ঠিক করুন','changes'=>['Authenticated Admin repair for public image and asset permissions. — Admin থেকে public image/asset permission repair।','Process 1000 entries per batch, with stop control and failure counts. — Batch progress, stop ও failure count।','Public files 644 and folders 755; private application files remain outside this operation. — Public files 644 ও folder 755।']],

 '2.4.9'=>['title'=>'Order protection compatibility fix — Order protection সংশোধন','changes'=>['Replace nullsafe syntax in Store Operations and Order Protection with explicit checks. — দুই service-এ explicit null check।','Fix middleware parse failure on older PHP; PHP 8.2 is recommended for this deployment. — Middleware parse error fix; PHP 8.2 ব্যবহার করুন।','Preserve customer blocking, invoice edits and all existing data. — Customer blocking, invoice edit ও data অক্ষত।']],

 '2.4.8'=>['title'=>'Complete deployment & live Inbox — সব update ও live Inbox','changes'=>['Website Chat moved into Inbox tabs; messages and conversation lists update without refresh. — Inbox tab-এ Website Chat ও auto update।','Incoming-message sound toggle and centered notification bell. — Message sound control ও bell alignment।','Cleaner UI, invoice links/actions, Inbox validation and private performance logs. — Cleaner UI, invoice, validation ও performance logs।','Includes all earlier store, checkout, analytics, image tools, backup, protection and integrations updates. — আগের সব feature অন্তর্ভুক্ত।']],

 '2.4.6'=>['title'=>'Purchase currency validation — Purchase currency/value যাচাই','changes'=>['Notification bell moved beside the profile with an unread badge. — Profile-এর পাশে notification bell ও unread badge।','Inbox setup/guide preserved while correcting the server release base. — Inbox setup/guide রেখে server base সংশোধন।','Normalize currency casing/whitespace and validate numeric Purchase amounts for browser/CAPI. — Browser/CAPI-এ currency ও numeric value যাচাই।','Preserve event IDs and block purchases with unknown amounts. — Event ID রেখে অজানা amount-এর Purchase বন্ধ।','Historical queued purchases without currency require review rather than guessing. — পুরোনো queued event-এর currency আন্দাজ করা হবে না।']],

 '2.4.5'=>['title'=>'Organized integrations & protection — Integration এবং protection menu','changes'=>['Steadfast and Inbox connections moved to Integrations. — Steadfast ও Inbox settings Integrations-এ।','Dedicated Customer Protection page groups IP, number and account block/unblock. — এক page-এ IP/number/account protection।','Demo/test inboxes hidden from the operational inbox without deleting data. — Data না মুছে Demo Inbox লুকানো।']],

 '2.4.4'=>['title'=>'Order-list IP & number blocks — অর্ডার list থেকে block','changes'=>['Block/unblock the recorded IP or customer number directly in All/Inhouse Orders. — Order list থেকে IP/number block-unblock।','Number-only blocking rejects guest orders without banning the registered account. — Account ban ছাড়াই guest number block।','Old orders without IP remain clearly marked; number blocking still works. — পুরোনো order-এ IP না থাকলেও number block।']],

 '2.4.3'=>['title'=>'Compact popup size selector — ছোট editable size','changes'=>['Move the size selector beside the product title in checkout popups. — Popup-এ product নামের পাশে size।','Keep selected size, available options and server verification unchanged. — Selected size, stock এবং server verification রাখা হয়েছে।']],

 '2.4.2'=>['title'=>'Order IP & customer blocking — অর্ডার থেকে IP/customer block','changes'=>['Record server-derived IP for new orders and show only in admin. — নতুন order-এর IP admin-এ দেখাবে।','Block/unblock IP or customer directly from the invoice/order page. — Invoice থেকে IP/customer block-unblock।','Guest phone blocking and registered account blocking guard checkout. — Guest mobile ও customer account দিয়ে checkout guard।']],

 '2.4.1'=>['title'=>'Invoice photos & product removal — ইনভয়েস ছবি ও product remove','changes'=>['Product photos restored across invoice, shared link, print, email and PDF. — সব invoice-এ product ছবি।','Remove selected invoice products with stock/total recalculation and undo before saving. — Product remove, stock/total হিসাব ও save-এর আগে undo।','Empty, paid, completed and live-courier invoice removals are guarded. — অনুপযুক্ত invoice পরিবর্তন বন্ধ।']],

 '2.4.0'=>['title'=>'Orders, courier & store operations — অর্ডার ও store management','changes'=>['Unified invoice document with admin edit/cancel and customer-safe shared links. — একই invoice design ও admin edit/cancel।','Encrypted Steadfast settings, mock booking, duplicate protection and delivery sync/webhook. — Steadfast mock/live, booking protection ও delivery sync।','Guest-safe notifications for all admins and refreshed notification count. — Guest order notification ও live count।','Unused image scan/bulk quarantine/restore, exact IP blocking and random Today Deal. — Image cleaner, IP block ও random Today Deal।','Order buttons open a checkout popup; other card clicks open the product. — Button-এ popup, card-এ product page।']],

 '2.3.19'=>['title'=>'Portrait product thumbnails — 450×600 thumbnail','changes'=>['Product card thumbnails use a 3:4 display ratio. — Product card-এ 3:4 ratio।','450×600 preset and bulk conversion preserve originals for restore. — 450×600 preset/bulk convert; original restore রাখা হয়েছে।']],

 '2.3.18'=>['title'=>'Instant order popup — দ্রুত অর্ডার popup','changes'=>['Open the COD popup immediately while price and stock are checked in the background. — Popup সঙ্গে সঙ্গে খুলবে; ভেতরে মূল্য/stock যাচাই হবে।','Selected size stays visible; confirm waits for a valid quote. — বাছাই করা size দেখাবে; যাচাইয়ের পরে confirm।','Abort stale quotes and allow retry on slow/failed requests. — পুরোনো request বাতিল ও retry।']],

 '2.3.17'=>['title'=>'Optional backup PIN — ঐচ্ছিক backup PIN','changes'=>['Use a 5+ character PIN/password or leave it blank for a password-free ZIP. — ৫+ অক্ষরের PIN অথবা password ছাড়া ZIP।','Admin password remains separate; existing encrypted backups are supported. — Admin password আলাদা; পুরোনো backup আগের মতো চলবে।']],

 '2.3.16'=>['title'=>'Backup diagnostics — Backup failure log','changes'=>['Separate backup middleware records safe diagnostic events and request IDs. — আলাদা safe log ও Request ID।','Admin diagnostic panel checks write permissions and PHP backup extensions. — Permission ও PHP readiness check।','Clear browser/network/server error details without exposing secrets. — Secrets ছাড়া পরিষ্কার error details।']],

 '2.3.15'=>['title'=>'Backup request fixes — আটকে থাকা request ঠিক করা','changes'=>['Validation errors return JSON instead of redirecting to HTML. — ভুল input-এ সরাসরি error দেখাবে।','Whole-response timeout and mandatory job ID prevent indefinite submitting state. — পুরো response timeout ও job ID যাচাই।']],

 '2.3.14'=>['title'=>'Background backup & safe stop — Backend backup ও Stop button','changes'=>['Backup continues in the worker across browser refresh or tab close. — Refresh/tab বন্ধ হলেও worker-এ backup চলবে।','Safe Stop backup button, cancelled status and incomplete ZIP removal. — নিরাপদ Stop, cancelled status ও অসম্পূর্ণ ZIP অপসারণ।','Restore safety backups cannot be cancelled. — Restore safety backup বন্ধ করা যাবে না।']],

 '2.3.13'=>['title'=>'Clear backup progress — স্পষ্ট backup progress','changes'=>['Stage labels, real file/table counts and percentage for the current stage. — ধাপ, file/table count ও চলমান ধাপের percentage।','Separate worker waiting and completion states; no misleading full bar. — Worker waiting এবং completion আলাদা দেখানো।']],

 '2.3.12'=>['title'=>'Update, Backup & Restore menu — একসঙ্গে update ও backup menu','changes'=>['Updates and website backups are grouped under Update, Backup & Restore. — একই menu-তে update এবং backup/restore।']],

 '2.3.11'=>['title'=>'Website backup, restore & checkup — ওয়েবসাইট ব্যাকআপ ও পুনরুদ্ধার','changes'=>['One encrypted ZIP for files, product images and database. — ফাইল, ছবি ও database এক encrypted ZIP-এ।','Choose files, database or both; mandatory safety backup before restore. — আলাদা restore এবং আগে safety backup।','Cache-excluding missing/changed file checkup and database row counts. — Cache বাদে file checkup ও database row count।']],

 '2.3.10'=>['title'=>'Image dimension filters — ছবির px filter','changes'=>['Show images under 300, 600, 1000 or a custom px threshold. — 300/600/1000 বা custom px-এর কম ছবি দেখুন।','Filter width, height, either side or both sides; top/bottom controls stay synchronized. — Width/height/either/both filter ও ওপর/নিচে sync।','Name search and dimension sorting work together; hidden rows are deselected for safer bulk processing. — Search/sort একসঙ্গে; hidden rows bulk selection থেকে বাদ।']],

 '2.3.9'=>['title'=>'Website Live Chat & image sorting — Website Live Chat ও image sorting','changes'=>['Independent website chat opens only from product or footer buttons; name and mobile are required first. — Product/footer button-এ আলাদা chat; আগে নাম ও নম্বর।','Product chat includes server-verified product, selected size and price snapshot; footer opens general contact. — Product/size/price snapshot; footer-এ general chat।','Separate admin live chat with manual replies, unread filters and close/reopen controls. — আলাদা admin live chat, manual replies, unread filter ও close/reopen।','Visitor sessions isolate conversations, contact/messages are encrypted and polling stops when chat is closed. — Session privacy, encrypted messages এবং chat বন্ধে polling বন্ধ।','Added top/bottom image filters and dimension sorting by area, width or height. — Image list-এর ওপর/নিচে filter ও dimension sorting।']],

 '2.3.8'=>['title'=>'Cache Management — আলাদা ক্যাশ ব্যবস্থাপনা','changes'=>['Added a dedicated cache menu; existing top-bar cache button is unchanged. — আলাদা cache menu, আগের button অপরিবর্তিত।','Separate website server, configured Cloudflare CDN and current-browser cache controls. — Server, Cloudflare CDN ও বর্তমান browser-এর আলাদা controls।','Reports each result honestly; saved browser data and service workers require explicit opt-in. — প্রতি কাজের ফল আলাদা; saved data/service worker আলাদা opt-in।','CDN tokens are encrypted and settings require the admin password; no global ISP/DNS purge claims. — CDN token encrypted, password প্রয়োজন; ISP/DNS clear দাবি নয়।']],

 '2.3.7'=>['title'=>'Image processing progress — ছবির processing progress','changes'=>['Progress indicators cover scan, optimization, resize, square conversion, enhancement and restoration. — Scan, optimize, resize, square, enhance ও restore-এ progress bar।','Bulk progress uses actual completed counts; single requests remain indeterminate until completion. — Bulk-এ আসল count; single কাজ শেষ না হওয়া পর্যন্ত চলমান bar।','Stop and errors retain actual progress rather than showing false completion. — Stop/error-এ ভুল completion নয়, আসল অগ্রগতি দেখাবে।']],

 '2.3.6'=>['title'=>'Separate Image Tools & bulk resizer — আলাদা Image Tools ও bulk resize','changes'=>['Main menu separates Image Optimize, Image Resizer and Image Enhancer. — Main menu-তে Optimize, Resizer ও Enhancer আলাদা।','Resizer has separate Resize and Convert 1:1/600x600 pages. — Resize এবং 1:1/600x600 conversion আলাদা page।','Single/bulk custom dimensions or 1x, 2x, 3x scaling, with bounded image size limits. — Single/bulk custom মাপ অথবা সর্বোচ্চ 3x scaling।','Original and saved draft restoration retained; product gallery stays unchanged. — Original/draft restore ও gallery অক্ষত।']],

 '2.3.5'=>['title'=>'Bulk image conversion, enhancer & restore — Bulk ছবি, enhancer ও restore','changes'=>['Select individual or multiple product thumbnails for 1:1 or exact 600x600 conversion. — Single/bulk select করে 1:1 বা exact 600x600 conversion।','Previous and original thumbnails are retained as selectable drafts; restore affects only the selected product. — Original/আগের ছবি draft-এ থাকে; নির্দিষ্ট product restore।','Non-AI sharpness, brightness and contrast enhancer creates a preview draft before applying. — Non-AI enhancer আগে preview draft তৈরি করে।','Original files and gallery are preserved; stale edits cannot overwrite newer thumbnail choices. — Original ও gallery অক্ষত; পুরোনো request নতুন thumbnail overwrite করতে পারে না।']],

 '2.3.4'=>['title'=>'Square product images — 1:1 product ছবি','changes'=>['Product images use square canvases up to 600px without enlarging small images. — ছোট ছবি বড় না করে সর্বোচ্চ 600px square ছবি।','Checker accepts smaller square images and converts non-square thumbnails. — ছোট square valid; non-square thumbnail convert।','Product cards, cart and gallery use square presentation across layouts. — Product card, cart ও gallery square presentation।']],

 '2.3.3'=>['title'=>'Image optimization panel & thumbnail checker — Image panel ও thumbnail checker','changes'=>['Per-file original/optimized bytes, savings, variants and processing history. — প্রতিটি ফাইলের size comparison, savings ও processing history।','Automatic on/off plus individual and batch manual optimization. — Auto on/off এবং single/batch manual optimization।','600×600 product thumbnail checker with safe convert action; originals and galleries preserved. — Thumbnail checker ও 600×600 convert; original ও gallery অক্ষত।']],

 '2.3.2'=>['title'=>'Backend-only image optimization — শুধু backend-এ image optimization','changes'=>['Public pages use existing optimized files or original images; no on-demand image processing. — Public page-এ existing optimized অথবা original ছবি; কোনো processing নয়।','Legacy optimized-image URLs also serve existing files or redirect to originals without conversion. — পুরোনো image URL-ও conversion ছাড়াই existing ছবি দেখাবে।','Upload, product save and admin batch optimization remain active. — Upload, product save ও admin batch optimization চালু থাকবে।']],

  '2.3.1' => 

  array (

    'title' => 'Order pages, Inbox & release notes fix — Order page, Inbox ও update বিবরণ সংশোধন',

    'changes' => 

    array (

      0 => 'Fixed Blade syntax errors on All Orders and Inhouse Orders. — All Orders ও Inhouse Orders-এর Blade syntax error ঠিক করা হয়েছে।',

      1 => 'Restored the Inbox middleware, webhook controller, worker, message views and UI assets omitted from the previous package. — আগের package-এ বাদ পড়া Inbox middleware, webhook, worker, views ও assets যুক্ত করা হয়েছে।',

      2 => 'Restored bilingual title and What’s New details for version 2.3.0, and added complete 2.3.1 release notes. — 2.3.0-এর বাংলা–ইংরেজি title ও What’s New ফেরত এবং 2.3.1-এর বিবরণ যুক্ত।',

    ),

  ),

  '2.2.2' => 

  array (

    'title' => 'Compressed storefront HTML — কম bandwidth-এ storefront',

    'changes' => 

    array (

      0 => 'Added gzip compression for public HTML when the browser accepts it, leaving JSON, images and admin responses unchanged. — Browser সমর্থন করলে public HTML gzip করা হয়; JSON, image ও admin response অপরিবর্তিত।',

      1 => 'Kept storefront asset selection safe in preview/render contexts without a route. — Route ছাড়া preview/render context-এ asset নির্বাচন নিরাপদ রাখা হয়েছে।',

    ),

  ),

  '2.2.1' => 

  array (

    'title' => 'Responsive template compatibility — Responsive template সংশোধন',

    'changes' => 

    array (

      0 => 'Fixed responsive image attributes alongside Blade conditional attributes across storefront themes. — সব storefront theme-এ Blade conditional attribute-এর সঙ্গে responsive image tag ঠিক করা হয়েছে।',

    ),

  ),

  '2.2.0' => 

  array (

    'title' => 'Mobile speed & automatic image optimization — মোবাইল গতি ও ছবি অপটিমাইজেশন',

    'changes' => 

    array (

      0 => 'Added responsive WebP copies for uploaded images, with original files preserved and safe fallback. — Upload করা ছবির responsive WebP copy, original অক্ষত ও fallback।',

      1 => 'New uploads and product saves automatically prepare optimized sizes; old images can be batch-optimized from Setup → Image Optimization. — নতুন upload ও product save-এ auto optimization; পুরোনো ছবি Setup-এর Image Optimization থেকে batch-এ optimize করা যায়।',

      2 => 'Product cards, galleries, logos and storefront banners use responsive image candidates and async decoding. — Product card, gallery, logo ও banner-এ responsive image ও async decoding।',

      3 => 'Public shopping pages use a lighter vendor bundle without uploader and chart code; account/upload pages keep the full bundle. — Shopping page-এ uploader ও chart ছাড়া হালকা JS; account/upload page-এ আগের library।',

      4 => 'Reduced unused font variants, removed unnecessary Bazar queries and eager-loaded product tax data. — অপ্রয়োজনীয় font variant ও Bazar query কমানো এবং tax data eager-load করা হয়েছে।',

    ),

  ),

  '2.1.7' => 

  array (

    'title' => 'Returning customer order search — নিয়মিত কাস্টমারের order search',

    'changes' => 

    array (

      0 => 'Order and returning-customer search normalize local, international and Bangla mobile numbers so linked order history is complete. — Local, international ও বাংলা mobile number মিলিয়ে Returning Customer-এর সম্পূর্ণ order history খোঁজা যায়।',

    ),

  ),

  '2.1.6' => 

  array (

    'title' => 'Returning customer recognition — নিয়মিত কাস্টমারের বিশেষ অভিজ্ঞতা',

    'changes' => 

    array (

      0 => 'Added a separate Returning Customers list based on normalized order mobile numbers, including guests. — Mobile number অনুযায়ী guest-সহ Returning Customers-এর আলাদা তালিকা।',

      1 => 'Invoices and order confirmation show a welcome-back badge and a special thank-you message when an earlier non-cancelled checkout exists. — আগের non-cancelled order থাকলে invoice ও confirmation-এ welcome-back badge ও বিশেষ ধন্যবাদ বার্তা।',

      2 => 'Admin order lists identify returning-customer orders; cancelled and duplicate split checkouts do not inflate the count. — Admin order list-এ returning badge; cancelled ও একই checkout-এর split order দিয়ে count বাড়ে না।',

      3 => 'No automatic external messages, discounts or priority-delivery promises are added. — বাইরে automatic message বা discount/priority delivery promise যোগ করা হয়নি।',

    ),

  ),

  '2.1.5' => 

  array (

    'title' => 'AM/PM order times & chart switch — AM/PM সময় ও chart switch',

    'changes' => 

    array (

      0 => 'Order views, tracking, history and invoices display Bangladesh time in 12-hour AM/PM format. — Order view, tracking, history ও invoice-এ বাংলাদেশ সময়ের 12-hour AM/PM format।',

      1 => 'Peak-hour labels and chart hover tooltips show AM/PM beside the mouse pointer, including order counts. — Peak hour ও hover tooltip-এ mouse-এর পাশে AM/PM সময় ও order সংখ্যা।',

      2 => 'Order time report supports switching between Bar and Pie with the same data and a remembered browser preference. — একই data Bar ও Pie chart-এ switch করা যায়; browser-এ পছন্দ মনে থাকে।',

    ),

  ),

  '2.1.4' => 

  array (

    'title' => 'Incomplete checkout refinements — অসম্পূর্ণ checkout সংশোধন',

    'changes' => 

    array (

      0 => 'Opted-out browsers skip checkout draft requests on the client as well as the server. — Opt-out browser থেকে draft request পাঠানোও বন্ধ।',

      1 => 'Checkout attempts without a supplied name display a clear anonymous or details-started label. — নাম না থাকলে anonymous বা details-started পরিচয় দেখায়।',

    ),

  ),

  '2.1.3' => 

  array (

    'title' => 'Incomplete checkouts & peak order times — অসম্পূর্ণ অর্ডার ও ব্যস্ত সময়',

    'changes' => 

    array (

      0 => 'Added Incomplete Orders under Sales for checkout visits and partially entered details after 15 minutes of inactivity. — Sales-এ ১৫ মিনিট inactive checkout visit ও আংশিক তথ্যের Incomplete Orders তালিকা।',

      1 => 'Cart checkout and COD popup drafts are captured; supplied customer fields are encrypted, and completed orders leave the incomplete list. — Cart ও COD popup draft থাকে; customer field encrypted এবং completed order অসম্পূর্ণ তালিকা থেকে বাদ।',

      2 => 'Added hourly order placements, busiest weekdays, peak hour and recent exact order times to Analytics. — Analytics-এ ঘণ্টা অনুযায়ী order, ব্যস্ত দিন/সময় ও সাম্প্রতিক order-এর নির্দিষ্ট সময়।',

      3 => 'Order timing supports daily, weekly, monthly and yearly ranges in Bangladesh time. — বাংলাদেশ সময় অনুযায়ী daily/weekly/monthly/yearly report।',

      4 => 'Use Enable analytics storage after updating to create the separate checkout draft table; no old incomplete history is invented. — Update-এর পর Enable analytics storage দিয়ে আলাদা draft table তৈরি করুন; পুরোনো অসম্পূর্ণ ইতিহাস বানানো হয় না।',

    ),

  ),

  '2.1.2' => 

  array (

    'title' => 'Visitor source & product details — ভিজিটর source ও product breakdown',

    'changes' => 

    array (

      0 => 'Added source-to-product breakdown with visitors, views and average active time, plus source/product filters. — Source থেকে কোন product দেখা হয়েছে, visitor/view/time এবং filter যোগ হয়েছে।',

      1 => 'Added first observed page/product per browser in the selected period, grouped by source. — নির্বাচিত সময়ে browser-এর প্রথম দেখা page/product source অনুযায়ী দেখানো হয়।',

      2 => 'New visits retain the original inbound source across internal pages with a 30-minute inactivity limit; known utm_source tags identify common platforms. — নতুন visit-এ মূল source ধরে রাখা হয়; ৩০ মিনিট inactivity limit এবং পরিচিত utm_source tag সমর্থন।',

      3 => 'Historical visits keep their original recorded source; no past journey is fabricated. — পুরোনো data আগের source-সহ থাকবে; পুরোনো journey বানানো হয় না।',

    ),

  ),

  '2.1.1' => 

  array (

    'title' => 'Analytics reporting polish — অ্যানালিটিক্স সংশোধন',

    'changes' => 

    array (

      0 => 'Referrer tracking sends only the origin, without URL paths or query strings. — Referrer-এর শুধু origin পাঠানো হয়; path ও query নয়।',

      1 => 'Clarified the report refresh interval. — Report refresh ও cache-এর বিবরণ পরিষ্কার করা হয়েছে।',

    ),

  ),

  '2.1.0' => 

  array (

    'title' => 'Footstep Insights dashboard & Analytics — নতুন ড্যাশবোর্ড ও অ্যানালিটিক্স',

    'changes' => 

    array (

      0 => 'Added Footstep Insights, a responsive admin dashboard, while preserving the original Classic dashboard. — আগের Classic অক্ষত রেখে নতুন responsive Footstep Insights dashboard যোগ হয়েছে।',

      1 => 'Layout & Appearance includes independent dashboard selection and previews for both versions. — Layout & Appearance থেকে দুই dashboard preview ও নির্বাচন করা যায়।',

      2 => 'Added Analytics beneath Dashboard with daily, weekly, monthly and yearly reports. — Dashboard-এর নিচে Analytics menu ও daily/weekly/monthly/yearly report।',

      3 => 'Reports show unique visitor browsers, page views, active time, popular products/pages, devices and referring domains. — Visitor browser, page view, active time, জনপ্রিয় product/page, device ও source report।',

      4 => 'Top selling products, recent orders and order/payment summaries use actual order data and exclude cancelled orders from value totals. — আসল order data দিয়ে top selling, recent order ও payment summary; বাতিল অর্ডারের value বাদ।',

      5 => 'First-party analytics excludes admin/staff, known bots and opted-out browsers; no account pages or URL query strings are collected. — Admin/staff, bot ও opt-out বাদ; account page ও URL query সংগ্রহ করা হয় না।',

      6 => 'Visitor history starts after Analytics storage is enabled; no past visitor counts are fabricated. Use Enable analytics storage after deployment. — Analytics storage চালুর পর visitor data জমবে; আগের data বানানো নয়। Update-এর পরে Enable analytics storage চাপুন।',

    ),

  ),

  '2.0.24' => 

  array (

    'title' => 'Dashboard cleanup — ড্যাশবোর্ড সংশোধন',

    'changes' => 

    array (

      0 => 'Removed the five integration status cards from the dashboard. Integration settings remain available in the Integrations menu. — Dashboard থেকে পাঁচটি integration status card সরানো হয়েছে; Integrations menu-তে settings থাকবে।',

    ),

  ),

  '2.0.23' => 

  array (

    'title' => 'Dedicated Integrations menu — আলাদা Integrations মেনু',

    'changes' => 

    array (

      0 => 'Moved Meta Pixel & CAPI, tracking integrations, WhatsApp API, Messenger API and SMTP settings into a dedicated Integrations main menu. — Meta Pixel & CAPI, tracking, WhatsApp, Messenger ও SMTP settings আলাদা Integrations main menu-তে রাখা হয়েছে।',

      1 => 'Marketing keeps Flash Deals, with existing staff access rules preserved. — Marketing-এ Flash Deals থাকবে; staff-এর আগের access rule বজায় আছে।',

    ),

  ),

  '2.0.22' => 

  array (

    'title' => 'Flash deal link actions — Flash deal লিংক বাটন',

    'changes' => 

    array (

      0 => 'Added Copy link and View eye buttons for each flash deal. View opens the public deal in a new tab. — প্রতিটি flash deal-এ Copy link ও View eye button যোগ হয়েছে; View নতুন tab-এ public page খুলবে।',

    ),

  ),

  '2.0.21' => 

  array (

    'title' => 'Messenger setup saving — Messenger সেটিং সংরক্ষণ',

    'changes' => 

    array (

      0 => 'Fixed first-time creation of Messenger setup and encrypted credential records. — প্রথমবার Messenger setup ও encrypted credential সেভ ঠিক করা হয়েছে।',

    ),

  ),

  '2.0.20' => 

  array (

    'title' => 'Product photos and Messenger setup — পণ্যের ছবি ও Messenger সেটআপ',

    'changes' => 

    array (

      0 => 'Product thumbnail is limited to one image and gallery to eight images, enforced in the uploader and on the server. — Thumbnail সর্বোচ্চ ১টি এবং gallery সর্বোচ্চ ৮টি।',

      1 => 'Product images automatically become 600×600 on save, keeping proportions on a white canvas; originals remain unchanged. — Save করলে অনুপাত ঠিক রেখে সাদা background-এ 600×600 ছবি হয়; আসল ছবি অক্ষত থাকে।',

      2 => 'Added admin Messenger API setup with encrypted credentials and future product-message options. It remains disabled until the future integration is implemented. — Admin-এ encrypted credentials ও ভবিষ্যৎ message option-সহ Messenger API setup যোগ হয়েছে; এখন সংযোগ বন্ধ।',

    ),

  ),

  '2.0.19' => 

  array (

    'title' => 'Popup and product action layout — পপআপ ও প্রোডাক্ট বাটন সংশোধন',

    'changes' => 

    array (

      0 => 'Select size on the product page before opening checkout; removed popup size selector and header price. — Popup খোলার আগে product page-এ size নির্বাচন; popup size selector ও উপরের দাম বাদ।',

      1 => 'Removed extra WhatsApp fields and the amount from the popup confirm button; total remains in the summary. — Popup-এর বাড়তি WhatsApp field ও confirm button-এর দাম বাদ; মোট summary-তে থাকবে।',

      2 => 'Four product actions now use two equal columns aligned with the large COD button on desktop and mobile. — Desktop ও mobile-এ চারটি button দুই সমান column-এ বড় COD button-এর সঙ্গে align করা হয়েছে।',

    ),

  ),

  '2.0.18' => 

  array (

    'title' => 'Cash on Delivery popup — ক্যাশ অন ডেলিভারি পপআপ',

    'changes' => 

    array (

      0 => 'Sticky Order Now opens a focused Cash on Delivery checkout popup. — নিচের Order Now থেকে Cash on Delivery checkout popup খুলবে।',

      1 => 'Added a large Bengali COD order button above the four product actions. — চারটি product button-এর উপরে বড় বাংলা COD order button যোগ হয়েছে।',

      2 => 'Popup includes size, name, mobile/WhatsApp, delivery area, address and server-calculated totals without notes or coupon fields. — Size, নাম, mobile/WhatsApp, এলাকা, ঠিকানা ও মোট দাম আছে; note ও coupon নেই।',

      3 => 'Popup checkout orders only the selected product and preserves other cart items. — Popup শুধু নির্বাচিত পণ্য অর্ডার করে, cart-এর অন্য পণ্য অক্ষত থাকে।',

    ),

  ),

  '2.0.17' => 

  array (

    'title' => 'Order Now button labels — Order Now বাটনের লেখা',

    'changes' => 

    array (

      0 => 'Updated gallery and product order labels to Order Now. — Gallery ও product-এর order button-এ Order Now লেখা হয়েছে।',

    ),

  ),

  '2.0.16' => 

  array (

    'title' => 'Direct order buttons in galleries — গ্যালারিতে অর্ডার বাটন',

    'changes' => 

    array (

      0 => 'Product cards show Order instead of Add to Cart across all layouts. — সব layout-এর product card-এ Add to Cart-এর বদলে অর্ডার বাটন।',

      1 => 'Products with options open the product order form; products without options go directly to checkout after adding the item. — Size/option থাকলে product order form খুলবে; না থাকলে সরাসরি checkout-এ যাবে।',

      2 => 'Includes checkout size editing, revised delivery areas, admin order filters and the Inhouse Orders menu. — Checkout size পরিবর্তন, delivery এলাকা সংশোধন, admin order filter ও Inhouse Orders menu অন্তর্ভুক্ত।',

    ),

  ),

  '2.0.15' => 

  array (

    'title' => 'Order filters and Sales menu — অর্ডার ফিল্টার ও Sales মেনু',

    'changes' => 

    array (

      0 => 'Fixed inclusive date-range filtering, including the final day. — তারিখের শেষ দিনের সব অর্ডারসহ date filter ঠিক করা হয়েছে।',

      1 => 'Added consistent delivery and payment filters, order/phone/name search, and reset controls. — Delivery ও payment filter, order/phone/name search এবং reset option ঠিক করা হয়েছে।',

      2 => 'Restored Inhouse Orders in the Sales menu and fixed its filter form. — Sales menu-তে Inhouse Orders ফিরিয়ে তার filter form ঠিক করা হয়েছে।',

      3 => 'Includes individual checkout pairs with editable sizes and the removal of sub city delivery area. — Checkout-এ প্রতিটি জোড়া আলাদা, size পরিবর্তন এবং sub city এলাকা বাদ দেওয়া অন্তর্ভুক্ত।',

    ),

  ),

  '2.0.14' => 

  array (

    'title' => 'Checkout size selection — চেকআউটে সাইজ পরিবর্তন',

    'changes' => 

    array (

      0 => 'Each pair is displayed separately without a quantity input. — Quantity input ছাড়া প্রতিটি জোড়া আলাদা দেখানো হয়।',

      1 => 'Change the size or remove a single pair directly in checkout. Prices and totals refresh after the change. — Checkout থেকে প্রতিটি জোড়ার size বদলানো বা remove করা যায়; দাম ও মোট পুনরায় হিসাব হয়।',

      2 => 'Removed sub city Of Dhaka from delivery areas; Dhaka and outside Dhaka remain. — ডেলিভারি এলাকা থেকে sub city Of Dhaka বাদ দেওয়া হয়েছে।',

      3 => 'Existing checkout information is retained while updating a size. — Size পরিবর্তনের সময় পূরণ করা checkout তথ্য থাকে।',

    ),

  ),

  '1.9.17' => 

  array (

    'title' => 'All Admins in Staffs — সকল অ্যাডমিনের তালিকা',

    'changes' => 

    array (

      0 => 'Staffs now includes an All Admins page with search and pagination. — Staffs-এ search ও pagination-সহ All Admins পেজ যোগ হয়েছে।',

      1 => 'Shows admin name, email, phone, status, created date and your own account marker. — Admin-এর নাম, ইমেইল, ফোন, status, তৈরির তারিখ ও নিজের account দেখা যাবে।',

    ),

  ),

  '1.9.16' => 

  array (

    'title' => 'Color pack fix — কালার প্যাক ঠিক করা হয়েছে',

    'changes' => 

    array (

      0 => 'Bazar theme accents now follow the selected color pack. — Bazar theme-এর button, price, menu accent, slider ও countdown নির্বাচিত color pack অনুযায়ী বদলাবে।',

      1 => 'Removed fixed orange overrides from the theme stylesheet. — Theme CSS থেকে নির্দিষ্ট orange রঙের override সরানো হয়েছে।',

    ),

  ),

  '1.9.2' => 

  array (

    'title' => 'Reference layout & Flash Deals — নতুন slider ও flash deal layout',

    'changes' => 

    array (

      0 => 'Horizontal two-column Flash Deal cards replace Top Selling. — Top Selling-এর জায়গায় horizontal Flash Deal card।',

      1 => 'Category, brand and product sliders use arrows, dots and mobile swipe without horizontal scrollbars. — Category, brand ও product slider-এ arrow, dots ও swipe।',

      2 => 'White footer, section underlines, brand row, larger promo banner and actual published customer reviews. — White footer, underline, brand row, বড় banner ও প্রকাশিত review।',

    ),

  ),

  '1.9.0' => 

  array (

    'title' => 'Footstep Bazar theme — নতুন বাজার থিম',

    'changes' => 

    array (

      0 => 'New responsive retail theme inspired by the reference storefront. — Reference অনুযায়ী নতুন responsive retail theme।',

      1 => 'New homepage, category navigation, product cards, catalog and product page styling. — নতুন home, menu, product card, shop ও product design।',

      2 => 'Mobile menu, search and shopping cart drawers with the existing checkout. — Mobile menu, search ও cart drawer এবং আগের checkout।',

      3 => 'Choose Footstep Bazar and Bazar Orange in Layout & Appearance. — Layout & Appearance থেকে theme ও color নির্বাচন করুন।',

    ),

  ),

  '1.8.4' => 

  array (

    'title' => 'Studio storefront & simpler checkout — স্টুডিও ডিজাইন ও সহজ চেকআউট',

    'changes' => 

    array (

      0 => 'Studio design extended to shop, product details, collections, brands and flash-sale pages, with shared product actions. — Shop, product details, collections, brands ও flash-sale পেজে Studio ডিজাইন এবং একই product action যুক্ত হয়েছে।',

      1 => 'One-page Cash on Delivery checkout for guests and signed-in customers across all layouts. — সব layout-এ guest ও logged-in customer-এর জন্য এক পৃষ্ঠার Cash on Delivery checkout।',

      2 => 'Saved customer details prefill checkout; successful orders update the customer profile. — সংরক্ষিত customer তথ্য checkout-এ পূরণ থাকে; সফল অর্ডারের পরে profile update হয়।',

      3 => 'Guest checkout details stay in that browser, with a button to clear saved details. — Guest-এর checkout তথ্য সেই browser-এ থাকে এবং মুছে ফেলার button আছে।',

      4 => 'Product sharing through WhatsApp, Facebook and supported native sharing for Messenger / Instagram. — WhatsApp, Facebook এবং সমর্থিত native share দিয়ে Messenger / Instagram-এ product share করা যায়।',

      5 => 'Sticky product title, price and order controls, including flash-deal product behavior. — Product title, price ও order control-এর sticky bar এবং flash-deal product-এর বিশেষ আচরণ যুক্ত হয়েছে।',

      6 => 'Admin can add and edit imported product reviews. — Admin থেকে imported product review যোগ ও edit করা যায়।',

      7 => 'Footer WhatsApp button matches the social icons in size and style. — Footer-এর WhatsApp icon অন্য social icon-এর সমান size ও style করা হয়েছে।',

    ),

  ),

  '1.8.5' => 

  array (

    'title' => 'Release history & What’s New — আপডেটের ইতিহাস ও নতুন কী আছে',

    'changes' => 

    array (

      0 => 'Release history now shows an update title and an ! information button. — Release history-তে update-এর title এবং ! তথ্য button যুক্ত হয়েছে।',

      1 => 'The information button opens the complete What’s New list for that release. — তথ্য button চাপলে সেই release-এর সম্পূর্ণ What’s New তালিকা খুলবে।',

      2 => 'Added the changes from the previous 1.8.4 release to its existing history entry. — আগের 1.8.4 release-এর কাজগুলো তার existing history entry-তে দেখানো হয়েছে।',

      3 => 'Release titles and change lists are supplied through a signed release-notes catalogue for existing and future versions. — বর্তমান ও ভবিষ্যৎ version-এর title ও পরিবর্তনের তালিকা signed release-notes catalogue দিয়ে দেওয়া হয়।',

      4 => 'Existing timestamps, actions, version numbers, admin IDs and rollback controls are preserved. — আগের সময়, action, version, admin ID এবং rollback control অক্ষত রাখা হয়েছে।',

    ),

  ),

  '1.8.6' => 

  array (

    'title' => 'Bilingual release notes — বাংলা ও ইংরেজিতে আপডেটের বিবরণ',

    'changes' => 

    array (

      0 => 'Release titles and What’s New now show both English and Bangla. — Release title এবং What’s New এখন English ও বাংলায় দেখা যাবে।',

      1 => 'Added Bangla translations for the 1.8.4 and 1.8.5 changes. — 1.8.4 ও 1.8.5-এর পরিবর্তনের বাংলা বিবরণ যুক্ত হয়েছে।',

      2 => 'History labels and popup controls include both languages. — History-এর label ও popup control দুই ভাষায় দেওয়া হয়েছে।',

    ),

  ),

  '2.0.0' => 

  array (

    'title' => 'Order experience & admin management — অর্ডার ও অ্যাডমিন ব্যবস্থাপনা',

    'changes' => 

    array (

      0 => 'Redesigned responsive order confirmation with delivery, payment, product summary and tracking actions. — Confirmation পেজে নতুন responsive UI, delivery, payment, product summary ও tracking button।',

      1 => 'Order number can display its last part followed by the customer mobile number, e.g. 15022038 - 017XXXXXXXX. — অর্ডার নম্বরের শেষ অংশের সঙ্গে কাস্টমারের মোবাইল দেখানো যাবে।',

      2 => 'The same order format appears on confirmation, shared invoice and PDF invoice; original order records remain unchanged. — Confirmation, shared invoice ও PDF invoice-এ একই format; আগের order record অক্ষত।',

      3 => 'Backend option in Layout & Appearance chooses the full original number or last part with customer phone. — Backend থেকে full number অথবা শেষ অংশ ও ফোন নির্বাচন করা যায়।',

      4 => 'Order tracking accepts the original number and the new customer-phone format. — পুরোনো ও নতুন দুই format দিয়েই tracking করা যায়।',

      5 => 'Admin accounts can be added, edited and deleted from Staffs > All Admins, with current-admin password confirmation. — Staffs > All Admins থেকে নিজের পাসওয়ার্ড দিয়ে admin add, edit ও delete করা যায়।',

      6 => 'The first administrator is pinned as Primary Admin and protected against account editing or deletion. — প্রথম admin Primary হিসেবে সংরক্ষিত; তাকে edit/delete করা যাবে না।',

      7 => 'Self-deletion is blocked, new passwords are hashed, and blank edit passwords retain the existing password. — নিজের account delete বন্ধ; password hash হয়; edit-এ password খালি রাখলে আগেরটি থাকে।',

      8 => 'Admin role/status labels and protected-account indicators are clearer. — Admin role/status ও protected account চিহ্ন আরও পরিষ্কার।',

    ),

  ),

  '2.0.1' => 

  array (

    'title' => 'Admin list UI fix — অ্যাডমিন তালিকার UI সংশোধন',

    'changes' => 

    array (

      0 => 'Role, status and current-account badges now fit their text without clipping. — Role, status ও নিজের account badge-এর লেখা আর কাটা যাবে না।',

      1 => 'Name and You badge have separate spacing; header, search and actions use a cleaner layout. — নাম ও You badge আলাদা; header, search ও action পরিষ্কারভাবে সাজানো।',

      2 => 'Mobile admin rows use stacked cards instead of a wide table. — Mobile-এ প্রশস্ত table-এর বদলে card layout।',

      3 => 'Primary Admin protection and all account-management rules remain unchanged. — Primary Admin protection ও account-management rule অপরিবর্তিত।',

    ),

  ),

  '2.0.2' => 

  array (

    'title' => 'Storefront polish & admin names — UI সংশোধন ও অ্যাডমিনের নাম',

    'changes' => 

    array (

      0 => 'Removed stray @endpush text from the homepage. — Home page-এর বাড়তি @endpush লেখা সরানো হয়েছে।',

      1 => 'Promotional areas now have a compact image-based layout and clearer spacing. — Promo section-এ compact image layout ও spacing ঠিক করা হয়েছে।',

      2 => 'Removed the marked footer copyright row and Bazar privacy-preferences button without changing tracking consent rules. — চিহ্নিত footer-এর বাড়তি অংশ সরানো; tracking consent rule অপরিবর্তিত।',

      3 => 'Product description images sit on separate lines with readable text spacing. — Product description-এর ছবি ও লেখার alignment ঠিক করা হয়েছে।',

      4 => 'Favicon falls back to the store logo when the configured icon is unavailable. — Site icon না থাকলে logo favicon হিসেবে ব্যবহৃত হবে।',

      5 => 'Release history shows each admin name beside their ID, with a fallback for removed accounts. — Release history-তে Admin ID-এর পাশে নাম দেখাবে।',

    ),

  ),

  '2.0.3' => 

  array (

    'title' => 'Banner sizing fix — ব্যানারের মাপ সংশোধন',

    'changes' => 

    array (

      0 => 'Bounded the homepage hero and side panel heights on desktop and mobile. — ডেস্কটপ ও মোবাইলে হোমপেজ ব্যানারের উচ্চতা ঠিক করা হয়েছে।',

    ),

  ),

  '2.0.4' => 

  array (

    'title' => 'Footer copyright restored — ফুটার কপিরাইট ফিরিয়ে আনা',

    'changes' => 

    array (

      0 => 'Restored the centered footer copyright from website settings. — Website settings অনুযায়ী ফুটারের মাঝখানে কপিরাইট ফিরিয়ে আনা হয়েছে।',

    ),

  ),

  '2.0.5' => 

  array (

    'title' => 'Banner image fitting — ব্যানারের ছবি প্রদর্শন',

    'changes' => 

    array (

      0 => 'Adjusted promotional image fitting to avoid excessive cropping. — প্রমোশনাল ছবির অতিরিক্ত crop কমানো হয়েছে।',

    ),

  ),

  '2.0.6' => 

  array (

    'title' => 'Distinct recommendations — আলাদা সুপারিশকৃত পণ্য',

    'changes' => 

    array (

      0 => 'Just For You excludes products already shown in homepage category sections. — ক্যাটাগরি সেকশনে দেখানো পণ্য Just For You থেকে বাদ দেওয়া হয়েছে।',

      1 => 'Added runner artwork for the Running category. — Running ক্যাটাগরির জন্য runner artwork যোগ হয়েছে।',

    ),

  ),

  '2.0.7' => 

  array (

    'title' => 'Storefront refinements — স্টোরফ্রন্ট সংশোধন',

    'changes' => 

    array (

      0 => 'Fixed homepage banner heights and image fitting. — হোমপেজ ব্যানারের উচ্চতা ও ছবি প্রদর্শন ঠিক করা হয়েছে।',

      1 => 'Restored the centered footer copyright. — ফুটারের মাঝখানে কপিরাইট ফিরিয়ে দেওয়া হয়েছে।',

      2 => 'Replaced the Running category photo with a runner icon. — Running ক্যাটাগরিতে রানার আইকন যোগ করা হয়েছে।',

      3 => 'Just For You now excludes products already displayed in category sections. — ক্যাটাগরি সেকশনে দেখানো পণ্য Just For You-তে পুনরায় আসবে না।',

    ),

  ),

  '2.0.8' => 

  array (

    'title' => 'Custom campaign artwork — নতুন ক্যাম্পেইন artwork',

    'changes' => 

    array (

      0 => 'Added custom navy and gold campaign artwork to two promotional panels. — দুই প্রমোশনাল প্যানেলে navy ও gold artwork যুক্ত করা হয়েছে।',

    ),

  ),

  '2.0.9' => 

  array (

    'title' => 'Two distinct banner images — দুটি আলাদা ব্যানার ছবি',

    'changes' => 

    array (

      0 => 'Replaced the shared abstract artwork with a running lifestyle photo and a studio sneaker photo. — একই abstract artwork বদলে running lifestyle ও studio sneaker-এর আলাদা ছবি দেওয়া হয়েছে।',

    ),

  ),

  '2.0.10' => 

  array (

    'title' => 'New banners and mobile checkout fixes — নতুন ব্যানার ও মোবাইল সংশোধন',

    'changes' => 

    array (

      0 => 'Added two distinct promotional images: running lifestyle and studio sneakers. — দুই জায়গায় আলাদা running lifestyle ও studio sneaker ছবি যোগ করা হয়েছে।',

      1 => 'Fixed mobile navigation anchoring and the unwanted bottom gap. — মোবাইলের নিচের bar ও ফাঁকা জায়গা ঠিক করা হয়েছে।',

      2 => 'Product pages show the sticky checkout without the bottom menu or floating cart on mobile. — মোবাইল product page-এ bottom menu ও floating cart-এর বদলে sticky checkout থাকবে।',

      3 => 'Sticky checkout hides when the main order button is visible, including flash deal products. — মূল order button দেখা গেলে flash deal-সহ sticky checkout লুকাবে।',

      4 => 'Product title in the mobile sticky bar supports two lines. — মোবাইল sticky bar-এ পণ্যের নাম দুই লাইনে দেখা যাবে।',

    ),

  ),

  '2.0.11' => 

  array (

    'title' => 'WhatsApp checkout contact — চেকআউটে WhatsApp যোগাযোগ',

    'changes' => 

    array (

      0 => 'Removed the email field from checkout across all layouts. — সব layout-এর checkout থেকে email field সরানো হয়েছে।',

      1 => 'Added a WhatsApp checkbox beside the mobile number and an optional separate WhatsApp number. — মোবাইল নম্বরের পাশে WhatsApp checkbox এবং আলাদা ঐচ্ছিক WhatsApp নম্বর যোগ করা হয়েছে।',

      2 => 'Saved WhatsApp contact appears in admin order details; guest details are remembered in the browser. — WhatsApp যোগাযোগ admin order details-এ দেখা যায়; guest-এর তথ্য browser-এ মনে রাখা হয়।',

    ),

  ),

  '2.0.12' => 

  array (

    'title' => 'Guest WhatsApp contact saving — গেস্টের WhatsApp তথ্য সংরক্ষণ',

    'changes' => 

    array (

      0 => 'Guest and signed-in orders both retain the selected WhatsApp contact. — Guest ও logged-in দুই ধরনের অর্ডারে WhatsApp তথ্য সংরক্ষিত হয়।',

      1 => 'Verified checkout without an email field across all four layouts. — চারটি layout-এ email field ছাড়া checkout পরীক্ষা করা হয়েছে।',

    ),

  ),

  '2.0.13' => 

  array (

    'title' => 'Release titles and full details — আপডেটের শিরোনাম ও পূর্ণ বিবরণ',

    'changes' => 

    array (

      0 => 'Restored bilingual titles and What’s New details for releases 2.0.3 through 2.0.12, including the cumulative 2.0.7 and 2.0.10 updates. — 2.0.3 থেকে 2.0.12 পর্যন্ত update-এর বাংলা–ইংরেজি title ও বিবরণ যোগ করা হয়েছে, 2.0.7 ও 2.0.10-সহ।',

      1 => 'Included the email-free checkout and WhatsApp contact fields. — Email ছাড়া checkout ও WhatsApp যোগাযোগের field অন্তর্ভুক্ত আছে।',

      2 => 'Existing release history, timestamps and admin attribution remain intact. — আগের update history, সময় ও admin পরিচয় অক্ষত রাখা হয়েছে।',

    ),

  ),

  '2.3.0' => 

  array (

    'title' => 'Unified AI Inbox & platform upgrades — AI Inbox ও প্ল্যাটফর্ম আপডেট',

    'changes' => 

    array (

      0 => 'Added a unified Messenger and WhatsApp inbox with multiple Pages/numbers, filters, unread counts and manual replies. — একাধিক Page/number, filter, unread ও manual reply সহ unified Inbox।',

      1 => 'Added Gemini AI draft/auto/manual modes, staff takeover, scoped product access and separate encrypted credentials. — Gemini draft/auto/manual, staff takeover, নির্দিষ্ট product access ও আলাদা encrypted credentials।',

      2 => 'Added signed webhooks, duplicate handling, database queue, delivery status and AI usage limits. — Signed webhook, duplicate handling, queue, delivery status ও AI usage limit।',

      3 => 'Added mock testing, product photo/link sharing and order drafts without confirming or changing real orders. — Mock test, product ছবি/link এবং actual order পরিবর্তন ছাড়া order draft।',

      4 => 'Included mobile speed and responsive image optimization, gallery ordering and stock-aware size selection. — Mobile speed, responsive image optimization, gallery ordering ও stock অনুযায়ী size selection।',

      5 => 'Admin footer follows the installed platform release. Inbox database setup is a separate password-confirmed action. — Footer installed version দেখায়; Inbox database setup আলাদা password দিয়ে করতে হয়।',

    ),

  ),

);

