<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/merchant_layout.php';
$merchant = merchantAuth();
$mid = $merchant['merchant_id'];

// Fetch all transactions for this merchant from Firebase
$txnMap = fbQuery('transactions', 'merchant_id', $mid);
$txnList = array_values($txnMap);

$stats = ['total'=>0,'success'=>0,'pending'=>0,'volume'=>0,'today'=>0,'pending_w'=>0];
$today = date('Y-m-d');
foreach ($txnList as $t) {
    $stats['total']++;
    if ($t['status'] === 'success') {
        $stats['success']++;
        $stats['volume'] += floatval($t['amount']);
        if (substr($t['created_at'] ?? '', 0, 10) === $today) $stats['today'] += floatval($t['amount']);
    }
    if ($t['status'] === 'pending') $stats['pending']++;
}

// Pending withdrawals count
$allW = fbQuery('withdrawals', 'merchant_id', $mid);
foreach ($allW as $w) { if (($w['status']??'') === 'pending') $stats['pending_w']++; }

$rate = $stats['total'] > 0 ? round($stats['success'] / $stats['total'] * 100, 1) : 0;

// Recent 8
usort($txnList, fn($a,$b) => strcmp($b['created_at']??'', $a['created_at']??''));
$recent = array_slice($txnList, 0, 8);

merchantLayoutHead('Dashboard');
?>
<body>
<?php merchantLayoutBody($merchant,'dashboard'); merchantLayoutTopbar('Dashboard',$merchant);?>
<div class="page-body">
<div class="stats-grid">
  <div class="stat-card gold">
    <div class="stat-icon gold"><i class="fas fa-wallet"></i></div>
    <div class="stat-value">₹<?=number_format($merchant['balance'],2)?></div>
    <div class="stat-label">Available Balance</div>
  </div>
  <div class="stat-card primary">
    <div class="stat-icon primary"><i class="fas fa-indian-rupee-sign"></i></div>
    <div class="stat-value">₹<?=number_format($stats['volume'],2)?></div>
    <div class="stat-label">Total Volume</div>
  </div>
  <div class="stat-card success">
    <div class="stat-icon success"><i class="fas fa-chart-line"></i></div>
    <div class="stat-value"><?=$rate?>%</div>
    <div class="stat-label">Success Rate</div>
  </div>
  <div class="stat-card info">
    <div class="stat-icon info"><i class="fas fa-calendar-day"></i></div>
    <div class="stat-value">₹<?=number_format($stats['today'],2)?></div>
    <div class="stat-label">Today's Volume</div>
  </div>
  <div class="stat-card warning">
    <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
    <div class="stat-value"><?=$stats['pending']?></div>
    <div class="stat-label">Pending Txns</div>
  </div>
  <div class="stat-card danger">
    <div class="stat-icon danger"><i class="fas fa-money-bill-transfer"></i></div>
    <div class="stat-value"><?=$stats['pending_w']?></div>
    <div class="stat-label">Pending Withdrawals</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-receipt" style="color:var(--primary)"></i> Recent Transactions</div>
    <a href="<?=SITE_URL?>/merchant/transactions.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <?php if(empty($recent)): ?>
  <p style="text-align:center;color:var(--text-muted);padding:24px 0;font-size:14px">No transactions yet. <a href="<?=SITE_URL?>/merchant/create-link.php">Create your first payment link</a></p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>TXN ID</th><th>Amount</th><th>Order No</th><th>Status</th><th>Time</th></tr></thead>
    <tbody>
    <?php foreach($recent as $t): ?>
    <tr>
      <td class="mono" style="font-size:11px;color:var(--primary)"><?=e($t['txn_id'])?></td>
      <td style="color:var(--gold);font-weight:700">₹<?=number_format($t['amount'],2)?></td>
      <td style="font-size:12px;color:var(--text-muted)"><?=e($t['merchant_order_no']??'—')?></td>
      <td><?php $sc=['success'=>'badge-success','failed'=>'badge-danger','pending'=>'badge-warning','expired'=>'badge-muted'];
          echo '<span class="badge '.($sc[$t['status']]??'badge-muted').'">'.e($t['status']).'</span>'; ?></td>
      <td style="font-size:11px;color:var(--text-muted)"><?=e(substr($t['created_at']??'',0,16))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
</div>
<?php merchantLayoutFooter(); ?>
