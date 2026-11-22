@extends('backend.layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3"><h1 class="h3">{{ $account?'Edit Admin / অ্যাডমিন এডিট':'Add Admin / অ্যাডমিন যোগ' }}</h1><a href="{{ route('staffs.admins') }}" class="btn btn-light">All Admins</a></div>
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div class="card"><div class="card-body"><form method="POST" action="{{ $account?route('admin_accounts.update',$account->id):route('admin_accounts.store') }}">@csrf @if($account) @method('PUT') @endif
<div class="form-group"><label for="admin-name">Name / নাম</label><input id="admin-name" name="name" value="{{ old('name',$account->name??'') }}" maxlength="191" class="form-control" required autocomplete="name"></div>
<div class="form-group"><label for="admin-email">Email / ইমেইল</label><input id="admin-email" name="email" type="email" value="{{ old('email',$account->email??'') }}" maxlength="191" class="form-control" required autocomplete="email"></div>
<div class="form-group"><label for="admin-phone">Phone / ফোন</label><input id="admin-phone" name="phone" value="{{ old('phone',$account->phone??'') }}" maxlength="20" class="form-control" autocomplete="tel"></div>
<div class="form-group"><label for="admin-password">{{ $account?'New password (optional) / নতুন পাসওয়ার্ড (ঐচ্ছিক)':'Password / পাসওয়ার্ড' }}</label><input id="admin-password" name="password" type="password" minlength="8" maxlength="200" class="form-control" autocomplete="new-password" @if(!$account) required @endif><small class="text-muted">Minimum 8 characters. @if($account)Leave blank to keep the existing password.@endif</small></div>
<div class="form-group"><label for="admin-password-confirmation">Confirm new password</label><input id="admin-password-confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" @if(!$account) required @endif></div>
<hr><div class="form-group"><label for="current-admin-password">Your admin password / আপনার অ্যাডমিন পাসওয়ার্ড</label><input id="current-admin-password" name="current_password" type="password" class="form-control" autocomplete="current-password" required></div>
<button class="btn btn-primary" type="submit">{{ $account?'Save changes':'Add admin' }}</button><a href="{{ route('staffs.admins') }}" class="btn btn-light">Cancel</a>
</form></div></div>
@endsection