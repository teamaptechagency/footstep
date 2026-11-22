@php $trackingCards = \App\Services\TrackingIntegrations::cards(); @endphp
<style>
.integration-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:14px;margin-bottom:22px}.integration-card{display:block;padding:15px;border:1px solid #dce5ef;border-radius:14px;background:#fff;box-shadow:0 2px 5px #14213d08;color:#334155}.integration-card.active{border-color:#86efac;background:linear-gradient(120deg,#f0fdf4,#fff)}.integration-title{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:#0f172a}.integration-icon{display:inline-flex;justify-content:center;align-items:center;color:#fff;width:22px;height:22px;border-radius:6px}.integration-dot{margin-left:auto;width:8px;height:8px;background:#94a3b8;border-radius:50%}.active .integration-dot{background:#22c55e;box-shadow:0 0 0 3px #bbf7d0}.integration-state{font-size:14px;font-weight:700;color:#64748b;margin:7px 0}.active .integration-state{color:#16a34a}.integration-id{font-size:11px;color:#64748b;overflow-wrap:anywhere}@media(max-width:1100px){.integration-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:600px){.integration-grid{grid-template-columns:1fr 1fr}}
</style>
<div class="integration-grid">
@foreach($trackingCards as $key=>$card)
    <a class="integration-card {{ $card['configured'] ? 'active' : '' }}" href="{{ $key === 'meta' ? route('platform.meta') : route('platform.tracking.provider', $key) }}">
        <div class="integration-title"><span class="integration-icon" style="background:{{ $card['color'] }}">{{ $card['icon'] }}</span>{{ $card['label'] }}<span class="integration-dot"></span></div>
        <div class="integration-state">{{ $card['configured'] ? ($card['legacy'] ? 'Legacy / Configured' : 'Enabled / ID configured') : 'Disabled / Inactive' }}</div>
        <div class="integration-id">{{ $card['id'] ? 'ID: '.$card['id'] : 'No ID configured' }}</div>
    </a>
@endforeach
</div>
