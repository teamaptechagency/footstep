<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NativeMobileController as Mobile;
use App\Http\Middleware\NativeMobileAuth;

Route::prefix('mobile/v1')->name('mobile.')->middleware(\App\Http\Middleware\NativeCatalogRevision::class)->group(function(){
    Route::get('privacy',[Mobile::class,'privacyPage']);
    Route::get('support',[Mobile::class,'support']);
    Route::get('account-deletion',[Mobile::class,'deletionPage']);
    Route::get('health',[Mobile::class,'health']);
    Route::get('deals',[\App\Http\Controllers\StorefrontV3Controller::class,'deals']);
    Route::get('deals/{key}/products',[\App\Http\Controllers\StorefrontV3Controller::class,'dealProducts'])->where('key','[0-9]+');
    Route::get('home',[Mobile::class,'home']);
    Route::get('site',[\App\Http\Controllers\StorefrontV3Controller::class,'site']);
    Route::get('home-sections',function(){return response()->json(['data'=>\App\Services\HomepageSections::publicSections()])->header('Cache-Control','private, no-store');});
    Route::get('home-banners',function(){return response()->json(['data'=>\App\Services\HomepageBanners::publicData()])->header('Cache-Control','private, no-store');});
    Route::get('products',[Mobile::class,'products']);
    Route::get('products/{id}',[Mobile::class,'product'])->whereNumber('id');
    Route::post('login',[Mobile::class,'login'])->middleware('throttle:6,1');
    Route::post('register',[Mobile::class,'register'])->middleware('throttle:3,60');
    Route::middleware(NativeMobileAuth::class)->group(function(){
        Route::post('account/delete',[Mobile::class,'deleteAccount'])->middleware('throttle:3,15');
        Route::get('me',[Mobile::class,'me']);
        Route::post('logout',[Mobile::class,'logout']);
        Route::get('orders',[Mobile::class,'orders']);
        Route::get('orders/{id}',[Mobile::class,'order'])->whereNumber('id');
        Route::post('checkout/quote',[Mobile::class,'quote']);
        Route::post('checkout',[Mobile::class,'checkout'])->middleware('throttle:10,1');
        Route::get('admin/catalog/options',[\App\Http\Controllers\NativeCatalogController::class,'options']);
        Route::get('admin/catalog/media',[\App\Http\Controllers\NativeCatalogController::class,'media']);
        Route::post('admin/catalog/upload',[\App\Http\Controllers\NativeCatalogController::class,'upload'])->middleware('throttle:60,1');
        Route::get('admin/catalog/products/{id}',[\App\Http\Controllers\NativeCatalogController::class,'product'])->whereNumber('id');
        Route::post('admin/catalog/products',[\App\Http\Controllers\NativeCatalogController::class,'saveProduct']);
        Route::post('admin/catalog/products/{id}',[\App\Http\Controllers\NativeCatalogController::class,'saveProduct'])->whereNumber('id');
        Route::get('admin/catalog/deals',[\App\Http\Controllers\NativeCatalogController::class,'deals']);
        Route::get('admin/catalog/deals/{id}',[\App\Http\Controllers\NativeCatalogController::class,'deal'])->whereNumber('id');
        Route::post('admin/catalog/deals',[\App\Http\Controllers\NativeCatalogController::class,'saveDeal']);
        Route::post('admin/catalog/deals/{id}',[\App\Http\Controllers\NativeCatalogController::class,'saveDeal'])->whereNumber('id');
        Route::post('admin/catalog/deals/{id}/state',[\App\Http\Controllers\NativeCatalogController::class,'dealState'])->whereNumber('id');
        Route::get('admin/dashboard',[Mobile::class,'dashboard']);
        Route::get('admin/menus',[Mobile::class,'menus']);
        Route::get('admin/products',[Mobile::class,'adminProducts']);
        Route::get('admin/products/{id}',[Mobile::class,'adminProduct'])->whereNumber('id');
        Route::post('admin/products',[Mobile::class,'createProduct']);
        Route::post('admin/products/{id}',[Mobile::class,'updateProduct'])->whereNumber('id');
        Route::post('admin/orders/{id}/action',[Mobile::class,'orderAction'])->whereNumber('id');
        Route::get('admin/resource/{key}',[Mobile::class,'resource'])->where('key','[a-z0-9_.-]+');
    });
});
