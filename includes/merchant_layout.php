<?php
// includes/merchant_layout.php
// Usage: include this file, call merchantLayoutHead($title) then merchantLayoutBody($merchant, $active)

function merchantLayoutHead($title = 'Dashboard') {
    echo <<<HTML
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title} — Ghora Pay Merchant</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Mono:wght@400;500&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
    --bg: #0A0F1E;
    --surface: #111827;
    --surface2: #1a2235;
    --sidebar-bg: #0d1425;
    --sidebar-w: 260px;
    --primary: #6366f1;
    --primary-glow: rgba(99,102,241,0.3);
    --gold: #F59E0B;
    --gold-glow: rgba(245,158,11,0.25);
    --success: #22c55e;
    --danger: #ef4444;
    --warning: #eab308;
    --info: #06b6d4;
    --text: #e2e8f0;
    --text-muted: #64748b;
    --text-dim: #94a3b8;
    --border: rgba(255,255,255,0.07);
    --radius: 12px;
    --radius-lg: 18px;
    --shadow: 0 4px 24px rgba(0,0,0,0.4);
    --transition: 0.2s cubic-bezier(0.4,0,0.2,1);
}
[data-theme="light"] {
    --bg: #f1f5f9;
    --surface: #ffffff;
    --surface2: #f8fafc;
    --sidebar-bg: #1e1b4b;
    --text: #1e293b;
    --text-muted: #64748b;
    --text-dim: #94a3b8;
    --border: rgba(0,0,0,0.08);
    --shadow: 0 4px 24px rgba(0,0,0,0.1);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }
body {
    font-family: 'Inter', sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    overflow-x: hidden;
}

/* ===== SIDEBAR ===== */
.sidebar {
    width: var(--sidebar-w);
    background: var(--sidebar-bg);
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(99,102,241,0.15);
    z-index: 100;
    transition: transform var(--transition);
    overflow: hidden;
}
.sidebar::before {
    content: '';
    position: absolute;
    top: -80px; left: -80px;
    width: 260px; height: 260px;
    background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
    pointer-events: none;
}
.sidebar-brand {
    padding: 24px 20px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}
.brand-icon {
    width: 40px; height: 40px;
    background: linear-gradient(135deg, var(--primary), var(--gold));
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff;
    box-shadow: 0 4px 12px var(--primary-glow);
    flex-shrink: 0;
}
.brand-text { font-family: 'Syne', sans-serif; }
.brand-name { font-size: 18px; font-weight: 800; color: #fff; line-height: 1; }
.brand-sub { font-size: 10px; color: var(--text-muted); letter-spacing: 0.1em; text-transform: uppercase; margin-top: 2px; }

.sidebar-nav { flex: 1; overflow-y: auto; padding: 16px 12px; }
.sidebar-nav::-webkit-scrollbar { width: 3px; }
.sidebar-nav::-webkit-scrollbar-track { background: transparent; }
.sidebar-nav::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 99px; }

.nav-section { margin-bottom: 8px; }
.nav-section-title {
    font-size: 10px; font-weight: 600; letter-spacing: 0.12em;
    text-transform: uppercase; color: var(--text-muted);
    padding: 0 8px; margin-bottom: 6px; margin-top: 16px;
}
.nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    color: var(--text-dim);
    text-decoration: none;
    font-size: 14px; font-weight: 500;
    transition: all var(--transition);
    margin-bottom: 2px;
    position: relative;
    overflow: hidden;
}
.nav-item::before {
    content: ''; position: absolute;
    inset: 0; background: var(--primary);
    opacity: 0; transition: opacity var(--transition);
    border-radius: 8px;
}
.nav-item:hover { color: var(--text); }
.nav-item:hover::before { opacity: 0.08; }
.nav-item.active {
    color: #fff;
    background: linear-gradient(135deg, rgba(99,102,241,0.25), rgba(99,102,241,0.1));
    border: 1px solid rgba(99,102,241,0.3);
}
.nav-item.active::after {
    content: ''; position: absolute;
    right: 0; top: 50%; transform: translateY(-50%);
    width: 3px; height: 60%; background: var(--primary);
    border-radius: 3px 0 0 3px;
}
.nav-item i { width: 18px; text-align: center; font-size: 15px; position: relative; z-index: 1; }
.nav-item span { position: relative; z-index: 1; }

.sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid var(--border);
    flex-shrink: 0;
}
.merchant-info {
    display: flex; align-items: center; gap: 10px;
    padding: 10px;
    background: rgba(255,255,255,0.04);
    border-radius: 10px;
    margin-bottom: 10px;
}
.merchant-avatar {
    width: 34px; height: 34px;
    background: linear-gradient(135deg, var(--primary), var(--gold));
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-family: 'Syne', sans-serif;
    font-weight: 800; font-size: 13px; color: #fff;
    flex-shrink: 0;
}
.merchant-name { font-size: 13px; font-weight: 600; color: var(--text); }
.merchant-id { font-size: 10px; color: var(--text-muted); font-family: 'DM Mono', monospace; }

/* ===== MAIN ===== */
.main-content {
    margin-left: var(--sidebar-w);
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}
.topbar {
    height: 64px;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center;
    padding: 0 24px;
    gap: 16px;
    position: sticky; top: 0; z-index: 50;
    backdrop-filter: blur(20px);
}
.topbar-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; flex: 1; }
.topbar-actions { display: flex; align-items: center; gap: 12px; }
.theme-toggle {
    width: 36px; height: 36px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--text); font-size: 14px;
    transition: all var(--transition);
}
.theme-toggle:hover { border-color: var(--primary); color: var(--primary); }
.hamburger {
    display: none; width: 36px; height: 36px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; cursor: pointer;
    align-items: center; justify-content: center;
    color: var(--text); font-size: 16px;
}

.page-body { flex: 1; padding: 24px; }

/* ===== CARDS ===== */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 24px;
    box-shadow: var(--shadow);
}
.card-title {
    font-family: 'Syne', sans-serif;
    font-size: 16px; font-weight: 700;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px;
    position: relative; overflow: hidden;
    box-shadow: var(--shadow);
    transition: transform var(--transition), box-shadow var(--transition);
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,0,0,0.3); }
.stat-card::before {
    content: ''; position: absolute;
    top: -30px; right: -30px;
    width: 100px; height: 100px;
    border-radius: 50%;
    opacity: 0.1;
}
.stat-card.primary::before { background: var(--primary); }
.stat-card.gold::before { background: var(--gold); }
.stat-card.success::before { background: var(--success); }
.stat-card.danger::before { background: var(--danger); }

.stat-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 16px;
}
.stat-icon.primary { background: rgba(99,102,241,0.15); color: var(--primary); }
.stat-icon.gold { background: rgba(245,158,11,0.15); color: var(--gold); }
.stat-icon.success { background: rgba(34,197,94,0.15); color: var(--success); }
.stat-icon.danger { background: rgba(239,68,68,0.15); color: var(--danger); }
.stat-value { font-family: 'Syne', sans-serif; font-size: 26px; font-weight: 800; line-height: 1; }
.stat-label { font-size: 12px; color: var(--text-muted); margin-top: 6px; text-transform: uppercase; letter-spacing: 0.08em; }

/* ===== TABLE ===== */
.table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; font-size: 14px; }
thead th {
    background: var(--surface2);
    padding: 12px 16px;
    text-align: left;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
}
tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    vertical-align: middle;
}
tbody tr:last-child td { border-bottom: none; }
tbody tr:hover td { background: rgba(255,255,255,0.02); }
.mono { font-family: 'DM Mono', monospace; font-size: 13px; }

/* ===== BADGES ===== */
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600; letter-spacing: 0.04em;
}
.badge-success { background: rgba(34,197,94,0.15); color: var(--success); }
.badge-danger { background: rgba(239,68,68,0.15); color: var(--danger); }
.badge-warning { background: rgba(234,179,8,0.15); color: var(--warning); }
.badge-info { background: rgba(6,182,212,0.15); color: var(--info); }
.badge-muted { background: rgba(100,116,139,0.15); color: var(--text-muted); }

/* ===== FORM ===== */
.form-group { margin-bottom: 18px; }
.form-label { display: block; font-size: 13px; font-weight: 500; color: var(--text-dim); margin-bottom: 8px; }
.form-control {
    width: 100%; padding: 11px 14px;
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 8px; color: var(--text);
    font-size: 14px; font-family: 'Inter', sans-serif;
    transition: border-color var(--transition), box-shadow var(--transition);
    outline: none;
}
.form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px var(--primary-glow); }
.form-control::placeholder { color: var(--text-muted); }

/* ===== BUTTONS ===== */
.btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 8px;
    font-size: 14px; font-weight: 600;
    border: none; cursor: pointer;
    transition: all var(--transition);
    text-decoration: none;
    font-family: 'Inter', sans-serif;
}
.btn-primary { background: var(--primary); color: #fff; box-shadow: 0 4px 12px var(--primary-glow); }
.btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); }
.btn-gold { background: var(--gold); color: #000; box-shadow: 0 4px 12px var(--gold-glow); }
.btn-gold:hover { filter: brightness(1.1); transform: translateY(-1px); }
.btn-success { background: var(--success); color: #fff; }
.btn-danger { background: var(--danger); color: #fff; }
.btn-outline {
    background: transparent; color: var(--text);
    border: 1px solid var(--border);
}
.btn-outline:hover { border-color: var(--primary); color: var(--primary); }
.btn-sm { padding: 6px 14px; font-size: 12px; }

/* ===== ALERT ===== */
.alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
.alert-success { background: rgba(34,197,94,0.12); border: 1px solid rgba(34,197,94,0.25); color: var(--success); }
.alert-danger { background: rgba(239,68,68,0.12); border: 1px solid rgba(239,68,68,0.25); color: var(--danger); }
.alert-warning { background: rgba(234,179,8,0.12); border: 1px solid rgba(234,179,8,0.25); color: var(--warning); }
.alert-info { background: rgba(6,182,212,0.12); border: 1px solid rgba(6,182,212,0.25); color: var(--info); }

/* ===== MODAL ===== */
.modal-overlay {
    position: fixed; inset: 0; background: rgba(0,0,0,0.7);
    z-index: 1000; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(4px);
    opacity: 0; pointer-events: none;
    transition: opacity var(--transition);
}
.modal-overlay.open { opacity: 1; pointer-events: auto; }
.modal {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 28px;
    width: 100%;
    max-width: 480px;
    transform: scale(0.95) translateY(20px);
    transition: transform var(--transition);
    max-height: 90vh;
    overflow-y: auto;
}
.modal-overlay.open .modal { transform: scale(1) translateY(0); }
.modal-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
.modal-close { cursor: pointer; color: var(--text-muted); font-size: 18px; }
.modal-close:hover { color: var(--text); }

/* ===== GRID ===== */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

/* ===== SIDEBAR OVERLAY FOR MOBILE ===== */
.sidebar-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,0.6); z-index: 99;
}

@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); }
    .sidebar.open { transform: translateX(0); }
    .sidebar-overlay.show { display: block; }
    .main-content { margin-left: 0; }
    .hamburger { display: flex; }
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .grid-2 { grid-template-columns: 1fr; }
    .page-body { padding: 14px; }
    .topbar { padding: 0 14px; gap: 10px; }
    .topbar-title { font-size: 15px; }
    table { font-size: 12px; }
    th, td { padding: 9px 10px; }
    .card-header { flex-direction: column; align-items: flex-start; }
    .modal { padding: 20px; }
    .modal-title { font-size: 16px; }
}
@media (max-width: 480px) {
    .stats-grid { grid-template-columns: 1fr 1fr; }
    .card { padding: 14px; }
    .topbar { padding: 0 12px; }
    .page-body { padding: 10px; }
    .btn { padding: 8px 12px; font-size: 13px; }
}
</style>
HTML;
}

function merchantLayoutBody($merchant, $active = 'dashboard') {
    $initial = strtoupper(substr($merchant['name'], 0, 1));
    $nav = [
        'dashboard'    => ['icon' => 'fa-gauge',       'label' => 'Dashboard',    'url' => '/merchant/dashboard.php'],
        'transactions' => ['icon' => 'fa-receipt',     'label' => 'Transactions', 'url' => '/merchant/transactions.php'],
        'create-link'  => ['icon' => 'fa-link',        'label' => 'Create Link',  'url' => '/merchant/create-link.php'],
        'withdraw'     => ['icon' => 'fa-money-bill-transfer', 'label' => 'Withdraw', 'url' => '/merchant/withdraw.php'],
        'profile'      => ['icon' => 'fa-user-shield', 'label' => 'Profile',      'url' => '/merchant/profile.php'],
    ];
    echo '<div class="sidebar-overlay" id="sidebarOverlay"></div>';
    echo '<aside class="sidebar" id="sidebar">';
    echo '<div class="sidebar-brand">';
    echo '<div class="brand-icon"><i class="fas fa-horse"></i></div>';
    echo '<div class="brand-text"><div class="brand-name">Ghora Pay</div><div class="brand-sub">Merchant Panel</div></div>';
    echo '</div>';
    echo '<nav class="sidebar-nav">';
    echo '<div class="nav-section-title">Navigation</div>';
    foreach ($nav as $key => $item) {
        $cls = $active === $key ? ' active' : '';
        echo "<a href='" . SITE_URL . $item['url'] . "' class='nav-item{$cls}'><i class='fas {$item['icon']}'></i><span>{$item['label']}</span></a>";
    }
    echo '</nav>';
    echo '<div class="sidebar-footer">';
    echo "<div class='merchant-info'><div class='merchant-avatar'>{$initial}</div><div><div class='merchant-name'>" . htmlspecialchars($merchant['name']) . "</div><div class='merchant-id'>" . $merchant['merchant_id'] . "</div></div></div>";
    echo "<a href='" . SITE_URL . "/logout.php' class='nav-item' style='color:var(--danger)'><i class='fas fa-right-from-bracket'></i><span>Logout</span></a>";
    echo '</div></aside>';
    echo '<div class="main-content">';
}

function merchantLayoutTopbar($title, $merchant) {
    $theme_icon = 'fa-moon';
    echo <<<HTML
<div class="topbar">
    <button class="hamburger" id="hamburgerBtn"><i class="fas fa-bars"></i></button>
    <div class="topbar-title">{$title}</div>
    <div class="topbar-actions">
        <button class="theme-toggle" id="themeToggle" title="Toggle theme"><i class="fas {$theme_icon}" id="themeIcon"></i></button>
        <div style="font-size:13px;color:var(--text-muted);">Balance: <span style="color:var(--gold);font-weight:700;font-family:'DM Mono',monospace">₹{$merchant['balance']}</span></div>
    </div>
</div>
HTML;
}

function merchantLayoutFooter() {
    echo <<<HTML
</div><!-- end main-content -->
<script>
// Sidebar toggle
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('sidebarOverlay');
const hamburger = document.getElementById('hamburgerBtn');
if (hamburger) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
    });
}
if (overlay) overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
});

// Theme toggle
const html = document.documentElement;
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');
const savedTheme = localStorage.getItem('gp_theme') || 'dark';
html.setAttribute('data-theme', savedTheme);
if (themeIcon) themeIcon.className = savedTheme === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
if (themeToggle) themeToggle.addEventListener('click', () => {
    const cur = html.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    html.setAttribute('data-theme', next);
    localStorage.setItem('gp_theme', next);
    themeIcon.className = next === 'dark' ? 'fas fa-moon' : 'fas fa-sun';
});
</script>
</body></html>
HTML;
}
