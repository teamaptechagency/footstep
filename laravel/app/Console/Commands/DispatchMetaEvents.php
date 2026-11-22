<?php
namespace App\Console\Commands;

use App\Services\MetaPixel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DispatchMetaEvents extends Command
{
    protected $signature='meta:dispatch {--limit= : Override configured batch limit}';
    protected $description='Deliver encrypted, deduplicated Meta CAPI outbox events without blocking checkout';

    public function handle()
    {
        $processed=app(MetaPixel::class)->dispatch(function($payload,$token,$version){
            $body=['data'=>[$payload['event']]];
            if(!empty($payload['test_code']))$body['test_event_code']=$payload['test_code'];
            $response=Http::withToken($token)->withOptions(['allow_redirects'=>false,'connect_timeout'=>3])->timeout(8)->post('https://graph.facebook.com/'.$version.'/'.$payload['pixel'].'/events',$body);
            $json=$response->json()?:[];
            return ['status'=>$response->status(),'events_received'=>(int)($json['events_received']??0),'error_code'=>(int)($json['error']['code']??0),'transient'=>(bool)($json['error']['is_transient']??false)];
        },(int)($this->option('limit')?:MetaPixel::settings()['dispatch_limit']??10));
        $this->info('Processed '.$processed.' Meta event(s).');return 0;
    }
}
