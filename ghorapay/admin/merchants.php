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
    <div style="display:flex;gap:10px;align-items:center">
      <input type="text" id="searchBox" class="form-control" style="max-width:220px" placeholder="Search name / ID..." oninput="loadMerchants()">
      <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="fas fa-plus"></i> Add</button>
    </div>
  </div>
  <div style="overflow-x:auto"><table id="merchantTable">
    <thead><tr><th>Merchant ID</th><th>Name</th><th>Domain</th><th>Balance</th><th>Status</th><th>2FA</th><th>Created</th><th>Actions</th></tr></thead>
    <tbody id="merchantBody"><tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">Loading...</td></tr></tbody>
  </table></div>
</div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-store-plus"></i> Add Merchant</div>
    <div class="form-group" style="margin-top:16px"><label class="form-label">Name</label><input type="text" id="addName" class="form-control" placeholder="Business name"></div>
    <div class="form-group"><label class="form-label">Domain (optional)</label><input type="text" id="addDomain" class="form-control" placeholder="yoursite.com"></div>
    <div id="addAlert"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="submitAdd()"><i class="fas fa-check"></i> Create</button>
      <button class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-pen"></i> Edit Merchant</div>
    <input type="hidden" id="editId">
    <div class="form-group" style="margin-top:16px"><label class="form-label">Name</label><input type="text" id="editName" class="form-control"></div>
    <div class="form-group"><label class="form-label">Domain</label><input type="text" id="editDomain" class="form-control"></div>
    <div class="form-group"><label class="form-label">Status</label>
      <select id="editStatus" class="form-control">
        <option value="live">Live</option><option value="suspended">Suspended</option><option value="deleted">Deleted</option>
      </select>
    </div>
    <div id="editAlert"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="submitEdit()"><i class="fas fa-floppy-disk"></i> Save</button>
      <button class="btn btn-warning btn-sm" onclick="resetPwd()" style="white-space:nowrap"><i class="fas fa-key"></i> Reset Pass</button>
      <button class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Transactions Modal -->
<div class="modal-overlay" id="txnModal">
  <div class="modal" style="max-width:800px">
    <div class="modal-title"><i class="fas fa-receipt"></i> Transactions — <span id="txnMerchantName"></span></div>
    <div style="overflow-x:auto;margin-top:16px"><table>
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
      <td style="color:var(--gold);font-weight:700">₹${parseFloat(m.balance).toFixed(2)}</td>
      <td><span class="badge ${sc[m.status]||'badge-muted'}">${m.status}</span></td>
      <td>${m.totp_enabled ? '<span class="badge badge-success">ON</span>' : '<span class="badge badge-muted">OFF</span>'}</td>
      <td style="font-size:11px;color:var(--text-muted)">${(m.created_at||'').substring(0,10)}</td>
      <td style="white-space:nowrap">
        <button class="btn btn-outline btn-sm" onclick='openEdit(${JSON.stringify(m)})'><i class="fas fa-pen"></i></button>
        <button class="btn btn-outline btn-sm" onclick="viewTxns('${m.merchant_id}','${m.name}')"><i class="fas fa-receipt"></i></button>
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
        al.innerHTML = `<div class="alert alert-success" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.25);color:var(--success)"><strong>Created!</strong> ID: ${res.merchant_id} | Default password: ${res.default_password}</div>`;
        loadMerchants();
    } else {
        al.innerHTML = `<div class="alert alert-danger" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger)">${res.message}</div>`;
    }
}

function openEdit(m) {
    document.getElementById('editId').value = m.merchant_id;
    document.getElementById('editName').value = m.name;
    document.getElementById('editDomain').value = m.domain || '';
    document.getElementById('editStatus').value = m.status;
    document.getElementById('editAlert').innerHTML = '';
    document.getElementById('editModal').classList.add('open');
}

async function submitEdit() {
    const id     = document.getElementById('editId').value;
    const name   = document.getElementById('editName').value.trim();
    const domain = document.getElementById('editDomain').value.trim();
    const status = document.getElementById('editStatus').value;
    const al     = document.getElementById('editAlert');
    const res = await fetch(`${SITE}/api/admin/merchants.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'update',merchant_id:id,name,domain,status,csrf_token:CSRF})
    }).then(r=>r.json());
    if (res.success) { closeModal('editModal'); loadMerchants(); }
    else { al.innerHTML = `<div class="alert alert-danger" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger)">${res.message}</div>`; }
}

async function resetPwd() {
    const id = document.getElementById('editId').value;
    if (!confirm(`Reset password for ${id} to "12345" and clear 2FA?`)) return;
    const res = await fetch(`${SITE}/api/admin/merchants.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'reset_password',merchant_id:id,csrf_token:CSRF})
    }).then(r=>r.json());
    document.getElementById('editAlert').innerHTML = `<div class="alert ${res.success?'alert-success':'alert-danger'}" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(${res.success?'34,197,94':'239,68,68'},0.1);border:1px solid rgba(${res.success?'34,197,94':'239,68,68'},0.25);color:var(--${res.success?'success':'danger'})">${res.message}</div>`;
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
      <td style="color:var(--gold);font-weight:700">₹${parseFloat(t.amount).toFixed(2)}</td>
      <td class="mono" style="font-size:11px;color:var(--success)">${t.utr||'—'}</td>
      <td style="font-size:11px;color:var(--text-muted)">${t.upi_address||'—'}</td>
      <td><span class="badge ${sc[t.status]||'badge-muted'}">${t.status}</span></td>
      <td style="font-size:11px;color:var(--text-muted)">${(t.created_at||'').substring(0,16)}</td>
    </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">No transactions.</td></tr>';
    document.getElementById('txnBody').innerHTML = html;
}

// Close modals on backdrop click
document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});

loadMerchants();
</script>
<?php adminLayoutFooter(); ?>
