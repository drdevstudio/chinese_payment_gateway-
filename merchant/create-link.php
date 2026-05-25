<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/merchant_layout.php';
$m = merchantAuth();
$merchant = fbGet('merchants/' . $m['merchant_id']);
$settings = getSettings();
merchantLayoutHead('Create Payment Link');
?>
<body>
<?php merchantLayoutBody($merchant,'create-link'); merchantLayoutTopbar('Create Payment Link',$merchant);?>
<div class="page-body" style="max-width:680px">

<!-- Manual create form -->
<div class="card" style="margin-bottom:16px">
  <div class="card-title"><i class="fas fa-link" style="color:var(--primary)"></i> Create Payment Link</div>
  <p style="font-size:13px;color:var(--text-muted);margin-bottom:20px">Generate a payment link to share with your customer via WhatsApp, email, or embed on your site.</p>

  <div class="form-group">
    <label class="form-label">Amount (₹) <small style="color:var(--text-muted)">Min: ₹<?=e($settings['min_amount'])?> | Max: ₹<?=e($settings['max_amount'])?></small></label>
    <input type="number" id="amt" class="form-control" placeholder="Enter amount" min="<?=e($settings['min_amount'])?>" max="<?=e($settings['max_amount'])?>" step="0.01">
  </div>
  <div class="form-group">
    <label class="form-label">Your Order / Reference No <small style="color:var(--text-muted)">(optional — your internal order ID)</small></label>
    <input type="text" id="orderNo" class="form-control" placeholder="e.g. ORDER_12345 (auto-generated if blank)">
  </div>
  <div class="form-group">
    <label class="form-label">Redirect URL <small style="color:var(--text-muted)">(after payment, optional)</small></label>
    <input type="url" id="rurl" class="form-control" placeholder="https://yoursite.com/success">
  </div>
  <div id="alertBox"></div>
  <button class="btn btn-primary" style="width:100%" onclick="create()"><i class="fas fa-link"></i> Generate Payment Link</button>
</div>

<!-- Result -->
<div id="resultCard" style="display:none" class="card" style="margin-bottom:16px">
  <div class="card-title"><i class="fas fa-check-circle" style="color:var(--success)"></i> Payment Link Created!</div>
  <div class="form-group">
    <label class="form-label">Payment URL (share this with customer)</label>
    <div style="display:flex;gap:8px">
      <input type="text" id="payUrl" class="form-control" readonly>
      <button class="btn btn-outline" onclick="copyUrl()" title="Copy"><i class="fas fa-copy"></i></button>
    </div>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px" class="txn-info-grid">
    <div>
      <label class="form-label">Ghora Pay TXN ID</label>
      <input type="text" id="txnId" class="form-control" readonly>
    </div>
    <div>
      <label class="form-label">Your Order No</label>
      <input type="text" id="txnOrderNo" class="form-control" readonly>
    </div>
  </div>
  <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
    <i class="fas fa-info-circle" style="color:var(--info)"></i>
    Once the customer pays, your balance is credited automatically (minus commission).
    Use <strong>TXN ID</strong> or <strong>Order No</strong> to check payment status later.
  </p>
  <div style="display:flex;gap:8px">
    <a id="openLink" href="#" target="_blank" class="btn btn-primary" style="flex:1;justify-content:center"><i class="fas fa-external-link-alt"></i> Open Payment Page</a>
    <button class="btn btn-outline" onclick="resetForm()"><i class="fas fa-plus"></i> New</button>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     EMBED CODE — Fixed: includes api_key + signature
     ═══════════════════════════════════════════════════ -->
<div class="card">
  <div class="card-title"><i class="fas fa-code" style="color:var(--gold)"></i> Embed in Your Site</div>
  <div style="background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:var(--danger)">
    <i class="fas fa-triangle-exclamation"></i>
    <strong>Server-side only!</strong> Never put your API Key or signature generation in browser JavaScript. Use PHP/Node.js on your server.
  </div>
  <div style="background:var(--surface2);border-radius:8px;padding:14px;font-size:12px;font-family:'DM Mono',monospace;color:var(--text);overflow-x:auto;line-height:1.9">
<span style="color:var(--text-muted)">// PHP — put this in your checkout.php (server-side)</span><br>
<span style="color:var(--gold)">$merchant_id</span>       = '<span style="color:var(--primary)"><?=e($merchant['merchant_id'])?></span>';<br>
<span style="color:var(--gold)">$api_key</span>           = '<span style="color:var(--warning)">YOUR_API_KEY</span>'; <span style="color:var(--text-muted)">// from dashboard</span><br>
<span style="color:var(--gold)">$amount</span>            = number_format($orderAmount, 2, '.', '');<br>
<span style="color:var(--gold)">$merchant_order_no</span> = $yourOrderId; <span style="color:var(--text-muted)">// your DB order ID</span><br>
<span style="color:var(--gold)">$redirect_url</span>      = 'https://yoursite.com/payment-success';<br><br>
<span style="color:var(--text-muted)">// ① Build signature (MUST match this exact order)</span><br>
<span style="color:var(--gold)">$signature</span> = <span style="color:var(--success)">md5</span>(<br>
&nbsp;&nbsp;<span style="color:var(--gold)">$merchant_id</span> . <span style="color:var(--gold)">$amount</span> . <span style="color:var(--gold)">$merchant_order_no</span> .<br>
&nbsp;&nbsp;<span style="color:var(--gold)">$api_key</span>    . <span style="color:var(--gold)">$redirect_url</span><br>
);<br><br>
<span style="color:var(--text-muted)">// ② Call Ghora Pay API</span><br>
<span style="color:var(--gold)">$ch</span> = curl_init('<?=SITE_URL?>/api/create_payment.php');<br>
curl_setopt_array(<span style="color:var(--gold)">$ch</span>, [<br>
&nbsp;&nbsp;CURLOPT_POST           =&gt; true,<br>
&nbsp;&nbsp;CURLOPT_RETURNTRANSFER =&gt; true,<br>
&nbsp;&nbsp;CURLOPT_POSTFIELDS     =&gt; http_build_query([<br>
&nbsp;&nbsp;&nbsp;&nbsp;'merchant_id'       =&gt; <span style="color:var(--gold)">$merchant_id</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;'amount'            =&gt; <span style="color:var(--gold)">$amount</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;'merchant_order_no' =&gt; <span style="color:var(--gold)">$merchant_order_no</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;'redirect_url'      =&gt; <span style="color:var(--gold)">$redirect_url</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;'signature'         =&gt; <span style="color:var(--gold)">$signature</span>,<span style="color:var(--text-muted)"> // ← required!</span><br>
&nbsp;&nbsp;])<br>
]);<br>
<span style="color:var(--gold)">$res</span> = json_decode(curl_exec(<span style="color:var(--gold)">$ch</span>), true);<br><br>
<span style="color:var(--text-muted)">// ③ Redirect customer to payment page</span><br>
if (<span style="color:var(--gold)">$res</span>['success']) {<br>
&nbsp;&nbsp;header('Location: ' . <span style="color:var(--gold)">$res</span>['pay_url']);<br>
&nbsp;&nbsp;<span style="color:var(--text-muted)">// $res also contains: txn_id, merchant_order_no, amount, upi</span><br>
} else {<br>
&nbsp;&nbsp;echo 'Error: ' . <span style="color:var(--gold)">$res</span>['message'];<br>
}
  </div>

  <!-- Signature checker tool -->
  <div style="margin-top:16px;padding:16px;background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.15);border-radius:10px">
    <div style="font-size:13px;font-weight:600;margin-bottom:10px;color:var(--primary)"><i class="fas fa-flask"></i> Test Signature Generator</div>
    <p style="font-size:12px;color:var(--text-muted);margin-bottom:12px">Use this to verify your code generates the correct signature. Never use this in production.</p>
    <div style="display:grid;gap:8px">
      <input id="tAmount"   class="form-control" placeholder="Amount (e.g. 500.00)" style="font-size:13px">
      <input id="tOrderNo"  class="form-control" placeholder="merchant_order_no" style="font-size:13px">
      <input id="tRedirect" class="form-control" placeholder="redirect_url (or leave blank)" style="font-size:13px">
    </div>
    <button class="btn btn-outline" style="width:100%;margin-top:10px" onclick="testSig()"><i class="fas fa-calculator"></i> Generate Test Signature</button>
    <div id="sigResult" style="margin-top:10px;display:none">
      <label class="form-label">Expected Signature (md5)</label>
      <div style="display:flex;gap:8px">
        <input id="sigOut" class="form-control" readonly style="font-family:'DM Mono',monospace;font-size:12px">
        <button class="btn btn-outline" onclick="copyText(document.getElementById('sigOut').value,this)" title="Copy"><i class="fas fa-copy"></i></button>
      </div>
      <p style="font-size:11px;color:var(--text-muted);margin-top:6px" id="sigFormula"></p>
    </div>
  </div>
</div>
</div>

<script>
const SU='<?=SITE_URL?>';
const MID='<?=e($merchant['merchant_id'])?>';
const AKEY='<?=e($merchant['api_key']??'')?>';

async function create() {
  const amt    = document.getElementById('amt').value;
  const rurl   = document.getElementById('rurl').value.trim();
  const order  = document.getElementById('orderNo').value.trim();
  if (!amt || parseFloat(amt) <= 0) { showAlert('Enter a valid amount', 'danger'); return; }

  // Generate signature on frontend (for manual tool only — in production do this server-side!)
  const fmtAmt = parseFloat(amt).toFixed(2);
  const ordNo  = order || ('ORD_' + Date.now());
  const sigStr = MID + fmtAmt + ordNo + AKEY + rurl;
  const sig    = await md5Hash(sigStr);

  const body = new URLSearchParams({
    merchant_id: MID, amount: fmtAmt,
    merchant_order_no: ordNo,
    redirect_url: rurl, signature: sig
  });

  const res = await fetch(SU + '/api/create_payment.php', { method: 'POST', body }).then(r => r.json());

  if (res.success) {
    document.getElementById('payUrl').value      = res.pay_url;
    document.getElementById('txnId').value       = res.txn_id;
    document.getElementById('txnOrderNo').value  = res.merchant_order_no || ordNo;
    document.getElementById('openLink').href     = res.pay_url;
    document.getElementById('resultCard').style.display = 'block';
    document.getElementById('alertBox').innerHTML = '';
    document.getElementById('resultCard').scrollIntoView({ behavior: 'smooth' });
  } else {
    showAlert(res.message, 'danger');
  }
}

function copyUrl() {
  copyText(document.getElementById('payUrl').value, document.querySelector('[onclick="copyUrl()"]'));
}
function copyText(txt, btn) {
  navigator.clipboard.writeText(txt);
  const orig = btn.innerHTML;
  btn.innerHTML = '<i class="fas fa-check" style="color:var(--success)"></i>';
  setTimeout(() => btn.innerHTML = orig, 1500);
}
function resetForm() {
  document.getElementById('resultCard').style.display = 'none';
  document.getElementById('alertBox').innerHTML = '';
  document.getElementById('amt').value = '';
  document.getElementById('orderNo').value = '';
  document.getElementById('rurl').value = '';
}
function showAlert(msg, type) {
  document.getElementById('alertBox').innerHTML =
    `<div class="alert alert-${type}"><i class="fas fa-${type==='danger'?'circle-exclamation':'check-circle'}"></i> ${msg}</div>`;
}

// Test signature generator
async function testSig() {
  const amt  = document.getElementById('tAmount').value.trim();
  const ord  = document.getElementById('tOrderNo').value.trim();
  const rurl = document.getElementById('tRedirect').value.trim();
  if (!amt) { alert('Enter an amount'); return; }
  const fmtAmt = parseFloat(amt).toFixed(2);
  const str  = MID + fmtAmt + ord + AKEY + rurl;
  const hash = await md5Hash(str);
  document.getElementById('sigOut').value = hash;
  document.getElementById('sigFormula').textContent = 'md5("' + MID + '" + "' + fmtAmt + '" + "' + ord + '" + "[api_key]" + "' + rurl + '")';
  document.getElementById('sigResult').style.display = 'block';
}

// Pure JS MD5 implementation
async function md5Hash(str) {
  // Use SubtleCrypto if available for other hashes, but MD5 isn't supported natively
  // Using a simple MD5 implementation
  function md5(s) {
    function L(k,d){return(k<<d)|(k>>>(32-d))}
    function K(G,k){var I,d,F,H,x;F=(G&2147483648);H=(k&2147483648);I=(G&1073741824);d=(k&1073741824);x=(G&1073741823)+(k&1073741823);if(I&d){return(x^2147483648^F^H)}if(I|d){if(x&1073741824){return(x^3221225472^F^H)}else{return(x^1073741824^F^H)}}else{return(x^F^H)}}
    function r(d,F,k,H,G,I,x){d=K(d,K(K(F&k|~F&H,G),x));return K(L(d,I),F)}
    function q(d,F,k,H,G,I,x){d=K(d,K(K(F&H|k&~H,G),x));return K(L(d,I),F)}
    function p(d,F,k,H,G,I,x){d=K(d,K(K(F^k^H,G),x));return K(L(d,I),F)}
    function n(d,F,k,H,G,I,x){d=K(d,K(K(k^(F|~H),G),x));return K(L(d,I),F)}
    function u(G){var F='';var k;for(var d=3;d>=0;d--){k=(G>>>(d*8))&255;F+=('0'+k.toString(16)).slice(-2)}return F}
    var C=[];var D,E,A,z,y;var B=[];s=unescape(encodeURIComponent(s));for(var i=0;i<s.length;i++){C[i>>2]|=s.charCodeAt(i)<<((i%4)*8)}C[C.length]=0x80;while(C.length%16!=14)C[C.length]=0;C[C.length]=s.length*8;C[C.length]=0;
    var a=1732584193,b=-271733879,c=-1732584194,f=271733878;
    for(var i=0;i<C.length;i+=16){D=a;E=b;A=c;z=f;
    a=r(a,b,c,f,C[i+0],7,-680876936);f=r(f,a,b,c,C[i+1],12,-389564586);c=r(c,f,a,b,C[i+2],17,606105819);b=r(b,c,f,a,C[i+3],22,-1044525330);a=r(a,b,c,f,C[i+4],7,-176418897);f=r(f,a,b,c,C[i+5],12,1200080426);c=r(c,f,a,b,C[i+6],17,-1473231341);b=r(b,c,f,a,C[i+7],22,-45705983);a=r(a,b,c,f,C[i+8],7,1770035416);f=r(f,a,b,c,C[i+9],12,-1958414417);c=r(c,f,a,b,C[i+10],17,-42063);b=r(b,c,f,a,C[i+11],22,-1990404162);a=r(a,b,c,f,C[i+12],7,1804603682);f=r(f,a,b,c,C[i+13],12,-40341101);c=r(c,f,a,b,C[i+14],17,-1502002290);b=r(b,c,f,a,C[i+15],22,1236535329);
    a=q(a,b,c,f,C[i+1],5,-165796510);f=q(f,a,b,c,C[i+6],9,-1069501632);c=q(c,f,a,b,C[i+11],14,643717713);b=q(b,c,f,a,C[i+0],20,-373897302);a=q(a,b,c,f,C[i+5],5,-701558691);f=q(f,a,b,c,C[i+10],9,38016083);c=q(c,f,a,b,C[i+15],14,-660478335);b=q(b,c,f,a,C[i+4],20,-405537848);a=q(a,b,c,f,C[i+9],5,568446438);f=q(f,a,b,c,C[i+14],9,-1019803690);c=q(c,f,a,b,C[i+3],14,-187363961);b=q(b,c,f,a,C[i+8],20,1163531501);a=q(a,b,c,f,C[i+13],5,-1444681467);f=q(f,a,b,c,C[i+2],9,-51403784);c=q(c,f,a,b,C[i+7],14,1735328473);b=q(b,c,f,a,C[i+12],20,-1926607734);
    a=p(a,b,c,f,C[i+5],4,-378558);f=p(f,a,b,c,C[i+8],11,-2022574463);c=p(c,f,a,b,C[i+11],16,1839030562);b=p(b,c,f,a,C[i+14],23,-35309556);a=p(a,b,c,f,C[i+1],4,-1530992060);f=p(f,a,b,c,C[i+4],11,1272893353);c=p(c,f,a,b,C[i+7],16,-155497632);b=p(b,c,f,a,C[i+10],23,-1094730640);a=p(a,b,c,f,C[i+13],4,681279174);f=p(f,a,b,c,C[i+0],11,-358537222);c=p(c,f,a,b,C[i+3],16,-722521979);b=p(b,c,f,a,C[i+6],23,76029189);a=p(a,b,c,f,C[i+9],4,-640364487);f=p(f,a,b,c,C[i+12],11,-421815835);c=p(c,f,a,b,C[i+15],16,530742520);b=p(b,c,f,a,C[i+2],23,-995338651);
    a=n(a,b,c,f,C[i+0],6,-198630844);f=n(f,a,b,c,C[i+7],10,1126891415);c=n(c,f,a,b,C[i+14],15,-1416354905);b=n(b,c,f,a,C[i+5],21,-57434055);a=n(a,b,c,f,C[i+12],6,1700485571);f=n(f,a,b,c,C[i+3],10,-1894986606);c=n(c,f,a,b,C[i+10],15,-1051523);b=n(b,c,f,a,C[i+1],21,-2054922799);a=n(a,b,c,f,C[i+8],6,1873313359);f=n(f,a,b,c,C[i+15],10,-30611744);c=n(c,f,a,b,C[i+6],15,-1560198380);b=n(b,c,f,a,C[i+13],21,1309151649);a=n(a,b,c,f,C[i+4],6,-145523070);f=n(f,a,b,c,C[i+11],10,-1120210379);c=n(c,f,a,b,C[i+2],15,718787259);b=n(b,c,f,a,C[i+9],21,-343485551);
    a=K(a,D);b=K(b,E);c=K(c,A);f=K(f,z)}
    return(u(a)+u(b)+u(c)+u(f)).toLowerCase();
  }
  return md5(str);
}
</script>
<style>
@media(max-width:480px){.txn-info-grid{grid-template-columns:1fr!important}}
</style>
<?php merchantLayoutFooter();?>
