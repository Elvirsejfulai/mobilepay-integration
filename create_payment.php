<?php
declare(strict_types=1);
require __DIR__.'/config.php';

// Konvertuj DKK → øre
$amountDkk = isset($_POST['amount'])? (float)$_POST['amount']: 12.50;
$amountMinor = (int)round($amountDkk*100);

$token = getAccessToken();
$correlationId = uuidv4();
$reference = 'ORDER-'.uuidv4();

// Tijelo zahtjeva za kreiranje uplate
$payload = [
    'amount'=>$amountMinor,
    'paymentPointId'=>PAYMENT_POINT_ID,
    'reference'=>$reference,
    'idempotencyKey'=>uuidv4(),
    'redirectUri'=>REDIRECT_URI,
];

// Pozovi MobilePay
$res = httpPostJson(MOBILEPAY_API_BASE.'/v1/payments', mobilepayHeaders($token,$correlationId), $payload);

if ($res['status']===200 && !empty($res['json']['mobilePayAppRedirectUri'])) {
    if (!empty($res['json']['paymentId'])) {
        updateOrderStatus($res['json']['paymentId'],'initiated',['reference'=>$reference,'amount'=>$amountMinor]);
    }
    header('Location: '.$res['json']['mobilePayAppRedirectUri']);
    exit;
}
http_response_code(500); echo $res['raw'];
