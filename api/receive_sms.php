<?php
require_once __DIR__ . '/../config.php';
setCorsHeaders();
verifyApiKey();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'POST required'],405);

$device_id  = trim($_POST['device_id']  ?? '');
$amount     = floatval($_POST['amount'] ?? 0);
$utr        = trim($_POST['utr']        ?? '');
$sender_id  = trim($_POST['sender_id']  ?? '');
$txn_id_app = trim($_POST['txn_id']     ?? '');
$raw_msg    = trim($_POST['message']    ?? '');

if (!$device_id || !$utr || $amount <= 0) jsonResponse(['success'=>false,'message'=>'Missing required fields']);
if (!preg_match('/^\d{12}$/', $utr))      jsonResponse(['success'=>false,'message'=>'UTR must be 12 digits']);

// Check if UTR already processed
$existing = fbGet("sms_logs/$utr");
if ($existing !== null) jsonResponse(['success'=>true,'message'=>'UTR already logged','already'=>true]);

// Find UPI record by device_id
$upiRec = fbGet("upi_ids/$device_id");
if (!$upiRec || ($upiRec['status'] ?? '') !== 'active') {
    // Still log but no transaction match
    fbPut("sms_logs/$utr", [
        'utr'         => $utr,
        'device_id'   => $device_id,
        'amount'      => $amount,
        'sender_id'   => $sender_id,
        'txn_id'      => null,
        'raw_message' => $raw_msg,
        'received_at' => date('Y-m-d H:i:s'),
    ]);
    jsonResponse(['success'=>true,'message'=>'SMS logged, UPI device not found','claimed'=>false]);
}

// Find matching pending transaction for this device and amount
$txnMap = fbQuery('transactions', 'device_id', $device_id);
$txn = null;
foreach ($txnMap as $tid => $t) {
    if (($t['status'] ?? '') === 'pending'
        && ($t['claimed'] ?? 0) == 0
        && abs(floatval($t['amount']) - $amount) < 0.01) {
        $txn = $t;
        break;
    }
}

// Log the SMS
$logData = [
    'utr'         => $utr,
    'device_id'   => $device_id,
    'amount'      => $amount,
    'sender_id'   => $sender_id,
    'txn_id'      => $txn ? $txn['txn_id'] : null,
    'raw_message' => $raw_msg,
    'received_at' => date('Y-m-d H:i:s'),
];
fbPut("sms_logs/$utr", $logData);

if ($txn) {
    $comm   = getCommissions();
    $payIn  = floatval($comm['pay_in']);
    $credit = $amount * (1 - $payIn / 100);
    $mid    = $txn['merchant_id'];

    // Get current merchant balance
    $merch = fbGet("merchants/$mid");
    $newBalance = round(floatval($merch['balance'] ?? 0) + $credit, 2);

    // Get current today_received
    $todayRcv = floatval($upiRec['today_received'] ?? 0) + $amount;

    // Atomic multi-path update
    fbMultiUpdate([
        "transactions/{$txn['txn_id']}/status"     => 'success',
        "transactions/{$txn['txn_id']}/utr"        => $utr,
        "transactions/{$txn['txn_id']}/claimed"    => 1,
        "transactions/{$txn['txn_id']}/claimed_at" => date('Y-m-d H:i:s'),
        "transactions/{$txn['txn_id']}/sender_id"  => $sender_id,
        "transactions/{$txn['txn_id']}/raw_message"=> $raw_msg,
        "merchants/$mid/balance"                   => $newBalance,
        "upi_ids/$device_id/today_received"        => $todayRcv,
        "sms_logs/$utr/txn_id"                     => $txn['txn_id'],
    ]);

    jsonResponse(['success'=>true,'message'=>'Payment auto-confirmed','txn_id'=>$txn['txn_id'],'credited'=>$credit]);
} else {
    jsonResponse(['success'=>true,'message'=>'SMS logged, no matching transaction','claimed'=>false]);
}
