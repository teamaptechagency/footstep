@extends('backend.layouts.app')
@section('content')
<div class="mx-auto" style="max-width:850px">
    <h1 class="h4 mb-3">International interface</h1>
    <div class="card">
        <div class="card-header"><h2 class="h6 mb-0">Storefront settings</h2><span class="badge {{ $enabled ? 'badge-success' : 'badge-secondary' }}">{{ $enabled ? 'Enabled' : 'Disabled' }}</span></div>
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('settings.international.save') }}">
                @csrf
                <input type="hidden" name="enabled" value="0">
                <div class="d-flex align-items-center mb-3" style="gap:16px">
                    <label class="aiz-switch aiz-switch-success mb-0"><input id="international-enabled" type="checkbox" name="enabled" value="1" {{ $enabled ? 'checked' : '' }}><span class="slider round"></span></label>
                    <label for="international-enabled" class="mb-0 fw-600">Enable international interface / আন্তর্জাতিক ডিজাইন চালু করুন</label>
                </div>
                <p class="text-muted">On: visitors with an IP outside Bangladesh see the international design. Shared browser location can also select it for Bangladesh or unknown IPs.</p>
                <p class="text-muted">Off: all visitors see the standard design. This setting applies to footstep.astrallabs.uk.</p>
                <button type="submit" class="btn btn-primary">Save settings</button>
            </form>
        </div>
    </div>
</div>
@endsection
