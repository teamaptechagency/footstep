<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Product;
use Artisan;
use Cache;
use CoreComponentRepository;

class AdminController extends Controller
{
    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function admin_dashboard(Request $request)
    {   
        $request->validate(['period'=>'nullable|in:day,yesterday,week,month,year','dashboard_layout'=>'nullable|in:classic,modern','delivery_from'=>'nullable|date_format:Y-m-d','delivery_to'=>'nullable|date_format:Y-m-d']);
        try{$delivery=\App\Services\DeliveryActivity::report($request->input('delivery_from'),$request->input('delivery_to'));}
        catch(\InvalidArgumentException $e){throw \Illuminate\Validation\ValidationException::withMessages(['delivery_from'=>$e->getMessage()]);}
        if(\App\Services\AdminDashboardAppearance::selected($request)==='modern') {
            $user=auth()->user();$permissions=$user->user_type==='staff'?json_decode($user->staff->role->permissions??'[]',true):[];
            abort_unless($user->user_type==='admin'||in_array('1',$permissions?:[]),403);
            return view('backend.dashboards.modern',['report'=>\App\Services\StoreAnalytics::report($request->input('period','day')),'delivery'=>$delivery]);
        }
        CoreComponentRepository::initializeCache();
        $root_categories = Category::where('level', 0)->get();

        $cached_graph_data = Cache::remember('cached_graph_data', 86400, function() use ($root_categories){
            $num_of_sale_data = null;
            $qty_data = null;
            foreach ($root_categories as $key => $category){
                $category_ids = \App\Utility\CategoryUtility::children_ids($category->id);
                $category_ids[] = $category->id;

                $products = Product::with('stocks')->whereIn('category_id', $category_ids)->get();
                $qty = 0;
                $sale = 0;
                foreach ($products as $key => $product) {
                    $sale += $product->num_of_sale;
                    foreach ($product->stocks as $key => $stock) {
                        $qty += $stock->qty;
                    }
                }
                $qty_data .= $qty.',';
                $num_of_sale_data .= $sale.',';
            }
            $item['num_of_sale_data'] = $num_of_sale_data;
            $item['qty_data'] = $qty_data;

            return $item;
        });

        return view('backend.dashboard', compact('root_categories', 'cached_graph_data','delivery'));
    }

    function clearCache(Request $request)
    {
        Artisan::call('cache:clear');
        flash(translate('Cache cleared successfully'))->success();
        return back();
    }
}
