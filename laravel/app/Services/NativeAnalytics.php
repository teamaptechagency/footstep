<?php
namespace App\Services;
class NativeAnalytics {
 public static function resource($key,$period){
  $r=StoreAnalytics::report($period,true);$w=$r['window'];$rows=[];$add=function($title,$detail)use(&$rows){$rows[]=['title'=>$title,'detail'=>(string)$detail];};
  if($key==='analytics.outcomes'){$o=OrderOutcomeReport::fromStatuses($r['statuses']);$add('Delivered',$o['delivered']);$add('Cancelled',$o['cancelled']);$add('Other orders',$o['other']);$add('Success rate',$o['success_rate']===null?'No finalized orders':$o['success_rate'].'%');$add('Cancellation rate',$o['cancel_rate']===null?'No finalized orders':$o['cancel_rate'].'%');$add('Total orders',$o['total']);}
  else{$add('Orders',$r['orders']['count']);$add('Order value',format_price($r['orders']['value']));$add('Paid order value',format_price($r['orders']['paid']));$add('Page views',$r['traffic']['views']);$add('Unique visitors',$r['traffic']['visitors']);$add('Average active time',StoreAnalytics::duration($r['traffic']['average']));foreach($r['statuses'] as $v)$add(ucfirst($v->status).' orders',$v->total);foreach($r['top_selling'] as $v){$p=$r['product_map']->get($v->product_id);$add('Top seller · '.($p?$p->name:'Removed product #'.$v->product_id),$v->units.' units · '.format_price($v->value));}foreach($r['pages'] as $v)$add('Page · '.$v->title,$v->views.' views · '.$v->visitors.' visitors');foreach($r['sources'] as $v)$add('Source · '.AnalyticsAcquisition::label($v->referrer),$v->views.' views');}
  return ['title'=>$key==='analytics.outcomes'?'Order outcomes':'Analytics','data'=>$rows,'period'=>$w['period'],'range'=>$w['local_start']->format('d M Y').' – '.$w['local_end']->copy()->subDay()->format('d M Y'),'read_only'=>true,'message'=>$key==='analytics.outcomes'?'Rates use delivered and cancelled orders only.':($r['ready']?'Live store report · Asia/Dhaka':'Visitor tracking storage is unavailable.')];
 }
}
