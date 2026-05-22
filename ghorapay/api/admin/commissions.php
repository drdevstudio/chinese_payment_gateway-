<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/auth.php';
setCorsHeaders();
adminAuth();

$action = $_POST['action'] ?? ($_GET['action'] ?? 'get');

if ($action === 'get') {
    $comm = getCommissions();
    $set  = getSettings();
    jsonResponse([
        'success'    => true,
        'pay_in'     => $comm['pay_in'],
        'pay_out'    => $comm['pay_out'],
        'min_amount' => $set['min_amount'],
        'max_amount' => $set['max_amount'],
    ]);
}

if ($action === 'update') {
    verifyCsrf();
    $pay_in  = round(floatval($_POST['pay_in']  ?? 0), 2);
    $pay_out = round(floatval($_POST['pay_out'] ?? 0), 2);
    $min_amt = round(floatval($_POST['min_amount'] ?? 1), 2);
    $max_amt = round(floatval($_POST['max_amount'] ?? 100000), 2);

    if ($pay_in < 0 || $pay_in > 100)    jsonResponse(['success'=>false,'message'=>'Pay-in must be 0-100%']);
    if ($pay_out < 0 || $pay_out > 100)  jsonResponse(['success'=>false,'message'=>'Pay-out must be 0-100%']);
    if ($min_amt < 1)                    jsonResponse(['success'=>false,'message'=>'Min amount must be >= 1']);
    if ($max_amt <= $min_amt)            jsonResponse(['success'=>false,'message'=>'Max amount must be greater than min']);
    if ($max_amt > 1000000)              jsonResponse(['success'=>false,'message'=>'Max amount too large']);

    fbPut('commissions', ['pay_in' => $pay_in, 'pay_out' => $pay_out, 'updated_at' => date('Y-m-d H:i:s')]);
    fbPut('settings',    ['min_amount' => $min_amt, 'max_amount' => $max_amt, 'updated_at' => date('Y-m-d H:i:s')]);
    jsonResponse(['success' => true, 'message' => 'Settings updated']);
}

jsonResponse(['success' => false, 'message' => 'Unknown action']);
