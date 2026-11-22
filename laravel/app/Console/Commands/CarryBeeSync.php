<?php
namespace App\Console\Commands;

use App\Models\BusinessSetting;
use App\Services\{CarryBee, StoreOperations};
use Illuminate\Console\Command;

class CarryBeeSync extends Command
{
    protected $signature='carrybee:sync';
    protected $description='Refresh CarryBee parcels with bounded work and a persistent cursor';

    public function handle()
    {
        if (!CarryBee::settings()['enabled']) return 0;
        $cursor=StoreOperations::get('carrybee_sync_cursor',['type'=>'']);
        $rows=BusinessSetting::whereRaw("type LIKE 'ops!_shipment!_%' ESCAPE '!'")->where('type','>',$cursor['type'])->orderBy('type')->limit(100)->pluck('type');
        $deadline=microtime(true)+40;$sent=0;$errors=0;$last='';
        foreach ($rows as $type) {
            if ($sent>=5 || microtime(true)>$deadline) break;
            $last=$type;$id=(int)substr($type,13);$parcel=StoreOperations::shipment($id);
            if (($parcel['provider']??'')!=='carrybee' || !in_array($parcel['state']??'',['sending','uncertain','booked'],true)) continue;
            $sent++;
            try {CarryBee::sync($id);} catch (\Throwable $e) {$errors++;}
        }
        if ($last) StoreOperations::put('carrybee_sync_cursor',['type'=>$last]);
        elseif ($rows->isEmpty()) StoreOperations::put('carrybee_sync_cursor',['type'=>'']);
        StoreOperations::put('carrybee_sync_health',['at'=>gmdate('c'),'checked'=>$sent,'failed'=>$errors]);
        $this->info('CarryBee checked '.$sent.', failed '.$errors.'.');
        return 0;
    }
}
