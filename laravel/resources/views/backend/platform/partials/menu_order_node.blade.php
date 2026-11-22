<li class="menu-order-row depth-{{ $level }}" data-order-id="{{ $node['id'] }}" draggable="true">
    <div class="menu-order-row-head">
        <span class="menu-order-grip" aria-hidden="true"><i class="las la-grip-vertical"></i></span>
        <span class="menu-order-position" aria-hidden="true"></span>
        <strong>{{ $node['label'] }}</strong>
        @if($node['children'])<button type="button" class="menu-order-expand" aria-expanded="false" aria-label="Show {{ $node['label'] }} submenu"><span>{{ count($node['children']) }} items</span><i class="las la-angle-down" aria-hidden="true"></i></button>@endif
        <div class="menu-order-actions"><button type="button" data-move="up" aria-label="Move {{ $node['label'] }} up">↑</button><button type="button" data-move="down" aria-label="Move {{ $node['label'] }} down">↓</button></div>
    </div>
    @if($node['children'])
    <ul class="menu-order-list nested" data-order-group="{{ $node['id'] }}" aria-label="{{ $node['label'] }} submenu order" hidden>
        @foreach($node['children'] as $child)
            @include('backend.platform.partials.menu_order_node',['node'=>$child,'level'=>$level+1])
        @endforeach
    </ul>
    @endif
</li>
