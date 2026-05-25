<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/merchant_layout.php';
$merchant = merchantAuth();
$mid      = $merchant['merchant_id'];
$comm     = getCommissions();
$payOut   = floatval($comm['pay_out']);
$fee_pct  = $payOut;

// Fetch withdrawals
$wMap  = fbQuery('withdrawals', 'merchant_id', $mid);
$wList = array_values($wMap);
usort($wList, fn($a,$b) => strcmp($b['created_at']??'', $a['created_at']??''));
$wList = array_slice($wList, 0, 100);

merchantLayoutHead('Withdraw Funds');
?>
<body>
<?php merchantLayoutBody($merchant,'withdraw'); merchantLayoutTopbar('Withdraw Funds',$merchant);?>
<div class="page-body" style="max-width:680px">

<?php if (!$merchant['withdraw_password']): ?>
<div class="alert alert-warning" style="padding:14px 16px;border-radius:10px;background:rgba(234,179,8,0.1);border:1px solid rgba(234,179,8,0.25);color:var(--warning);font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px">
  <i class="fas fa-triangle-exclamation"></i> You haven't set a withdrawal password yet. <a href="<?=SITE_URL?>/merchant/profile.php" style="color:inherit;font-weight:700;margin-left:4px">Set it in Profile →</a>
</div>
<?php endif; ?>

<div class="card" style="margin-bottom:16px">
  <div class="card-title" style="margin-bottom:16px"><i class="fas fa-money-bill-transfer" style="color:var(--gold)"></i> Withdraw Funds</div>
  <div style="display:flex;justify-content:space-between;align-items:center;background:var(--surface2);border-radius:10px;padding:14px 18px;margin-bottom:16px">
    <div>
      <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px">Available Balance</div>
      <div style="font-size:28px;font-weight:800;color:var(--gold);font-family:'Syne',sans-serif">₹<?=number_format($merchant['balance'],2)?></div>
    </div>
    <div style="text-align:right;font-size:12px;color:var(--text-muted)">Withdrawal fee<br><strong style="color:var(--text);font-size:14px"><?=$fee_pct?>%</strong></div>
  </div>

  <div class="form-group">
    <label class="form-label">Amount (₹) <span style="color:var(--text-muted);font-size:12px">Min: ₹100</span></label>
    <input type="number" id="wAmt" class="form-control" placeholder="Enter amount" min="100" max="<?=floor($merchant['balance'])?>" step="1" oninput="calcFee()">
    <div id="feeNote" style="font-size:12px;color:var(--text-muted);margin-top:6px"></div>
  </div>
  <div class="form-group">
    <label class="form-label">UPI Address</label>
    <input type="text" id="wUpi" class="form-control" placeholder="yourname@upi">
  </div>
  <div class="form-group">
    <label class="form-label">Withdrawal Password</label>
    <input type="password" id="wPass" class="form-control" placeholder="Your withdrawal password">
  </div>
  <div class="form-group">
    <label class="form-label">2FA Code (Google Authenticator)</label>
    <input type="text" id="wTotp" class="form-control" maxlength="6" placeholder="6-digit code" inputmode="numeric" style="font-family:'DM Mono',monospace;letter-spacing:.2em;font-size:20px;text-align:center">
  </div>
  <div id="wAlert"></div>
  <button class="btn btn-primary" style="width:100%;margin-top:8px" onclick="submitWithdraw()">
    <i class="fas fa-paper-plane"></i> Submit Withdrawal
  </button>
</div>

<div class="card">
  <div class="card-title" style="margin-bottom:16px"><i class="fas fa-history" style="color:var(--primary)"></i> Withdrawal History</div>
  <?php if(empty($wList)): ?>
  <p style="text-align:center;color:var(--text-muted);padding:20px 0;font-size:14px">No withdrawals yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Amount</th><th>UPI</th><th>Status</th><th>Note</th><th>Date</th></tr></thead>
    <tbody>
    <?php foreach($wList as $w): ?>
    <tr>
      <td style="font-weight:700;color:var(--gold)">₹<?=number_format($w['amount'],2)?></td>
      <td class="mono" style="font-size:12px"><?=e($w['upi_address']??'—')?></td>
      <td><?php $sc=['success'=>'badge-success','failed'=>'badge-danger','pending'=>'badge-warning'];
          echo '<span class="badge '.($sc[$w['status']]??'badge-muted').'">'.e($w['status']).'</span>';?></td>
      <td style="font-size:12px;color:var(--text-muted)"><?=e($w['note']??'—')?></td>
      <td style="font-size:11px;color:var(--text-muted)"><?=e(substr($w['created_at']??'',0,16))?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
</div>

<script>
const BALANCE = <?=floatval($merchant['balance'])?>;
const FEE_PCT = <?=$fee_pct?>;
function calcFee() {
    const a = parseFloat(document.getElementById('wAmt').value) || 0;
    const fee = +(a * FEE_PCT / 100).toFixed(2);
    const total = +(a + fee).toFixed(2);
    const note = document.getElementById('feeNote');
    if (a > 0) {
        note.innerHTML = `Fee: ₹${fee} (${FEE_PCT}%) &nbsp;|&nbsp; Total deducted: ₹${total} &nbsp;|&nbsp; You receive: ₹${a}`;
        note.style.color = total > BALANCE ? 'var(--danger)' : 'var(--text-muted)';
    } else { note.textContent = ''; }
}
async function submitWithdraw() {
    const amount = parseFloat(document.getElementById('wAmt').value) || 0;
    const upi    = document.getElementById('wUpi').value.trim();
    const pass   = document.getElementById('wPass').value;
    const code   = document.getElementById('wTotp').value.trim();
    const alert  = document.getElementById('wAlert');
    if (amount < 100) { alert.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Minimum withdrawal is ₹100</div>'; return; }
    if (!upi)  { alert.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Enter UPI address</div>'; return; }
    if (!pass) { alert.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Enter withdrawal password</div>'; return; }
    if (code.length !== 6) { alert.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> Enter 6-digit TOTP code</div>'; return; }
    alert.innerHTML = '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Processing...</div>';
    try {
        const res = await fetch('<?=SITE_URL?>/api/merchant/auth.php', {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action:'withdraw',amount,upi_address:upi,withdraw_password:pass,totp_code:code,csrf_token:'<?=csrfToken()?>'})
        }).then(r=>r.json());
        if (res.success) {
            alert.innerHTML = `<div class="alert alert-success"><i class="fas fa-check-circle"></i> ${res.message}</div>`;
            document.getElementById('wAmt').value = '';
            document.getElementById('wTotp').value = '';
            setTimeout(() => location.reload(), 2000);
        } else {
            alert.innerHTML = `<div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> ${res.message || 'Failed'}</div>`;
        }
    } catch(e) {
        alert.innerHTML = '<div class="alert alert-danger"><i class="fas fa-wifi"></i> Network error. Try again.</div>';
    }
}
</script>
<?php merchantLayoutFooter(); ?>
