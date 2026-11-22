<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App;

class FlashDeal extends Model
{
    protected $with = ['flash_deal_translations'];

    // New groups lead until an administrator assigns their explicit position.
    public function scopeDisplayOrder($query)
    {
        return $query->orderByRaw('CASE WHEN sort_order IS NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function getTranslation($field = '', $lang = false){
        $lang = $lang == false ? App::getLocale() : $lang;
        $flash_deal_translation = $this->flash_deal_translations->where('lang', $lang)->first();
        return $flash_deal_translation != null ? $flash_deal_translation->$field : $this->$field;
    }

    public function flash_deal_translations(){
      return $this->hasMany(FlashDealTranslation::class);
    }

    public function flash_deal_products()
    {
        return $this->hasMany(FlashDealProduct::class)->orderBy('id','asc');
    }
}
