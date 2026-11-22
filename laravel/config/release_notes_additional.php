<?php

// Release-history fallback; signed package notes remain the primary source.
return array (
  '2.4.12' => 
  array (
    'title' => 'Backup file diagnostics — ব্যাকআপ file সংশোধন',
    'changes' => 
    array (
      0 => 'Check filesystem free space before/during backup, allowing ZIP temporary-copy reserve; account quota may differ. — Backup-এর আগে/মাঝে খালি জায়গা পরীক্ষা ও temporary ZIP reserve।',
      1 => 'Bound archive batches and snapshot small files to reduce open-handle and file-change failures. — সীমিত batch ও ছোট file snapshot।',
      2 => 'Keep the original failure when ZIP closing also fails. — ZIP close-এর error দিয়ে আসল error ঢেকে যাবে না।',
      3 => 'Show safe error code, file stage, relative filename and source line. — নির্দিষ্ট error code/file/stage দেখাবে।',
      4 => 'Record worker PHP version and make failed jobs visible in cron output. — Worker PHP version ও cron-এ failed job দেখাবে।',
      5 => 'Differentiate failed backup from interrupted restore. — Backup fail-এ Restore recovery message নয়।',
      6 => 'Clean failed standalone partial ZIPs while retaining restore safety archives. — Failed partial ZIP cleanup; safety backup অক্ষত।',
      7 => 'Preserve original site data and existing backup format; no SQL import required. — Data/backup format অক্ষত; SQL লাগবে না।',
    ),
  ),
  '2.4.13' => 
  array (
    'title' => 'Backup storage & rollback retention — Backup ও ২ version rollback',
    'changes' => 
    array (
      0 => 'Bound archive batches and snapshot small files to reduce open-handle and file-change failures. — সীমিত batch ও ছোট file snapshot।',
      1 => 'Keep the original failure when ZIP closing also fails. — ZIP close-এর error দিয়ে আসল error ঢেকে যাবে না।',
      2 => 'Show safe error code, file stage, relative filename and source line. — নির্দিষ্ট error code/file/stage দেখাবে।',
      3 => 'Record worker PHP version and make failed jobs visible in cron output. — Worker PHP version ও cron-এ failed job দেখাবে।',
      4 => 'Differentiate failed backup from interrupted restore. — Backup fail-এ Restore recovery message নয়।',
      5 => 'Clean failed standalone partial ZIPs while retaining restore safety archives. — Failed partial ZIP cleanup; safety backup অক্ষত।',
      6 => 'Preserve original site data and existing backup format; no SQL import required. — Data/backup format অক্ষত; SQL লাগবে না।',
      7 => 'Check filesystem free space before/during backup, allowing ZIP temporary-copy reserve; account quota may differ. — Backup-এর আগে/মাঝে খালি জায়গা পরীক্ষা ও temporary ZIP reserve।',
      8 => 'Keep the last two release rollback snapshots; prune older snapshots after successful installation. — সর্বশেষ ২টি rollback snapshot; সফল update-এর পরে পুরোনো cleanup।',
      9 => 'Keep release history and separate full-site archives; show available rollback count. — History ও full-site backup অক্ষত; rollback count দেখাবে।',
      10 => 'Explain upload storage/quota, PHP temporary-folder and size-limit failures. — Upload-এর storage/temp/limit পরীক্ষা করার নির্দেশনা।',
      11 => 'Clicking a notification opens its order and marks that notification read for the current Admin. — Notification click-এ order খুলবে ও unread count কমবে।',
    ),
  ),
  '2.4.14' => 
  array (
    'title' => 'Storefront, Inbox & order management — UI, Inbox ও order আপডেট',
    'changes' => 
    array (
      0 => 'New compact homepage hero, four Today’s Deal products rotating every 10 seconds, and removed homepage Flash Sale blocks/category tiles. — ছোট hero, ৪টি rotating deal, অতিরিক্ত section বাদ।',
      1 => 'Improved partial-word, multi-word, Bangla, product ID and SKU search; consistent suggestions/results. — এক অক্ষর/শব্দ/বাংলা/SKU search সংশোধন।',
      2 => 'Responsive Flash Deal group page, direct Order Now popup hooks, and products nearer the first mobile screen. — Flash Deal responsive product-first design।',
      3 => 'Adidas Terrex mobile header shows logo, For Today and a browser-persistent first-visit 24-hour timer. Other pages keep their headers. — শুধু নির্দিষ্ট collection-এ For Today timer।',
      4 => 'Removed marked Facebook Page and Contract header links. — চিহ্ন দেওয়া header links বাদ।',
      5 => 'Compact performance-log table with a plain-language report below and expandable technical details. — সহজ log table ও নিচে বোঝার মতো report।',
      6 => 'Updated Website Chat, Messenger/WhatsApp Inbox, connections styling and empty states; live refresh and sound retained. — পুরো Inbox UI refresh; live update/sound থাকবে।',
      7 => 'Added Yesterday to dashboard/analytics with Bangladesh midnight boundaries and 24 hourly chart buckets. — Dashboard/Analytics-এ Yesterday।',
      8 => 'Incomplete orders require a customer mobile number; exclude legacy visit/name-only rows, and show clickable product images/titles with sizes. — Mobile ছাড়া incomplete তালিকায় নয়; linked photo/title/size।',
      9 => 'All Orders/Inhouse Orders now show customer/contact under order code, product images/sizes, responsive mobile cards and no download button. — Order list-এর নতুন mobile UI।',
      10 => 'Admin-only invoice delivery history popup; encrypted BD Courier settings in Integrations, short encrypted report cache and safe API errors. — Admin-only success report ও encrypted API settings।',
      11 => 'Show Steadfast breakdown returned by BD Courier. Direct Steadfast history API remains unverified and is not called. — Steadfast breakdown থাকবে; unverified direct API চালু নয়।',
      12 => 'Keep the last two authenticated rollback snapshots; retain release history and separate full-site archives. — সর্বশেষ দুই rollback backup; history/full-site archive থাকবে।',
      13 => 'Backup free-space checks and bounded archive batches; safer diagnostic failures and cleanup of failed standalone partial archives. — Backup space/batch/error/partial cleanup সংশোধন।',
      14 => 'Opening an order notification marks that notification read for the current admin. — Notification click-এ unread কমবে।',
      15 => 'No SQL import, customer-message sending, real courier lookup or database replacement is performed by this package. — এই code update-এ SQL import/real lookup/database replacement নেই।',
    ),
  ),
  '2.4.15' => 
  array (
    'title' => 'Complete store update — Storefront, Inbox ও courier sync',
    'changes' => 
    array (
      0 => 'New compact homepage hero, four Today’s Deal products rotating every 10 seconds, and removed homepage Flash Sale blocks/category tiles. — ছোট hero, ৪টি rotating deal, অতিরিক্ত section বাদ।',
      1 => 'Improved partial-word, multi-word, Bangla, product ID and SKU search; consistent suggestions/results. — এক অক্ষর/শব্দ/বাংলা/SKU search সংশোধন।',
      2 => 'Responsive Flash Deal group page, direct Order Now popup hooks, and products nearer the first mobile screen. — Flash Deal responsive product-first design।',
      3 => 'Adidas Terrex mobile header shows logo, For Today and a browser-persistent first-visit 24-hour timer. Other pages keep their headers. — শুধু নির্দিষ্ট collection-এ For Today timer।',
      4 => 'Removed marked Facebook Page and Contract header links. — চিহ্ন দেওয়া header links বাদ।',
      5 => 'Compact performance-log table with a plain-language report below and expandable technical details. — সহজ log table ও নিচে বোঝার মতো report।',
      6 => 'Updated Website Chat, Messenger/WhatsApp Inbox, connections styling and empty states; live refresh and sound retained. — পুরো Inbox UI refresh; live update/sound থাকবে।',
      7 => 'Added Yesterday to dashboard/analytics with Bangladesh midnight boundaries and 24 hourly chart buckets. — Dashboard/Analytics-এ Yesterday।',
      8 => 'Incomplete orders require a customer mobile number; exclude legacy visit/name-only rows, and show clickable product images/titles with sizes. — Mobile ছাড়া incomplete তালিকায় নয়; linked photo/title/size।',
      9 => 'All Orders/Inhouse Orders now show customer/contact under order code, product images/sizes, responsive mobile cards and no download button. — Order list-এর নতুন mobile UI।',
      10 => 'Admin-only invoice delivery history popup; encrypted BD Courier settings in Integrations, short encrypted report cache and safe API errors. — Admin-only success report ও encrypted API settings।',
      11 => 'Show Steadfast breakdown returned by BD Courier. Direct Steadfast history API remains unverified and is not called. — Steadfast breakdown থাকবে; unverified direct API চালু নয়।',
      12 => 'Keep the last two authenticated rollback snapshots; retain release history and separate full-site archives. — সর্বশেষ দুই rollback backup; history/full-site archive থাকবে।',
      13 => 'Backup free-space checks and bounded archive batches; safer diagnostic failures and cleanup of failed standalone partial archives. — Backup space/batch/error/partial cleanup সংশোধন।',
      14 => 'Opening an order notification marks that notification read for the current admin. — Notification click-এ unread কমবে।',
      15 => 'No SQL import, customer-message sending, real courier lookup or database replacement is performed by this package. — এই code update-এ SQL import/real lookup/database replacement নেই।',
      16 => 'Public product thumbnails fill their 3:4 media cards with cover cropping. — Public thumbnail card পূর্ণ করে দেখাবে।',
      17 => 'Mobile bottom menu now links to Flash Deals instead of Notifications. — Mobile menu-তে Notifications-এর বদলে Flash Deals।',
      18 => 'Steadfast live settings queue old unfinished orders for exact-invoice reconciliation; cron processes batches, skips missing/mismatched references and never books new parcels. — Live connect-এ পুরোনো order exact invoice sync queue; ভুল match বদলাবে না।',
      19 => 'Open delivery-success reports from the order list in a small admin-only popup without visiting the provider website. — Order list থেকেই courier report popup।',
    ),
  ),
  '2.4.16' => 
  array (
    'title' => 'Hero image spacing — Hero image-এর নিচের ফাঁকা জায়গা সংশোধন',
    'changes' => 
    array (
      0 => 'Fill the desktop hero row with its image; remove the blank tail under fixed-height slides. — Hero row image দিয়ে পূর্ণ হবে; নিচের খালি অংশ বাদ।',
      1 => 'Tighten the space below the hero and refresh stylesheet versions for mobile/desktop. — Hero-এর নিচের spacing ও CSS cache version ঠিক করা হয়েছে।',
      2 => 'No SQL import or customer/order data changes. Full browser verification remains pending local MySQL recovery. — SQL লাগবে না; MySQL recovery-এর পরে browser যাচাই বাকি।',
    ),
  ),
  '2.4.17' => 
  array (
    'title' => 'Thumbnail zoom & chat switch — Thumbnail zoom ও Live Chat on/off',
    'changes' => 
    array (
      0 => 'Public product card thumbnails use cover and provide zoom in, zoom out and reset controls. — Card image cover, +/− zoom ও reset।',
      1 => 'Website Live Chat is OFF by default. Admin can enable/disable it from Inbox → Website Chat; existing conversations remain available. — Live Chat আপাতত OFF; admin পরে ON করতে পারবেন; history থাকবে।',
      2 => 'Disabled public chat hides product/footer buttons and blocks start/message/poll APIs; Messenger and WhatsApp are unchanged. — বন্ধ অবস্থায় public chat API/button বন্ধ; Messenger/WhatsApp অপরিবর্তিত।',
    ),
  ),
  '2.4.18' => 
  array (
    'title' => 'Product images & courier report — ছবি ও কুরিয়ার রিপোর্ট',
    'changes' => 
    array (
      0 => 'Fix gallery previews and preserve drag/arrow ordering below the picker. — নির্বাচিত gallery ছবি নিচে দেখা যাবে এবং সাজানো যাবে।',
      1 => 'Auto-create 450×600 JPEG product images without mandatory EXIF; originals are retained. — EXIF ছাড়াও 450×600 auto conversion; মূল ছবি থাকবে।',
      2 => 'If hosting conversion fails, keep valid originals and allow product saving with a warning. — Hosting conversion ব্যর্থ হলে মূল ছবি রেখে save/publish হবে এবং warning দেখাবে।',
      3 => 'Move zoom controls to the single-product main gallery; support drag/hover panning and reset. Product cards return to their previous design. — বড় ছবিতে zoom/drag; card-এ zoom থাকবে না।',
      4 => 'Cover main product images across layouts and mobile to reduce side gaps. — সব layout ও mobile-এর বড় ছবিতে cover।',
      5 => 'Admin courier popup shows delivery gauge, Steadfast breakdown and provider risk/fraud details when supplied; unavailable data stays unknown. — Admin popup-এ success gauge ও API-তে পাওয়া fraud details; না পাওয়া তথ্য শূন্য ধরা হবে না।',
    ),
  ),
  '2.4.19' => 
  array (
    'title' => 'Gesture zoom & bulk enhance — Zoom এবং Bulk Enhance',
    'changes' => 
    array (
      0 => 'Remove zoom buttons. Main product images support mobile pinch, mouse-wheel zoom, drag/hover pan and reset. — Button ছাড়া pinch ও mouse wheel zoom, drag এবং reset।',
      1 => 'Product save/publish retains the selected original images without auto-converting, resizing or changing upload IDs. Manual image tools remain available. — Save/publish-এ মূল ছবি থাকবে; auto resize/convert বন্ধ।',
      2 => 'Use quality 90 for responsive optimized copies, preserve originals and avoid upscaling. — High-quality optimization; মূল ছবি ও aspect ratio অক্ষত; upscale নয়।',
      3 => 'Add bulk enhancement settings, select-all, real item progress and stop-after-current. — Bulk Enhance, selection, progress ও stop।',
      4 => 'Enhanced drafts use quality 95 without resizing; review and explicitly apply or restore the original. — Enhanced draft preview দেখে apply; original restore করা যাবে।',
      5 => 'Optional EXIF support no longer blocks manual enhancement/optimization. — EXIF না থাকলেও manual image processing চলবে।',
    ),
  ),
  '2.4.21' => 
  array (
    'title' => 'Backup storage & rollback clarity — ব্যাকআপ ও rollback বিবরণ',
    'changes' => 
    array (
      0 => 'Backup panel: select and delete failed/stopped backup ZIPs, temporary files and records, individually or in bulk, with current admin password and explicit confirmation. Completed backups and restore/referenced recovery data remain protected. / Failed বা stopped backup একক ও bulk delete; completed ও restore safety backup সুরক্ষিত।',
      1 => 'Show backup ZIP availability and sizes; on-demand resumable storage scan lists folder usage and large ZIP/archive files. Filesystem free space is distinguished from hosting account quota. / ZIP size ও folder অনুযায়ী জায়গার হিসাব, বড় archive তালিকা এবং pause/resume scan।',
      2 => 'Rollback preview shows the next target version, last two retained snapshot steps, exact restore/remove file lists, saved sizes and checksum eligibility. History entries do not imply backup availability. / কোন version-এ ফিরবে, কোন files বদলাবে এবং backup আছে কি না স্পষ্ট দেখা যাবে।',
      3 => 'This release contains backup/storage and rollback UI changes only; no speed-phase changes, SQL imports or automatic cleanup of customer files. / Speed-এর কাজ এই update-এ নেই; database বা product image স্বয়ংক্রিয়ভাবে মুছবে না।',
    ),
  ),
  '2.4.22' => 
  array (
    'title' => 'Backup request limit fix — Backup 429 সংশোধন',
    'changes' => 
    array (
      0 => 'Separate backup progress, creation, restore, diagnostics, cleanup and storage-scan rate limits per admin/action; progress reads no longer consume create/restore attempts. / প্রতিটি কাজের আলাদা limit; progress দেখলে backup তৈরির limit শেষ হবে না।',
      1 => 'Use one progress request at a time, slower polling, hidden-tab pause and Retry-After backoff. Never automatically repeat backup creation, deletion or restore. / 429 এলে অপেক্ষা করে শুধু status আবার দেখবে; backup/restore নিজে থেকে পুনরায় submit নয়।',
      2 => 'Show clear rate-limit messages and preserve Retry-After in private diagnostic responses. This does not claim to fix an unclassified older HTTP 500 or hosting account quota. / 429-এর অপেক্ষার সময় ও পরিষ্কার error; পুরোনো 500 বা hosting quota আলাদা বিষয়।',
    ),
  ),
  '2.4.23' => 
  array (
    'title' => 'Old image & optimized copy cleanup — পুরোনো ছবি bulk cleanup',
    'changes' => 
    array (
      0 => 'Add Cleaner → Old Image / Copy Cleanup, also linked from Image Optimize. Load old originals, resize/enhance drafts or obsolete optimized copies with age filters and file-size totals. / Original/draft ও unused optimized copy আলাদা তালিকা, বয়স ও MB filter।',
      1 => 'Select single or multiple images, confirm permanent loss of selected restore history, and enter current admin password. Current product/gallery, shared media and other database/code references block deletion. / Selection, password ও confirmation ছাড়া মুছবে না; ব্যবহৃত বা shared ছবি সুরক্ষিত।',
      2 => 'Recheck current renderer keys, prepared manifests and source files before removing obsolete optimized copies. Preserve source originals; no automatic/public-page deletion. / Optimized copy মুছবার আগে নতুন করে usage পরীক্ষা; source original অক্ষত।',
      3 => 'Show actual deleted count, freed MB, batch progress and stop-after-current. Removing an old original also removes its saved history links; full backup ZIPs are not shrunk. / অগ্রগতি ও মুক্ত জায়গার হিসাব; original delete হলে সেটিতে restore করা যাবে না।',
    ),
  ),
  '2.4.24' => 
  array (
    'title' => 'Courier connection & Meta delivery health — Connection test ও daily report',
    'changes' => 
    array (
      0 => 'Steadfast: saved-credential read-only connection test, clear saved/missing/test status, manual delivery sync and optional old-order restart. Tests never create parcels. / API connection test, status, manual sync ও পুরোনো order আবার খোঁজার option।',
      1 => 'Explain webhook token, invoice matching and scheduler setup in Bangla; show last sync and cron heartbeat. Existing-order sync never creates new parcels or marks payment paid. / Webhook optional; field নির্দেশনা, শেষ sync ও worker status।',
      2 => 'Meta: synthetic Test Events PageView connection test, production/test-separated daily queued/attempt/accepted/failed/retry/discard counters in Dhaka time and per-event breakdown. / Daily API activity report; secret/customer data report-এ নেই।',
      3 => 'Remove obsolete ROLLBACK DEMO banner; distinguish configured browser Pixel from verified CAPI receipt and show backlog/worker guidance. Configurable bounded CAPI worker batch; counters begin after this update. / পুরোনো demo banner বাদ; pending অর্থ ও cron নির্দেশনা; নতুন report এই update থেকে।',
    ),
  ),
  '2.4.25' => 
  array (
    'title' => 'Courier network diagnostics — HTTP 0 ও cron date সংশোধন',
    'changes' => 
    array (
      0 => 'Classify DNS, connection, timeout, TLS certificate, CA bundle and PHP HTTP-client failures using safe cURL codes; never expose raw exceptions or credentials. / HTTP 0-এর নির্দিষ্ট কারণ ও নির্দেশনা; secret/raw error দেখাবে না।',
      1 => 'Show safe network failure details for manual and old-order sync. Preserve SSL certificate verification; no automatic resending or new parcel creation. / Sync failure-এর কারণ; SSL bypass বা নতুন parcel নয়।',
      2 => 'Replace Invalid Date with an explicit missing/invalid cron heartbeat explanation and show PHP/cURL/OpenSSL readiness. / Cron timestamp ভুল বা অনুপস্থিত হলে পরিষ্কার নির্দেশনা।',
    ),
  ),
  '2.4.26' => 
  array (
    'title' => 'Labeled Steadfast accounts — এক site-এ একাধিক courier account',
    'changes' => 
    array (
      0 => 'Add multiple encrypted Steadfast accounts with unique labels and label-based Integration submenus. Existing credentials remain under Main account; accounts can be enabled/disabled. / Label দিয়ে একাধিক account, menu ও পৃথক Test/Sync।',
      1 => 'Show labeled Send buttons on order lists, admin invoice and the admin controls of shared invoices. Persist selected account on each parcel; prevent real duplicate booking across accounts. / যে label চাপবেন সেই account-এ parcel; অন্য account-এ duplicate booking বন্ধ।',
      2 => 'Sync and webhook authentication always use the parcel account; retain backward compatibility for old unlabeled parcels and separate account reports/reconciliation cursors. / পুরোনো parcel Main account-এ থাকবে; প্রতিটির webhook token ও sync report আলাদা।',
      3 => 'Require current admin password for account credentials, preserve blank saved secrets, prevent changing booked API identity and allow secret rotation. Mock tests verify isolation without real parcels. / Admin password, encrypted credentials, shared label/key checks ও নিরাপদ account ownership।',
    ),
  ),
  '2.4.27' => 
  array (
    'title' => 'Courier account rename fix — Account label Save 500 সংশোধন',
    'changes' => 
    array (
      0 => 'Fix Laravel parsing of account-ID validation so existing Main/additional account labels and settings can be saved instead of HTTP 500. Saved credentials remain unchanged when blank. / Main বা অন্য account-এর নাম Save-এর 500 fix; blank secret আগের মতো থাকে।',
      1 => 'Fix the same pipe-containing regex validation issue for selected JPG/WebP optimized-copy cleanup requests. / Optimized JPG/WebP selection validation সংশোধন।',
      2 => 'Verify actual controller save requests with the real Laravel validator and password checks in an isolated fixture. DNS/cURL 6 and cron configuration remain separate hosting issues. / Rename ও password check পরীক্ষিত; DNS/cron এই code fix-এর অংশ নয়।',
    ),
  ),
  '2.4.28' => 
  array (
    'title' => 'Compact orders & deal collections — ছোট order card ও Flash Deal group',
    'changes' => 
    array (
      0 => 'Remove Steadfast booking buttons/account booking status from order overview cards; keep courier booking controls on admin invoices. Move customer courier-history check to a compact action button. / Order list থেকে booking controls বাদ; invoice-এ থাকবে।',
      1 => 'Compact mobile order layout: customer/phone together, IP/block controls close by, smaller product rows, amount/payment/delivery status in one row and a slim action footer. / কম ফাঁকা জায়গা, ছোট card ও কাছাকাছি প্রয়োজনীয় তথ্য।',
      2 => 'Redesign Flash Deals index as responsive collection cards with group titles, eligible product counts and product previews. Whole card opens its own deal group. / Flash Deals-এ group card; খুললে ওই group-এর products।',
      3 => 'Preserve existing group product pages, order filters and invoice courier features. Batch collection data loading and retain active-date/published-product filtering. / আগের group product page ও order functions অক্ষত; active collection ও published products দেখাবে।',
    ),
  ),
  '2.4.29' => 
  array (
    'title' => 'Customer review tools — বাস্তব review ব্যবস্থাপনা',
    'changes' => 
    array (
      0 => 'Remove the footer Privacy preferences button while retaining existing tracking consent and privacy signals. / Footer button সরানো; consent settings অক্ষত।',
      1 => 'Improve real customer review entry: best-selling products first, Bangla/English text, editable source and order reference kept in admin only. / Best-selling product আগে; আসল review ও private reference edit।',
      2 => 'New reviews start as unpublished drafts. Product average remains calculated from published ratings; no fabricated reviews or fixed averages. / নতুন review draft; প্রকাশিত rating থেকেই গড়।',
      3 => 'Add working product/comment search, publication filter and rating sort in the review list. No SQL import required. / Review search, draft filter ও rating sort; SQL লাগবে না।',
    ),
  ),
  '2.4.30' => 
  array (
    'title' => 'Editable sample review drafts — অপ্রকাশিত নমুনা review',
    'changes' => 
    array (
      0 => 'Create a one-time batch of 40 explicitly labeled unpublished sample drafts across up to five best-selling published products. No invented customer names or verified-purchase claims. / সর্বোচ্চ ৫টি best seller-এ ৪০টি নমুনা draft; বাস্তব review নয়।',
      1 => 'Require replacement with actual customer name/comment and explicit confirmation before sample publication. Block direct publication toggles for samples. / আসল feedback ও confirmation ছাড়া নমুনা প্রকাশ বন্ধ।',
      2 => 'Keep sample ratings out of public averages; edit drafts through existing review management. No automatic live database changes or SQL import. / Draft rating গড়ে নয়; admin থেকে তৈরি/edit; SQL লাগবে না।',
    ),
  ),
  '2.4.31' => 
  array (
    'title' => 'Review delete controls — Review single ও bulk delete',
    'changes' => 
    array (
      0 => 'Add single delete and select-page/bulk delete for manual reviews and sample drafts, with confirmation and admin-only POST/CSRF routes. / Manual ও sample review single/bulk delete ও confirmation।',
      1 => 'Recalculate published product averages and remove review metadata in the same transaction. Reject missing, duplicate or non-manual selections; limit batches to 100. / Rating পুনর্গণনা ও metadata cleanup; নিরাপদ selection validation।',
      2 => 'Deletion is permanent. Preserve the one-time sample-generation marker so cleanup does not accidentally regenerate the same batch. No SQL import. / Delete স্থায়ী; আগের sample batch ফেরত তৈরি হবে না; SQL লাগবে না।',
    ),
  ),
  '2.5.0' => 
  array (
    'title' => 'Footstep 2.5.0 — সম্মিলিত backend, reviews ও responsive update',
    'changes' => 
    array (
      0 => 'Fix All Orders and Inhouse Orders responsive filter toolbars: stable title, wrapping filters, fluid controls and compact cards based on available content width. / Order heading না ভেঙে ছোট screen-এ filter সাজানো ও card layout।',
      1 => 'Consolidate all completed 2.4.32–2.4.35 changes into a single direct upgrade from 2.4.31 to 2.5.0: review UI, Classic/Insights backend themes, Footstep/Astral Labs branding, developer footer switch and menus. / মাঝের version আলাদা install নয়; সব সম্পন্ন পরিবর্তন একসঙ্গে 2.5.0।',
      2 => 'Remove active Inbox and website chat entry points and workers while retaining stored history. Keep unrelated Pixel/CAPI, courier, protection, media and backup functionality. / Inbox বন্ধ; অন্য integration ও আগের data অক্ষত।',
      3 => 'Collapse long topbar search text when the available toolbar width is narrow; retain accessible search and all header controls. / ছোট toolbar-এ search icon; controls চাপা পড়বে না।',
      4 => 'Verify local route rendering, existing release tests, package signatures and rollback. No SQL import or automatic data cleanup. / Local render, signature ও rollback পরীক্ষিত; SQL বা auto-delete নয়।',
    ),
  ),
  '2.6.0' => 
  array (
    'title' => 'Footstep 2.6.0 — Speed, code cleanup ও order success report',
    'changes' => 
    array (
      0 => 'Batch public catalog, category tree, brands and image lookups; keep stock/pricing fresh per request. / Catalog/category/brand ও ছবির lookup একসঙ্গে; নতুন request-এ stock ও price নতুন করে পড়া।',
      1 => 'Move product recommendations and section queries out of Blade into StorefrontData; separate checkout variant selection, pricing and storefront hooks into services. / Blade থেকে product query সরিয়ে catalog service; checkout ও storefront-এর দায়িত্ব আলাদা।',
      2 => 'Serve prepared image manifests on public pages; metadata is prepared by upload/admin/cron without automatic resizing or conversion. Original fallback retained. / Public page-এ প্রস্তুত metadata; auto-convert নয়; original fallback থাকবে।',
      3 => 'Reuse private initial popup quotes to avoid the first extra quote request; retain server price, size and stock validation on size changes and confirmation. / Popup-এর প্রথম বাড়তি request কমানো; size/change/confirm-এ server validation অক্ষত।',
      4 => 'Use a smaller shopping JavaScript bundle on supported storefront pages; retain editor plugins on pages that require them. / Shopping page-এ ছোট JavaScript; প্রয়োজনীয় editor page অক্ষত।',
      5 => 'Add Delivered vs Cancelled pie report to Dashboard and Analytics with Today, Yesterday, Weekly, Monthly and Yearly filters. Pending/other orders are counted separately. / পাঁচ সময়ের success/cancel pie; pending আলাদা; report order তৈরির সময় অনুসারে।',
      6 => 'Separate startup, middleware and SQL timing; add secret-free five-minute scheduler/queue snapshots to Performance Logs. Verify local routes, fixtures, signed install and rollback; no SQL import. / Timing ও cron health; local পরীক্ষা ও signed rollback; SQL import লাগবে না।',
    ),
  ),
);
