<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admin_layout.php';
$admin = adminAuth();

// ── Fetch stats from Firebase ─────────────────────────────────────────────────
$allTxns     = fbGet('transactions') ?? [];
$allMerchants= fbGet('merchants') ?? [];
$allWithdrawals = fbGet('withdrawals') ?? [];
$allUpi      = fbGet('upi_ids') ?? [];
$today       = date('Y-m-d');

$liveMerchants = 0;
foreach ($allMerchants as $m) {
    if (($m['status'] ?? '') === 'live') $liveMerchants++;
}

$totalTxn = count($allTxns);
$successTxn = $totalVol = $todayVol = 0;
foreach ($allTxns as $t) {
    if (($t['status'] ?? '') === 'success') {
        $successTxn++;
        $totalVol += floatval($t['amount'] ?? 0);
        if (!empty($t['created_at']) && substr($t['created_at'], 0, 10) === $today) {
            $todayVol += floatval($t['amount'] ?? 0);
        }
    }
}

$pendingW = 0;
foreach ($allWithdrawals as $w) {
    if (($w['status'] ?? '') === 'pending') $pendingW++;
}

$activeUpi = 0;
foreach ($allUpi as $u) {
    if (($u['status'] ?? '') === 'active') $activeUpi++;
}

$rate = $totalTxn > 0 ? round($successTxn / $totalTxn * 100, 1) : 0;

// Recent 10 transactions
$txnList = array_values($allTxns);
usort($txnList, fn($a,$b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));
$recent = array_slice($txnList, 0, 10);
// Attach merchant name
foreach ($recent as &$r) {
    $mid = $r['merchant_id'] ?? '';
    $r['mn'] = $allMerchants[$mid]['name'] ?? '—';
}
unset($r);

// Pending withdrawals (first 5)
$pendingWithdrawals = [];
foreach ($allWithdrawals as $key => $w) {
    if (($w['status'] ?? '') === 'pending') {
        $mid = $w['merchant_id'] ?? '';
        $w['mn'] = $allMerchants[$mid]['name'] ?? '—';
        $pendingWithdrawals[] = $w;
        if (count($pendingWithdrawals) >= 5) break;
    }
}

$stats = [
    'merchants'   => $liveMerchants,
    'total_vol'   => $totalVol,
    'success_txn' => $successTxn,
    'today_vol'   => $todayVol,
    'pending_w'   => $pendingW,
    'active_upi'  => $activeUpi,
];

adminLayoutHead('Dashboard');
?>
<body>
<?php adminLayoutBody($admin,'dashboard'); adminLayoutTopbar('Dashboard'); ?>
<div class="page-body">
<div class="stats-grid">
  <div class="stat-card primary"><div class="stat-icon primary"><i class="fas fa-store"></i></div><div class="stat-value"><?= number_format($stats['merchants']) ?></div><div class="stat-label">Live Merchants</div></div>
  <div class="stat-card gold"><div class="stat-icon gold"><i class="fas fa-indian-rupee-sign"></i></div><div class="stat-value">₹<?= number_format($stats['total_vol'],2) ?></div><div class="stat-label">Total Volume</div></div>
  <div class="stat-card success"><div class="stat-icon success"><i class="fas fa-chart-line"></i></div><div class="stat-value"><?= $rate ?>%</div><div class="stat-label">Success Rate</div></div>
  <div class="stat-card info"><div class="stat-icon info"><i class="fas fa-calendar-day"></i></div><div class="stat-value">₹<?= number_format($stats['today_vol'],2) ?></div><div class="stat-label">Today's Volume</div></div>
  <div class="stat-card danger"><div class="stat-icon danger"><i class="fas fa-clock"></i></div><div class="stat-value"><?= $stats['pending_w'] ?></div><div class="stat-label">Pending Withdrawals</div></div>
  <div class="stat-card primary"><div class="stat-icon primary"><i class="fas fa-qrcode"></i></div><div class="stat-value"><?= $stats['active_upi'] ?></div><div class="stat-label">Active UPI IDs</div></div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px" class="dash-grid">
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-receipt" style="color:var(--primary)"></i> Recent Transactions</div>
    <a href="<?= SITE_URL ?>/admin/merchants.php" class="btn btn-outline btn-sm">All</a>
  </div>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>TXN ID</th><th>Merchant</th><th>Amount</th><th>Status</th><th>Time</th></tr></thead>
    <tbody>
    <?php foreach($recent as $r): ?>
    <tr>
      <td class="mono" style="font-size:11px"><?= e($r['txn_id']) ?></td>
      <td style="font-size:13px"><?= e($r['mn']) ?></td>
      <td style="color:var(--gold);font-weight:700">₹<?= number_format($r['amount'],2) ?></td>
      <td><?php $sc=['success'=>'badge-success','failed'=>'badge-danger','pending'=>'badge-warning','expired'=>'badge-muted'];
          echo '<span class="badge '.($sc[$r['status']]??'badge-muted').'">'.e($r['status']).'</span>'; ?></td>
      <td style="color:var(--text-muted);font-size:11px"><?= e(substr($r['created_at']??'',0,16)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-money-bill-transfer" style="color:var(--warning)"></i> Pending Withdrawals</div>
    <a href="<?= SITE_URL ?>/admin/withdrawals.php" class="btn btn-outline btn-sm">All</a>
  </div>
  <?php if(empty($pendingWithdrawals)): ?>
  <p style="text-align:center;color:var(--text-muted);padding:20px 0;font-size:14px"><i class="fas fa-check-circle" style="color:var(--success)"></i> All clear!</p>
  <?php else: ?>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>Merchant</th><th>Amount</th><th>UPI</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($pendingWithdrawals as $w): ?>
    <tr>
      <td style="font-size:13px"><?= e($w['mn']) ?></td>
      <td style="color:var(--gold);font-weight:700">₹<?= number_format($w['amount'],2) ?></td>
      <td class="mono" style="font-size:11px"><?= e($w['upi_address']) ?></td>
      <td style="color:var(--text-muted);font-size:11px"><?= e(substr($w['created_at']??'',0,10)) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
</div>
</div>
<style>.dash-grid{@media(max-width:768px){grid-template-columns:1fr!important}}</style>
<?php adminLayoutFooter(); ?>
