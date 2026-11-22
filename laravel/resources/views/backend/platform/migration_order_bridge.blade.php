@extends('backend.layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h4">Order Migration Bridge</h1><a class="btn btn-light" href="{{ route('platform.migration-orders') }}">Refresh status</a></div>
@if(session('bridge_notice'))<div class="alert alert-info">{{ session('bridge_notice') }}</div>@endif
<div class="card"><div class="card-body">
<h2 class="h5">পুরোনো hosting → এই VPS</h2>
<p>DNS পরিবর্তনের সময় পুরোনো website-এ আসা order এখানে copy হবে। পুরোনো hosting-এ ছোট export file ও private configuration upload করতে হবে।</p>
<p><strong>{{ $enabled ? 'Enabled / চালু' : 'Disabled / বন্ধ' }}</strong> · মেয়াদ: {{ $expires ? date('d M Y', $expires) : 'Not configured' }}</p>
<form method="post" action="{{ route('platform.migration-orders.toggle') }}">@csrf<input type="hidden" name="enabled" value="{{ $enabled ? 0 : 1 }}"><button class="btn {{ $enabled ? 'btn-danger' : 'btn-primary' }}" type="submit">{{ $enabled ? 'Stop bridge' : 'Start bridge' }}</button></form>
<div class="alert alert-warning mt-3 mb-0">একই order দুই server থেকে courier-এ দেবেন না। এই bridge stock, login, product/media বা SMS sync করে না। VPS-এ বদলানো order overwrite না করে নিচে conflict দেখাবে।</div>
</div></div>
<div class="row">
@foreach(['synced'=>'Synced orders','conflict'=>'Needs review','error'=>'Import errors'] as $key=>$label)<div class="col-md-4"><div class="card"><div class="card-body"><div>{{ $label }}</div><strong class="h3">{{ $counts[$key] ?? 0 }}</strong></div></div></div>@endforeach
</div>
<div class="card"><div class="card-body"><h2 class="h5">Worker status</h2><p>Latest result: <strong>{{ $status['status'] ?? 'Not run' }}</strong></p><p>Last attempt: {{ $status['last_attempt'] ?? '—' }}<br>Last successful batch: {{ $status['last_success'] ?? '—' }}</p>@if(!empty($status['error']))<div class="alert alert-danger">{{ $status['error'] }}<br>source_http_404/503 হলে পুরোনো server-এর upload/configuration পরীক্ষা করুন। network_error হলে source SSL/network পরীক্ষা করুন।</div>@endif<p class="mb-0">প্রতি minute-এ সর্বোচ্চ ১৫০টি order পরীক্ষা হয়। বড় backlog একাধিক pass-এ শেষ হবে। এখানকার সময় UTC।</p></div></div>
<div class="card"><div class="card-header">Review required (latest 50)</div><div class="table-responsive"><table class="table"><thead><tr><th>Old order ID</th><th>VPS order ID</th><th>Reason</th><th>Checked (UTC)</th></tr></thead><tbody>@forelse($issues as $issue)<tr><td>{{ $issue->source_id }}</td><td>{{ $issue->order_id ?? 'Not imported' }}</td><td>{{ $issue->reason }}</td><td>{{ $issue->synced_at }}</td></tr>@empty<tr><td colspan="4">No conflicts recorded.</td></tr>@endforelse</tbody></table></div></div>
@endsection
