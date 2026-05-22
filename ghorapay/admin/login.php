<?php
require_once __DIR__.'/../config.php';
if (!empty($_SESSION['admin_username'])) {
    header('Location: '.SITE_URL.'/admin/dashboard.php'); exit;
}
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    checkLoginRate('admin_login', 5, 300);
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    // Look up admin by username (username is the Firebase key)
    $admin = fbGet('admins/' . $user);
    if (is_array($admin) && isset($admin['password']) && password_verify($pass, $admin['password'])) {
        clearLoginRate('admin_login');
        session_regenerate_id(true);
        $_SESSION['admin_username'] = $user;
        header('Location: '.SITE_URL.'/admin/dashboard.php'); exit;
    }
    $err = 'Invalid credentials';
}
?><!DOCTYPE html><html lang="en" data-theme="dark"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login — Ghora Pay</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#060B18;--surf:#0D1425;--pri:#6366f1;--gld:#F59E0B;--tx:#e2e8f0;--mu:#64748b;--bd:rgba(255,255,255,0.07);--red:#ef4444;}
[data-theme=light]{--bg:#eef2f7;--surf:#fff;--tx:#1e293b;--bd:rgba(0,0,0,0.08);}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--tx);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;overflow:hidden;}
.o{position:fixed;border-radius:50%;filter:blur(100px);pointer-events:none;}
.o1{width:350px;height:350px;background:rgba(99,102,241,0.18);top:-80px;right:-80px;}
.o2{width:250px;height:250px;background:rgba(245,158,11,0.12);bottom:-60px;left:-60px;}
.card{background:var(--surf);border:1px solid var(--bd);border-radius:20px;padding:40px;width:100%;max-width:400px;z-index:1;box-shadow:0 24px 64px rgba(0,0,0,0.5);}
.brand{text-align:center;margin-bottom:28px;}
.bi{width:52px;height:52px;background:linear-gradient(135deg,#6366f1,#F59E0B);border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin:0 auto 10px;box-shadow:0 8px 20px rgba(99,102,241,0.35);}
.bh{font-family:'Syne',sans-serif;font-size:20px;font-weight:800;}
.adm{display:inline-flex;align-items:center;gap:5px;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.25);color:#ef4444;font-size:11px;font-weight:700;padding:3px 10px;border-radius:999px;text-transform:uppercase;letter-spacing:.08em;margin-top:8px;}
.fg{margin-bottom:16px;}
.fl{display:block;font-size:13px;font-weight:500;color:var(--mu);margin-bottom:7px;}
.iw{position:relative;}
.ii{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--mu);font-size:14px;pointer-events:none;}
.fc{width:100%;padding:12px 14px 12px 40px;background:rgba(255,255,255,0.04);border:1px solid var(--bd);border-radius:10px;color:var(--tx);font-size:14px;outline:none;transition:border-color .15s;font-family:inherit;}
.fc:focus{border-color:var(--pri);box-shadow:0 0 0 3px rgba(99,102,241,0.2);}
.fc::placeholder{color:var(--mu);}
.eye{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--mu);cursor:pointer;font-size:14px;padding:0;}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#6366f1,#818cf8);border:none;border-radius:10px;color:#fff;font-size:15px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;margin-top:6px;box-shadow:0 6px 20px rgba(99,102,241,0.3);}
.btn:hover{transform:translateY(-1px);}
.alert{padding:11px 14px;border-radius:8px;font-size:13px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--red);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.bl{display:block;text-align:center;font-size:12px;color:var(--mu);text-decoration:none;margin-top:14px;}
.bl:hover{color:var(--pri);}
.tb{position:fixed;top:20px;right:20px;width:36px;height:36px;background:var(--surf);border:1px solid var(--bd);border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--tx);font-size:13px;}
</style></head><body>
<div class="o o1"></div><div class="o o2"></div>
<button class="tb" id="tb"><i class="fas fa-moon" id="ti"></i></button>
<div class="card">
  <div class="brand"><div class="bi"><i class="fas fa-horse"></i></div>
  <div class="bh">Ghora Pay</div>
  <div class="adm"><i class="fas fa-shield-halved"></i> Admin Panel</div></div>
  <?php if($err):?><div class="alert"><i class="fas fa-circle-exclamation"></i> <?=e($err)?></div><?php endif;?>
  <form method="POST" autocomplete="off">
    <?=csrfField()?>
    <div class="fg"><label class="fl">Username</label>
      <div class="iw"><i class="fas fa-user ii"></i><input type="text" name="username" class="fc" placeholder="admin" required autocomplete="off"></div></div>
    <div class="fg"><label class="fl">Password</label>
      <div class="iw"><i class="fas fa-lock ii"></i>
        <input type="password" name="password" id="pf" class="fc" placeholder="Password" required>
        <button type="button" class="eye" onclick="var s=document.getElementById('pf');var show=s.type==='password';s.type=show?'text':'password';this.innerHTML=show?'<i class=fas\ fa-eye-slash></i>':'<i class=fas\ fa-eye></i>'"><i class="fas fa-eye"></i></button>
      </div></div>
    <button type="submit" class="btn"><i class="fas fa-right-from-bracket"></i> Login</button>
  </form>
  <a href="<?=SITE_URL?>/login.php" class="bl"><i class="fas fa-arrow-left"></i> Merchant Login</a>
</div>
<script>
const tb=document.getElementById('tb'),ti=document.getElementById('ti');
const sv=localStorage.getItem('gp_admin_theme')||'dark';
document.documentElement.setAttribute('data-theme',sv);ti.className=sv==='dark'?'fas fa-moon':'fas fa-sun';
tb.onclick=()=>{const n=document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';document.documentElement.setAttribute('data-theme',n);localStorage.setItem('gp_admin_theme',n);ti.className=n==='dark'?'fas fa-moon':'fas fa-sun';};
</script>
</body></html>
