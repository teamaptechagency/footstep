@if(\App\Services\BackendModules::enabled('protection.manage'))
@php
 $orderBlockInfo=$orderProtectionRows[$blockOrder->id]??\App\Services\OrderProtection::info($blockOrder);
 $hideIp=!empty($numberOnly);
@endphp
<div class="mb-2 order-block-controls" data-protection-url="{{ route('store-ops.protect',$blockOrder->id) }}" data-protection-token="{{ csrf_token() }}"><div class="d-flex flex-wrap justify-content-end" style="gap:5px">
@if(!$hideIp)<button type="button" data-order-protect="{{ $orderBlockInfo['ip_blocked']?'unblock_ip':'block_ip' }}" class="btn btn-sm {{ $orderBlockInfo['ip_blocked']?'btn-soft-success':'btn-soft-danger' }}" title="{{ $orderBlockInfo['ip']?:'IP not recorded for this old order' }}" @if(!$orderBlockInfo['ip']||(!$orderBlockInfo['ip_blocked']&&$orderBlockInfo['ip']===request()->ip())) disabled @endif><i class="las la-shield-alt" aria-hidden="true"></i> {{ $orderBlockInfo['ip_blocked']?'Unblock IP':'Block IP' }}</button>@endif
<button type="button" data-order-protect="{{ $orderBlockInfo['phone_blocked']?'unblock_phone':'block_phone' }}" class="btn btn-sm {{ $orderBlockInfo['phone_blocked']?'btn-soft-success':'btn-soft-danger' }}" title="{{ $orderBlockInfo['phone']?:'Valid customer mobile not available' }}" @if(!$orderBlockInfo['phone']||!$orderBlockInfo['can_block_customer']) disabled @endif><i class="las la-phone-slash" aria-hidden="true"></i> {{ $orderBlockInfo['phone_blocked']?'Unblock number':'Block number' }}</button></div><small data-protection-result role="status" class="d-block text-danger"></small></div>
@if(!$hideIp&&empty($hideMeta))<small class="d-block text-muted mb-1">IP: {{ $orderBlockInfo['ip']?:'Not recorded' }} · {{ $orderBlockInfo['phone']?:'No number' }}</small>@endif

@endif
