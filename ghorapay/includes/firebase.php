<?php
/**
 * Ghora Pay — Firebase Realtime Database REST API Helper
 *
 * Uses open rules (read: true, write: true) — no API key or service account needed.
 * Set FIREBASE_URL in config.php to your database URL:
 *   define('FIREBASE_URL', 'https://YOUR-PROJECT-default-rtdb.firebaseio.com');
 */

// ── Low-level request ─────────────────────────────────────────────────────────
function fbRequest(string $method, string $path, $data = null, array $query = []) {
    $url = rtrim(FIREBASE_URL, '/') . '/' . ltrim($path, '/') . '.json';
    if ($query) $url .= '?' . http_build_query($query);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    $body   = curl_exec($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        error_log("Firebase cURL error ($method $url): $err");
        return null;
    }
    $decoded = json_decode($body, true);
    return $decoded; // null on Firebase null, array/string otherwise
}

// ── CRUD shortcuts ────────────────────────────────────────────────────────────
function fbGet(string $path, array $query = []) {
    return fbRequest('GET', $path, null, $query);
}
function fbPut(string $path, $data) {
    return fbRequest('PUT', $path, $data);
}
function fbPost(string $path, $data): ?string {
    // Firebase POST returns {"name":"<auto-id>"}
    $r = fbRequest('POST', $path, $data);
    return is_array($r) && isset($r['name']) ? $r['name'] : null;
}
function fbPatch(string $path, array $data) {
    return fbRequest('PATCH', $path, $data);
}
function fbDelete(string $path) {
    return fbRequest('DELETE', $path);
}

// ── Multi-path atomic update ──────────────────────────────────────────────────
// $updates = ['/path/a' => value, '/path/b' => value2]
function fbMultiUpdate(array $updates) {
    return fbRequest('PATCH', '', $updates);
}

// ── Query helpers ─────────────────────────────────────────────────────────────
/**
 * Query a collection filtered by a single indexed field.
 * Returns an associative array [key => record] or empty array.
 */
function fbQuery(string $path, string $orderBy, $equalTo): array {
    $query = ['orderBy' => '"' . $orderBy . '"'];
    if (is_bool($equalTo)) {
        $query['equalTo'] = $equalTo ? 'true' : 'false';
    } elseif (is_numeric($equalTo)) {
        $query['equalTo'] = $equalTo;
    } else {
        $query['equalTo'] = '"' . $equalTo . '"';
    }
    $result = fbGet($path, $query);
    if (!is_array($result)) return [];
    return $result; // keyed by firebase key
}

/**
 * Like fbQuery but returns a flat list of values only.
 */
function fbQueryValues(string $path, string $orderBy, $equalTo): array {
    return array_values(fbQuery($path, $orderBy, $equalTo));
}

/**
 * Get all records from a collection.
 * Returns flat list sorted by $orderBy field DESC if provided.
 */
function fbAll(string $path, string $orderBy = '', bool $desc = false, int $limit = 0): array {
    $query = [];
    if ($orderBy) {
        $query['orderBy'] = '"' . $orderBy . '"';
        if ($limit > 0) {
            $query[$desc ? 'limitToLast' : 'limitToFirst'] = $limit;
        }
    }
    $result = fbGet($path, $query);
    if (!is_array($result)) return [];
    $rows = array_values($result);
    if ($orderBy && $desc) {
        usort($rows, fn($a, $b) => strcmp((string)($b[$orderBy] ?? ''), (string)($a[$orderBy] ?? '')));
    }
    return $rows;
}

// ── Domain-level helpers ──────────────────────────────────────────────────────

function getSettings(): array {
    $s = fbGet('settings');
    return is_array($s) ? $s : ['min_amount' => 1.00, 'max_amount' => 100000.00];
}

function getCommissions(): array {
    $c = fbGet('commissions');
    return is_array($c) ? $c : ['pay_in' => 2.00, 'pay_out' => 1.50];
}

function generateId(int $len = 16): string {
    $c = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $r = '';
    for ($i = 0; $i < $len; $i++) $r .= $c[random_int(0, 35)];
    return $r;
}

function generateMerchantId(): string {
    do {
        $id  = 'M' . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $chk = fbGet("merchants/$id");
    } while ($chk !== null);
    return $id;
}

function resetDailyLimitsIfNeeded(): void {
    $today  = date('Y-m-d');
    $upiMap = fbGet('upi_ids');
    if (!is_array($upiMap)) return;
    $updates = [];
    foreach ($upiMap as $devId => $upi) {
        if (!isset($upi['last_reset']) || $upi['last_reset'] < $today) {
            $updates["upi_ids/$devId/today_received"] = 0;
            $updates["upi_ids/$devId/last_reset"]     = $today;
        }
    }
    if ($updates) fbMultiUpdate($updates);
}
