
<div class="card" id="rollback-detail" data-url="{{ route('website-backup.rollback-preview') }}"><div class="card-body">
<h2 class="h5">Rollback plan / কোন version-এ ফিরবে</h2>
<p>শুধু সর্বশেষ ২টি update snapshot রাখা হয়। নিচের history হলো কাজের রেকর্ড—প্রতিটি history row-এর backup আছে, এমন নয়।</p>
@forelse($rollbackInfo['snapshots'] as $snapshot)
<div class="border rounded p-3 mb-3"><strong>{{ $snapshot['step']===1 ? 'Next rollback / পরের rollback' : 'Then / তারপর (আগের rollback সফল হলে)' }}: {{ $snapshot['from'] }} → {{ $snapshot['target'] }}</strong>
<div class="mt-2">{{ $snapshot['retained'] ? 'Saved files found / Backup পাওয়া গেছে' : 'Unavailable / Backup নেই বা যাচাই করা যায়নি' }} · {{ $snapshot['restore_count'] }} files restore · {{ $snapshot['remove_count'] }} newly added files remove · {{ number_format($snapshot['restore_bytes']/1048576,2) }} MB restored contents</div>
@foreach($snapshot['issues'] as $issue)<p class="text-danger mb-0">{{ $issue }}</p>@endforeach</div>
@empty<p>No rollback snapshot in current history / ফিরিয়ে নেওয়ার snapshot নেই।</p>@endforelse
<div class="alert alert-info">Rollback শুধু ওই update-এর code files ফিরাবে। Orders, customer information, database, uploaded product images, .env ও vendor packages ফিরবে না। দ্বিতীয় ধাপে যেতে প্রথম rollback সফল হতে হবে। Missing backup বা manual file change থাকলে rollback বন্ধ থাকবে।</div>
@if($rollbackInfo['next'])<button type="button" class="btn btn-light" id="rb-check">Check exact files & eligibility / কী কী ফিরবে দেখুন</button>@endif
<p id="rb-status" class="mt-3" role="status"></p><div class="table-responsive" style="max-height:360px"><table class="table table-sm" id="rb-files" hidden><thead><tr><th>File</th><th>Action</th><th>Saved size</th></tr></thead><tbody></tbody></table></div>
</div></div><script src="{{ static_asset('assets/js/rollback-preview.js') }}?v=2.4.21" defer></script>
