<?php
namespace App\Services;

class SingleVendor
{
    public const DISABLED_SETTINGS=['vendor_system_activation','seller_wholesale_product','seller_auction_product','pos_activation_for_seller','seller_product_manage_by_admin','product_manage_by_admin','product_approve_by_admin','classified_product'];

    public static function ownerId()
    {
        return (int)storefront_request_value('single_vendor_owner',function(){
            $id=\App\Models\User::where('user_type','admin')->orderBy('id')->value('id');
            if(!$id)throw new \RuntimeException('Store administrator is missing.');
            return $id;
        });
    }

    public static function cartGroups($carts)
    {
        $items=is_array($carts)?array_values($carts):$carts->values()->all();
        return count($items)?[self::ownerId()=>$items]:[];
    }

    public static function blocksRoute($route)
    {
        if(!$route)return false;
        $parts=explode('@',$route->getActionName());
        $controller=strtolower(class_basename($parts[0]));$method=strtolower($parts[1]??'');$name=strtolower($route->getName()??'');
        if(strpos($controller,'seller')!==false||in_array($controller,['shopcontroller','commissioncontroller','customerproductcontroller','customerpackagecontroller'],true))return true;
        if(in_array('seller',$route->gatherMiddleware(),true))return true;
        if(strpos($method,'seller')!==false&&$method!=='bestseller')return true;
        if(strpos($method,'commission')!==false)return true;
        if($controller==='homecontroller'&&in_array($method,['shop','filter_shop','premium_package_index'],true))return true;
        if(preg_match('/(^|[._])(?:sellers?|shops?|commission)(?:[._]|$)/',$name))return true;
        return false;
    }
}
