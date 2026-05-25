<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admin_layout.php';
adminAuth();
adminLayoutHead('Merchants');
?>
<body>
<?php adminLayoutBody([],'merchants'); adminLayoutTopbar('Merchants');?>
<div class="page-body">
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-store" style="color:var(--primary)"></i> Merchants</div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="text" id="searchBox" class="form-control" style="max-width:200px" placeholder="Search name / ID..." oninput="loadMerchants()">
      <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="fas fa-plus"></i> Add</button>
    </div>
  </div>
  <div class="table-wrap"><table id="merchantTable">
    <thead><tr><th>Merchant ID</th><th>Name</th><th>Domain</th><th>Balance</th><th>Status</th><th>2FA</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody id="merchantBody"><tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">Loading...</td></tr></tbody>
  </table></div>
</div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-store-plus"></i> Add Merchant <span class="modal-close" onclick="closeModal('addModal')"><i class="fas fa-times"></i></span></div>
    <div class="form-group" style="margin-top:14px"><label class="form-label">Business Name</label><input type="text" id="addName" class="form-control" placeholder="Business name"></div>
    <div class="form-group"><label class="form-label">Domain (optional)</label><input type="text" id="addDomain" class="form-control" placeholder="yoursite.com"></div>
    <div id="addAlert"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="submitAdd()"><i class="fas fa-check"></i> Create</button>
      <button class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Edit Modal (full editable) -->
<div class="modal-overlay" id="editModal">
  <div class="modal" style="max-width:560px">
    <div class="modal-title"><i class="fas fa-pen"></i> Edit Merchant <span class="modal-close" onclick="closeModal('editModal')"><i class="fas fa-times"></i></span></div>
    <input type="hidden" id="editId">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px">
      <div class="form-group" style="margin-bottom:0"><label class="form-label">Name</label><input type="text" id="editName" class="form-control"></div>
      <div class="form-group" style="margin-bottom:0"><label class="form-label">Domain</label><input type="text" id="editDomain" class="form-control"></div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Status</label>
        <select id="editStatus" class="form-control">
          <option value="live">Live</option><option value="suspended">Suspended</option><option value="deleted">Deleted</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Balance (₹) <small style="color:var(--text-muted)">direct set</small></label>
        <input type="number" id="editBalance" class="form-control" step="0.01" min="0" placeholder="0.00">
      </div>
    </div>
    <div style="margin-top:12px">
      <label class="form-label">Balance Adjustment <small style="color:var(--text-muted)">(add or subtract — leave 0 to keep current)</small></label>
      <div style="display:flex;gap:8px;align-items:center">
        <select id="balanceOp" class="form-control" style="max-width:100px">
          <option value="add">+ Add</option>
          <option value="sub">− Deduct</option>
        </select>
        <input type="number" id="balanceAdj" class="form-control" step="0.01" min="0" placeholder="Amount" value="0">
      </div>
    </div>
    <div style="margin-top:12px">
      <label class="form-label">API Key <small style="color:var(--text-muted)">(read-only — use Regen to change)</small></label>
      <div style="display:flex;gap:8px">
        <input type="text" id="editApiKey" class="form-control mono" style="font-size:11px" readonly>
        <button class="btn btn-outline btn-sm" onclick="copyField('editApiKey',this)" title="Copy"><i class="fas fa-copy"></i></button>
        <button class="btn btn-warning btn-sm" onclick="regenApiKey()" title="Regenerate"><i class="fas fa-rotate"></i></button>
      </div>
    </div>
    <div style="margin-top:12px">
      <label class="form-label">Commission Override (%) <small style="color:var(--text-muted)">(blank = use global)</small></label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
        <div>
          <input type="number" id="editPayIn" class="form-control" step="0.01" min="0" max="100" placeholder="Pay-in %">
          <div style="font-size:11px;color:var(--text-muted);margin-top:3px">Pay-in commission</div>
        </div>
        <div>
          <input type="number" id="editPayOut" class="form-control" step="0.01" min="0" max="100" placeholder="Pay-out %">
          <div style="font-size:11px;color:var(--text-muted);margin-top:3px">Pay-out commission</div>
        </div>
      </div>
    </div>
    <div id="editAlert"></div>
    <div style="display:flex;gap:8px;margin-top:16px;flex-wrap:wrap">
      <button class="btn btn-primary" style="flex:1" onclick="submitEdit()"><i class="fas fa-floppy-disk"></i> Save Changes</button>
      <button class="btn btn-warning btn-sm" onclick="resetPwd()"><i class="fas fa-key"></i> Reset Pass</button>
      <button class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Transactions Modal -->
<div class="modal-overlay" id="txnModal">
  <div class="modal" style="max-width:820px">
    <div class="modal-title"><i class="fas fa-receipt"></i> Transactions — <span id="txnMerchantName"></span> <span class="modal-close" onclick="closeModal('txnModal')"><i class="fas fa-times"></i></span></div>
    <div class="table-wrap" style="margin-top:14px"><table>
      <thead><tr><th>TXN ID</th><th>Order</th><th>Amount</th><th>UTR</th><th>UPI</th><th>Status</th><th>Date</th></tr></thead>
      <tbody id="txnBody"><tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">Loading...</td></tr></tbody>
    </table></div>
    <button class="btn btn-outline" style="margin-top:14px" onclick="closeModal('txnModal')">Close</button>
  </div>
</div>

<script>
const CSRF = '<?=csrfToken()?>';
const SITE = '<?=SITE_URL?>';

async function loadMerchants() {
    const q = document.getElementById('searchBox').value;
    const data = await fetch(`${SITE}/api/admin/merchants.php?action=list&q=${encodeURIComponent(q)}`).then(r=>r.json());
    const sc = {live:'badge-success',suspended:'badge-warning',deleted:'badge-muted'};
    const html = (data.data || []).map(m => `<tr>
      <td class="mono" style="font-size:11px;color:var(--primary)">${m.merchant_id}</td>
      <td style="font-weight:600">${m.name}</td>
      <td style="font-size:12px;color:var(--text-muted)">${m.domain||'—'}</td>
      <td style="color:var(--gold);font-weight:700">₹${parseFloat(m.balance||0).toFixed(2)}</td>
      <td><span class="badge ${sc[m.status]||'badge-muted'}">${m.status}</span></td>
      <td>${m.totp_enabled ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-muted">OFF</span>'}</td>
      <td style="font-size:11px;color:var(--text-muted)">${(m.created_at||'').substring(0,10)}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-outline btn-sm" onclick='openEdit(${JSON.stringify(m)})'><i class="fas fa-pen"></i></button>
        <button class="btn btn-outline btn-sm" onclick="viewTxns('${m.merchant_id}','${m.name.replace(/'/g,"\\'")}')"><i class="fas fa-receipt"></i></button>
      </td>
    </tr>`).join('') || '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">No merchants found.</td></tr>';
    document.getElementById('merchantBody').innerHTML = html;
}

function openAddModal() { document.getElementById('addModal').classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

async function submitAdd() {
    const name = document.getElementById('addName').value.trim();
    const dom  = document.getElementById('addDomain').value.trim();
    const al   = document.getElementById('addAlert');
    al.innerHTML = '';
    const res = await fetch(`${SITE}/api/admin/merchants.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'add',name,domain:dom,csrf_token:CSRF})
    }).then(r=>r.json());
    if (res.success) {
        al.innerHTML = `<div class="alert-success"><i class="fas fa-check-circle"></i> <strong>Created!</strong> ID: <strong>${res.merchant_id}</strong> | Default password: <strong>${res.default_password}</strong></div>`;
        loadMerchants();
    } else {
        al.innerHTML = `<div class="alert-danger"><i class="fas fa-circle-exclamation"></i> ${res.message}</div>`;
    }
}

function openEdit(m) {
    document.getElementById('editId').value      = m.merchant_id;
    document.getElementById('editName').value    = m.name;
    document.getElementById('editDomain').value  = m.domain || '';
    document.getElementById('editStatus').value  = m.status;
    document.getElementById('editBalance').value = parseFloat(m.balance||0).toFixed(2);
    document.getElementById('editApiKey').value  = m.api_key || '';
    document.getElementById('editPayIn').value   = m.commission_pay_in || '';
    document.getElementById('editPayOut').value  = m.commission_pay_out || '';
    document.getElementById('balanceAdj').value  = '0';
    document.getElementById('balanceOp').value   = 'add';
    document.getElementById('editAlert').innerHTML = '';
    document.getElementById('editModal').classList.add('open');
}

async function submitEdit() {
    const id      = document.getElementById('editId').value;
    const name    = document.getElementById('editName').value.trim();
    const domain  = document.getElementById('editDomain').value.trim();
    const status  = document.getElementById('editStatus').value;
    const balance = document.getElementById('editBalance').value;
    const balAdj  = parseFloat(document.getElementById('balanceAdj').value)||0;
    const balOp   = document.getElementById('balanceOp').value;
    const payIn   = document.getElementById('editPayIn').value;
    const payOut  = document.getElementById('editPayOut').value;
    const al      = document.getElementById('editAlert');
    const res = await fetch(`${SITE}/api/admin/merchants.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'update',merchant_id:id,name,domain,status,balance,balance_adj:balAdj,balance_op:balOp,commission_pay_in:payIn,commission_pay_out:payOut,csrf_token:CSRF})
    }).then(r=>r.json());
    if (res.success) {
        al.innerHTML = `<div class="alert-success"><i class="fas fa-check-circle"></i> ${res.message||'Saved!'}</div>`;
        loadMerchants();
    } else {
        al.innerHTML = `<div class="alert-danger"><i class="fas fa-circle-exclamation"></i> ${res.message}</div>`;
    }
}

async function resetPwd() {
    const id = document.getElementById('editId').value;
    if (!confirm(`Reset password for ${id} to "12345" and clear 2FA?`)) return;
    const res = await fetch(`${SITE}/api/admin/merchants.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'reset_password',merchant_id:id,csrf_token:CSRF})
    }).then(r=>r.json());
    document.getElementById('editAlert').innerHTML = `<div class="${res.success?'alert-success':'alert-danger'}"><i class="fas fa-${res.success?'check-circle':'circle-exclamation'}"></i> ${res.message}</div>`;
}

async function regenApiKey() {
    const id = document.getElementById('editId').value;
    if (!confirm(`Regenerate API key for ${id}? The old key will stop working immediately.`)) return;
    const res = await fetch(`${SITE}/api/admin/merchants.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'regen_apikey',merchant_id:id,csrf_token:CSRF})
    }).then(r=>r.json());
    if (res.success) {
        document.getElementById('editApiKey').value = res.api_key;
        document.getElementById('editAlert').innerHTML = `<div class="alert-success"><i class="fas fa-check-circle"></i> API Key regenerated!</div>`;
    }
}

function copyField(fieldId, btn) {
    const val = document.getElementById(fieldId).value;
    navigator.clipboard.writeText(val);
    const orig = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check" style="color:var(--success)"></i>';
    setTimeout(() => btn.innerHTML = orig, 1500);
}

async function viewTxns(mid, name) {
    document.getElementById('txnMerchantName').textContent = name;
    document.getElementById('txnBody').innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px"><i class="fas fa-spinner fa-spin"></i> Loading...</td></tr>';
    document.getElementById('txnModal').classList.add('open');
    const data = await fetch(`${SITE}/api/admin/merchants.php?action=transactions&merchant_id=${mid}`).then(r=>r.json());
    const sc = {success:'badge-success',failed:'badge-danger',pending:'badge-warning',expired:'badge-muted'};
    const html = (data.data||[]).map(t => `<tr>
      <td class="mono" style="font-size:10px;color:var(--primary)">${t.txn_id}</td>
      <td style="font-size:11px;color:var(--text-muted)">${t.merchant_order_no||'—'}</td>
      <td style="color:var(--gold);font-weight:700">₹${parseFloat(t.amount||0).toFixed(2)}</td>
      <td class="mono" style="font-size:11px;color:var(--success)">${t.utr||'—'}</td>
      <td style="font-size:11px;color:var(--text-muted)">${t.upi_address||'—'}</td>
      <td><span class="badge ${sc[t.status]||'badge-muted'}">${t.status}</span></td>
      <td style="font-size:11px;color:var(--text-muted)">${(t.created_at||'').substring(0,16)}</td>
    </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">No transactions.</td></tr>';
    document.getElementById('txnBody').innerHTML = html;
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});

loadMerchants();
</script>
<?php adminLayoutFooter(); ?>
