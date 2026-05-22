<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/auth.php';
setCorsHeaders();
adminAuth();

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    resetDailyLimitsIfNeeded();
    $all  = fbGet('upi_ids') ?? [];
    $rows = [];
    foreach ($all as $devId => $u) {
        $remaining = floatval($u['daily_limit'] ?? 0) - floatval($u['today_received'] ?? 0);
        $u['remaining']  = max(0, $remaining);
        $u['device_id']  = $devId; // ensure device_id field populated
        $rows[] = $u;
    }
    usort($rows, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($action === 'add') {
    verifyCsrf();
    $upi  = trim($_POST['upi_address'] ?? '');
    $dev  = trim($_POST['device_id']   ?? '');
    $name = trim($_POST['holder_name'] ?? '');
    $lim  = round(floatval($_POST['daily_limit'] ?? 100000), 2);

    if (!$upi || !$dev || !$name) jsonResponse(['success'=>false,'message'=>'All fields required']);
    if (!preg_match('/^[\w.\-]+@[\w]+$/', $upi)) jsonResponse(['success'=>false,'message'=>'Invalid UPI format']);
    if ($lim < 100) jsonResponse(['success'=>false,'message'=>'Daily limit must be >= 100']);

    // Check uniqueness of device_id (it's the Firebase key) and upi_address
    $existing = fbGet("upi_ids/$dev");
    if ($existing !== null) jsonResponse(['success'=>false,'message'=>'Device ID already exists']);

    // Check upi_address uniqueness
    $allUpi = fbGet('upi_ids') ?? [];
    foreach ($allUpi as $u) {
        if (($u['upi_address'] ?? '') === $upi) {
            jsonResponse(['success'=>false,'message'=>'UPI address already exists']);
        }
    }

    fbPut("upi_ids/$dev", [
        'device_id'      => $dev,
        'upi_address'    => $upi,
        'holder_name'    => $name,
        'daily_limit'    => $lim,
        'today_received' => 0,
        'last_reset'     => date('Y-m-d'),
        'status'         => 'active',
        'created_at'     => date('Y-m-d H:i:s'),
    ]);
    jsonResponse(['success' => true, 'message' => 'UPI added']);
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    verifyCsrf();
    $dev  = trim($_POST['device_id']   ?? '');
    $upi  = trim($_POST['upi_address'] ?? '');
    $name = trim($_POST['holder_name'] ?? '');
    $lim  = round(floatval($_POST['daily_limit'] ?? 100000), 2);
    $stat = $_POST['status'] ?? 'active';

    if (!in_array($stat, ['active','inactive'], true)) jsonResponse(['success'=>false,'message'=>'Invalid status']);

    // Check upi_address uniqueness (excluding current device)
    $allUpi = fbGet('upi_ids') ?? [];
    foreach ($allUpi as $dId => $u) {
        if ($dId !== $dev && ($u['upi_address'] ?? '') === $upi) {
            jsonResponse(['success'=>false,'message'=>'UPI address already used by another record']);
        }
    }

    fbPatch("upi_ids/$dev", [
        'upi_address' => $upi,
        'holder_name' => $name,
        'daily_limit' => $lim,
        'status'      => $stat,
    ]);
    jsonResponse(['success' => true, 'message' => 'Updated']);
}

// ── DELETE ────────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    verifyCsrf();
    $dev = trim($_POST['device_id'] ?? '');
    fbDelete("upi_ids/$dev");
    jsonResponse(['success' => true, 'message' => 'Deleted']);
}

// ── TRANSACTIONS for a UPI ────────────────────────────────────────────────────
if ($action === 'transactions') {
    $dev  = trim($_GET['device_id'] ?? '');
    $all  = fbQuery('transactions', 'device_id', $dev);
    $allM = fbGet('merchants') ?? [];

    $rows = array_values($all);
    usort($rows, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    $rows = array_slice($rows, 0, 300);

    foreach ($rows as &$t) {
        $mid = $t['merchant_id'] ?? '';
        $t['merchant_name'] = $allM[$mid]['name'] ?? '—';
    }
    unset($t);
    jsonResponse(['success' => true, 'data' => $rows]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action']);
