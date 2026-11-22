<?php
namespace App\Services;
use App\Models\{User,Order};
use Illuminate\Support\Facades\{DB,Hash,Schema};

class NativeAccountDeletion
{
    public static function delete(User $user,string $password)
    {
        abort_unless($user->user_type==='customer',403,'Administrator accounts cannot be deleted from the customer app.');
        abort_unless(Hash::check($password,$user->password),422,'Your current password is incorrect.');
        DB::transaction(function()use($user){
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $orders=Order::where('user_id',$user->id)->lockForUpdate()->get();
            abort_if($orders->contains(fn($o)=>!in_array($o->delivery_status,['delivered','cancelled'],true)),409,'Please complete or resolve your active orders before deleting your account. Contact Foot Step support for help.');
            $anonymous=json_encode(['name'=>'Deleted customer','email'=>'','phone'=>'','address'=>'','city'=>'','country'=>'Bangladesh']);
            $combined=$orders->pluck('combined_order_id')->filter()->unique()->all();
            Order::where('user_id',$user->id)->update(['user_id'=>null,'shipping_address'=>$anonymous,'updated_at'=>now()]);
            if($combined)DB::table('combined_orders')->whereIn('id',$combined)->where('user_id',$user->id)->update(['user_id'=>0,'shipping_address'=>$anonymous,'updated_at'=>now()]);
            foreach(['addresses','carts','wishlists','customers','reviews'] as $table)if(Schema::hasTable($table))DB::table($table)->where('user_id',$user->id)->delete();
            if(Schema::hasTable('uploads'))DB::table('uploads')->where('user_id',$user->id)->update(['user_id'=>null]);
            if(Schema::hasTable('password_resets')&&$user->email)DB::table('password_resets')->where('email',$user->email)->delete();
            $user->notifications()->delete();$user->tokens()->delete();
            DB::table('footstep_mobile_tokens')->where('user_id',$user->id)->delete();
            DB::table('footstep_mobile_checkouts')->where('user_id',$user->id)->delete();
            $user->delete();
        });
    }
}
