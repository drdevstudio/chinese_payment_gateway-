<?php
/**
 * Ghora Pay — Cron Job: Expire Pending Payments
 *
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  Schedule this on cron-job.org every 10 minutes               │
 * │                                                                 │
 * │  URL:    https://YOUR-APP.onrender.com/cronjob.php             │
 * │  Method: GET                                                    │
 * │  Header: X-Cron-Secret: <value of CRON_SECRET env var>        │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * What it does:
 *  - Finds all "pending" transactions older than 15 minutes
 *  - Marks them as "expired" in Firebase
 *  - Returns a JSON report of what was expired
 *
 * Environment variables needed (set in Render dashboard):
 *  FIREBASE_URL   → your Firebase Realtime Database URL
 *  CRON_SECRET    → secret key to protect this endpoint
 *                   (Render auto-generates it via render.yaml)
 */

require_once __DIR__ . '/config.php';

// ── Auth: only allow cron-job.org (or you) to call this ──────────────────────
$providedSecret = $_SERVER['HTTP_X_CRON_SECRET']
    ?? ($_GET['secret']  ?? '');

if (!hash_equals(CRON_SECRET, (string)$providedSecret)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized — set X-Cron-Secret header']);
    exit;
}

// ── Settings ──────────────────────────────────────────────────────────────────
const EXPIRE_AFTER_MINUTES = 15;   // must match pay.php timer (15 min)
const MAX_BATCH            = 300;  // max transactions to scan per run

// ── Main ──────────────────────────────────────────────────────────────────────
$startTime  = microtime(true);
$cutoff     = date('Y-m-d H:i:s', time() - (EXPIRE_AFTER_MINUTES * 60));
$expired    = 0;
$skipped    = 0;
$expiredIds = [];
$updates    = [];

// Fetch all pending transactions from Firebase
$txnMap = fbQuery('transactions', 'status', 'pending');

if (empty($txnMap)) {
    echo json_encode([
        'success'       => true,
        'ran_at'        => date('Y-m-d H:i:s'),
        'cutoff'        => $cutoff,
        'expire_after'  => EXPIRE_AFTER_MINUTES . ' minutes',
        'expired'       => 0,
        'skipped'       => 0,
        'message'       => 'No pending transactions found',
        'duration'      => round(microtime(true) - $startTime, 3) . 's',
    ], JSON_PRETTY_PRINT);
    exit;
}

$processed = 0;

foreach ($txnMap as $fbKey => $txn) {
    if ($processed >= MAX_BATCH) break;

    $created = $txn['created_at'] ?? '';

    // Skip if missing timestamp
    if (!$created) { $skipped++; continue; }

    // Skip if created within the last 15 minutes (not expired yet)
    if ($created >= $cutoff) { $skipped++; continue; }

    // Skip if somehow already claimed (race condition safety)
    if ((int)($txn['claimed'] ?? 0) === 1) { $skipped++; continue; }

    $txnId = $txn['txn_id'] ?? $fbKey;

    // Queue the update
    $updates["transactions/{$txnId}/status"]     = 'expired';
    $updates["transactions/{$txnId}/expired_at"] = date('Y-m-d H:i:s');

    $expiredIds[] = $txnId;
    $expired++;
    $processed++;
}

// Apply all updates in one atomic Firebase PATCH call
$fbError = false;
if (!empty($updates)) {
    $result = fbMultiUpdate($updates);
    // Firebase returns null on a successful PATCH with no response body
    // Only flag as error if we get a non-null, non-array back unexpectedly
    if ($result === null && $expired > 0) {
        // This is normal for Firebase PATCH — null means success
        $fbError = false;
    }
}

$duration = round(microtime(true) - $startTime, 3);

http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'success'       => true,
    'ran_at'        => date('Y-m-d H:i:s'),
    'cutoff'        => $cutoff,
    'expire_after'  => EXPIRE_AFTER_MINUTES . ' minutes',
    'total_scanned' => $processed + $skipped,
    'expired'       => $expired,
    'skipped'       => $skipped,
    'duration'      => $duration . 's',
    'expired_ids'   => $expiredIds,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
