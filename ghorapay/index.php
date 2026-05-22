<?php require_once __DIR__.'/config.php'; ?><!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ghora Pay — Fast UPI Payment Gateway</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Inter:wght@300;400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--bg:#0A0F1E;--surf:#111827;--surf2:#1a2235;--pri:#6366f1;--gld:#F59E0B;--tx:#e2e8f0;--mu:#64748b;--bd:rgba(255,255,255,0.07);--ok:#22c55e;--transition:.2s cubic-bezier(.4,0,.2,1);}
[data-theme=light]{--bg:#f0f4ff;--surf:#fff;--surf2:#f8fafc;--tx:#1e293b;--mu:#64748b;--bd:rgba(0,0,0,0.08);}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
html{scroll-behavior:smooth;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--tx);overflow-x:hidden;transition:background var(--transition),color var(--transition);}

/* TICKER */
.ticker{background:linear-gradient(90deg,rgba(99,102,241,0.9),rgba(245,158,11,0.9));padding:8px 0;overflow:hidden;position:relative;}
.ticker-inner{display:flex;gap:40px;animation:ticker 20s linear infinite;white-space:nowrap;}
.ticker-inner:hover{animation-play-state:paused;}
@keyframes ticker{from{transform:translateX(0)}to{transform:translateX(-50%)}}
.tick-item{display:flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:#fff;letter-spacing:.04em;}

/* NAVBAR */
nav{position:sticky;top:0;z-index:50;background:rgba(10,15,30,0.85);backdrop-filter:blur(20px);border-bottom:1px solid var(--bd);padding:0 40px;height:64px;display:flex;align-items:center;justify-content:space-between;}
[data-theme=light] nav{background:rgba(240,244,255,0.9);}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--tx);}
.nav-icon{width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#F59E0B);border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;box-shadow:0 4px 12px rgba(99,102,241,0.4);}
.nav-title{font-family:'Syne',sans-serif;font-size:18px;font-weight:800;}
.nav-actions{display:flex;align-items:center;gap:12px;}
.nav-link{font-size:14px;font-weight:500;color:var(--mu);text-decoration:none;transition:color var(--transition);}
.nav-link:hover{color:var(--tx);}
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:8px;font-size:14px;font-weight:700;border:none;cursor:pointer;font-family:inherit;text-decoration:none;transition:all var(--transition);}
.btn-primary{background:var(--pri);color:#fff;box-shadow:0 4px 12px rgba(99,102,241,0.3);}
.btn-primary:hover{filter:brightness(1.1);transform:translateY(-1px);}
.btn-outline{background:transparent;border:1px solid var(--bd);color:var(--tx);}
.btn-outline:hover{border-color:var(--pri);color:var(--pri);}
.theme-btn{width:36px;height:36px;background:var(--surf);border:1px solid var(--bd);border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--tx);font-size:13px;}

/* HERO */
.hero{position:relative;min-height:90vh;display:flex;align-items:center;justify-content:center;text-align:center;padding:60px 20px;overflow:hidden;}
.hero-orb{position:absolute;border-radius:50%;filter:blur(100px);pointer-events:none;}
.ho1{width:500px;height:500px;background:rgba(99,102,241,0.2);top:-100px;left:-150px;animation:drift1 8s ease-in-out infinite;}
.ho2{width:400px;height:400px;background:rgba(245,158,11,0.15);bottom:-80px;right:-100px;animation:drift2 10s ease-in-out infinite;}
.ho3{width:300px;height:300px;background:rgba(34,197,94,0.1);top:50%;left:50%;transform:translate(-50%,-50%);animation:drift3 12s ease-in-out infinite;}
@keyframes drift1{0%,100%{transform:translate(0,0)}50%{transform:translate(40px,20px)}}
@keyframes drift2{0%,100%{transform:translate(0,0)}50%{transform:translate(-30px,-20px)}}
@keyframes drift3{0%,100%{transform:translate(-50%,-50%)}50%{transform:translate(-45%,-55%)}}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);padding:6px 14px;border-radius:999px;font-size:12px;font-weight:600;color:var(--pri);letter-spacing:.06em;margin-bottom:20px;animation:fadeUp .6s ease both;}
.hero h1{font-family:'Syne',sans-serif;font-size:clamp(42px,7vw,80px);font-weight:800;line-height:1.05;margin-bottom:20px;animation:fadeUp .6s .1s ease both;}
.hero h1 span{background:linear-gradient(135deg,#6366f1,#F59E0B);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{font-size:clamp(16px,2.5vw,20px);color:var(--mu);max-width:540px;margin:0 auto 36px;line-height:1.6;font-weight:300;animation:fadeUp .6s .2s ease both;}
.hero-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;animation:fadeUp .6s .3s ease both;}
.hero-actions .btn{padding:14px 28px;font-size:15px;}

/* STATS STRIP */
.stats-strip{background:var(--surf);border-top:1px solid var(--bd);border-bottom:1px solid var(--bd);padding:32px 40px;}
.stats-inner{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:24px;text-align:center;}
.stat-item{opacity:0;transform:translateY(20px);transition:all .6s ease;}
.stat-item.visible{opacity:1;transform:translateY(0);}
.stat-num{font-family:'Syne',sans-serif;font-size:36px;font-weight:800;background:linear-gradient(135deg,var(--pri),var(--gld));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.stat-lbl{font-size:13px;color:var(--mu);margin-top:4px;font-weight:500;}

/* FEATURES */
.section{padding:80px 40px;max-width:1100px;margin:0 auto;}
.section-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);padding:5px 14px;border-radius:999px;font-size:12px;font-weight:600;color:var(--gld);letter-spacing:.06em;margin-bottom:14px;}
.section h2{font-family:'Syne',sans-serif;font-size:clamp(28px,4vw,44px);font-weight:800;margin-bottom:14px;line-height:1.1;}
.section p.lead{font-size:16px;color:var(--mu);max-width:520px;line-height:1.7;margin-bottom:48px;}
.features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;}
.feat-card{background:var(--surf);border:1px solid var(--bd);border-radius:16px;padding:24px;transition:transform var(--transition),border-color var(--transition),box-shadow var(--transition);opacity:0;transform:translateY(30px);}
.feat-card.visible{opacity:1;transform:translateY(0);}
.feat-card:hover{transform:translateY(-4px);border-color:rgba(99,102,241,0.3);box-shadow:0 12px 32px rgba(99,102,241,0.1);}
.feat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;margin-bottom:16px;}
.feat-title{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:8px;}
.feat-text{font-size:13px;color:var(--mu);line-height:1.7;}

/* HOW IT WORKS */
.steps-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;counter-reset:steps;}
.step-card{background:var(--surf);border:1px solid var(--bd);border-radius:16px;padding:28px 24px;position:relative;overflow:hidden;opacity:0;transform:translateY(30px);transition:all .6s ease;}
.step-card.visible{opacity:1;transform:translateY(0);}
.step-num{position:absolute;top:16px;right:20px;font-family:'DM Mono',monospace;font-size:48px;font-weight:700;color:rgba(99,102,241,0.07);line-height:1;}
.step-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:14px;background:rgba(99,102,241,0.12);color:var(--pri);}
.step-title{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:6px;}
.step-text{font-size:13px;color:var(--mu);line-height:1.6;}

/* CTA */
.cta-section{padding:80px 40px;text-align:center;}
.cta-inner{background:linear-gradient(135deg,rgba(99,102,241,0.15),rgba(245,158,11,0.1));border:1px solid rgba(99,102,241,0.2);border-radius:24px;padding:60px 40px;max-width:700px;margin:0 auto;position:relative;overflow:hidden;}
.cta-inner::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse at 50% 0%,rgba(99,102,241,0.1),transparent 70%);}
.cta-inner h2{font-family:'Syne',sans-serif;font-size:clamp(28px,4vw,40px);font-weight:800;margin-bottom:14px;position:relative;}
.cta-inner p{font-size:16px;color:var(--mu);margin-bottom:28px;position:relative;}
.cta-inner .btn{position:relative;padding:14px 32px;font-size:15px;}

/* FOOTER */
footer{background:var(--surf);border-top:1px solid var(--bd);padding:40px;text-align:center;}
.foot-brand{display:flex;align-items:center;gap:8px;justify-content:center;margin-bottom:16px;}
.foot-text{font-size:13px;color:var(--mu);}

/* ANIMATIONS */
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:768px){
  nav{padding:0 16px;}
  .nav-link{display:none;}
  .section{padding:60px 20px;}
  .hero{min-height:80vh;}
  .stats-strip{padding:24px 20px;}
  .cta-section{padding:40px 20px;}
  .cta-inner{padding:40px 24px;}
}
</style>
</head>
<body>

<!-- TICKER -->
<div class="ticker">
  <div class="ticker-inner">
    <?php $ticks=['⚡ Instant Settlement','🔒 Bank-grade Security','📱 All UPI Apps Supported','💰 Low Commission','🌐 API Ready','🤖 Auto SMS Verification','⚡ 99.9% Uptime','🔒 End-to-End Encrypted']; $all=array_merge($ticks,$ticks); foreach($all as $t): ?><span class="tick-item"><i class="fas fa-horse"></i> <?=e($t)?></span><?php endforeach; ?>
  </div>
</div>

<!-- NAVBAR -->
<nav>
  <a class="nav-brand" href="<?=SITE_URL?>">
    <div class="nav-icon"><i class="fas fa-horse"></i></div>
    <span class="nav-title">Ghora Pay</span>
  </a>
  <div class="nav-actions">
    <a href="#features" class="nav-link">Features</a>
    <a href="#how" class="nav-link">How It Works</a>
    <button class="theme-btn" id="tb"><i class="fas fa-moon" id="ti"></i></button>
    <a href="<?=SITE_URL?>/login.php" class="btn btn-primary"><i class="fas fa-right-from-bracket"></i> Login</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-orb ho1"></div>
  <div class="hero-orb ho2"></div>
  <div class="hero-orb ho3"></div>
  <div style="position:relative;z-index:1">
    <div class="hero-badge"><i class="fas fa-bolt"></i> INDIA'S FASTEST UPI GATEWAY</div>
    <h1>Accept UPI Payments<br>at <span>Lightning Speed</span></h1>
    <p>Ghora Pay powers your business with instant UPI settlements, multi-device SMS capture, and a merchant dashboard that gives you full control.</p>
    <div class="hero-actions">
      <a href="<?=SITE_URL?>/login.php" class="btn btn-primary"><i class="fas fa-store"></i> Merchant Login</a>
      <a href="#how" class="btn btn-outline"><i class="fas fa-play-circle"></i> See How It Works</a>
    </div>
  </div>
</section>

<!-- STATS -->
<div class="stats-strip">
  <div class="stats-inner">
    <div class="stat-item"><div class="stat-num" data-target="99.9" data-suffix="%">0</div><div class="stat-lbl">Success Rate</div></div>
    <div class="stat-item"><div class="stat-num" data-target="50000" data-suffix="+">0</div><div class="stat-lbl">Transactions Daily</div></div>
    <div class="stat-item"><div class="stat-num" data-target="3" data-suffix="s">0</div><div class="stat-lbl">Avg Settlement Time</div></div>
    <div class="stat-item"><div class="stat-num" data-target="500" data-suffix="+">0</div><div class="stat-lbl">Active Merchants</div></div>
    <div class="stat-item"><div class="stat-num" data-target="2" data-suffix="%">0</div><div class="stat-lbl">Low Commission</div></div>
  </div>
</div>

<!-- FEATURES -->
<section class="section" id="features">
  <div class="section-badge"><i class="fas fa-star"></i> FEATURES</div>
  <h2>Everything you need<br>to accept payments</h2>
  <p class="lead">Built for merchants who need reliability, speed, and complete control over their payment flow.</p>
  <div class="features-grid">
    <?php $feats=[
      ['fas fa-bolt','rgba(245,158,11,0.15)','#F59E0B','Instant Settlement','Payments are auto-confirmed the moment our Android app captures the bank SMS — zero manual work.'],
      ['fas fa-mobile-alt','rgba(99,102,241,0.15)','#6366f1','All UPI Apps','QR code, PhonePe, Google Pay, Paytm, and any UPI app — all deep-link integrations built-in.'],
      ['fas fa-shield-halved','rgba(34,197,94,0.15)','#22c55e','Two-Factor Auth','Merchant accounts protected by Google Authenticator 2FA and bcrypt-hashed passwords.'],
      ['fas fa-qrcode','rgba(6,182,212,0.15)','#06b6d4','Multi-UPI Routing','Add multiple UPI IDs with daily limits. System auto-selects available UPI to balance load.'],
      ['fas fa-code','rgba(239,68,68,0.15)','#ef4444','Simple API','One API call creates a payment link. Webhooks and redirect URLs for seamless integration.'],
      ['fas fa-chart-bar','rgba(245,158,11,0.15)','#F59E0B','Full Dashboard','Real-time transaction history, balance tracking, withdrawal management — all in one panel.'],
    ];
    foreach($feats as [$ic,$bg,$col,$ti,$tx]): ?>
    <div class="feat-card">
      <div class="feat-icon" style="background:<?=$bg?>;color:<?=$col?>"><i class="<?=$ic?>"></i></div>
      <div class="feat-title"><?=$ti?></div>
      <div class="feat-text"><?=$tx?></div>
    </div>
    <?php endforeach;?>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section" id="how" style="background:var(--surf);border-radius:24px;padding:60px 40px;max-width:1100px;margin:0 auto 40px;">
  <div style="text-align:center;margin-bottom:48px">
    <div class="section-badge" style="margin:0 auto 14px"><i class="fas fa-diagram-project"></i> HOW IT WORKS</div>
    <h2>From checkout to wallet<br>in under 5 seconds</h2>
  </div>
  <div class="steps-grid">
    <?php $steps=[
      ['fas fa-link','Create Link','Merchant calls the API with amount. Ghora Pay selects an available UPI ID and returns a payment URL.'],
      ['fas fa-qrcode','Customer Pays','Customer scans the QR or taps a UPI app button. Pays through their preferred app.'],
      ['fas fa-sms','SMS Captured','Android app on the UPI device reads the bank confirmation SMS — extracts UTR and amount automatically.'],
      ['fas fa-check-circle','Auto Confirmed','Server matches the SMS to the pending transaction. Payment marked success. Merchant balance credited instantly.'],
    ];
    foreach($steps as $i=>[$ic,$ti,$tx]): ?>
    <div class="step-card">
      <div class="step-num"><?=str_pad($i+1,2,'0',STR_PAD_LEFT)?></div>
      <div class="step-icon"><i class="<?=$ic?>"></i></div>
      <div class="step-title"><?=$ti?></div>
      <div class="step-text"><?=$tx?></div>
    </div>
    <?php endforeach;?>
  </div>
</section>

<!-- CTA -->
<div class="cta-section">
  <div class="cta-inner">
    <h2>Ready to accept<br>instant UPI payments?</h2>
    <p>Join hundreds of merchants already using Ghora Pay to grow their business.</p>
    <a href="<?=SITE_URL?>/login.php" class="btn btn-primary"><i class="fas fa-horse"></i> Get Started — Login</a>
  </div>
</div>

<!-- FOOTER -->
<footer>
  <div class="foot-brand">
    <div class="nav-icon"><i class="fas fa-horse"></i></div>
    <span style="font-family:'Syne',sans-serif;font-size:18px;font-weight:800">Ghora Pay</span>
  </div>
  <p class="foot-text">Fast, secure UPI payment gateway. Built for Indian merchants.</p>
  <p class="foot-text" style="margin-top:8px">© <?=date('Y')?> Ghora Pay. All rights reserved.</p>
</footer>

<script>
// Theme
const tb=document.getElementById('tb'),ti=document.getElementById('ti'),html=document.documentElement;
const sv=localStorage.getItem('gp_theme')||'dark';
html.setAttribute('data-theme',sv);ti.className=sv==='dark'?'fas fa-moon':'fas fa-sun';
tb.onclick=()=>{const n=html.getAttribute('data-theme')==='dark'?'light':'dark';html.setAttribute('data-theme',n);localStorage.setItem('gp_theme',n);ti.className=n==='dark'?'fas fa-moon':'fas fa-sun';};

// Scroll animations
const obs=new IntersectionObserver(entries=>entries.forEach(e=>{if(e.isIntersecting){e.target.classList.add('visible');obs.unobserve(e.target);}}),{threshold:.15});
document.querySelectorAll('.feat-card,.step-card,.stat-item').forEach(el=>obs.observe(el));

// Count-up
function countUp(el,target,suffix,decimals=0){
  const dur=1800,step=16;let current=0,steps=dur/step;
  const inc=target/steps;
  const timer=setInterval(()=>{
    current=Math.min(current+inc,target);
    el.textContent=(decimals?current.toFixed(decimals):Math.floor(current))+suffix;
    if(current>=target)clearInterval(timer);
  },step);
}
const statsObs=new IntersectionObserver(entries=>entries.forEach(e=>{
  if(e.isIntersecting){
    const el=e.target.querySelector('.stat-num');
    if(el&&!el.dataset.counted){
      el.dataset.counted='1';
      const t=parseFloat(el.dataset.target);
      const s=el.dataset.suffix||'';
      countUp(el,t,s,t%1!==0?1:0);
    }
  }
}),{threshold:.3});
document.querySelectorAll('.stat-item').forEach(el=>statsObs.observe(el));
</script>
</body></html>
