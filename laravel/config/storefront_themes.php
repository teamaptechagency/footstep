<?php
return [
    // Register future layouts here; frame views reuse the existing global scripts and modals.
    'layouts'=>[
        'bazar'=>['name'=>'Footstep Bazar','description'=>'A full responsive retail theme with orange accents, dark category navigation, promotional banners, product grids and a shopping drawer. / সম্পূর্ণ responsive বাজার স্টাইল।','frame'=>'frontend.layouts.themes.bazar','stylesheet'=>'assets/css/themes/bazar.css'],
        'studio'=>['name'=>'Footstep Studio','description'=>'A complete modern footwear storefront: bold campaign, curated collections, quick shopping and a clean responsive catalog.','frame'=>'frontend.layouts.themes.studio','stylesheet'=>'assets/css/themes/studio.css'],
        'premium'=>['name'=>'Footstep Premium','description'=>'A refined upgrade of the existing design: editorial header, modern product cards, generous spacing and a polished mobile experience.','frame'=>'frontend.layouts.themes.premium','stylesheet'=>'assets/css/themes/premium.css'],
        'classic'=>['name'=>'Footstep Classic','description'=>'Existing storefront design, navigation, product sections and footer.','frame'=>'frontend.layouts.themes.classic','stylesheet'=>null],
    ],
    'colors'=>[
        'bazar-orange'=>['name'=>'Bazar Orange','primary'=>'#F58A1F','hover'=>'#DB7410','description'=>'Orange retail accents with dark green navigation.'],
        'ink'=>['name'=>'Studio Ink','primary'=>'#20241E','hover'=>'#41493B','description'=>'Charcoal accents for the Studio storefront.'],
        'existing'=>['name'=>'Existing brand colors','description'=>'Keep the colors from your current website settings.'],
        'emerald'=>['name'=>'Emerald','primary'=>'#008A45','hover'=>'#006B35','description'=>'Fresh green for the Footstep brand.'],
        'navy'=>['name'=>'Ocean Navy','primary'=>'#174A8B','hover'=>'#103667','description'=>'Deep blue with a clean retail feel.'],
        'rose'=>['name'=>'Rose','primary'=>'#BE185D','hover'=>'#9D174D','description'=>'A bold pink accent.'],
        'amber'=>['name'=>'Warm Amber','primary'=>'#B45309','hover'=>'#92400E','description'=>'Warm orange and brown accents.'],
    ],
];
