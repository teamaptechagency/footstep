@php
    $appearanceOptions=\App\Services\StorefrontAppearance::settings();
    $appearancePalette=\App\Services\StorefrontAppearance::palette();
    $appearanceStylesheet=\App\Services\StorefrontAppearance::stylesheet();
@endphp
@if($appearanceStylesheet)
<link rel="stylesheet" href="{{ static_asset($appearanceStylesheet) }}?v={{ filemtime(public_path($appearanceStylesheet)) }}">
@endif
@if($appearanceOptions['color']!=='existing')
<style id="storefront-color-pack">:root{--primary:{{ $appearancePalette['primary'] }};--hov-primary:{{ $appearancePalette['hover'] }};--soft-primary:{{ hex2rgba($appearancePalette['primary'],.15) }};}</style>
@endif
