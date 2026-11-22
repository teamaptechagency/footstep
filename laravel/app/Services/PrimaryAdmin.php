<?php
namespace App\Services;
use App\Models\User;
use App\Models\BusinessSetting;
class PrimaryAdmin {
    const KEY='primary_admin_user_id';
    public static function id(){
        $id=BusinessSetting::where('type',self::KEY)->value('value');
        if($id!==null)return (int)$id;
        $id=User::where('user_type','admin')->orderBy('id')->value('id');
        if(!$id)throw new \RuntimeException('Primary administrator is missing.');
        $row=new BusinessSetting();$row->type=self::KEY;$row->value=(string)$id;$row->save();return (int)$row->value;
    }
    public static function protect($user){
        if((int)$user->id===self::id())throw \Illuminate\Validation\ValidationException::withMessages(['admin'=>'Primary admin is protected and cannot be edited or deleted. / প্রাইমারি অ্যাডমিন পরিবর্তন বা মুছে ফেলা যাবে না।']);
    }
}