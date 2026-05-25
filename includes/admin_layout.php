<?php
function adminLayoutHead(string $title = 'Admin') {
    $site = defined('SITE_URL') ? SITE_URL : '';
    echo <<<HTML
<!DOCTYPE html><html lang="en" data-theme="dark"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title} — Ghora Pay Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#060B18;--surface:#0D1425;--surface2:#111d32;--sidebar-bg:#060B18;--sidebar-w:240px;--primary:#6366f1;--gold:#F59E0B;--success:#22c55e;--danger:#ef4444;--warning:#eab308;--info:#06b6d4;--text:#e2e8f0;--text-muted:#64748b;--border:rgba(255,255,255,0.07);}
[data-theme=light]{--bg:#eef2f7;--surface:#fff;--surface2:#f8fafc;--sidebar-bg:#1e293b;--text:#1e293b;--text-muted:#64748b;--border:rgba(0,0,0,0.08);}
*{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;overflow-x:hidden;}

/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar-w);background:var(--sidebar-bg);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:200;overflow-y:auto;transition:transform .3s cubic-bezier(.4,0,.2,1);}
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:190;backdrop-filter:blur(2px);}
.sidebar-overlay.show{display:block;}
.sb-brand{padding:22px 18px 14px;display:flex;align-items:center;gap:10px;border-bottom:1px solid var(--border);}
.sb-icon{width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#F59E0B);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0;}
.sb-name{font-family:'Syne',sans-serif;font-size:17px;font-weight:800;color:#fff;}
.sb-badge{font-size:10px;background:rgba(239,68,68,0.2);color:#ef4444;padding:2px 8px;border-radius:999px;font-weight:700;letter-spacing:.06em;margin-left:4px;}
.nav{padding:12px 8px;flex:1;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:10px;color:var(--text-muted);text-decoration:none;font-size:13px;font-weight:500;transition:all .15s;margin-bottom:2px;}
.nav-item:hover,.nav-item.active{background:rgba(99,102,241,0.12);color:#fff;}
.nav-item.active{color:var(--primary);}
.nav-item i{width:18px;text-align:center;font-size:14px;}
.sb-footer{padding:16px;border-top:1px solid var(--border);}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;min-width:0;}
.topbar{padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;gap:12px;}
.topbar-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.topbar-right{display:flex;align-items:center;gap:10px;flex-shrink:0;}
.hamburger{display:none;width:36px;height:36px;background:var(--surface2);border:1px solid var(--border);border-radius:8px;cursor:pointer;align-items:center;justify-content:center;color:var(--text);font-size:15px;flex-shrink:0;}
.page-body{padding:20px;flex:1;}

/* ── CARDS ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;position:relative;overflow:hidden;}
.stat-card::before{content:'';position:absolute;top:-20px;right:-20px;width:80px;height:80px;border-radius:50%;opacity:.12;}
.stat-card.primary::before{background:var(--primary);}
.stat-card.gold::before{background:var(--gold);}
.stat-card.success::before{background:var(--success);}
.stat-card.danger::before{background:var(--danger);}
.stat-card.info::before{background:var(--info);}
.stat-card.warning::before{background:var(--warning);}
.stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:12px;}
.stat-icon.primary{background:rgba(99,102,241,0.15);color:var(--primary);}
.stat-icon.gold{background:rgba(245,158,11,0.15);color:var(--gold);}
.stat-icon.success{background:rgba(34,197,94,0.15);color:var(--success);}
.stat-icon.danger{background:rgba(239,68,68,0.15);color:var(--danger);}
.stat-icon.info{background:rgba(6,182,212,0.15);color:var(--info);}
.stat-icon.warning{background:rgba(234,179,8,0.15);color:var(--warning);}
.stat-value{font-family:'Syne',sans-serif;font-size:24px;font-weight:800;line-height:1;margin-bottom:5px;}
.stat-label{font-size:12px;color:var(--text-muted);font-weight:500;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:14px;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;}
.card-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px;}

/* ── TABLE ── */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{padding:10px 12px;text-align:left;color:var(--text-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.06em;border-bottom:1px solid var(--border);white-space:nowrap;}
td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:rgba(255,255,255,0.02);}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700;letter-spacing:.04em;}
.badge-success{background:rgba(34,197,94,0.15);color:var(--success);}
.badge-danger{background:rgba(239,68,68,0.15);color:var(--danger);}
.badge-warning{background:rgba(234,179,8,0.15);color:var(--warning);}
.badge-muted{background:rgba(100,116,139,0.15);color:var(--text-muted);}
.badge-info{background:rgba(6,182,212,0.15);color:var(--info);}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s;text-decoration:none;white-space:nowrap;}
.btn-primary{background:var(--primary);color:#fff;}
.btn-primary:hover{opacity:.88;}
.btn-success{background:var(--success);color:#fff;}
.btn-warning{background:var(--warning);color:#000;}
.btn-danger{background:var(--danger);color:#fff;}
.btn-outline{background:transparent;border:1px solid var(--border);color:var(--text);}
.btn-outline:hover{border-color:var(--primary);color:var(--primary);}
.btn-sm{padding:6px 12px;font-size:12px;}

/* ── FORM ── */
.form-control{width:100%;padding:10px 14px;background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:9px;color:var(--text);font-size:13px;outline:none;font-family:inherit;transition:border-color .15s;}
.form-control:focus{border-color:var(--primary);}
.form-group{margin-bottom:14px;}
.form-label{display:block;font-size:12px;font-weight:500;color:var(--text-muted);margin-bottom:6px;}
select.form-control option{background:var(--surface2);color:var(--text);}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:300;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(6px);opacity:0;pointer-events:none;transition:opacity .2s;padding:16px;}
.modal-overlay.open{opacity:1;pointer-events:auto;}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:18px;padding:24px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;transform:scale(.94);transition:transform .2s;}
.modal-overlay.open .modal{transform:scale(1);}
.modal-title{font-family:'Syne',sans-serif;font-size:17px;font-weight:800;margin-bottom:4px;display:flex;align-items:center;gap:8px;}
.modal-close{margin-left:auto;cursor:pointer;color:var(--text-muted);font-size:16px;padding:4px;}
.modal-close:hover{color:var(--text);}
.mono{font-family:'DM Mono',monospace;}
.alert-success{background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:var(--success);padding:10px 14px;border-radius:8px;font-size:13px;margin-top:10px;}
.alert-danger{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger);padding:10px 14px;border-radius:8px;font-size:13px;margin-top:10px;}
.tb-btn{background:var(--surface);border:1px solid var(--border);border-radius:8px;width:34px;height:34px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text);font-size:13px;}
.tb-btn:hover{border-color:var(--primary);color:var(--primary);}

/* ── RESPONSIVE ── */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;}
  .hamburger{display:flex;}
  .stats-grid{grid-template-columns:repeat(2,1fr);}
  .page-body{padding:14px;}
}
@media(max-width:480px){
  .stats-grid{grid-template-columns:1fr 1fr;}
  .card{padding:14px;}
  .topbar{padding:12px 14px;}
  .page-body{padding:10px;}
  .modal{padding:18px;}
  .topbar-title{font-size:16px;}
}
</style>
HTML;
}

function adminLayoutBody(array $admin, string $active = '') {
    $site = SITE_URL;
    $nav = [
        'dashboard'   => ['icon'=>'fa-gauge','label'=>'Dashboard'],
        'merchants'   => ['icon'=>'fa-store','label'=>'Merchants'],
        'upi'         => ['icon'=>'fa-qrcode','label'=>'UPI IDs'],
        'withdrawals' => ['icon'=>'fa-money-bill-transfer','label'=>'Withdrawals'],
        'commissions' => ['icon'=>'fa-sliders','label'=>'Settings'],
    ];
    echo '<div class="sidebar-overlay" id="sidebarOverlay"></div>';
    echo '<aside class="sidebar" id="adminSidebar"><div class="sb-brand"><div class="sb-icon"><i class="fas fa-horse"></i></div>';
    echo '<div><div class="sb-name">Ghora Pay</div></div>';
    echo '<span class="sb-badge">ADMIN</span></div><nav class="nav">';
    foreach ($nav as $key => $item) {
        $cls = $active === $key ? 'nav-item active' : 'nav-item';
        echo '<a href="'.$site.'/admin/'.$key.'.php" class="'.$cls.'"><i class="fas '.$item['icon'].'"></i> '.$item['label'].'</a>';
    }
    echo '</nav><div class="sb-footer"><a href="'.$site.'/admin/logout.php" class="nav-item" style="color:var(--danger)"><i class="fas fa-right-from-bracket"></i> Logout</a></div></aside>';
    echo '<main class="main">';
}

function adminLayoutTopbar(string $title = '') {
    $site = SITE_URL;
    echo '<div class="topbar">';
    echo '<button class="hamburger" id="adminHamburger"><i class="fas fa-bars"></i></button>';
    echo '<div class="topbar-title">'.htmlspecialchars($title, ENT_QUOTES).'</div>';
    echo '<div class="topbar-right">';
    echo '<button class="tb-btn" id="themeToggle"><i class="fas fa-moon" id="themeIcon"></i></button>';
    echo '</div></div>';
}

function adminLayoutFooter() {
    echo '</main><script>';
    // Sidebar toggle
    echo 'const _sb=document.getElementById("adminSidebar"),_ov=document.getElementById("sidebarOverlay"),_hb=document.getElementById("adminHamburger");';
    echo 'if(_hb){_hb.addEventListener("click",()=>{_sb.classList.toggle("open");_ov.classList.toggle("show");});}';
    echo 'if(_ov){_ov.addEventListener("click",()=>{_sb.classList.remove("open");_ov.classList.remove("show");});}';
    // Theme
    echo 'const t=document.getElementById("themeToggle"),i=document.getElementById("themeIcon");';
    echo 'const sv=localStorage.getItem("gp_admin_theme")||"dark";';
    echo 'document.documentElement.setAttribute("data-theme",sv);';
    echo 'if(i)i.className=sv==="dark"?"fas fa-moon":"fas fa-sun";';
    echo 'if(t)t.onclick=()=>{const n=document.documentElement.getAttribute("data-theme")==="dark"?"light":"dark";';
    echo 'document.documentElement.setAttribute("data-theme",n);localStorage.setItem("gp_admin_theme",n);';
    echo 'if(i)i.className=n==="dark"?"fas fa-moon":"fas fa-sun";};';
    echo '</script></body></html>';
}
