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
  <div class="card-header" style="flex-wrap:wrap;gap:10px">
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
  <div class="table-wrap"><table>
    <thead><tr><th>TXN ID</th><th>Order No</th><th>Amount</th><th>UTR</th><th>UPI</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>
    <?php foreach($txnList as $t): ?>
    <tr onclick="showTxnDetail(<?=htmlspecialchars(json_encode($t),ENT_QUOTES)?> )" style="cursor:pointer">
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

<!-- Transaction Detail Modal -->
<div class="modal-overlay" id="txnDetailModal">
  <div class="modal" style="max-width:520px">
    <div class="modal-title">
      <span><i class="fas fa-receipt" style="color:var(--primary)"></i> Transaction Detail</span>
      <span class="modal-close" onclick="closeTxnModal()"><i class="fas fa-times"></i></span>
    </div>
    <div id="txnDetailBody" style="margin-top:8px"></div>
    <div style="margin-top:18px">
      <button class="btn btn-outline" style="width:100%" onclick="closeTxnModal()">Close</button>
    </div>
  </div>
</div>

<style>
.detail-row{display:flex;justify-content:space-between;align-items:flex-start;padding:9px 0;border-bottom:1px solid var(--border);font-size:13px;gap:12px;}
.detail-row:last-child{border-bottom:none;}
.detail-label{color:var(--text-muted);font-size:12px;min-width:120px;flex-shrink:0;}
.detail-val{font-weight:500;text-align:right;word-break:break-all;}
@media(max-width:600px){.detail-row{flex-direction:column;gap:2px}.detail-val{text-align:left}}
</style>

<script>
function showTxnDetail(t) {
  const sc = {success:'badge-success',failed:'badge-danger',pending:'badge-warning',expired:'badge-muted'};
  const badge = `<span class="badge ${sc[t.status]||'badge-muted'}">${t.status}</span>`;
  const copy = (val,btn) => {
    navigator.clipboard.writeText(val);
    const o=btn.innerHTML; btn.innerHTML='<i class="fas fa-check" style="color:var(--success)"></i>';
    setTimeout(()=>btn.innerHTML=o,1500);
  };
  document.getElementById('txnDetailBody').innerHTML = `
    <div class="detail-row"><div class="detail-label">Status</div><div class="detail-val">${badge}</div></div>
    <div class="detail-row"><div class="detail-label">TXN ID</div><div class="detail-val"><span class="mono" style="font-size:12px;color:var(--primary)">${t.txn_id||'—'}</span> <button class="btn btn-sm btn-outline" style="padding:3px 8px;font-size:11px" onclick="event.stopPropagation();navigator.clipboard.writeText('${t.txn_id||''}');this.innerHTML='✓'"><i class="fas fa-copy"></i></button></div></div>
    <div class="detail-row"><div class="detail-label">Order No</div><div class="detail-val">${t.merchant_order_no||'—'}</div></div>
    <div class="detail-row"><div class="detail-label">Amount</div><div class="detail-val" style="color:var(--gold);font-weight:700;font-size:16px">₹${parseFloat(t.amount||0).toFixed(2)}</div></div>
    <div class="detail-row"><div class="detail-label">UTR / Ref No</div><div class="detail-val"><span class="mono" style="color:var(--success)">${t.utr||'—'}</span></div></div>
    <div class="detail-row"><div class="detail-label">UPI Address</div><div class="detail-val mono" style="font-size:12px">${t.upi_address||'—'}</div></div>
    <div class="detail-row"><div class="detail-label">Holder Name</div><div class="detail-val">${t.holder_name||'—'}</div></div>
    <div class="detail-row"><div class="detail-label">Created At</div><div class="detail-val" style="color:var(--text-muted)">${(t.created_at||'').substring(0,16)||'—'}</div></div>
    ${t.redirect_url?`<div class="detail-row"><div class="detail-label">Redirect URL</div><div class="detail-val" style="font-size:11px">${t.redirect_url}</div></div>`:''}
  `;
  document.getElementById('txnDetailModal').classList.add('open');
}
function closeTxnModal() { document.getElementById('txnDetailModal').classList.remove('open'); }
document.getElementById('txnDetailModal').addEventListener('click', e => {
  if (e.target === e.currentTarget) closeTxnModal();
});
</script>
<?php merchantLayoutFooter(); ?>
