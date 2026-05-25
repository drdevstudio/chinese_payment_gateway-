<?php
require_once __DIR__.'/../config.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/admin_layout.php';
adminAuth();
adminLayoutHead('Withdrawals');
?>
<body>
<?php adminLayoutBody([],'withdrawals'); adminLayoutTopbar('Withdrawals');?>
<div class="page-body">
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-money-bill-transfer" style="color:var(--warning)"></i> Withdrawals</div>
    <div style="display:flex;gap:8px">
      <button class="btn btn-sm active-filter" id="f_pending" onclick="loadW('pending',this)">Pending</button>
      <button class="btn btn-outline btn-sm" id="f_success" onclick="loadW('success',this)">Approved</button>
      <button class="btn btn-outline btn-sm" id="f_failed" onclick="loadW('failed',this)">Failed</button>
      <button class="btn btn-outline btn-sm" id="f_all" onclick="loadW('all',this)">All</button>
    </div>
  </div>
  <div class="table-wrap"><table>
    <thead><tr><th>Merchant</th><th>Amount</th><th>UPI</th><th>Status</th><th>Note</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody id="wBody"><tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px"><i class="fas fa-spinner fa-spin"></i></td></tr></tbody>
  </table></div>
</div>
</div>

<!-- Approve/Fail Modal -->
<div class="modal-overlay" id="actionModal">
  <div class="modal" style="max-width:440px">
    <div class="modal-title" id="actionTitle"></div>
    <input type="hidden" id="actionId">
    <input type="hidden" id="actionStatus">
    <div class="form-group" style="margin-top:16px">
      <label class="form-label">Note (optional)</label>
      <input type="text" id="actionNote" class="form-control" placeholder="UTR or reason">
    </div>
    <div id="actionAlert"></div>
    <div style="display:flex;gap:10px;margin-top:16px">
      <button class="btn btn-primary" style="flex:1" onclick="submitAction()"><i class="fas fa-check"></i> Confirm</button>
      <button class="btn btn-outline" onclick="closeModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
const CSRF = '<?=csrfToken()?>';
const SITE = '<?=SITE_URL?>';
let currentFilter = 'pending';

async function loadW(status, btn) {
    currentFilter = status;
    document.querySelectorAll('[id^=f_]').forEach(b => { b.className = 'btn btn-outline btn-sm'; });
    if (btn) btn.className = 'btn btn-primary btn-sm';
    document.getElementById('wBody').innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px"><i class="fas fa-spinner fa-spin"></i></td></tr>';
    const data = await fetch(`${SITE}/api/admin/withdrawals.php?action=list&status=${status}`).then(r=>r.json());
    const sc = {success:'badge-success',failed:'badge-danger',pending:'badge-warning'};
    const html = (data.data||[]).map(w => `<tr>
      <td style="font-weight:600">${w.mn||'—'}</td>
      <td style="color:var(--gold);font-weight:700">₹${parseFloat(w.amount).toFixed(2)}</td>
      <td class="mono" style="font-size:11px">${w.upi_address||'—'}</td>
      <td><span class="badge ${sc[w.status]||'badge-muted'}">${w.status}</span></td>
      <td style="font-size:12px;color:var(--text-muted)">${w.note||'—'}</td>
      <td style="font-size:11px;color:var(--text-muted)">${(w.created_at||'').substring(0,16)}</td>
      <td style="white-space:nowrap">${w.status==='pending'?`
        <button class="btn btn-success btn-sm" onclick="openAction('${w.id}','success','Approve withdrawal')"><i class="fas fa-check"></i></button>
        <button class="btn btn-danger btn-sm" onclick="openAction('${w.id}','failed','Fail withdrawal')"><i class="fas fa-times"></i></button>
      `:'—'}</td>
    </tr>`).join('') || '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:20px">No withdrawals.</td></tr>';
    document.getElementById('wBody').innerHTML = html;
}

function openAction(id, status, title) {
    document.getElementById('actionId').value = id;
    document.getElementById('actionStatus').value = status;
    document.getElementById('actionTitle').textContent = title;
    document.getElementById('actionNote').value = '';
    document.getElementById('actionAlert').innerHTML = '';
    document.getElementById('actionModal').classList.add('open');
}
function closeModal() { document.getElementById('actionModal').classList.remove('open'); }

async function submitAction() {
    const id     = document.getElementById('actionId').value;
    const status = document.getElementById('actionStatus').value;
    const note   = document.getElementById('actionNote').value;
    const al     = document.getElementById('actionAlert');
    const res = await fetch(`${SITE}/api/admin/withdrawals.php`, {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'update',id,status,note,csrf_token:CSRF})
    }).then(r=>r.json());
    if (res.success) { closeModal(); loadW(currentFilter, null); }
    else { al.innerHTML = `<div class="alert alert-danger" style="margin-top:10px;padding:10px;border-radius:8px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:var(--danger)">${res.message}</div>`; }
}

document.getElementById('actionModal').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
loadW('pending', document.getElementById('f_pending'));
</script>
<?php adminLayoutFooter(); ?>
