@extends('backend.layouts.app')
@section('content')
@php $rollbackCount=\App\Services\RollbackRetention::count(); @endphp
@php
 $releaseActorIds=collect($auditEvents)->pluck('actor')->filter(function($id){return is_scalar($id)&&ctype_digit((string)$id);})->unique()->values()->all();
 $releaseActorNames=\App\Models\User::whereIn('id',$releaseActorIds)->pluck('name','id');
@endphp
<div class="d-flex align-items-center justify-content-between mb-3"><h1 class="h4 mb-0">Updates & rollback</h1><a class="btn btn-soft-primary" href="{{ route('platform.tracking') }}">Marketing integrations</a></div>
@if(session('platform_success'))<div class="alert alert-success">{{ session('platform_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@if($problem)<div class="alert alert-danger">{{ $problem }}</div>@endif
<div class="row"><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Platform release</div><strong class="h3">{{ $releaseState['version'] }}</strong></div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Signature verification</div><strong class="{{ $keyReady ? 'text-success' : 'text-danger' }}">{{ $keyReady ? 'Trusted public key installed' : 'Public key setup required' }}</strong></div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">Rollback</div><strong>{{ $rollbackCount ? $rollbackCount.' previous version backup(s) available' : 'No retained rollback backup' }}</strong></div></div></div></div>
@if($pending)<div class="alert alert-danger">Interrupted operation detected. Installation is locked. Use the documented CLI recovery command before serving traffic.</div>@endif
<div class="card"><div class="card-body"><h2 class="h6">1. Validate a release package</h2><p class="text-muted">Only signed Footstep packages for the installed version are accepted. ZIP contents, SHA-256 checksums, existing file hashes and PHP compatibility are checked before installation. Maximum ZIP size: 25 MB.</p>
<form method="POST" action="{{ route('platform.releases.upload') }}" enctype="multipart/form-data">@csrf
<input type="file" name="package" accept=".zip" required class="form-control mb-3"><button class="btn btn-primary" {{ !$keyReady || $pending ? 'disabled' : '' }}>Validate package</button>
</form></div></div>
@if($preview)
<div class="card"><div class="card-body"><h2 class="h6">2. Review verified release</h2><p><strong>{{ $preview['manifest']['from_version'] }} → {{ $preview['manifest']['version'] }}</strong> · {{ count($preview['manifest']['files']) }} files · PHP ≥ {{ $preview['manifest']['php_min'] }}</p>
@php $verifiedNotes=array_replace(config('release_notes', []), config('release_notes_additional', []))[$preview['manifest']['version']] ?? []; @endphp
@if(!empty($preview['manifest']['release_notes']) || !empty($verifiedNotes['changes']))
<div class="alert alert-light border"><h3 class="h6">What's New — {{ $preview['manifest']['title'] ?? ($verifiedNotes['title'] ?? $preview['manifest']['version']) }}</h3><ul class="mb-0 pl-4">@foreach(($preview['manifest']['release_notes'] ?? ($verifiedNotes['changes'] ?? [])) as $change)<li>{{ $change }}</li>@endforeach</ul></div>
@endif
<div style="max-height:240px;overflow:auto"><table class="table table-sm"><thead><tr><th>File</th><th>Action / কার্যক্রম</th></tr></thead><tbody>@foreach($preview['manifest']['files'] as $file)<tr><td><code>{{ $file['path'] }}</code></td><td>{{ $file['before_sha256'] === null ? 'Add' : 'Replace with backup' }}</td></tr>@endforeach</tbody></table></div>
<form method="POST" action="{{ route('platform.releases.install') }}">@csrf<input type="hidden" name="package" value="{{ $preview['id'] }}"><label for="install-password">Confirm your admin password</label><input class="form-control mb-3" id="install-password" type="password" name="password" autocomplete="current-password" required><button class="btn btn-success">Back up & install verified release</button></form>
</div></div>
@endif
@php $rollbackInfo=\App\Services\ReleaseRollbackPreview::current($releaseState,false); @endphp
@include('backend.platform.rollback_preview')
@php $rollbackHistoryMarked=[]; @endphp
<div class="card"><div class="card-body"><h2 class="h6">Release history / আপডেটের ইতিহাস</h2><div class="table-responsive"><table class="table"><thead><tr><th>Time (UTC) / সময়</th><th>Action / কার্যক্রম</th><th>Version / সংস্করণ</th><th>Title / শিরোনাম</th><th>Admin ID / অ্যাডমিন</th><th>Rollback backup</th><th>What's New / নতুন কী আছে</th></tr></thead><tbody>
@forelse($auditEvents as $release)
@php
 $notesVersion=$release['operation']==='Installed' ? $release['to'] : $release['from'];
 $knownNotes=array_replace(config('release_notes', []), config('release_notes_additional', []))[$notesVersion] ?? [];
 $releaseTitle=$release['title'] ?? ($knownNotes['title'] ?? ('Release '.$notesVersion));
 $releaseChanges=!empty($release['release_notes']) ? $release['release_notes'] : ($knownNotes['changes'] ?? []);
 $historySnapshot=null;
 if($release['operation']==='Installed'&&!isset($rollbackHistoryMarked[$release['to']])){
  foreach($rollbackInfo['snapshots'] as $item){if($item['from']===$release['to']){$historySnapshot=$item;$rollbackHistoryMarked[$release['to']]=true;break;}}
 }
@endphp
<tr><td>{{ $release['at'] }}</td><td>{{ $release['operation'] }}</td><td>{{ $release['from'] }} → {{ $release['to'] }}</td><td>{{ $releaseTitle }}</td><td><span class="text-muted">{{ $release['actor'] }}</span>@if(ctype_digit((string)$release['actor']))<span class="ml-1">· {{ $releaseActorNames->get((int)$release['actor']) ?: 'Deleted account / মুছে ফেলা অ্যাকাউন্ট' }}</span>@else<span class="d-block small text-muted">System / সিস্টেম</span>@endif</td><td>@if($historySnapshot && $historySnapshot['retained'])<span class="badge badge-soft-success">{{ $historySnapshot['step']===1 ? 'Next rollback' : 'Second step' }}</span><small class="d-block">→ {{ $historySnapshot['target'] }}</small>@else<span class="text-muted small">History only / Backup unavailable</span>@endif</td><td><button type="button" class="btn btn-sm btn-outline-primary rounded-circle release-notes-button" data-toggle="modal" data-target="#release-notes-{{ $loop->index }}" aria-label="What's New in {{ $notesVersion }}" title="What's New / নতুন কী আছে" style="width:32px;height:32px;padding:0;font-size:18px;font-weight:700">!</button></td></tr>
@empty<tr><td colspan="7">No upgrade installed yet. / এখনো কোনো আপডেট install করা হয়নি।</td></tr>@endforelse</tbody></table></div>
@foreach($auditEvents as $release)
@php
 $notesVersion=$release['operation']==='Installed' ? $release['to'] : $release['from'];
 $knownNotes=array_replace(config('release_notes', []), config('release_notes_additional', []))[$notesVersion] ?? [];
 $releaseTitle=$release['title'] ?? ($knownNotes['title'] ?? ('Release '.$notesVersion));
 $releaseChanges=!empty($release['release_notes']) ? $release['release_notes'] : ($knownNotes['changes'] ?? []);
@endphp
<div class="modal fade" id="release-notes-{{ $loop->index }}" tabindex="-1" role="dialog" aria-modal="true" aria-labelledby="release-notes-title-{{ $loop->index }}"><div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document"><div class="modal-content"><div class="modal-header"><div><small class="text-muted">What's New / নতুন কী আছে · {{ $notesVersion }}</small><h3 class="h5 mb-0 mt-1" id="release-notes-title-{{ $loop->index }}">{{ $releaseTitle }}</h3></div><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div><div class="modal-body">@if(count($releaseChanges))<ul class="mb-0 pl-4">@foreach($releaseChanges as $change)<li class="mb-3">{{ $change }}</li>@endforeach</ul>@else<p class="text-muted mb-0">Release notes were not provided for this update. / এই আপডেটের বিবরণ দেওয়া হয়নি।</p>@endif</div><div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Close / বন্ধ করুন</button></div></div></div></div>
@endforeach
@if($rollbackCount)<form method="POST" action="{{ route('platform.releases.rollback') }}">@csrf<label for="rollback-password">Admin password to restore the verified release / যাচাইকৃত rollback-এর জন্য password</label><input class="form-control mb-3" id="rollback-password" type="password" name="password" autocomplete="current-password" required><label class="d-block"><input type="checkbox" required> উপরের file list ও target version দেখে rollback করতে সম্মত।</label><button id="rb-submit" disabled class="btn btn-outline-danger">Check rollback plan first / আগে যাচাই করুন</button></form>@endif
</div></div>
<div class="alert alert-info">Rollback: শুধু সর্বশেষ ২টি version snapshot রাখা হবে। নতুন update সফল হলে তার আগের snapshots মুছবে; history থাকবে। আলাদা full website backups এই নিয়মে মুছবে না।<br>Code-only updates: customer data, orders, uploads, .env, vendor packages and database schemas are excluded. No SQL or install scripts run. Keep the signing private key offline. Interrupted upgrades can be restored using CLI recovery; manual edits after an upgrade block rollback rather than silently overwrite your work.</div>
@endsection
