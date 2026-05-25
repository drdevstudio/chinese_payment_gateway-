<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admin_layout.php';
adminAuth();
adminLayoutHead('UPI Management');
?>
<body>
<?php adminLayoutBody([],'upi'); adminLayoutTopbar('UPI Management');?>
<div class="page-body">
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-qrcode" style="color:var(--success)"></i> UPI IDs</div>
    <button class="btn btn-primary btn-sm" onclick="openAddModal()"><i class="fas fa-plus"></i> Add UPI</button>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Device ID</th><th>UPI Address</th><th>Holder</th><th>Daily Limit</th><th>Today Recv</th><th>Remaining</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody id="upiBody"><tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px"><i class="fas fa-spinner fa-spin"></i></td></tr></tbody>
  </table></div>
</div>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-plus-circle"></i> Add UPI ID</div>
    <div class="form-group" style="margin-top:16px"><label class="form-label">UPI Address</label><input type="text" id="aUpi" class="form-control" placeholder="name@bank"></div>
    <div class="form-group"><label class="form-label">Device ID <small style="color:var(--text-muted)">(unique identifier — from Android app)</small></label><input type="text" id="aDev" class="form-control" placeholder="e.g. SHA256-of-ANDROID-ID"></div>
    <div class="form-group"><label class="form-label">Holder Name</label><input type="text" id="aName" class="form-control" placeholder="Account holder name"></div>
    <div class="form-group"><label class="form-label">Daily Limit (₹)</label><input type="number" id="aLimit" class="form-control" value="100000" min="100"></div>
    <div id="addAlert"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="submitAdd()"><i class="fas fa-check"></i> Add</button>
      <button class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-title"><i class="fas fa-pen"></i> Edit UPI</div>
    <input type="hidden" id="eDev">
    <div class="form-group" style="margin-top:16px"><label class="form-label">UPI Address</label><input type="text" id="eUpi" class="form-control"></div>
    <div class="form-group"><label class="form-label">Holder Name</label><input type="text" id="eName" class="form-control"></div>
    <div class="form-group"><label class="form-label">Daily Limit (₹)</label><input type="number" id="eLimit" class="form-control" min="100"></div>
    <div class="form-group"><label class="form-label">Status</label>
      <select id="eStatus" class="form-control"><option value="active">Active</option><option value="inactive">Inactive</option></select>
    </div>
    <div id="editAlert"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="submitEdit()"><i class="fas fa-floppy-disk"></i> Save</button>
      <button class="btn btn-danger btn-sm" onclick="deleteUpi()"><i class="fas fa-trash"></i></button>
      <button class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
    </div>
  </div>
</div>

<script>
const CSRF = '<?=csrfToken()?>';
const SITE = '<?=SITE_URL?>';

async function loadUpi() {
    const data = await fetch(`${SITE}/api/admin/upi.php?action=list`).then(r=>r.json());
    const sc = {active:'badge-success',inactive:'badge-muted'};
    const html = (data.data||[]).map(u => {
        const rem = Math.max(0, parseFloat(u.remaining||0)).toFixed(2);
        const pct = u.daily_limit > 0 ? Math.min(100, (u.today_received||0)/u.daily_limit*100) : 0;
        return `<tr>
          <td class="mono" style="font-size:11px;color:var(--primary)">${u.device_id}</td>
          <td class="mono" style="font-size:12px;color:var(--gold)">${u.upi_address}</td>
          <td style="font-size:13px">${u.holder_name||'—'}</td>
          <td>₹${parseFloat(u.daily_limit||0).toFixed(0)}</td>
          <td>
            <div style="font-size:12px;color:var(--success);margin-bottom:4px">₹${parseFloat(u.today_received||0).toFixed(2)}</div>
            <div style="background:var(--surface2);border-radius:4px;height:4px;width:80px;overflow:hidden">
              <div style="width:${pct}%;height:100%;background:${pct>85?'var(--danger)':'var(--success)'};border-radius:4px"></div>
            </div>
          </td>
          <td style="color:${parseFloat(rem)<500?'var(--warning)':'var(--text)'}">₹${rem}</td>
          <td><span class="badge ${sc[u.status]||'badge-muted'}">${u.status}</span></td>
          <td>
            <button class="btn btn-outline btn-sm" onclick='openEdit(${JSON.stringify(u)})'><i class="fas fa-pen"></i></button>
          </td>
        </tr>`;
    }).join('') || '<tr><td colspan="8" style="text-align:center;color:var(--text-muted);padding:20px">No UPI IDs configured.</td></tr>';
    document.getElementById('upiBody').innerHTML = html;
}

function openAddModal() { document.getElementById('addModal').classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

async function submitAdd() {
    const al = document.getElementById('addAlert');
    const res = await fetch(`${SITE}/api/admin/upi.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action:'add',
            upi_address: document.getElementById('aUpi').value.trim(),
            device_id: document.getElementById('aDev').value.trim(),
            holder_name: document.getElementById('aName').value.trim(),
            daily_limit: document.getElementById('aLimit').value,
            csrf_token: CSRF
        })
    }).then(r=>r.json());
    if (res.success) { closeModal('addModal'); loadUpi(); }
    else { al.innerHTML = `<div class="alert alert-danger" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger)">${res.message}</div>`; }
}

function openEdit(u) {
    document.getElementById('eDev').value   = u.device_id;
    document.getElementById('eUpi').value   = u.upi_address;
    document.getElementById('eName').value  = u.holder_name || '';
    document.getElementById('eLimit').value = u.daily_limit;
    document.getElementById('eStatus').value= u.status;
    document.getElementById('editAlert').innerHTML = '';
    document.getElementById('editModal').classList.add('open');
}

async function submitEdit() {
    const al = document.getElementById('editAlert');
    const res = await fetch(`${SITE}/api/admin/upi.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
            action:'update',
            device_id: document.getElementById('eDev').value,
            upi_address: document.getElementById('eUpi').value,
            holder_name: document.getElementById('eName').value,
            daily_limit: document.getElementById('eLimit').value,
            status: document.getElementById('eStatus').value,
            csrf_token: CSRF
        })
    }).then(r=>r.json());
    if (res.success) { closeModal('editModal'); loadUpi(); }
    else { al.innerHTML = `<div class="alert alert-danger" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger)">${res.message}</div>`; }
}

async function deleteUpi() {
    const dev = document.getElementById('eDev').value;
    if (!confirm(`Delete UPI device: ${dev}? This cannot be undone.`)) return;
    const res = await fetch(`${SITE}/api/admin/upi.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'delete',device_id:dev,csrf_token:CSRF})
    }).then(r=>r.json());
    if (res.success) { closeModal('editModal'); loadUpi(); }
}

document.querySelectorAll('.modal-overlay').forEach(el => {
    el.addEventListener('click', e => { if (e.target === el) el.classList.remove('open'); });
});
loadUpi();
</script>
<?php adminLayoutFooter(); ?>
