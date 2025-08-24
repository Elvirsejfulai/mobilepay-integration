<?php
declare(strict_types=1);
require __DIR__.'/config.php';

$raw = file_get_contents('php://input') ?: '';
$headers = function_exists('getallheaders')? getallheaders():[];
if(!validateWebhookSignature($raw,$headers)){http_response_code(400);exit;}
$evt = json_decode($raw,true);
$type = $evt['eventType']??''; $data=$evt['data']??[]; $pid=$data['id']??null;

$token=getAccessToken();
switch($type){
  case 'payment.reserved':
    $resp=httpGet(MOBILEPAY_API_BASE.'/v1/payments/'.$pid, mobilepayHeaders($token,uuidv4()));
    $amt=(int)($resp['json']['amount']??0);
    if($amt>0){
      $cap=httpPostJson(MOBILEPAY_API_BASE.'/v1/payments/'.$pid.'/capture', mobilepayHeaders($token,uuidv4()), ['amount'=>$amt]);
      if($cap['status']===204) updateOrderStatus($pid,'captured');
      else updateOrderStatus($pid,'capture_failed',['raw'=>$cap['raw']]);
    }
    break;
  case 'payment.cancelled_by_user': updateOrderStatus($pid,'cancelled'); break;
  case 'payment.expired': updateOrderStatus($pid,'expired'); break;
  case 'transfer.succeeded': updateOrderStatus($pid,'transfer_succeeded'); break;
  default: updateOrderStatus($pid,'event:'.$type,['raw'=>$evt]); break;
}
http_response_code(200);
