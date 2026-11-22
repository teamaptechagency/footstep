<?php
namespace App\Services;

/** Reduce exceptions to a safe category; never expose URLs, headers, keys or raw messages. */
class CourierNetworkFailure
{
    public static function classify(\Throwable $error)
    {
        $number=0;$message='';$cursor=$error;
        for($i=0;$cursor&&$i<6;$i++,$cursor=$cursor->getPrevious()){
            $message.=' '.strtolower($cursor->getMessage());
            if(method_exists($cursor,'getHandlerContext')){try{$context=$cursor->getHandlerContext();$number=(int)($context['errno']??$number);}catch(\Throwable $ignored){}}
        }
        if(!$number&&preg_match('/curl error\s+([0-9]+)/',$message,$m))$number=(int)$m[1];
        $types=[5=>'proxy_dns_failed',6=>'dns_failed',7=>'connection_failed',28=>'timeout',35=>'tls_handshake_failed',51=>'tls_certificate_failed',58=>'tls_certificate_failed',60=>'tls_certificate_failed',77=>'ca_bundle_unavailable',83=>'tls_certificate_failed'];
        if(isset($types[$number]))$code=$types[$number];
        elseif(strpos($message,'certificate')!==false||strpos($message,'ssl')!==false)$code='tls_certificate_failed';
        elseif(strpos($message,'resolve host')!==false||strpos($message,'getaddrinfo')!==false)$code='dns_failed';
        elseif(strpos($message,'timed out')!==false||strpos($message,'timeout')!==false)$code='timeout';
        elseif(strpos($message,'undefined method')!==false||strpos($message,'undefined function')!==false||strpos($message,'call to')!==false)$code='http_client_runtime_error';
        else $code='network_or_provider_error';
        return ['code'=>$code,'curl_errno'=>$number>=1&&$number<=100?$number:null];
    }
}
