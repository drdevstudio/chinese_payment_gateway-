<?php
require_once __DIR__ . '/../config.php';
setCorsHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'POST required'],405);

$txn_id = trim($_POST['txn_id'] ?? '');
$utr    = trim($_POST['utr']    ?? '');

if (!$txn_id || !$utr) jsonResponse(['success'=>false,'message'=>'txn_id and utr required']);
if (!preg_match('/^\d{12}$/', $utr)) jsonResponse(['success'=>false,'message'=>'UTR must be exactly 12 digits']);

// Get transaction
$txn = fbGet("transactions/$txn_id");
if (!is_array($txn) || ($txn['status'] ?? '') !== 'pending') {
    jsonResponse(['success'=>false,'message'=>'Transaction not found or already processed']);
}

// Check SMS log for this UTR (unclaimed)
$smsLog = fbGet("sms_logs/$utr");

if (is_array($smsLog) && ($smsLog['txn_id'] ?? null) === null) {
    // Verify amount matches
    if (abs(floatval($smsLog['amount']) - floatval($txn['amount'])) > 0.01) {
        jsonResponse(['success'=>false,'message'=>"Amount mismatch. Expected ₹{$txn['amount']}, found ₹{$smsLog['amount']}"]);
    }

    $comm   = getCommissions();
    $payIn  = floatval($comm['pay_in']);
    $credit = floatval($txn['amount']) * (1 - $payIn / 100);
    $mid    = $txn['merchant_id'];
    $devId  = $txn['device_id'];

    // Get current merchant balance
    $merch      = fbGet("merchants/$mid");
    $newBalance = round(floatval($merch['balance'] ?? 0) + $credit, 2);

    // Get UPI today_received
    $upiRec   = fbGet("upi_ids/$devId");
    $todayRcv = floatval($upiRec['today_received'] ?? 0) + floatval($txn['amount']);

    fbMultiUpdate([
        "transactions/$txn_id/status"     => 'success',
        "transactions/$txn_id/utr"        => $utr,
        "transactions/$txn_id/claimed"    => 1,
        "transactions/$txn_id/claimed_at" => date('Y-m-d H:i:s'),
        "transactions/$txn_id/sender_id"  => $smsLog['sender_id'] ?? null,
        "transactions/$txn_id/raw_message"=> $smsLog['raw_message'] ?? null,
        "merchants/$mid/balance"          => $newBalance,
        "upi_ids/$devId/today_received"   => $todayRcv,
        "sms_logs/$utr/txn_id"            => $txn_id,
    ]);

    jsonResponse(['success'=>true,'message'=>'Payment verified successfully','txn_id'=>$txn_id,'credited'=>$credit]);

} else {
    // Check if UTR exists at all (already claimed)
    if (is_array($smsLog)) {
        jsonResponse(['success'=>false,'message'=>'This UTR has already been claimed']);
    }
    jsonResponse(['success'=>false,'message'=>'UTR not found in our records. Please wait for SMS confirmation or contact support.']);
}
