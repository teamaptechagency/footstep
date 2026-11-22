@extends('backend.layouts.app')
@section('content')
<div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap:12px"><div><h1 class="h4 mb-1">Marketing integrations</h1><p class="text-muted mb-0">যে integration সেটআপ করবেন, তার card খুলুন। প্রতিটির নিজস্ব settings ও নির্দেশনা আছে।</p></div><a href="{{ route('platform.releases') }}" class="btn btn-soft-primary">Updates & rollback</a></div>
@if(session('platform_success'))<div class="alert alert-success">{{ session('platform_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@include('backend.platform.cards')
<div class="card"><div class="card-body"><h2 class="h6">সেটআপ ও event receipt আলাদা</h2><p class="mb-0">Enabled / ID configured মানে সঠিক format-এর ID save আছে। Event সত্যিই পৌঁছেছে কি না provider-এর test tool-এ পরীক্ষা করুন। একই tag GTM ও direct integration—দুই জায়গা থেকে পাঠালে duplicate হতে পারে।</p></div></div>
@endsection
