<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/merchant_layout.php';
$merchant = merchantAuth();
$mid = $merchant['merchant_id'];

$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['all','pending','success','failed','expired'];
if (!in_array($status_filter, $allowed_statuses, true)) $status_filter = 'all';

$txnMap  = fbQuery('transactions', 'merchant_id', $mid) ?? [];
$txnList = array_values((array)$txnMap);
usort($txnList, fn($a,$b) => strcmp($b['created_at']??'', $a['created_at']??''));
if ($status_filter !== 'all') {
    $txnList = array_values(array_filter($txnList, fn($t) => ($t['status']??'') === $status_filter));
}
$txnList = array_slice($txnList, 0, 500);

merchantLayoutHead('Transactions');
?>
<body>
<?php merchantLayoutBody($merchant,'transactions'); merchantLayoutTopbar('Transactions',$merchant);?>
<div class="page-body">
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-list" style="color:var(--primary)"></i> Transactions</div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php foreach(['all'=>'All','pending'=>'Pending','success'=>'Success','failed'=>'Failed','expired'=>'Expired'] as $s=>$l): ?>
      <a href="?status=<?=$s?>" class="btn btn-sm <?=$status_filter===$s?'btn-primary':'btn-outline'"><?=$l?></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if(empty($txnList)): ?>
  <p style="text-align:center;color:var(--text-muted);padding:30px 0">No transactions found.</p>
  <?php else: ?>
  <div style="overflow-x:auto"><table>
    <thead><tr><th>TXN ID</th><th>Order No</th><th>Amount</th><th>UTR</th><th>UPI</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach($txnList as $t): ?>
    <tr>
      <td class="mono" style="font-size:11px;color:var(--primary)"><?=e($t['txn_id'])?></td>
      <td style="font-size:12px;color:var(--text-muted)"><?=e($t['merchant_order_no']??'—')?></td>
      <td style="font-weight:700;color:var(--gold)">₹<?=number_format($t['amount'],2)?></td>
      <td class="mono" style="font-size:11px;color:var(--success)"><?=e($t['utr']??'—')?></td>
      <td style="font-size:11px;color:var(--text-muted)"><?=e($t['upi_address']??'—')?></td>
      <td><?php $sc=['success'=>'badge-success','failed'=>'badge-danger','pending'=>'badge-warning','expired'=>'badge-muted'];
          echo '<span class="badge '.($sc[$t['status']]??'badge-muted').'">'.e($t['status']).'</span>';?></td>
      <td style="font-size:11px;color:var(--text-muted)"><?=e(substr($t['created_at']??'',0,16))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
</div>
<?php merchantLayoutFooter(); ?>
