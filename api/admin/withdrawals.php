<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/auth.php';
setCorsHeaders();
adminAuth();

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $status  = $_GET['status'] ?? 'pending';
    $allowed = ['pending','success','failed','all'];
    if (!in_array($status, $allowed, true)) $status = 'pending';

    $allW   = fbGet('withdrawals') ?? [];
    $allM   = fbGet('merchants')   ?? [];
    $rows   = [];

    foreach ($allW as $key => $w) {
        if ($status !== 'all' && ($w['status'] ?? '') !== $status) continue;
        $mid       = $w['merchant_id'] ?? '';
        $w['mn']   = $allM[$mid]['name'] ?? '—';
        $w['id']   = $w['id'] ?? $key;
        $rows[]    = $w;
    }
    usort($rows, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    $rows = array_slice($rows, 0, 500);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ── UPDATE (approve / fail) ───────────────────────────────────────────────────
if ($action === 'update') {
    verifyCsrf();
    $wid    = trim($_POST['id'] ?? '');
    $status = $_POST['status'] ?? '';
    $note   = trim($_POST['note'] ?? '');

    if (!in_array($status, ['success','failed'], true)) {
        jsonResponse(['success'=>false,'message'=>'Invalid status']);
    }

    // Find the withdrawal by fb_key stored in 'id' field OR scan
    // We stored fb_key as the 'id' field in the withdrawal record
    $allW = fbGet('withdrawals') ?? [];
    $fbKey = null;
    $row   = null;
    foreach ($allW as $k => $w) {
        if (($w['id'] ?? $k) == $wid || $k === $wid) {
            $fbKey = $k;
            $row   = $w;
            break;
        }
    }

    if (!$row || ($row['status'] ?? '') !== 'pending') {
        jsonResponse(['success'=>false,'message'=>'Withdrawal not found or already processed']);
    }

    $updates = [
        "withdrawals/$fbKey/status"     => $status,
        "withdrawals/$fbKey/note"       => $note,
        "withdrawals/$fbKey/updated_at" => date('Y-m-d H:i:s'),
    ];

    // If failed → refund balance to merchant
    if ($status === 'failed') {
        $comm   = getCommissions();
        $fee    = round(floatval($row['amount']) * floatval($comm['pay_out']) / 100, 2);
        $refund = floatval($row['amount']) + $fee;
        $mid    = $row['merchant_id'] ?? '';
        $merch  = fbGet("merchants/$mid");
        if (is_array($merch)) {
            $updates["merchants/$mid/balance"] = round(floatval($merch['balance'] ?? 0) + $refund, 2);
        }
    }

    fbMultiUpdate($updates);
    jsonResponse(['success' => true, 'message' => 'Withdrawal ' . ucfirst($status)]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action']);
