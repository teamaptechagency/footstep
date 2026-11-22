# Footstep Unified AI Inbox — setup ও review guide

যাচাইয়ের তারিখ: 1 November 2026। Local project: `E:\Tawhid\Ecommerce\New 3\Footstep\public_html`।

## এখন কী চালু আছে

- Admin → **AI Inbox**: একাধিক Messenger Page এবং WhatsApp Cloud API number, business/channel/unread filters, paginated history, manual replies, AI draft review, takeover/resume।
- Default **mock + AI draft**। `Footstep Test Lab`-এ দুটো dummy connection ও sample conversation আছে। এতে Gemini/Meta request হয় না, কোনো customer-কে message যায় না। Mock replies হচ্ছে deterministic test replies; আসল Gemini inference-এর দাবি নয়।
- Messaging credentials নিজস্ব `inbox_*` tables-এ encrypted। Pixel/CAPI, আগের reserved Messenger settings বা WhatsApp invoice settings থেকে কোনো secret copy/activate করা হয়নি।
- Channel-এর explicit product allowlist-এর published physical product-ই AI ও staff product attachment-এ পাওয়া যায়। খালি allowlist = কোনো product access নয়।
- Gemini Bengali/Banglish/English intent এবং অনুমোদিত product IDs নির্বাচন করে। Server পুনরায় actual stock, discounted price, tax, size, product URL ও configured policy থেকে উত্তর বানায়। Gemini-এর arbitrary factual prose ব্যবহার হয় না। এই সীমা hallucination কমাতে ইচ্ছাকৃত।
- Delivery-এর জন্য **Use actual Footstep COD delivery charges** explicit opt-in। Return policy-এর জন্য website page বা channel text বাছুন। ফাঁকা থাকলে AI অজানা বলবে; delivery deadline/return promise বানাবে না।
- ছবির জন্য **Queue image + link**। Order draft শুধু `inbox_order_drafts`-এ থাকে। Store orders, inventory, payment, cancellation/refund-এ কোনো write নেই।

## আগে mock flow দেখুন

1. Admin login করে `/admin/inbox` খুলুন। `Footstep Test Lab` বাছুন।
2. Sample conversation খুলে draft দেখুন। অথবা **Test lab → Mock inbound message** দিন।
3. **Process mock queue** চাপুন: customer message থেকে AI draft তৈরি হবে।
4. **Review draft** দিয়ে text edit করুন, **Simulate send** চাপুন। Staff takeover automatic হবে।
5. আবার **Process mock queue** চাপলে status `mock_sent` হবে। Meta delivery-এর বাস্তব প্রমাণ নয়।
6. Mode-এ `AI auto-reply` দিয়ে নতুন mock inbound দিন: mock auto flow দেখুন। Manual দিলে AI বন্ধ। `AI draft` দিয়ে পুনরায় শুরু করা যাবে।
7. Product photo/link এবং order draft তৈরি করে দেখুন। Draft তৈরি হলেও actual order count/stock বদলায় না।

## Google AI Studio

1. [Google AI Studio](https://aistudio.google.com/) → project তৈরি/বাছাই → **Get API key / API keys**। Key কেবল admin secure form-এ দিন; chat-এ পাঠাবেন না।
2. Google Cloud key restrictions-এ Generative Language API সীমাবদ্ধ করুন। Production customer content ব্যবহারের আগে উপযুক্ত billing/data terms নির্বাচন করুন। Free tier-এর data use paid tier থেকে আলাদা।
3. Admin → AI Inbox → **Connections & settings** → business তৈরি করুন।
4. `Gemini API key`-এ key, `Gemini model`-এ আপনার project-এ available model দিন। Default `gemini-3.5-flash-lite`; official model page-এ stable হিসেবে যাচাই করা হয়েছে। Model field configurable।
5. `Input USD / 1M tokens`, `Output USD / 1M tokens`, daily call limit এবং monthly estimated budget ঠিক করুন। Default standard text rates: $0.30 input / $2.50 output; model বদলালে rates-ও বদলান।
6. Secret save করার সময় current admin password দিন। Saved secret আর HTML-এ ফেরত আসে না; blank রাখলে আগেরটি থাকবে।
7. Real key metadata check করতে Mock uncheck করুন কিন্তু **Enable actual Meta sending বন্ধ রাখুন**। **Test Gemini model/key** শুধু model metadata GET করে—customer text বা inference পাঠায় না।
8. Real inbound থেকে AI draft চাইলে worker চালান। তখন scoped catalog, policies ও সাম্প্রতিক inbound conversation-এর সীমিত অংশ Google-এ যাবে। Phone/email patterns redact হয়; সব personal information সম্পূর্ণ anonymize হয় এমন দাবি নয়।

Official sources: [Models](https://ai.google.dev/gemini-api/docs/models), [3.5 Flash-Lite](https://ai.google.dev/gemini-api/docs/models/gemini-3.5-flash-lite), [generateContent](https://ai.google.dev/api/generate-content), [Pricing](https://ai.google.dev/gemini-api/docs/pricing)।

## Meta Developers: Messenger

1. [Meta Developers](https://developers.facebook.com/apps/) → business-এর app তৈরি/বাছাই করুন, Messenger capability যোগ করুন। App dashboard UI/requirements account অনুযায়ী ভিন্ন হতে পারে।
2. Facebook Page connect করুন। Page-এ messaging task-এর অধিকারসহ Page access token তৈরি করুন। App Secret Settings → Basic-এ পাবেন। Pixel ID/CAPI access token এখানে দেবেন না।
3. AI Inbox business settings-এ **Meta App Secret** এবং নিজের random 16–128 character **Webhook verify token** দিন। একই token Meta-র verification field-এ ব্যবহার করুন।
4. Business settings-এ দেখানো `https://YOUR-DOMAIN/inbox/webhook/BUSINESS-UUID` callback Meta-তে দিন। `localhost` Meta থেকে reachable নয়; valid public HTTPS deployment প্রয়োজন।
5. Page webhook-এর `messages`, `message_deliveries`, `message_reads`, প্রয়োজনমতো `messaging_postbacks` subscribe করুন এবং প্রত্যেক connected Page-এ app subscription দিন। এই implementation text/attachments ও delivery/read process করে; postback content থেকে অর্ডার action চালায় না।
6. Inbox connection-এ Channel=`Facebook Messenger`, Page ID, connection name ও **Page access token** দিন। Identity ভুল হলে নতুন connection তৈরি করুন; existing channel identity immutable।
7. প্রতিটি Page-এর instructions, allowed product IDs, delivery/return policy এবং default mode দিন। শুরুতে AI draft রাখুন।
8. **Test connection** কেবল Page identity GET করে। Success মানেই `pages_messaging` permission, app review ও webhook subscription সম্পূর্ণ হয়েছে এমন নয়।
9. Test app roles/test Page দিয়ে নিজে inbound দিন। সাধারণ customer access-এর জন্য প্রয়োজনীয় Advanced Access/App Review, business verification এবং `pages_messaging`; subscription-এর জন্য `pages_manage_metadata`, Page discovery flow-এ `pages_show_list` প্রযোজ্য হতে পারে। Exact granted scopes Meta dashboard-এ যাচাই করুন।

Meta official [Messenger API collection](https://www.postman.com/meta/messenger-platform-api/documentation/iyp204x/messenger-platform-api) এবং [Conversations API](https://www.postman.com/meta/messenger-platform-api/folder/22794852-255610cd-47f5-4f4d-b3fa-71aec360be9a) দেখুন।

## Meta Developers: WhatsApp Cloud API

1. একই business app-এ WhatsApp capability/setup যোগ করুন। প্রথমে Meta-provided **test phone number** ও আপনার নিজের verified test recipient ব্যবহার করুন।
2. WhatsApp Business Account ID (**WABA ID**) এবং **Phone Number ID** নিন। Display phone number-কে Phone Number ID হিসেবে দেবেন না।
3. Assigned WhatsApp assets সহ System User access token তৈরি করুন; `whatsapp_business_messaging` এবং management/template/subscription-এর জন্য `whatsapp_business_management` প্রয়োজন। Temporary developer token expire হয়।
4. Business App Secret / verify token আগের business settings-এ থাকবে। WhatsApp webhook object `whatsapp_business_account`-এর `messages` subscribe করুন; WABA subscribed apps-এ app যুক্ত করুন।
5. Inbox connection Channel=`WhatsApp Cloud API`; Phone Number ID, WABA ID, access token, name দিন। Number অনুযায়ী product allowlist ও policies দিন। একই business-এ যতগুলো number প্রয়োজন আলাদা connection করুন।
6. **Test connection** identity read-only। এরপর test recipient থেকে inbound দিয়ে AI draft দেখুন। Sending এখনও বন্ধ রাখুন।
7. বাস্তবে test recipient-কে reply দেওয়ার সিদ্ধান্ত নিলে নিজে business settings-এ **Enable actual Meta sending** চালু করুন, admin password দিন, তারপর draft approve করুন। Implementation চলাকালে কোনো real send করা হয়নি।
8. Production number-এ যাওয়ার আগে phone onboarding, business/app review, required permissions, WhatsApp quality/billing/opt-in এবং provider policy যাচাই করুন। App permission entitlement account অনুযায়ী ভিন্ন হতে পারে।

Meta official [WhatsApp Cloud API collection](https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api) এবং [WABA subscription example](https://www.postman.com/meta/whatsapp-business-platform/documentation/wlk6lh4/whatsapp-cloud-api?entity=request-13382743-c7117ace-d8c5-4b39-aaf7-fd5f4faaad74) দেখুন।

## Messaging rules এবং history সীমা

- Free-form reply, product image এবং AI auto-reply last customer inbound থেকে **24 ঘণ্টার মধ্যে** সীমাবদ্ধ। Window retry/actual send-এর সময়ও পুনরায় পরীক্ষা হয়। Old webhook future timestamp দিয়ে window বাড়াতে পারে না।
- WhatsApp-এ window-এর বাইরে শুধু staff-approved **Meta-approved template**। বর্তমান implementation variables/media header ছাড়া template support করে; exact name/language API দিয়ে approved কিনা যাচাই হয়। Staff-এর opt-in attestation audit হয়। Variables/media/marketing automation UI এই release-এ নেই।
- Messenger-এ window-এর বাইরে send blocked। Human Agent tag বা অন্য policy exception দিয়ে AI automation চালানো হয় না।
- Messenger external Page reply echo পেলে conversation manual হয়ে যায়; এই Inbox-এর নিজের known send echo duplicate হিসেবে বাদ যায়। WhatsApp Business app Coexistence echo/history event parser এই release-এ চালু নয়। External agent সেই app থেকে কাজ করলে Inbox-এ manual takeover ব্যবহার করুন।
- পুরোনো Messenger conversation eligible Page/token ও Conversations API permissions দিয়ে সীমিতভাবে পড়া সম্ভব; সাধারণ মানুষের জন্য Advanced Access প্রয়োজন। এই release **historical import UI/job চালায় না**।
- সাধারণ WhatsApp Cloud API-কে পুরোনো app history-এর arbitrary archive API হিসেবে ধরা হয়নি। Eligible Business App **Coexistence / Embedded Signup**-এ history sharing একটি আলাদা onboarding feature; account/permission/consent eligibility নির্ভর। Direct Meta history documentation এই session-এ rate-limited ছিল, তাই exact কতদিনের media/history আসবে এমন প্রতিশ্রুতি দেওয়া হয়নি। Import parser নেই; future work হিসেবে পৃথক consented migration দরকার।
- History import করলেও নতুন 24-hour reply window তৈরি করা যাবে না।
- Graph API **v26.0** pinned/configurable। Meta-maintained [SDK changelog](https://github.com/meta/facebook-ios-sdk/blob/main/CHANGELOG.md)-এ v26.0 default যাচাই করা হয়েছে। Direct Graph changelog fetch rate-limited ছিল; production enable করার আগে [Meta changelog](https://developers.facebook.com/docs/graph-api/changelog/) ও app-supported version আবার দেখুন।

## Admin update 2.2.6 → 2.3.0

Admin → Updates থেকে signed ZIP install করুন। তারপর AI Inbox খুলে current admin password দিয়ে **Create Inbox tables** চাপুন। cPanel/SQL import ছাড়াই নতুন tables হবে। এরপর Connections & settings-এ mock business ও dummy Page/number তৈরি করে test করুন। Live server-এ local demo/customer data বা credentials package-এ যায় না। নিচের CLI migration পদ্ধতি full source deployment-এর জন্য বিকল্প; Admin ZIP-এ migrations folder থাকে না।

## Hosting / deployment

Local check: PHP 8.2.12, Laravel 8.83.27, MySQL, cURL/OpenSSL/mbstring/PDO MySQL আছে। Dedicated queue command চলে। `storage/framework/cache`, `storage/framework/views`, `storage/logs` actual write probes সফল। Windows root directory `is_writable` flag-কে একমাত্র প্রমাণ ধরা হয়নি। Live cPanel resource/cron/SSH access এই session-এ দেওয়া হয়নি, তাই live capacity certify করা হয়নি।

1. Files deploy করার আগে code+database backup নিন। Existing `APP_KEY` অক্ষত রাখুন; নতুন key generate করলে encrypted credentials/history খোলা যাবে না।
2. শুধু নতুন migrations চালান (existing legacy migration history পুনরায় চালাবেন না):

```bash
cd /home/CPANEL_USER/public_html
php artisan migrate --path=database/migrations/2026_11_01_120000_create_unified_inbox.php --force
php artisan migrate --path=database/migrations/2026_11_01_120100_create_inbox_receipts.php --force
php artisan view:clear
php artisan route:clear
```

3. Existing `QUEUE_CONNECTION=sync` বদলাতে হয় না। Inbox নিজের durable database queue ব্যবহার করে; অন্য store jobs-এর behaviour বদলায়নি।
4. cPanel cron প্রতি মিনিটে (PHP path/username hosting অনুযায়ী):

```cron
* * * * * /usr/local/bin/php /home/CPANEL_USER/public_html/artisan inbox:work --stop-when-empty --max-time=50 >> /home/CPANEL_USER/inbox-worker.log 2>&1
```

5. VPS/Supervisor হলে:

```ini
[program:footstep-inbox]
command=/usr/bin/php /home/CPANEL_USER/public_html/artisan inbox:work --max-time=3600
directory=/home/CPANEL_USER/public_html
user=CPANEL_USER
autostart=true
autorestart=true
stopwaitsecs=90
redirect_stderr=true
stdout_logfile=/home/CPANEL_USER/inbox-worker.log
```

6. একসঙ্গে একই job claim আটকাতে row locks আছে। Lease expiry 3 মিনিট; bounded API timeouts এর কম। cPanel ছোট workload-এ minute cron যথেষ্ট হতে পারে, কিন্তু chat response up to ~1 minute delay হবে। Low-latency/high-volume হলে persistent worker/VPS প্রয়োজন। Provider allow outbound HTTPS to `graph.facebook.com`, `generativelanguage.googleapis.com` এবং public HTTPS webhook inbound।
7. Web server/user-এর `storage` subdirectories ও `bootstrap/cache` permission ঠিক করুন। MySQL InnoDB দরকার। App/server clock NTP সঠিক রাখুন। HTTPS, `APP_DEBUG=false`, cookie/session security এবং private backups বজায় রাখুন।
8. PHP errors/APM/HTTP debug tracing-এ request headers/bodies, secrets বা raw conversation capture বন্ধ রাখুন। Inbox application logs-এ API exception body/URL রাখা হয় না। Meta verification GET-এ verify token query parameter আসে—web server access log-এ এই endpoint-এর query string redact/omit করুন। Nginx-এ path-only log format বা dedicated webhook location logging policy, Apache/cPanel-এ provider-এর equivalent configuration লাগবে।
9. Admin status panel-এ worker last seen, pending/error code, token usage ও estimated cost দেখুন। 429/temporary AI errors bounded backoff retry হয়; AI generation manual retry করা যায়। Sending timeout/unknown provider outcome `uncertain` হয় এবং **blind retry হয় না**, যাতে duplicate customer message না যায়। API success মানে accepted, actual delivery/read webhook আলাদা।
10. Code rollback করলে queue worker আগে বন্ধ করুন। Inbox tables drop করবেন না—সেখানে conversation/history/credentials আছে। এই feature-এর schema migration code-only Admin updater নিজে চালায় না।

## Security ও operational boundaries

- Secrets ও message bodies Laravel Crypt encryption; browser-এ প্রয়োজনীয় conversation text ছাড়া private fields দেওয়া হয় না। APP_KEY ও database backup আলাদা সুরক্ষায় রাখুন।
- Admin সব business দেখতে পারে। Staff শুধু explicit membership অনুযায়ী business দেখতে/reply করতে পারে; credentials/settings admin-only। Channel product grant admin নির্ধারণ করে।
- Business-প্রতি নিজের Meta App callback ও app secret। একই Meta App-এর Pages/numbers একই business callback-এ রাখুন; আরেক business/Meta App-এর জন্য আলাদা business। App-level একই webhook object-এ একাধিক আলাদা callback override করবেন না।
- Inbound customer/customer-supplied linksকে tool instructions ধরা হয় না। Arbitrary remote image download বা browsing নেই; inbound attachments staff review।
- AI usage ledger per-business locked budget reservation করে। Cost একটি estimate, invoice নয়; uncertain calls conservatively reserved থাকে। Meta message fees, tax, currency conversion আলাদা। Model price manually configurable।
- নতুন live connection test নিজে metadata GET করে; actual permission, recipient eligibility, webhook subscription এবং delivery test আপনার Meta test assets দিয়ে আলাদাভাবে করতে হবে।
- Mock remote IDs real mode-এ পাঠানো blocked। Test lab থেকে live credentials যুক্ত করার বদলে production-এর আলাদা business তৈরি করা ভালো।

## Tests ও architecture

Workspace `footstep-ai-inbox/verify.php`: signature/tamper, verification, dedupe/unread, encrypted credentials, cross-business product/staff isolation, AI draft/auto/manual takeover, 24-hour gate, idempotent manual replies, delivery ordering, real product facts, no-order-write, UI render, fake Meta/Gemini adapters, token budget। Fixtures transaction rollback হয়।

Services: `app/Services/Inbox/{Store,Catalog,Webhooks,Gemini,Transport,Processor}.php`। Controllers: `InboxController`, `InboxWebhookController`। Worker: `InboxWork`। UI: `resources/views/backend/inbox` ও `public/assets/{css,js}/unified-inbox.*`। Tables আলাদা `inbox_*`; পুরোনো order schema বদলায়নি।

Browser automation Work settings-এ disabled ছিল; template render এবং HTTP/controller test হয়েছে, interactive browser visual QA হয়নি।
