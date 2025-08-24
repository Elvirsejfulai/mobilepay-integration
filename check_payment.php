<?php
declare(strict_types=1);
require __DIR__.'/config.php';

// Ručno provjeri status uplate
$pid=$_GET['paymentId']??''; 
if(!$pid){die('Dodaj paymentId u URL-u');}

$token=getAccessToken();
$res=httpGet(MOBILEPAY_API_BASE.'/v1/payments/'.urlencode($pid), mobilepayHeaders($token,uuidv4()));
header('Content-Type: application/json'); 
echo json_encode($res,JSON_PRETTY_PRINT);
