<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
  protected $with=['manualMetadata','user'];
  public function getManualMetadataKeyAttribute(){ return 'manual_review_'.$this->id; }
  public function manualMetadata(){ return $this->hasOne(BusinessSetting::class,'type','manual_metadata_key'); }
  public function getIsManualAttribute(){ return $this->manualMetadata!==null; }
  public function getDisplayNameAttribute(){ $meta=$this->manualMetadata?json_decode($this->manualMetadata->value,true):[];return $meta['name']??($this->user?$this->user->name:'Customer'); }
  public function user(){
    return $this->belongsTo(User::class);
  }

  public function product(){
    return $this->belongsTo(Product::class);
  }
}
