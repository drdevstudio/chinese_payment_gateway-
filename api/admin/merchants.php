<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/auth.php';
setCorsHeaders();
adminAuth();

$action = $_POST['action'] ?? ($_GET['action'] ?? '');

// ── LIST ──────────────────────────────────────────────────────────────────────
if ($action === 'list') {
    $q = strtolower(trim($_GET['q'] ?? ''));
    $all = fbGet('merchants') ?? [];
    $rows = [];
    foreach ($all as $mid => $m) {
        if (($m['status'] ?? '') === 'deleted') continue;
        if ($q && strpos(strtolower($m['name'] ?? ''), $q) === false
               && strpos(strtolower($mid), $q) === false) continue;
        $rows[] = [
            'merchant_id' => $mid,
            'name'        => $m['name'] ?? '',
            'domain'      => $m['domain'] ?? '',
            'balance'     => $m['balance'] ?? 0,
            'status'      => $m['status'] ?? 'live',
            'first_login' => $m['first_login'] ?? 0,
            'totp_enabled'=> $m['totp_enabled'] ?? 0,
            'created_at'  => $m['created_at'] ?? '',
        ];
    }
    usort($rows, fn($a,$b) => strcmp($b['created_at'], $a['created_at']));
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ── ADD ───────────────────────────────────────────────────────────────────────
if ($action === 'add') {
    verifyCsrf();
    $name   = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    if (strlen($name) < 2) jsonResponse(['success' => false, 'message' => 'Name required']);

    $mid  = generateMerchantId();
    $hash = password_hash('12345', PASSWORD_BCRYPT, ['cost' => 12]);
    fbPut("merchants/$mid", [
        'merchant_id'     => $mid,
        'name'            => $name,
        'password'        => $hash,
        'domain'          => $domain,
        'api_key'         => bin2hex(random_bytes(32)),
'payout_api_key'  => bin2hex(random_bytes(32)),
        'totp_secret'     => null,
        'totp_enabled'    => 0,
        'withdraw_password' => null,
        'balance'         => 0.00,
        'status'          => 'live',
        'first_login'     => 1,
        'created_at'      => date('Y-m-d H:i:s'),
    ]);
    jsonResponse(['success' => true, 'merchant_id' => $mid, 'default_password' => '12345']);
}

// ── UPDATE ────────────────────────────────────────────────────────────────────
if ($action === 'update') {
    verifyCsrf();
    $mid    = trim($_POST['merchant_id'] ?? '');
    $name   = trim($_POST['name'] ?? '');
    $domain = trim($_POST['domain'] ?? '');
    $status = $_POST['status'] ?? '';
    if (!in_array($status, ['live','suspended','deleted'], true)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status']);
    }
    fbPatch("merchants/$mid", ['name' => $name, 'domain' => $domain, 'status' => $status]);
    jsonResponse(['success' => true, 'message' => 'Updated']);
}

// ── RESET PASSWORD ────────────────────────────────────────────────────────────
if ($action === 'reset_password') {
    verifyCsrf();
    $mid = trim($_POST['merchant_id'] ?? '');
    fbPatch("merchants/$mid", [
        'password'         => password_hash('12345', PASSWORD_BCRYPT, ['cost' => 12]),
        'first_login'      => 1,
        'totp_secret'      => null,
        'totp_enabled'     => 0,
        'withdraw_password'=> null,
    ]);
    jsonResponse(['success' => true, 'message' => 'Reset to default password 12345, 2FA cleared']);
}

// ── TRANSACTIONS ──────────────────────────────────────────────────────────────
if ($action === 'transactions') {
    $mid  = trim($_GET['merchant_id'] ?? '');
    $all  = fbQuery('transactions', 'merchant_id', $mid);
    $upiMap = fbGet('upi_ids') ?? [];

    $rows = array_values($all);
    usort($rows, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
    $rows = array_slice($rows, 0, 300);

    foreach ($rows as &$t) {
        $devId = $t['device_id'] ?? '';
        $t['upi_address'] = $upiMap[$devId]['upi_address'] ?? ($t['upi_address'] ?? '—');
    }
    unset($t);
    jsonResponse(['success' => true, 'data' => $rows]);
}

jsonResponse(['success' => false, 'message' => 'Unknown action']);
