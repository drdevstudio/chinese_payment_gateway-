<?php
require_once __DIR__ . '/../config.php';
setCorsHeaders();

$txn_id = trim($_GET['txn_id'] ?? '');
if (!$txn_id) jsonResponse(['success'=>false,'message'=>'txn_id required']);

$txn = fbGet("transactions/$txn_id");
if (!is_array($txn)) jsonResponse(['success'=>false,'message'=>'Transaction not found']);

jsonResponse([
    'success'    => true,
    'txn_id'     => $txn['txn_id'],
    'status'     => $txn['status'],
    'amount'     => $txn['amount'],
    'utr'        => $txn['utr'],
    'claimed_at' => $txn['claimed_at'],
]);
