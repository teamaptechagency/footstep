@php($health=\App\Services\RuntimeHealth::read())
<div class="card mb-3"><div class="card-body"><h2 class="h6 mb-2">Background health / Cron ও queue</h2>
@if($health)
<div class="d-flex flex-wrap" style="gap:14px"><span>Scheduler snapshot: <strong>{{ \Carbon\Carbon::parse($health['at'])->timezone('Asia/Dhaka')->format('d M Y, g:i A') }}</strong></span><span>CLI PHP: {{ $health['php'] }}</span><span>Queue: {{ $health['queue'] }}</span><span>Pending jobs: {{ $health['pending_jobs']??'—' }}</span><span>Failed jobs: {{ $health['failed_jobs']??'—' }}</span></div>
@if(\Carbon\Carbon::parse($health['at'])->lt(now()->subMinutes(15)))<p class="text-warning mt-2 mb-0">Snapshot পুরোনো—cron চলছে কি না পরীক্ষা করুন।</p>@endif
@else<p class="text-muted mb-1">নতুন scheduler snapshot এখনো আসেনি। Cron থেকে প্রতি ৫ মিনিটে schedule:run চলতে হবে।</p>@endif
@if(config('queue.default')==='sync')<p class="small text-muted mt-2 mb-0">Queue এখন sync: queue-তে দেওয়া কাজও request-এর মধ্যে চলতে পারে। Background worker তৈরি না করে queue driver বদলাবেন না।</p>@endif
<p class="small text-muted mt-2 mb-0">এটি scheduler-এর উপস্থিতি দেখায়; queue worker job process করেছে—তার প্রমাণ নয়। কোনো customer/message/token এখানে রাখা হয় না।</p>
</div></div>
