<?php
require_once __DIR__.'/../../config.php';
require_once __DIR__.'/../../includes/totp.php';
setCorsHeaders();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(['success'=>false,'message'=>'POST required'],405);

$action = trim($_POST['action'] ?? '');

// ── LOGIN ─────────────────────────────────────────────────────────────────────
if ($action === 'login') {
    checkLoginRate('merchant_login');
    $id   = trim($_POST['merchant_id'] ?? '');
    $pass = $_POST['password'] ?? '';
    if (!$id || !$pass) jsonResponse(['success'=>false,'message'=>'Fields required']);

    $m = fbGet("merchants/$id");
    $valid = is_array($m) && ($m['status'] ?? '') !== 'deleted' && password_verify($pass, $m['password'] ?? '');
    if (!$valid) jsonResponse(['success'=>false,'message'=>'Invalid credentials']);
    if (($m['status'] ?? '') === 'suspended') jsonResponse(['success'=>false,'message'=>'Account suspended. Contact support.']);

    clearLoginRate('merchant_login');

    if ((int)($m['first_login'] ?? 0) === 1) {
        $_SESSION['setup_merchant'] = $id;
        jsonResponse(['success'=>true,'first_login'=>true,'merchant_id'=>$id,'name'=>$m['name']]);
    }
    if ((int)($m['totp_enabled'] ?? 0) === 1) {
        $_SESSION['totp_pending'] = $id;
        jsonResponse(['success'=>true,'totp_required'=>true,'merchant_id'=>$id]);
    }
    session_regenerate_id(true);
    $_SESSION['merchant_id'] = $id;
    jsonResponse(['success'=>true,'redirect'=>SITE_URL.'/merchant/dashboard.php']);
}

// ── SETUP 2FA ─────────────────────────────────────────────────────────────────
if ($action === 'setup_2fa') {
    $id = trim($_POST['merchant_id'] ?? '');
    if (empty($_SESSION['setup_merchant']) || $_SESSION['setup_merchant'] !== $id) {
        jsonResponse(['success'=>false,'message'=>'Session invalid']);
    }
    $m = fbGet("merchants/$id");
    if (!is_array($m) || ($m['first_login'] ?? 0) != 1) jsonResponse(['success'=>false,'message'=>'Not eligible']);

    $secret = TOTP::generateSecret(20);
    $_SESSION['pending_totp_secret'] = $secret;
    jsonResponse([
        'success' => true,
        'secret'  => $secret,
        'qr_url'  => TOTP::getQRUrl($secret, ($m['name'] ?? $id).' ('.$id.')')
    ]);
}

// ── VERIFY 2FA SETUP ──────────────────────────────────────────────────────────
if ($action === 'verify_2fa_setup') {
    $id      = trim($_POST['merchant_id'] ?? '');
    $code    = trim($_POST['code'] ?? '');
    $newpass = $_POST['new_password'] ?? '';

    if (empty($_SESSION['setup_merchant']) || $_SESSION['setup_merchant'] !== $id) {
        jsonResponse(['success'=>false,'message'=>'Session invalid']);
    }
    $secret = $_SESSION['pending_totp_secret'] ?? '';
    if (!$secret) jsonResponse(['success'=>false,'message'=>'No pending 2FA setup']);
    if (strlen($newpass) < 8) jsonResponse(['success'=>false,'message'=>'Password must be at least 8 characters']);
    if (!TOTP::verify($secret, $code)) jsonResponse(['success'=>false,'message'=>'Invalid authenticator code']);

    $hashed = password_hash($newpass, PASSWORD_BCRYPT, ['cost'=>12]);
    fbPatch("merchants/$id", [
        'password'    => $hashed,
        'totp_secret' => $secret,
        'totp_enabled'=> 1,
        'first_login' => 0,
    ]);

    unset($_SESSION['pending_totp_secret'], $_SESSION['setup_merchant']);
    session_regenerate_id(true);
    $_SESSION['merchant_id'] = $id;
    jsonResponse(['success'=>true,'redirect'=>SITE_URL.'/merchant/dashboard.php']);
}

// ── VERIFY 2FA LOGIN ──────────────────────────────────────────────────────────
if ($action === 'verify_2fa_login') {
    checkLoginRate('merchant_2fa');
    $id   = trim($_POST['merchant_id'] ?? '');
    $code = trim($_POST['code'] ?? '');
    if (empty($_SESSION['totp_pending']) || $_SESSION['totp_pending'] !== $id) {
        jsonResponse(['success'=>false,'message'=>'Session invalid']);
    }
    $m = fbGet("merchants/$id");
    if (!is_array($m) || ($m['totp_enabled'] ?? 0) != 1 || ($m['status'] ?? '') !== 'live') {
        jsonResponse(['success'=>false,'message'=>'Merchant not found']);
    }
    if (!TOTP::verify($m['totp_secret'] ?? '', $code)) {
        jsonResponse(['success'=>false,'message'=>'Invalid code']);
    }
    clearLoginRate('merchant_2fa');
    unset($_SESSION['totp_pending']);
    session_regenerate_id(true);
    $_SESSION['merchant_id'] = $id;
    jsonResponse(['success'=>true,'redirect'=>SITE_URL.'/merchant/dashboard.php']);
}

// ── CHANGE PASSWORD ───────────────────────────────────────────────────────────
if ($action === 'change_password') {
    if (empty($_SESSION['merchant_id'])) jsonResponse(['success'=>false,'message'=>'Not authenticated'],401);
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    if (strlen($new) < 8) jsonResponse(['success'=>false,'message'=>'Password min 8 characters']);
    $m = fbGet('merchants/'.$_SESSION['merchant_id']);
    if (!is_array($m) || !password_verify($old, $m['password'] ?? '')) {
        jsonResponse(['success'=>false,'message'=>'Current password incorrect']);
    }
    fbPatch('merchants/'.$_SESSION['merchant_id'], [
        'password' => password_hash($new, PASSWORD_BCRYPT, ['cost'=>12])
    ]);
    jsonResponse(['success'=>true,'message'=>'Password updated successfully']);
}

// ── SET WITHDRAWAL PASSWORD ───────────────────────────────────────────────────
if ($action === 'set_withdraw_password') {
    if (empty($_SESSION['merchant_id'])) jsonResponse(['success'=>false,'message'=>'Not authenticated'],401);
    $pass = $_POST['withdraw_password'] ?? '';
    $code = trim($_POST['totp_code'] ?? '');
    if (strlen($pass) < 8) jsonResponse(['success'=>false,'message'=>'Password min 8 characters']);
    $m = fbGet('merchants/'.$_SESSION['merchant_id']);
    if (!TOTP::verify($m['totp_secret'] ?? '', $code)) jsonResponse(['success'=>false,'message'=>'Invalid TOTP code']);
    fbPatch('merchants/'.$_SESSION['merchant_id'], [
        'withdraw_password' => password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12])
    ]);
    jsonResponse(['success'=>true,'message'=>'Withdrawal password set']);
}

// ── WITHDRAW ──────────────────────────────────────────────────────────────────
if ($action === 'withdraw') {
    if (empty($_SESSION['merchant_id'])) jsonResponse(['success'=>false,'message'=>'Not authenticated'],401);
    checkLoginRate('withdraw_'.$_SESSION['merchant_id'], 3, 60);

    $amount   = round(floatval($_POST['amount'] ?? 0), 2);
    $upi_addr = trim($_POST['upi_address'] ?? '');
    $wpass    = $_POST['withdraw_password'] ?? '';
    $code     = trim($_POST['totp_code'] ?? '');

    if ($amount < 100) jsonResponse(['success'=>false,'message'=>'Minimum withdrawal ₹100']);
    if (!$upi_addr || !preg_match('/^[\w.\-]+@[\w]+$/', $upi_addr)) {
        jsonResponse(['success'=>false,'message'=>'Invalid UPI address']);
    }

    $mid = $_SESSION['merchant_id'];
    $m   = fbGet("merchants/$mid");
    if (!is_array($m) || ($m['status'] ?? '') !== 'live') jsonResponse(['success'=>false,'message'=>'Merchant not found']);
    if (!$m['withdraw_password']) jsonResponse(['success'=>false,'message'=>'Set a withdrawal password first in Profile']);
    if (!password_verify($wpass, $m['withdraw_password'])) jsonResponse(['success'=>false,'message'=>'Invalid withdrawal password']);
    if (!TOTP::verify($m['totp_secret'] ?? '', $code)) jsonResponse(['success'=>false,'message'=>'Invalid TOTP code']);

    $comm   = getCommissions();
    $payOut = floatval($comm['pay_out']);
    $fee    = round($amount * $payOut / 100, 2);
    $deduct = $amount + $fee;
    $curBal = floatval($m['balance'] ?? 0);

    if ($curBal < $deduct) {
        jsonResponse(['success'=>false,'message'=>"Insufficient balance. Need ₹{$deduct} (incl. {$payOut}% fee)"]);
    }

    $newBal = round($curBal - $deduct, 2);

    // Create withdrawal record with auto-generated key
    $withdrawalData = [
        'merchant_id'  => $mid,
        'amount'       => $amount,
        'upi_address'  => $upi_addr,
        'status'       => 'pending',
        'note'         => null,
        'created_at'   => date('Y-m-d H:i:s'),
        'updated_at'   => date('Y-m-d H:i:s'),
    ];
    $fbKey = fbPost('withdrawals', $withdrawalData);

    // Store fb_key as the 'id' field for easy lookup later
    $updates = [
        "merchants/$mid/balance" => $newBal,
    ];
    if ($fbKey) {
        $updates["withdrawals/$fbKey/id"] = $fbKey;
    }
    fbMultiUpdate($updates);

    clearLoginRate('withdraw_'.$mid);
    jsonResponse(['success'=>true,'message'=>"Withdrawal of ₹{$amount} submitted. Fee: ₹{$fee}",'new_balance'=>$newBal]);
}

// ── REGENERATE API KEY ────────────────────────────────────────────────────────
if ($action === 'regen_api_key') {
    if (empty($_SESSION['merchant_id'])) jsonResponse(['success'=>false,'message'=>'Not authenticated'],401);
    $mid    = $_SESSION['merchant_id'];
    $newKey = bin2hex(random_bytes(32));
    fbPatch("merchants/$mid", ['api_key' => $newKey]);
    jsonResponse(['success'=>true,'api_key'=>$newKey]);
}

jsonResponse(['success'=>false,'message'=>'Unknown action']);
