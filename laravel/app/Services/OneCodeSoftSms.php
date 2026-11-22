<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

/** Fixed official API origin; no secrets or customer payloads in diagnostics. */
class OneCodeSoftSms
{
    const BASE = 'https://sms.ocs-api.top/api/v3';

    private static function client($key)
    {
        return Http::withToken($key)->acceptJson()->asJson()
            ->withOptions(['connect_timeout'=>4,'allow_redirects'=>false])->timeout(15);
    }

    public static function send($key, $phone, $message, $sender, $sandbox=false, $masking=true)
    {
        if (!$key) return ['state'=>'failed','notice'=>'api_key_missing'];
        if ($masking && (!$sender || preg_match('/^[+0-9]+$/D',$sender))) {
            return ['state'=>'failed','notice'=>'approved_masking_sender_required'];
        }
        $body=['number'=>$phone,'message'=>$message];
        if ($masking) $body['senderid']=$sender;
        try {
            $r=self::client($key)->post(self::BASE.($sandbox?'/sandbox':'').'/send-sms',$body);
            $d=$r->json();
            if ($r->status()===401) return ['state'=>'failed','notice'=>'provider_auth_rejected'];
            if ($r->successful() && ($d['status']??null)==='error') {
                return ['state'=>'failed','notice'=>'provider_rejected_check_balance_sender_or_portal'];
            }
            $items=$d['data']['results']??[];
            if (!$r->successful() || ($d['status']??null)!=='success' || count($items)!==1 || ($items[0]['to']??null)!==$phone) {
                return ['state'=>'uncertain','notice'=>'ambiguous_provider_response_check_portal'];
            }
            $item=$items[0];
            if ($sandbox) return ($item['would_be']??'')==='sent'
                ? ['state'=>'mocked','notice'=>'sandbox_passed_no_sms_sent']
                : ['state'=>'failed','notice'=>'sandbox_blocked_or_rejected'];
            if (($item['status']??'')==='sent' && preg_match('/^[A-Za-z0-9_-]{1,80}$/D',(string)($item['message_id']??''))) {
                return ['state'=>'accepted','notice'=>'provider_accepted_not_delivery_confirmation','provider_id'=>$item['message_id']];
            }
            if (in_array($item['status']??'',['failed','blocked','spam'],true)) {
                return ['state'=>'failed','notice'=>'provider_'. $item['status']];
            }
            return ['state'=>'uncertain','notice'=>'ambiguous_provider_response_check_portal'];
        } catch (\Throwable $e) {
            return ['state'=>'uncertain','notice'=>'network_error_check_portal_before_resending'];
        }
    }

    public static function balance($key)
    {
        if (!$key) return ['ok'=>false,'code'=>'api_key_missing'];
        try {
            $r=self::client($key)->get(self::BASE.'/balance');$d=$r->json();
            return $r->successful() && ($d['status']??null)==='success' && is_numeric($d['data']['balance']??null)
                ? ['ok'=>true,'balance'=>(string)$d['data']['balance']]
                : ['ok'=>false,'code'=>'provider_auth_or_connection_rejected'];
        } catch (\Throwable $e) { return ['ok'=>false,'code'=>'network_error']; }
    }

    public static function status($key, $reference)
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,80}$/D',$reference)) return 'unknown';
        try {
            $r=self::client($key)->get(self::BASE.'/status',['message_id'=>$reference]);$d=$r->json();
            if ($r->successful() && ($d['status']??null)==='success') {
                $state=strtolower((string)($d['data']['Data']['Status']??''));
                return in_array($state,['delivered','failed','undelivered','pending','sent'],true)?$state:'unknown';
            }
        } catch (\Throwable $e) {}
        return 'unknown';
    }
}
