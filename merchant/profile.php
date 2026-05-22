<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/merchant_layout.php';
$merchant = merchantAuth();
$mid      = $merchant['merchant_id'];
$settings = getSettings();
$sig_example = md5($mid . '500.00' . 'ORDER123' . ($merchant['api_key'] ?? '') . '');
merchantLayoutHead('Profile & API');
?>
<body>
<?php merchantLayoutBody($merchant,'profile'); merchantLayoutTopbar('Profile & API',$merchant);?>
<div class="page-body" style="max-width:760px">

<!-- API Credentials -->
<div class="card" style="margin-bottom:16px">
  <div class="card-title" style="margin-bottom:18px"><i class="fas fa-key" style="color:var(--gold)"></i> API Credentials</div>
  <div class="form-group">
    <label class="form-label">Merchant ID</label>
    <div style="display:flex;align-items:center;gap:10px">
      <input class="form-control" value="<?=e($mid)?>" readonly id="midField">
      <button class="btn btn-outline btn-sm" onclick="copyField('midField')"><i class="fas fa-copy"></i></button>
    </div>
  </div>
  <div class="form-group">
    <label class="form-label">API Key <small style="color:var(--text-muted)">(for creating payment links)</small></label>
    <div style="display:flex;align-items:center;gap:10px">
      <input class="form-control mono" value="<?=e($merchant['api_key']??'Not set — click Regenerate')?>" readonly id="apiKeyField" type="password">
      <button class="btn btn-outline btn-sm" onclick="toggleVis('apiKeyField',this)"><i class="fas fa-eye"></i></button>
      <button class="btn btn-outline btn-sm" onclick="copyField('apiKeyField')"><i class="fas fa-copy"></i></button>
    </div>
  </div>
  <button class="btn btn-warning btn-sm" onclick="regenKey()"><i class="fas fa-rotate"></i> Regenerate API Key</button>
  <div id="keyAlert" style="margin-top:10px"></div>
</div>

<!-- Signature Example -->
<div class="card" style="margin-bottom:16px">
  <div class="card-title" style="margin-bottom:12px"><i class="fas fa-code" style="color:var(--primary)"></i> Signature Formula</div>
  <p style="font-size:13px;color:var(--text-muted);margin-bottom:14px">Compute MD5 of this concatenated string — send as <code>signature</code> in your API call:</p>
  <div style="background:var(--surface2);border-radius:8px;padding:12px 14px;font-family:'DM Mono',monospace;font-size:13px;color:var(--primary);word-break:break-all;margin-bottom:10px">
    md5(merchant_id + amount + merchant_order_no + api_key + redirect_url)
  </div>
  <p style="font-size:12px;color:var(--text-muted)">Example for ₹500, order ORDER123, no redirect URL:<br>
  <span class="mono" style="color:var(--text)"><?= e($sig_example) ?></span></p>
  <p style="font-size:12px;color:var(--text-muted);margin-top:8px">Note: <code>amount</code> must be formatted to 2 decimal places (e.g. <code>500.00</code>). Amount limits: ₹<?=e($settings['min_amount'])?> – ₹<?=e($settings['max_amount'])?>.</p>
</div>

<!-- Change Password -->
<div class="card" style="margin-bottom:16px">
  <div class="card-title" style="margin-bottom:16px"><i class="fas fa-lock" style="color:var(--danger)"></i> Change Login Password</div>
  <div class="form-group">
    <label class="form-label">Current Password</label>
    <input type="password" id="oldPass" class="form-control" placeholder="Current password">
  </div>
  <div class="form-group">
    <label class="form-label">New Password</label>
    <input type="password" id="newPass" class="form-control" placeholder="Min 8 characters">
  </div>
  <div id="passAlert"></div>
  <button class="btn btn-primary" style="margin-top:10px" onclick="changePass()"><i class="fas fa-floppy-disk"></i> Update Password</button>
</div>

<!-- Withdrawal Password -->
<div class="card">
  <div class="card-title" style="margin-bottom:16px"><i class="fas fa-shield-halved" style="color:var(--success)"></i> Withdrawal Password <small style="font-size:12px;color:var(--text-muted)">(requires 2FA)</small></div>
  <?php if($merchant['withdraw_password']): ?>
  <p style="font-size:13px;color:var(--success);margin-bottom:14px"><i class="fas fa-check-circle"></i> Withdrawal password is set.</p>
  <?php else: ?>
  <p style="font-size:13px;color:var(--warning);margin-bottom:14px"><i class="fas fa-triangle-exclamation"></i> Not set yet. Required before making withdrawals.</p>
  <?php endif; ?>
  <div class="form-group">
    <label class="form-label">New Withdrawal Password</label>
    <input type="password" id="wdPass" class="form-control" placeholder="Min 8 characters">
  </div>
  <div class="form-group">
    <label class="form-label">2FA Code</label>
    <input type="text" id="wdTotp" class="form-control" maxlength="6" placeholder="6-digit code" inputmode="numeric" style="font-family:'DM Mono',monospace;letter-spacing:.2em;font-size:20px;text-align:center">
  </div>
  <div id="wdAlert"></div>
  <button class="btn btn-success" style="margin-top:10px" onclick="setWdPass()"><i class="fas fa-floppy-disk"></i> Set Withdrawal Password</button>
</div>
</div>

<script>
function copyField(id) {
    const el = document.getElementById(id);
    const v = el.type === 'password' ? el.value : el.value;
    navigator.clipboard.writeText(v);
    showToast('Copied!');
}
function toggleVis(id, btn) {
    const el = document.getElementById(id);
    const isPass = el.type === 'password';
    el.type = isPass ? 'text' : 'password';
    btn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
}
function showToast(msg) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = 'position:fixed;bottom:20px;right:20px;background:var(--success);color:#fff;padding:8px 16px;border-radius:8px;font-size:14px;font-weight:600;z-index:9999;animation:fade .3s ease';
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2000);
}
async function regenKey() {
    if (!confirm('Regenerate API key? Your old key will stop working immediately.')) return;
    const res = await fetch('<?=SITE_URL?>/api/merchant/auth.php', {
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=regen_api_key&csrf_token=<?=csrfToken()?>'
    }).then(r=>r.json());
    if (res.success) {
        document.getElementById('apiKeyField').value = res.api_key;
        document.getElementById('apiKeyField').type = 'text';
        document.getElementById('keyAlert').innerHTML = '<div class="alert alert-success" style="padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:var(--success)"><i class="fas fa-check-circle"></i> New key generated. Copy it now!</div>';
    }
}
async function changePass() {
    const old = document.getElementById('oldPass').value;
    const nw  = document.getElementById('newPass').value;
    const al  = document.getElementById('passAlert');
    if (nw.length < 8) { al.innerHTML = '<div class="alert alert-danger" style="padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger);margin-top:8px"><i class="fas fa-exclamation-circle"></i> Min 8 characters</div>'; return; }
    const res = await fetch('<?=SITE_URL?>/api/merchant/auth.php', {
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'change_password',old_password:old,new_password:nw,csrf_token:'<?=csrfToken()?>'})
    }).then(r=>r.json());
    al.innerHTML = `<div class="alert ${res.success?'alert-success':'alert-danger'}" style="padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(${res.success?'34,197,94':'239,68,68'},0.1);border:1px solid rgba(${res.success?'34,197,94':'239,68,68'},0.25);color:var(--${res.success?'success':'danger'});margin-top:8px"><i class="fas fa-${res.success?'check':'exclamation'}-circle"></i> ${res.message}</div>`;
}
async function setWdPass() {
    const pass = document.getElementById('wdPass').value;
    const code = document.getElementById('wdTotp').value;
    const al   = document.getElementById('wdAlert');
    if (pass.length < 8) { al.innerHTML = '<div class="alert alert-danger" style="padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger);margin-top:8px"><i class="fas fa-exclamation-circle"></i> Min 8 characters</div>'; return; }
    if (code.length !== 6) { al.innerHTML = '<div class="alert alert-danger" style="padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger);margin-top:8px"><i class="fas fa-exclamation-circle"></i> Enter 6-digit TOTP</div>'; return; }
    const res = await fetch('<?=SITE_URL?>/api/merchant/auth.php', {
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'set_withdraw_password',withdraw_password:pass,totp_code:code,csrf_token:'<?=csrfToken()?>'})
    }).then(r=>r.json());
    al.innerHTML = `<div class="alert ${res.success?'alert-success':'alert-danger'}" style="padding:10px 14px;border-radius:8px;font-size:13px;background:rgba(${res.success?'34,197,94':'239,68,68'},0.1);border:1px solid rgba(${res.success?'34,197,94':'239,68,68'},0.25);color:var(--${res.success?'success':'danger'});margin-top:8px"><i class="fas fa-${res.success?'check':'exclamation'}-circle"></i> ${res.message}</div>`;
    if (res.success) setTimeout(() => location.reload(), 1500);
}
</script>
<?php merchantLayoutFooter(); ?>
