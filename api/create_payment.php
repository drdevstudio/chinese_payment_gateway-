<?php
/**
 * Ghora Pay — Create Payment API (Firebase REST)
 *
 * AUTHENTICATION:
 *   signature = md5(merchant_id + amount + merchant_order_no + api_key + redirect_url)
 *
 * POST params:
 *   merchant_id, amount, signature, merchant_order_no (optional), redirect_url (optional)
 */
require_once __DIR__.'/../config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success'=>false,'message'=>'POST required'], 405);
}

// Rate limit per IP
$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown')[0];
checkLoginRate('create_payment_'.$ip, 30, 60);

// Read POST / JSON
$merchant_id       = trim($_POST['merchant_id']       ?? '');
$amount            = round(floatval($_POST['amount']   ?? 0), 2);
$signature         = trim($_POST['signature']          ?? '');
$merchant_order_no = trim($_POST['merchant_order_no']  ?? '');
$redirect_url      = trim($_POST['redirect_url']       ?? '');

if (!$merchant_id) {
    $json = json_decode(file_get_contents('php://input'), true);
    if ($json) {
        $merchant_id       = trim($json['merchant_id']       ?? '');
        $amount            = round(floatval($json['amount']  ?? 0), 2);
        $signature         = trim($json['signature']         ?? '');
        $merchant_order_no = trim($json['merchant_order_no'] ?? '');
        $redirect_url      = trim($json['redirect_url']      ?? '');
    }
}

if (!$merchant_id) jsonResponse(['success'=>false,'message'=>'merchant_id required'], 400);
if ($amount <= 0)  jsonResponse(['success'=>false,'message'=>'Invalid amount'], 400);
if (!$signature)   jsonResponse(['success'=>false,'message'=>'signature required'], 400);

// Fetch merchant from Firebase
$merchant = fbGet("merchants/$merchant_id");
$isLive   = is_array($merchant) && ($merchant['status'] ?? '') === 'live';

// Generate order no if missing (before signature check)
if (!$merchant_order_no) {
    $merchant_order_no = 'ORD_' . strtoupper(bin2hex(random_bytes(8)));
}

$formatted_amount = number_format($amount, 2, '.', '');
$stored_key       = $isLive ? ($merchant['api_key'] ?? '') : str_repeat('0', 64);
$expected_sig     = md5($merchant_id . $formatted_amount . $merchant_order_no . $stored_key . $redirect_url);

if (!$isLive || !hash_equals($expected_sig, strtolower($signature))) {
    jsonResponse(['success'=>false,'message'=>'Invalid signature or credentials'], 401);
}

// Domain whitelist
if (!empty($merchant['domain'])) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    if (!empty($origin)) {
        $allowedHost = strtolower(parse_url(trim($merchant['domain']), PHP_URL_HOST) ?: $merchant['domain']);
        $requestHost = strtolower(parse_url($origin, PHP_URL_HOST) ?: $origin);
        if ($requestHost && $allowedHost && $requestHost !== $allowedHost) {
            jsonResponse(['success'=>false,'message'=>'Origin domain not whitelisted','domain_error'=>true], 403);
        }
    }
}

// Amount limits
$settings = getSettings();
if ($amount < floatval($settings['min_amount']) || $amount > floatval($settings['max_amount'])) {
    jsonResponse(['success'=>false,'message'=>"Amount must be between ₹{$settings['min_amount']} and ₹{$settings['max_amount']}"], 400);
}

// Find available UPI — fetch all active, reset daily limits, pick one with capacity
resetDailyLimitsIfNeeded();
$allUpi = fbGet('upi_ids') ?? [];
$available = [];
foreach ($allUpi as $devId => $u) {
    if (($u['status'] ?? '') !== 'active') continue;
    $remaining = floatval($u['daily_limit'] ?? 0) - floatval($u['today_received'] ?? 0);
    if ($remaining >= $amount) {
        $available[] = ['device_id' => $devId, 'upi' => $u];
    }
}

if (empty($available)) {
    jsonResponse(['success'=>false,'message'=>'No UPI available right now. Please try again shortly.'], 503);
}

// Random pick
$pick   = $available[array_rand($available)];
$devId  = $pick['device_id'];
$upiRec = $pick['upi'];

// Create transaction
$txn_id = generateId(16);
fbPut("transactions/$txn_id", [
    'txn_id'           => $txn_id,
    'merchant_order_no'=> $merchant_order_no,
    'utr'              => null,
    'amount'           => $amount,
    'merchant_id'      => $merchant_id,
    'device_id'        => $devId,
    'upi_address'      => $upiRec['upi_address'] ?? '',
    'holder_name'      => $upiRec['holder_name']  ?? '',
    'sender_id'        => null,
    'status'           => 'pending',
    'claimed'          => 0,
    'claimed_at'       => null,
    'raw_message'      => null,
    'redirect_url'     => $redirect_url,
    'created_at'       => date('Y-m-d H:i:s'),
]);

clearLoginRate('create_payment_'.$ip);

jsonResponse([
    'success'           => true,
    'txn_id'            => $txn_id,
    'merchant_order_no' => $merchant_order_no,
    'amount'            => $amount,
    'upi'               => $upiRec['upi_address'] ?? '',
    'holder'            => $upiRec['holder_name']  ?? '',
    'pay_url'           => SITE_URL.'/pay.php?txn='.$txn_id,
]);
