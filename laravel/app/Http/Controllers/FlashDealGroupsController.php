<?php
namespace App\Http\Controllers;

use App\Models\{FlashDeal,Upload};
use Illuminate\Support\Facades\DB;

class FlashDealGroupsController extends Controller
{
    public function index(){
        $now=time();$deals=FlashDeal::displayOrder()->where('status',1)->where('start_date','<=',$now)->where('end_date','>',$now)->paginate(24);
        $ids=$deals->pluck('id')->all();$groups=[];
        if($ids){
            $members=DB::table('flash_deal_products')->join('products','products.id','=','flash_deal_products.product_id')->whereIn('flash_deal_products.flash_deal_id',$ids)->where('products.published',1)->where('products.approved',1)->where('products.auction_product',0)->where('products.digital',0)->orderBy('flash_deal_products.id')->get(['flash_deal_products.flash_deal_id','products.id','products.thumbnail_img']);
            $byDeal=$members->groupBy('flash_deal_id');$uploadIds=$deals->pluck('banner')->merge($members->pluck('thumbnail_img'))->filter()->unique()->all();$uploads=Upload::whereIn('id',$uploadIds)->get(['id','file_name'])->keyBy('id');
            foreach($deals as $deal){
                $items=($byDeal->get($deal->id)??collect())->unique('id');$photos=[];
                foreach($items->take(3)as $item){$upload=$uploads->get($item->thumbnail_img);if($upload)$photos[]=my_asset($upload->file_name);}
                $banner=$uploads->get($deal->banner);$cover=$banner?my_asset($banner->file_name):($photos[0]??static_asset('assets/img/placeholder.jpg'));
                $groups[]=['id'=>$deal->id,'title'=>$deal->getTranslation('title'),'url'=>route('flash-deal-details',$deal->slug),'cover'=>$cover,'photos'=>$photos,'count'=>$items->count()];
            }
        }
        return view('frontend.flash_deal.all_flash_deal_list',['dealGroups'=>$groups,'dealPaginator'=>$deals]);
    }
}
