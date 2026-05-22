<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/includes/totp.php';
if (!empty($_SESSION['merchant_id'])) {
    header('Location: '.SITE_URL.'/merchant/dashboard.php'); exit;
}
$err = e($_GET['err'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Merchant Login — Ghora Pay</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#0A0F1E;--surface:#111827;--primary:#6366f1;--gold:#F59E0B;--text:#e2e8f0;--text-muted:#64748b;--border:rgba(255,255,255,0.08);--danger:#ef4444;--success:#22c55e;}
[data-theme=light]{--bg:#f1f5f9;--surface:#fff;--text:#1e293b;--text-muted:#64748b;--border:rgba(0,0,0,0.08);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;position:relative;overflow:hidden;}
.orb{position:fixed;border-radius:50%;filter:blur(80px);pointer-events:none;opacity:0.5;}
.orb1{width:400px;height:400px;background:radial-gradient(circle,rgba(99,102,241,0.3),transparent);top:-100px;left:-100px;}
.orb2{width:300px;height:300px;background:radial-gradient(circle,rgba(245,158,11,0.2),transparent);bottom:-80px;right:-80px;}
.theme-btn{position:fixed;top:20px;right:20px;width:38px;height:38px;background:var(--surface);border:1px solid var(--border);border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text);font-size:14px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:20px;padding:40px;width:100%;max-width:420px;position:relative;z-index:1;box-shadow:0 24px 64px rgba(0,0,0,0.4);}
.brand{text-align:center;margin-bottom:32px;}
.brand-icon{width:56px;height:56px;background:linear-gradient(135deg,#6366f1,#F59E0B);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;color:#fff;margin:0 auto 12px;box-shadow:0 8px 20px rgba(99,102,241,0.4);}
.brand h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;}
.brand p{font-size:13px;color:var(--text-muted);margin-top:4px;}
.form-group{margin-bottom:16px;}
.form-label{display:block;font-size:13px;font-weight:500;color:var(--text-muted);margin-bottom:7px;}
.input-wrap{position:relative;}
.input-icon{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:14px;pointer-events:none;}
.form-control{width:100%;padding:12px 14px 12px 40px;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:10px;color:var(--text);font-size:14px;outline:none;transition:border-color .15s,box-shadow .15s;font-family:'Inter',sans-serif;}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(99,102,241,0.2);}
.form-control::placeholder{color:var(--text-muted);}
.eye-btn{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:14px;padding:0;}
.btn-submit{width:100%;padding:13px;background:linear-gradient(135deg,#6366f1,#818cf8);border:none;border-radius:10px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;font-family:'Inter',sans-serif;transition:all .15s;margin-top:8px;box-shadow:0 6px 20px rgba(99,102,241,0.35);}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 10px 28px rgba(99,102,241,0.45);}
.btn-submit:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.alert{padding:12px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.alert-danger{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger);}
.alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:var(--success);}
.divider{text-align:center;font-size:12px;color:var(--text-muted);margin:20px 0;}
.admin-link{display:block;text-align:center;font-size:12px;color:var(--text-muted);text-decoration:none;margin-top:16px;}
.admin-link:hover{color:var(--primary);}
/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:100;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .2s;}
.modal-overlay.open{opacity:1;pointer-events:auto;}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:32px;width:100%;max-width:400px;transform:scale(.94);transition:transform .2s;max-height:90vh;overflow-y:auto;}
.modal-overlay.open .modal{transform:scale(1);}
.modal-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;margin-bottom:6px;}
.modal-sub{font-size:13px;color:var(--text-muted);margin-bottom:24px;line-height:1.5;}
.secret-box{background:rgba(99,102,241,0.1);border:1px solid rgba(99,102,241,0.25);border-radius:8px;padding:12px 14px;font-family:'DM Mono',monospace;font-size:14px;color:var(--primary);letter-spacing:.1em;text-align:center;margin-bottom:16px;word-break:break-all;}
.qr-img{display:block;margin:0 auto 16px;border-radius:10px;border:3px solid var(--primary);}
.otp-inputs{display:flex;gap:8px;justify-content:center;margin-bottom:16px;}
.otp-input{width:44px;height:48px;text-align:center;font-size:20px;font-weight:700;font-family:'DM Mono',monospace;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:8px;color:var(--text);outline:none;transition:border-color .15s;}
.otp-input:focus{border-color:var(--primary);box-shadow:0 0 0 2px rgba(99,102,241,0.2);}
.spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,0.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
</style>
</head>
<body>
<div class="orb orb1"></div>
<div class="orb orb2"></div>
<button class="theme-btn" id="themeBtn"><i class="fas fa-moon" id="themeIcon"></i></button>

<div class="card">
  <div class="brand">
    <div class="brand-icon"><i class="fas fa-horse"></i></div>
    <h1><?= SITE_NAME ?></h1>
    <p>Merchant Login</p>
  </div>

  <div id="alertBox"></div>
  <?php if ($err === 'suspended'): ?>
  <div class="alert alert-danger"><i class="fas fa-ban"></i> Your account has been suspended.</div>
  <?php endif; ?>

  <div id="loginForm">
    <?= csrfField() ?>
    <div class="form-group">
      <label class="form-label">Merchant ID</label>
      <div class="input-wrap">
        <i class="fas fa-id-badge input-icon"></i>
        <input type="text" class="form-control" id="merchantId" placeholder="M00000000" autocomplete="username" spellcheck="false">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <div class="input-wrap">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" class="form-control" id="password" placeholder="Enter password" autocomplete="current-password">
        <button type="button" class="eye-btn" id="eyeBtn"><i class="fas fa-eye"></i></button>
      </div>
    </div>
    <button class="btn-submit" id="loginBtn" onclick="doLogin()">
      <i class="fas fa-arrow-right-to-bracket"></i> Login
    </button>
  </div>

  <a href="<?= SITE_URL ?>/admin/login.php" class="admin-link"><i class="fas fa-shield-halved"></i> Admin Panel</a>
</div>

<!-- 2FA VERIFY MODAL -->
<div class="modal-overlay" id="totpModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-shield-halved" style="color:var(--primary)"></i> Two-Factor Auth</div>
    <div class="modal-sub">Enter the 6-digit code from your Google Authenticator app.</div>
    <div class="otp-inputs" id="otpInputs">
      <input class="otp-input" maxlength="1" data-idx="0" inputmode="numeric" pattern="[0-9]">
<input class="otp-input" maxlength="1" data-idx="1" inputmode="numeric" pattern="[0-9]">
<input class="otp-input" maxlength="1" data-idx="2" inputmode="numeric" pattern="[0-9]">
<input class="otp-input" maxlength="1" data-idx="3" inputmode="numeric" pattern="[0-9]">
<input class="otp-input" maxlength="1" data-idx="4" inputmode="numeric" pattern="[0-9]">
<input class="otp-input" maxlength="1" data-idx="5" inputmode="numeric" pattern="[0-9]">
    </div>
    <button class="btn-submit" id="totpBtn" onclick="verifyTotp()"><i class="fas fa-check"></i> Verify</button>
  </div>
</div>

<!-- FIRST LOGIN: SETUP 2FA MODAL -->
<div class="modal-overlay" id="setupModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-qrcode" style="color:var(--gold)"></i> Setup 2FA & Password</div>
    <div class="modal-sub">Scan this QR code with Google Authenticator, then set your new password.</div>
    <img id="qrImage" class="qr-img" width="180" height="180" alt="QR Code">
    <div class="form-group">
      <label class="form-label">Secret Key (manual entry)</label>
      <div class="secret-box" id="secretKey"></div>
    </div>
    <div class="form-group">
      <label class="form-label">New Password (min 8 chars)</label>
      <div class="input-wrap">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" class="form-control" id="newPassword" placeholder="New password">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Confirm Password</label>
      <div class="input-wrap">
        <i class="fas fa-lock input-icon"></i>
        <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm password">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Authenticator Code (6 digits)</label>
      <div class="otp-inputs" id="setupOtpInputs">
        <input class="otp-input" maxlength="1" data-idx="0">
        <input class="otp-input" maxlength="1" data-idx="1">
        <input class="otp-input" maxlength="1" data-idx="2">
        <input class="otp-input" maxlength="1" data-idx="3">
        <input class="otp-input" maxlength="1" data-idx="4">
        <input class="otp-input" maxlength="1" data-idx="5">
      </div>
    </div>
    <button class="btn-submit" id="setupBtn" onclick="completeSetup()"><i class="fas fa-shield-check"></i> Complete Setup</button>
  </div>
</div>

<script>
const CSRF = '<?= csrfToken() ?>';
let pendingMerchantId = '';

// Theme
const themeBtn=document.getElementById('themeBtn'),themeIcon=document.getElementById('themeIcon');
const saved=localStorage.getItem('gp_theme')||'dark';
document.documentElement.setAttribute('data-theme',saved);
themeIcon.className=saved==='dark'?'fas fa-moon':'fas fa-sun';
themeBtn.onclick=()=>{
  const n=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
  document.documentElement.setAttribute('data-theme',n);
  localStorage.setItem('gp_theme',n);
  themeIcon.className=n==='dark'?'fas fa-moon':'fas fa-sun';
};

// Eye toggle
document.getElementById('eyeBtn').onclick=function(){
  const inp=document.getElementById('password');
  const show=inp.type==='password'; inp.type=show?'text':'password';
  this.innerHTML=show?'<i class="fas fa-eye-slash"></i>':'<i class="fas fa-eye"></i>';
};

// OTP input navigation
function setupOtpNav(containerId){
  const inputs=document.querySelectorAll('#'+containerId+' .otp-input');
  inputs.forEach((inp,i)=>{
    inp.addEventListener('input',e=>{
      const val=e.target.value.replace(/\D/,'');
      e.target.value=val;
      if(val&&i<inputs.length-1) inputs[i+1].focus();
    });
    inp.addEventListener('keydown',e=>{
      if(e.key==='Backspace'&&!inp.value&&i>0) inputs[i-1].focus();
    });
    inp.addEventListener('paste',e=>{
      const data=e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);
      if(data.length===6){
        e.preventDefault();
        [...data].forEach((c,j)=>{if(inputs[j])inputs[j].value=c;});
        inputs[5].focus();
      }
    });
  });
}
setupOtpNav('otpInputs');
setupOtpNav('setupOtpInputs');

function getOtp(containerId){
  return [...document.querySelectorAll('#'+containerId+' .otp-input')].map(i=>i.value).join('');
}
function clearOtp(containerId){
  document.querySelectorAll('#'+containerId+' .otp-input').forEach(i=>i.value='');
}

function showAlert(msg,type='danger'){
  document.getElementById('alertBox').innerHTML=`<div class="alert alert-${type}"><i class="fas fa-${type==='danger'?'circle-exclamation':'check-circle'}"></i> ${msg}</div>`;
}

async function post(url,data){
  data.csrf_token=CSRF;
  const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data)});
  return r.json();
}

async function doLogin(){
  const mid=document.getElementById('merchantId').value.trim();
  const pass=document.getElementById('password').value;
  if(!mid||!pass){showAlert('Please fill all fields');return;}
  const btn=document.getElementById('loginBtn');
  btn.innerHTML='<span class="spinner"></span> Logging in...';btn.disabled=true;
  try{
    const res=await post('<?= SITE_URL ?>/api/merchant/auth.php',{action:'login',merchant_id:mid,password:pass});
    if(res.success){
      pendingMerchantId=res.merchant_id||mid;
      if(res.first_login){
        await requestSetup();
        document.getElementById('setupModal').classList.add('open');
      } else if(res.totp_required){
        clearOtp('otpInputs');
        document.getElementById('totpModal').classList.add('open');
        setTimeout(()=>document.querySelector('#otpInputs .otp-input').focus(),100);
      } else {
        window.location.href=res.redirect;
      }
    } else showAlert(res.message);
  } catch(e){showAlert('Network error');}
  btn.innerHTML='<i class="fas fa-arrow-right-to-bracket"></i> Login';btn.disabled=false;
}

async function requestSetup(){
  const res=await post('<?= SITE_URL ?>/api/merchant/auth.php',{action:'setup_2fa',merchant_id:pendingMerchantId});
  if(res.success){
    document.getElementById('qrImage').src=res.qr_url;
    document.getElementById('secretKey').textContent=res.secret;
  }
}

async function verifyTotp(){
  const code=getOtp('otpInputs');
  if(code.length!==6){showAlert('Enter 6-digit code');return;}
  const btn=document.getElementById('totpBtn');
  btn.innerHTML='<span class="spinner"></span> Verifying...';btn.disabled=true;
  const res=await post('<?= SITE_URL ?>/api/merchant/auth.php',{action:'verify_2fa_login',merchant_id:pendingMerchantId,code});
  if(res.success) window.location.href=res.redirect;
  else{showAlert(res.message);clearOtp('otpInputs');document.querySelector('#otpInputs .otp-input').focus();}
  btn.innerHTML='<i class="fas fa-check"></i> Verify';btn.disabled=false;
}

async function completeSetup(){
  const newPass=document.getElementById('newPassword').value;
  const confPass=document.getElementById('confirmPassword').value;
  const code=getOtp('setupOtpInputs');
  if(newPass.length<8){showAlert('Password must be at least 8 characters');return;}
  if(newPass!==confPass){showAlert('Passwords do not match');return;}
  if(code.length!==6){showAlert('Enter 6-digit authenticator code');return;}
  const btn=document.getElementById('setupBtn');
  btn.innerHTML='<span class="spinner"></span> Setting up...';btn.disabled=true;
  const res=await post('<?= SITE_URL ?>/api/merchant/auth.php',{action:'verify_2fa_setup',merchant_id:pendingMerchantId,code,new_password:newPass});
  if(res.success) window.location.href=res.redirect;
  else{showAlert(res.message);}
  btn.innerHTML='<i class="fas fa-shield-check"></i> Complete Setup';btn.disabled=false;
}

// Enter key
document.getElementById('password').addEventListener('keydown',e=>{if(e.key==='Enter')doLogin();});
document.getElementById('merchantId').addEventListener('keydown',e=>{if(e.key==='Enter')document.getElementById('password').focus();});
</script>
</body></html>
