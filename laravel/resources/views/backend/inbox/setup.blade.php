@extends('backend.layouts.app')
@section('content')
@include('backend.inbox.tabs')<div class="card"><div class="card-body"><h1 class="h3">Inbox database setup required</h1><p>Run the dedicated Inbox migration from the setup guide. Existing store data is not modified.</p><code>php artisan migrate --path=database/migrations/2026_11_01_120000_create_unified_inbox.php --force</code></div></div>@endsection
