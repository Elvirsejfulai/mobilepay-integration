<?php
declare(strict_types=1);

define('SANDBOX', true);

// Ključevi koje dobiješ od MobilePay/Vipps portala
define('OCP_APIM_SUBSCRIPTION_KEY', 'TVOJ_SUBSCRIPTION_KEY');
define('CLIENT_ID', 'TVOJ_CLIENT_ID');
define('CLIENT_SECRET', 'TVOJ_CLIENT_SECRET');
define('PAYMENT_POINT_ID', 'TVOJ_PAYMENT_POINT_ID');

// Valuta i URL-ovi
define('CURRENCY_ISO', 'DKK');
define('REDIRECT_URI', 'https://tvoj-site.ba/mobilepay/redirect.php');
define('WEBHOOK_URL', 'https://tvoj-site.ba/mobilepay/webhook.php');
define('WEBHOOK_SIGNATURE_KEY', 'TVOJ_WEBHOOK_SIGNATURE_KEY');

// Sandbox ili produkcija
if (SANDBOX) {
    define('VIPPS_TOKEN_URL', 'https://apitest.vipps.no/accesstoken/get');
    define('MOBILEPAY_API_BASE', 'https://api.sandbox.mobilepay.dk');
} else {
    define('VIPPS_TOKEN_URL', 'https://api.vipps.no/accesstoken/get');
    define('MOBILEPAY_API_BASE', 'https://api.mobilepay.dk');
}

// Generiši UUID (koristi se za reference, idempotency, correlation)
function uuidv4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// Dohvati access token sa Vipps API
function getAccessToken(): string {
    $ch = curl_init(VIPPS_TOKEN_URL);
    $headers = [
        'client_id: ' . CLIENT_ID,
        'client_secret: ' . CLIENT_SECRET,
        'Ocp-Apim-Subscription-Key: ' . OCP_APIM_SUBSCRIPTION_KEY,
        'Content-Length: 0'
    ];
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode($raw, true);
    if ($http !== 200 || empty($json['access_token'])) die("Token error: $raw");
    return $json['access_token'];
}

// Headeri za MobilePay API pozive
function mobilepayHeaders(string $bearer, ?string $corr=null): array {
    $h = [
        'Authorization: Bearer ' . $bearer,
        'Ocp-Apim-Subscription-Key: ' . OCP_APIM_SUBSCRIPTION_KEY,
        'Content-Type: application/json',
        'Accept: application/json',
    ];
    if ($corr) $h[] = 'CorrelationId: ' . $corr;
    return $h;
}

// Helper za POST
function httpPostJson(string $url, array $headers, array $body): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => json_encode($body),
    ]);
    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status'=>$http,'json'=>json_decode($raw,true),'raw'=>$raw];
}

// Helper za GET
function httpGet(string $url, array $headers): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    $raw = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['status'=>$http,'json'=>json_decode($raw,true),'raw'=>$raw];
}

// Spremi ili update order status u JSON fajl (za demo/test)
function updateOrderStatus(string $paymentId, string $state, ?array $extra=null): void {
    $file = __DIR__ . '/orders.json';
    $data = file_exists($file)? json_decode(file_get_contents($file), true): [];
    if (!isset($data[$paymentId])) $data[$paymentId] = [];
    $data[$paymentId]['state'] = $state;
    if ($extra) $data[$paymentId] = array_merge($data[$paymentId], $extra);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Validacija webhook potpisa (MobilePay šalje x-mobilepay-signature)
function validateWebhookSignature(string $raw, array $headers): bool {
    $sigHeader = null;
    foreach ($headers as $k=>$v) {
        if (strtolower($k)==='x-mobilepay-signature') {
            $sigHeader = is_array($v)? $v[0]: $v; break;
        }
    }
    if (!$sigHeader) return false;
    $bodyNoWs = preg_replace('/\s+/', '', $raw);
    $key = WEBHOOK_SIGNATURE_KEY;
    $maybeDecoded = base64_decode($key, true);
    $keyBytes = $maybeDecoded!==false && $maybeDecoded!==''? $maybeDecoded: $key;
    $hash = hash_hmac('sha1', WEBHOOK_URL.$bodyNoWs, $keyBytes, true);
    $calc = base64_encode($hash);
    return hash_equals($sigHeader,$calc);
}
