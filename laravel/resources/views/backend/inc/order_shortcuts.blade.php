@if(auth()->check() && auth()->user()->user_type==='admin')
<style>#admin-order-shortcuts [hidden]{display:none!important}</style>
<nav class="d-flex align-items-center ml-2" aria-label="Order shortcuts" id="admin-order-shortcuts" data-url="{{ route('admin.order-menu-counts') }}" style="gap:6px">
@foreach(['orders'=>['Orders','all_orders.index','la-shopping-bag'],'incomplete'=>['Incomplete Orders','admin.incomplete-orders','la-clipboard-list']] as $key=>$item)
<a href="{{ route($item[1]) }}" class="btn btn-light btn-sm d-flex align-items-center" aria-label="{{ $item[0] }}" style="gap:5px;white-space:nowrap"><i class="las {{ $item[2] }}" aria-hidden="true"></i><span class="d-none d-md-inline">{{ $item[0] }}</span><span class="badge badge-success" data-order-menu="{{ $key }}" hidden></span></a>
@endforeach
</nav>
<script src="{{ static_asset('assets/js/admin-order-shortcuts.js') }}?v=20261120" defer></script>
@endif
