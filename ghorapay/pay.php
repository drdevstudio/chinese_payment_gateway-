<?php
require_once __DIR__.'/config.php';
$txn_id = trim($_GET['txn'] ?? '');
if (!$txn_id) { http_response_code(400); die('Missing transaction ID'); }

$txn = fbGet("transactions/$txn_id");
if (!is_array($txn)) { http_response_code(404); die('Transaction not found'); }
if (($txn['status'] ?? '') === 'expired') { die('This payment link has expired.'); }
if (($txn['status'] ?? '') === 'success') {
    if ($txn['redirect_url']) { header('Location: '.$txn['redirect_url'].'?txn_id='.$txn_id.'&status=success'); exit; }
}

$upi    = e($txn['upi_address'] ?? '');
$name   = e($txn['holder_name']  ?? '');
$amt    = number_format(floatval($txn['amount'] ?? 0), 2, '.', '');
$rawAmt = $txn['amount'] ?? 0;
$note   = 'Payment+'.$txn_id;
$upiStr   = "upi://pay?pa={$upi}&pn={$name}&am={$amt}&cu=INR&tn={$note}";
$phonepeStr = "phonepe://pay?pa={$upi}&pn={$name}&am={$amt}&cu=INR";
$gpayStr  = "tez://upi/pay?pa={$upi}&pn={$name}&am={$amt}&cu=INR&tn={$note}";
$paytmStr = "paytmmp://pay?pa={$upi}&pn={$name}&am={$amt}&cu=INR";
?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Pay ₹<?=$amt?> — Ghora Pay</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<style>
:root{--bg:#0A0F1E;--surf:#111827;--surf2:#1a2235;--pri:#6366f1;--gld:#F59E0B;--tx:#e2e8f0;--mu:#64748b;--bd:rgba(255,255,255,0.08);--ok:#22c55e;--red:#ef4444;--warn:#eab308;}
[data-theme=light]{--bg:#f1f5f9;--surf:#fff;--surf2:#f8fafc;--tx:#1e293b;--bd:rgba(0,0,0,0.08);}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:16px;position:relative;overflow-x:hidden;}
.orb{position:fixed;border-radius:50%;filter:blur(80px);pointer-events:none;z-index:0;}
.o1{width:300px;height:300px;background:rgba(99,102,241,0.15);top:-60px;left:-60px;}
.o2{width:200px;height:200px;background:rgba(245,158,11,0.1);bottom:-40px;right:-40px;}
.wrap{width:100%;max-width:420px;z-index:1;padding-top:10px;}
.brand{display:flex;align-items:center;gap:8px;justify-content:center;margin-bottom:20px;}
.brand-icon{width:34px;height:34px;background:linear-gradient(135deg,#6366f1,#F59E0B);border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;}
.brand-name{font-family:'Syne',sans-serif;font-size:17px;font-weight:800;}
.amount-box{text-align:center;background:var(--surf);border:1px solid var(--bd);border-radius:18px;padding:24px;margin-bottom:14px;position:relative;overflow:hidden;}
.amount-box::before{content:'';position:absolute;top:-30px;left:50%;transform:translateX(-50%);width:200px;height:100px;background:radial-gradient(ellipse,rgba(245,158,11,0.15),transparent 70%);pointer-events:none;}
.amt-label{font-size:13px;color:var(--mu);margin-bottom:6px;text-transform:uppercase;letter-spacing:.08em;}
.amt-value{font-family:'Syne',sans-serif;font-size:42px;font-weight:800;color:var(--gld);line-height:1;}
.amt-sub{font-size:13px;color:var(--mu);margin-top:8px;}
.txn-chip{display:inline-flex;align-items:center;gap:6px;background:var(--surf2);border:1px solid var(--bd);padding:4px 12px;border-radius:999px;font-size:11px;font-family:'DM Mono',monospace;color:var(--mu);margin-top:8px;}
.timer-bar{background:var(--surf);border:1px solid var(--bd);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px;margin-bottom:14px;font-size:13px;}
.timer-track{flex:1;background:var(--surf2);border-radius:999px;height:5px;overflow:hidden;}
.timer-fill{height:100%;background:linear-gradient(90deg,var(--ok),var(--gld));border-radius:999px;transition:width 1s linear;}
.timer-val{font-family:'DM Mono',monospace;font-weight:700;min-width:40px;text-align:right;}
.tabs{display:flex;gap:4px;background:var(--surf);border:1px solid var(--bd);border-radius:12px;padding:4px;margin-bottom:14px;}
.tab{flex:1;padding:9px 6px;border:none;background:none;color:var(--mu);font-size:13px;font-weight:600;border-radius:8px;cursor:pointer;transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px;font-family:inherit;}
.tab.active{background:var(--pri);color:#fff;box-shadow:0 2px 8px rgba(99,102,241,0.3);}
.panel{display:none;}.panel.active{display:block;}
.card{background:var(--surf);border:1px solid var(--bd);border-radius:14px;padding:20px;margin-bottom:14px;}
.upi-box{display:flex;align-items:center;justify-content:space-between;background:var(--surf2);border:1px solid var(--bd);border-radius:10px;padding:14px 16px;margin-bottom:12px;}
.upi-label{font-size:11px;color:var(--mu);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;}
.upi-value{font-family:'DM Mono',monospace;font-size:14px;font-weight:700;color:var(--pri);word-break:break-all;}
.copy-btn{background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);color:var(--pri);padding:8px 14px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;white-space:nowrap;flex-shrink:0;font-family:inherit;}
.copy-btn:hover{background:rgba(99,102,241,0.2);}
.app-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:12px;}
.app-btn{display:flex;align-items:center;justify-content:center;gap:8px;padding:13px;background:var(--surf2);border:1px solid var(--bd);border-radius:12px;color:var(--tx);text-decoration:none;font-size:14px;font-weight:600;transition:all .15s;}
.app-btn:hover{border-color:var(--pri);background:rgba(99,102,241,0.08);}
.app-btn img{width:24px;height:24px;border-radius:6px;object-fit:contain;}
.qr-wrap{text-align:center;padding:10px 0;}
#qrCanvas{display:inline-block;padding:14px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.3);}
.verify-section{background:var(--surf);border:1px solid var(--bd);border-radius:14px;padding:20px;margin-bottom:14px;}
.verify-title{font-size:14px;font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:8px;}
.utr-input{width:100%;padding:12px 14px;background:var(--surf2);border:1px solid var(--bd);border-radius:10px;color:var(--tx);font-size:16px;font-family:'DM Mono',monospace;outline:none;letter-spacing:.08em;text-align:center;}
.utr-input:focus{border-color:var(--pri);}
.submit-btn{width:100%;padding:14px;background:linear-gradient(135deg,#6366f1,#818cf8);border:none;border-radius:10px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;margin-top:10px;transition:all .15s;}
.submit-btn:disabled{opacity:.5;cursor:not-allowed;}
.alert{padding:11px 14px;border-radius:8px;font-size:13px;margin-top:10px;display:flex;align-items:center;gap:8px;}
.alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:var(--ok);}
.alert-danger{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--red);}
.alert-info{background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.25);color:var(--pri);}
.success-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.8);z-index:999;display:none;align-items:center;justify-content:center;backdrop-filter:blur(8px);}
.success-overlay.show{display:flex;}
.success-card{background:var(--surf);border:1px solid var(--bd);border-radius:24px;padding:40px 32px;text-align:center;max-width:360px;width:90%;animation:pop .3s ease;}
@keyframes pop{from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1}}
.success-icon{width:80px;height:80px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:36px;color:#fff;box-shadow:0 8px 32px rgba(34,197,94,0.4);}
.success-title{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;margin-bottom:8px;}
.success-sub{font-size:14px;color:var(--mu);line-height:1.6;}
.steps{background:var(--surf);border:1px solid var(--bd);border-radius:12px;padding:16px;margin-bottom:14px;}
.step{display:flex;align-items:flex-start;gap:12px;padding:8px 0;border-bottom:1px solid var(--bd);font-size:13px;}
.step:last-child{border-bottom:none;}
.step-num{width:22px;height:22px;background:var(--pri);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;margin-top:1px;}
.theme-btn{position:fixed;top:12px;right:12px;width:34px;height:34px;background:var(--surf);border:1px solid var(--bd);border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--tx);z-index:10;}
</style></head>
<body>
<div class="orb o1"></div><div class="orb o2"></div>
<button class="theme-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>

<!-- Success overlay -->
<div class="success-overlay" id="successOverlay">
  <div class="success-card">
    <div class="success-icon"><i class="fas fa-check"></i></div>
    <div class="success-title">Payment Confirmed!</div>
    <div class="success-sub">Your payment of <strong style="color:var(--gld)">₹<?=$amt?></strong> has been received successfully.<br><br>You will be redirected shortly.</div>
  </div>
</div>

<div class="wrap">
  <div class="brand">
    <div class="brand-icon"><i class="fas fa-horse"></i></div>
    <div class="brand-name">Ghora Pay</div>
  </div>

  <!-- Amount -->
  <div class="amount-box">
    <div class="amt-label">Amount to Pay</div>
    <div class="amt-value">₹<?=$amt?></div>
    <div class="amt-sub">Pay to: <strong><?=$name?></strong></div>
    <div class="txn-chip"><i class="fas fa-receipt" style="font-size:10px"></i> <?=e($txn_id)?></div>
  </div>

  <!-- Timer -->
  <div class="timer-bar">
    <i class="fas fa-clock" style="color:var(--warn)"></i>
    <span style="flex:1;font-size:12px;color:var(--mu)">Time remaining</span>
    <div class="timer-track"><div class="timer-fill" id="timerFill" style="width:100%"></div></div>
    <div class="timer-val" id="timerVal">15:00</div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab active" onclick="showTab('upi',this)"><i class="fas fa-mobile-alt"></i> Pay App</button>
    <button class="tab" onclick="showTab('qr',this)"><i class="fas fa-qrcode"></i> QR Code</button>
    <button class="tab" onclick="showTab('manual',this)"><i class="fas fa-keyboard"></i> Manual</button>
  </div>

  <!-- Pay App Tab -->
  <div class="panel active" id="tab-upi">
    <div class="card">
      <div class="upi-box">
        <div>
          <div class="upi-label">UPI ID</div>
          <div class="upi-value" id="upiAddr"><?=$upi?></div>
        </div>
        <button class="copy-btn" onclick="copyUpi()"><i class="fas fa-copy"></i> Copy</button>
      </div>
      <div class="app-grid">
        <a href="<?=$phonepeStr?>" class="app-btn"><i class="fas fa-circle" style="color:#5f259f"></i> PhonePe</a>
        <a href="<?=$gpayStr?>" class="app-btn"><i class="fas fa-circle" style="color:#4285F4"></i> Google Pay</a>
        <a href="<?=$paytmStr?>" class="app-btn"><i class="fas fa-circle" style="color:#00BAF2"></i> Paytm</a>
        <a href="<?=$upiStr?>" class="app-btn"><i class="fas fa-circle" style="color:#F59E0B"></i> Any UPI</a>
      </div>
    </div>
    <div class="steps">
      <div class="step"><div class="step-num">1</div><div>Copy the UPI ID or tap a payment app button above</div></div>
      <div class="step"><div class="step-num">2</div><div>Complete payment of <strong>₹<?=$amt?></strong> in your app</div></div>
      <div class="step"><div class="step-num">3</div><div>Enter your 12-digit UTR below to confirm instantly</div></div>
    </div>
  </div>

  <!-- QR Tab -->
  <div class="panel" id="tab-qr">
    <div class="card">
      <div class="qr-wrap"><div id="qrCanvas"></div></div>
      <p style="text-align:center;font-size:12px;color:var(--mu);margin-top:12px">Scan with any UPI app</p>
    </div>
  </div>

  <!-- Manual Tab -->
  <div class="panel" id="tab-manual">
    <div class="card">
      <div style="font-size:13px;color:var(--mu);margin-bottom:14px">Transfer ₹<strong style="color:var(--gld)"><?=$amt?></strong> to the following UPI ID manually:</div>
      <div class="upi-box" style="margin-bottom:0">
        <div>
          <div class="upi-label">UPI ID</div>
          <div class="upi-value"><?=$upi?></div>
        </div>
        <button class="copy-btn" onclick="copyUpi()"><i class="fas fa-copy"></i> Copy</button>
      </div>
    </div>
  </div>

  <!-- Verify UTR -->
  <div class="verify-section">
    <div class="verify-title"><i class="fas fa-shield-halved" style="color:var(--pri)"></i> Confirm Payment</div>
    <p style="font-size:13px;color:var(--mu);margin-bottom:12px">After paying, enter your 12-digit UTR / Reference Number from the payment confirmation SMS.</p>
    <input type="text" id="utrInput" class="utr-input" maxlength="12" placeholder="Enter 12-digit UTR" oninput="this.value=this.value.replace(/\D/g,'').slice(0,12)" inputmode="numeric">
    <button class="submit-btn" id="verifyBtn" onclick="verifyUtr()"><i class="fas fa-check-circle"></i> Confirm Payment</button>
    <div id="verifyResult"></div>
  </div>
</div>

<script>
const TXN_ID = '<?= e($txn_id) ?>';
const SITE_URL = '<?= SITE_URL ?>';
const REDIRECT_URL = '<?= e($txn['redirect_url'] ?? '') ?>';
const TOTAL_SECS = 900; // 15 minutes
let remaining = TOTAL_SECS;
let pollInterval;

// QR Code
new QRCode(document.getElementById('qrCanvas'), {
    text: '<?= addslashes($upiStr) ?>',
    width: 200, height: 200,
    colorDark: '#000', colorLight: '#fff',
    correctLevel: QRCode.CorrectLevel.M
});

// Timer
function startTimer() {
    const fill = document.getElementById('timerFill');
    const val  = document.getElementById('timerVal');
    const iv   = setInterval(() => {
        remaining--;
        if (remaining <= 0) {
            clearInterval(iv);
            val.textContent = '00:00';
            fill.style.width = '0%';
            fill.style.background = 'var(--red)';
            return;
        }
        const m = Math.floor(remaining / 60);
        const s = remaining % 60;
        val.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
        fill.style.width = (remaining / TOTAL_SECS * 100) + '%';
        if (remaining < 180) fill.style.background = 'linear-gradient(90deg,var(--red),var(--warn))';
    }, 1000);
}

// Tabs
function showTab(name, btn) {
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

// Copy UPI
function copyUpi() {
    navigator.clipboard.writeText('<?= addslashes($txn['upi_address'] ?? '') ?>');
    const btn = event.currentTarget;
    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
    btn.style.background = 'rgba(34,197,94,0.2)';
    btn.style.borderColor = 'rgba(34,197,94,0.4)';
    btn.style.color = 'var(--ok)';
    setTimeout(() => { btn.innerHTML = '<i class="fas fa-copy"></i> Copy'; btn.style = ''; }, 2000);
}

// Verify UTR
async function verifyUtr() {
    const utr = document.getElementById('utrInput').value.trim();
    const resultDiv = document.getElementById('verifyResult');
    if (utr.length !== 12 || !/^\d{12}$/.test(utr)) {
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> Enter a valid 12-digit UTR</div>';
        return;
    }
    const btn = document.getElementById('verifyBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    resultDiv.innerHTML = '';
    try {
        const res = await fetch(SITE_URL + '/api/verify_utr.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({txn_id: TXN_ID, utr: utr})
        }).then(r => r.json());
        if (res.success) {
            onPaymentSuccess();
        } else {
            resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-circle-exclamation"></i> ' + (res.message || 'Verification failed') + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Payment';
        }
    } catch(e) {
        resultDiv.innerHTML = '<div class="alert alert-danger"><i class="fas fa-wifi"></i> Network error. Please try again.</div>';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Payment';
    }
}

// Payment success handler
function onPaymentSuccess() {
    clearInterval(pollInterval);
    document.getElementById('successOverlay').classList.add('show');
    setTimeout(() => {
        if (REDIRECT_URL) {
            window.location.href = REDIRECT_URL + '?txn_id=' + TXN_ID + '&status=success';
        }
    }, 3000);
}

// Auto-poll for payment status
function startPolling() {
    pollInterval = setInterval(async () => {
        try {
            const res = await fetch(SITE_URL + '/api/check_payment.php?txn_id=' + TXN_ID).then(r => r.json());
            if (res.success && res.status === 'success') onPaymentSuccess();
            if (res.success && res.status === 'expired') clearInterval(pollInterval);
        } catch(e) {}
    }, 5000);
}

// Theme toggle
const themeBtn = document.getElementById('themeBtn');
const themeIcon = document.getElementById('themeIcon');
const savedTheme = localStorage.getItem('gp_theme') || 'dark';
document.documentElement.setAttribute('data-theme', savedTheme);
themeIcon.className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
themeBtn.onclick = () => {
    const n = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', n);
    localStorage.setItem('gp_theme', n);
    themeIcon.className = n === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
};

startTimer();
startPolling();
</script>
</body></html>
