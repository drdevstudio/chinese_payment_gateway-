<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admin_layout.php';
adminAuth();
adminLayoutHead('Commissions & Settings');
?>
<body>
<?php adminLayoutBody([],'commissions'); adminLayoutTopbar('Commissions & Settings');?>
<div class="page-body" style="max-width:560px">
<div class="card">
  <div class="card-title" style="margin-bottom:20px"><i class="fas fa-sliders" style="color:var(--gold)"></i> Commission & Amount Settings</div>
  <div id="loadState" style="text-align:center;padding:20px;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Loading...</div>
  <form id="commForm" style="display:none" onsubmit="saveSettings(event)">
    <div class="form-group">
      <label class="form-label">Pay-In Commission (%) <small style="color:var(--text-muted)">Charged on each incoming payment</small></label>
      <input type="number" id="payIn" name="pay_in" class="form-control" step="0.01" min="0" max="100" required>
    </div>
    <div class="form-group">
      <label class="form-label">Pay-Out Commission (%) <small style="color:var(--text-muted)">Charged on each withdrawal</small></label>
      <input type="number" id="payOut" name="pay_out" class="form-control" step="0.01" min="0" max="100" required>
    </div>
    <div class="form-group">
      <label class="form-label">Minimum Payment Amount (₹)</label>
      <input type="number" id="minAmt" name="min_amount" class="form-control" step="0.01" min="1" required>
    </div>
    <div class="form-group">
      <label class="form-label">Maximum Payment Amount (₹)</label>
      <input type="number" id="maxAmt" name="max_amount" class="form-control" step="0.01" min="1" required>
    </div>
    <div id="commAlert"></div>
    <button type="submit" class="btn btn-primary" style="margin-top:8px;width:100%"><i class="fas fa-floppy-disk"></i> Save Settings</button>
  </form>
</div>
</div>
<script>
const CSRF = '<?=csrfToken()?>';
const SITE = '<?=SITE_URL?>';
async function loadSettings() {
    const data = await fetch(`${SITE}/api/admin/commissions.php?action=get`).then(r=>r.json());
    document.getElementById('payIn').value  = data.pay_in;
    document.getElementById('payOut').value = data.pay_out;
    document.getElementById('minAmt').value = data.min_amount;
    document.getElementById('maxAmt').value = data.max_amount;
    document.getElementById('loadState').style.display = 'none';
    document.getElementById('commForm').style.display = 'block';
}
async function saveSettings(e) {
    e.preventDefault();
    const al  = document.getElementById('commAlert');
    const res = await fetch(`${SITE}/api/admin/commissions.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action:'update',
            pay_in: document.getElementById('payIn').value,
            pay_out: document.getElementById('payOut').value,
            min_amount: document.getElementById('minAmt').value,
            max_amount: document.getElementById('maxAmt').value,
            csrf_token: CSRF
        })
    }).then(r=>r.json());
    al.innerHTML = `<div class="alert ${res.success?'alert-success':'alert-danger'}" style="margin-top:10px;padding:10px 14px;border-radius:8px;background:rgba(${res.success?'34,197,94':'239,68,68'},0.1);border:1px solid rgba(${res.success?'34,197,94':'239,68,68'},0.25);color:var(--${res.success?'success':'danger'})">${res.message}</div>`;
}
loadSettings();
</script>
<?php adminLayoutFooter(); ?>
