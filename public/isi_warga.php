<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/header.php';
$IS_ADMIN = is_admin();
?>
<div class="card" <?php if(!$IS_ADMIN) echo 'style="display:none;"'; ?>>
  <h2>Isi / Update Data Warga</h2>
  <form class="form" id="warga-form" onsubmit="return false;">
    <label>Blok Rumah</label>
    <input class="input" id="blok" placeholder="Contoh: BA1" required />
    <label>Nama Warga</label>
    <input class="input" id="nama" placeholder="Nama Warga" required />
    <label>Status</label>
    <select id="status" class="input">
      <option value="huni">Huni</option>
      <option value="tidak_huni">Tidak Huni</option>
    </select>
    <div style="display:flex; gap:8px; align-items:center;">
      <button class="button" onclick="saveWarga()">Simpan</button>
    </div>
    <p id="msg" class="small"></p>
  </form>
</div>
<div class="card" style="margin-top:12px;">
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <h3 style="margin:0;">Daftar Warga</h3>
      <?php if($IS_ADMIN): ?><button id="btnBulkDelete" class="button secondary" onclick="bulkDelete()" disabled>Hapus Terpilih</button><?php endif; ?>
    </div>
    <div style="margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
      <input id="search_blok" class="input" placeholder="Cari Blok (misal: BA1)" style="max-width:140px;" />
      <input id="search_nama" class="input" placeholder="Cari Nama Warga" style="max-width:220px;" />
      <button class="button secondary" onclick="applySearch()">Cari</button>
      <button class="button secondary" onclick="resetSearch()">Reset</button>
    </div>
    <table class="table">
      <thead><tr><?php if($IS_ADMIN): ?><th><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"/></th><?php endif; ?><th>Blok</th><th>Nama</th><th>Status</th><?php if($IS_ADMIN): ?><th>Aksi</th><?php endif; ?></tr></thead>
    <tbody id="warga-rows"></tbody>
  </table>
  <div id="pagination" style="margin-top:8px; display:flex; flex-wrap:wrap; gap:6px; align-items:center;"></div>
</div>

<div class="card" style="margin-top:12px; <?php if(!$IS_ADMIN) echo 'display:none;'; ?>">
  <h3 style="margin-top:0;">Import Data dari Excel (CSV)</h3>
  <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <input type="file" id="imp_file" accept=".csv,.xlsx" />

    <button class="button" onclick="importWarga()">Import</button>
  </div>
  <p id="imp_msg" class="small"></p>
  <p class="small" style="color:#64748b;">Format kolom: NO., BLOK, NO RUMAH, NAMA WARGA, STATUS. Untuk file Excel, silakan export ke CSV dahulu.</p>
</div>

<!-- Toast Edit -->
<div id="editToast" class="card" style="position:fixed; top:16px; right:16px; width:360px; display:none; z-index:1000; box-shadow:0 8px 24px rgba(0,0,0,0.12);">
  <h3 style="margin-top:0;">Edit Data Warga</h3>
  <form onsubmit="return false;">
    <label>Blok Rumah</label>
    <input class="input" id="edit_blok" placeholder="Contoh: BA1" />
    <label>Nama Warga</label>
    <input class="input" id="edit_nama" placeholder="Nama Warga" />
    <label>Status</label>
    <select id="edit_status" class="input">
      <option value="huni">Huni</option>
      <option value="tidak_huni">Tidak Huni</option>
    </select>
    <div style="display:flex; gap:8px; align-items:center; margin-top:8px;">
      <button class="button" onclick="submitEdit()">Simpan Perubahan</button>
      <button class="button secondary" onclick="closeEdit()">Batal</button>
    </div>
    <p id="edit_msg" class="small"></p>
  </form>
</div>

<script>
let editingId = null; const selectedIds = new Set();
let currentPage = 1; const pageSize = 10; let totalPages = 1; const IS_ADMIN = <?php echo $IS_ADMIN ? 'true' : 'false'; ?>;

function updateBulkActionState(){
  const btn = document.getElementById('btnBulkDelete');
  if(!btn) return;
  const count = selectedIds.size;
  btn.disabled = count === 0;
  btn.textContent = count > 0 ? `Hapus Terpilih (${count})` : 'Hapus Terpilih';
}

function toggleRowCheck(cb){
  const id = parseInt(cb.dataset.id, 10);
  if(!id) return;
  if(cb.checked){ selectedIds.add(id); } else { selectedIds.delete(id); }
  updateBulkActionState();
}

function toggleSelectAll(master){
  const rows = document.querySelectorAll('#warga-rows input.row-check');
  selectedIds.clear();
  rows.forEach(cb => {
    cb.checked = master.checked;
    const id = parseInt(cb.dataset.id,10);
    if(master.checked && id) selectedIds.add(id);
  });
  updateBulkActionState();
}

async function bulkDelete(){
  const count = selectedIds.size;
  const msg = document.getElementById('msg');
  msg.textContent = '';
  if(count === 0) return;
  if(!confirm(`Yakin hapus ${count} data?`)) return;
  try{
    const res = await fetch('/api/users.php', {
      method:'DELETE',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ ids: Array.from(selectedIds) })
    });
    const data = await res.json();
    if(!res.ok){ throw new Error(data.error || 'Gagal menghapus data terpilih'); }
    selectedIds.clear();
    const master = document.getElementById('selectAll'); if(master) master.checked = false;
    updateBulkActionState();
    msg.textContent = `Berhasil menghapus ${data.deleted ?? count} data`;
    msg.style.color = '#2ecc71';
    loadWarga();
  }catch(e){
    msg.textContent = e.message;
    msg.style.color = '#e74c3c';
  }
}

async function saveWarga(){
  const msg = document.getElementById('msg');
  msg.textContent = '';
  try{
    const payload = {
      blok_rumah: document.getElementById('blok').value.trim(),
      nama_warga: document.getElementById('nama').value.trim(),
      status: document.getElementById('status').value
    };
    const res = await fetch('/api/users.php', {
      method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(!res.ok){ throw new Error(data.error||'Gagal menyimpan'); }
    msg.textContent = 'Tersimpan'; msg.style.color = '#2ecc71';
    loadWarga();
  }catch(e){ msg.textContent = e.message; msg.style.color = '#e74c3c'; }
}

function openEdit(u){
  editingId = u.id;
  document.getElementById('edit_blok').value = u.blok_rumah;
  document.getElementById('edit_nama').value = u.nama_warga;
  document.getElementById('edit_status').value = u.status;
  document.getElementById('edit_msg').textContent = '';
  document.getElementById('editToast').style.display = 'block';
}

function closeEdit(){
  document.getElementById('editToast').style.display = 'none';
  editingId = null;
}

async function submitEdit(){
  const msg = document.getElementById('edit_msg');
  msg.textContent = '';
  try{
    const payload = {
      id: editingId,
      blok_rumah: document.getElementById('edit_blok').value.trim(),
      nama_warga: document.getElementById('edit_nama').value.trim(),
      status: document.getElementById('edit_status').value
    };
    if(!payload.id){ throw new Error('ID tidak valid'); }
    const res = await fetch('/api/users.php', {
      method:'PUT',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if(!res.ok){ throw new Error(data.error||'Gagal memperbarui'); }
    msg.textContent = 'Berhasil diperbarui'; msg.style.color = '#2ecc71';
    setTimeout(()=>{ closeEdit(); loadWarga(); }, 400);
  }catch(e){ msg.textContent = e.message; msg.style.color = '#e74c3c'; }
}

async function deleteWarga(id){
  const msg = document.getElementById('msg');
  msg.textContent = '';
  if(!confirm('Yakin hapus data warga ini?')) return;
  try{
    const res = await fetch('/api/users.php?id='+encodeURIComponent(id), { method:'DELETE', headers:{'Content-Type':'application/json'} });
    const data = await res.json();
    if(!res.ok){ throw new Error(data.error||'Gagal menghapus'); }
    msg.textContent = 'Berhasil dihapus'; msg.style.color = '#2ecc71';
    loadWarga();
  }catch(e){ msg.textContent = e.message; msg.style.color = '#e74c3c'; }
}

async function renderPagination(meta){
  const p = document.getElementById('pagination');
  if(!p) return;
  p.innerHTML = '';
  totalPages = meta?.total_pages ?? totalPages;
  const windowSize = 5;
  const windowStart = Math.floor((currentPage-1)/windowSize)*windowSize + 1;
  const windowEnd = Math.min(windowStart + windowSize - 1, totalPages);
  // Prev shifts window to previous chunk
  const prev = document.createElement('button'); prev.className='button secondary'; prev.textContent='Prev';
  prev.disabled = windowStart <= 1;
  prev.onclick=()=> { const target = Math.max(windowStart - windowSize, 1); loadWarga(target); };
  p.appendChild(prev);
  // Numbers in current window
  for(let i=windowStart;i<=windowEnd;i++){
    const b=document.createElement('button');
    b.className='button'+(i===currentPage?'':' secondary');
    b.textContent=String(i);
    b.onclick=()=> loadWarga(i);
    p.appendChild(b);
  }
  // Next shifts window to next chunk
  const next = document.createElement('button'); next.className='button secondary'; next.textContent='Next';
  next.disabled = windowEnd >= totalPages;
  next.onclick=()=> { const target = Math.min(windowStart + windowSize, totalPages); loadWarga(target); };
  p.appendChild(next);
}

function applySearch(){ loadWarga(1); }
function resetSearch(){
  const sb = document.getElementById('search_blok'); if(sb) sb.value='';
  const sn = document.getElementById('search_nama'); if(sn) sn.value='';
  loadWarga(1);
}

async function loadWarga(page){
  if(page){ currentPage = page; }
  const tbody = document.getElementById('warga-rows');
  tbody.innerHTML = '';
  // reset selection state
  selectedIds.clear();
  const master = document.getElementById('selectAll');
  if(master) master.checked = false;
  updateBulkActionState();
  try{
    const blokVal = (document.getElementById('search_blok')?.value || '').trim();
    const namaVal = (document.getElementById('search_nama')?.value || '').trim();
    let url = `/api/users.php?page=${currentPage}&limit=${pageSize}`;
    if(namaVal) url += `&q=${encodeURIComponent(namaVal)}`;
    if(blokVal) url += `&blok=${encodeURIComponent(blokVal)}`;
    const res = await fetch(url);
    const data = await res.json();
    (data.users||[]).forEach(u=>{
      const tr = document.createElement('tr');
      if (IS_ADMIN) {
        tr.innerHTML = `<td><input type="checkbox" class="row-check" data-id="${u.id}" onchange="toggleRowCheck(this)"/></td><td>${u.blok_rumah}</td><td>${u.nama_warga}</td><td>${u.status}</td><td></td>`;
        const tdAksi = tr.children[4];
        const btnEdit = document.createElement('button');
        btnEdit.className = 'button';
        btnEdit.textContent = 'Edit';
        btnEdit.onclick = ()=> openEdit(u);
        tdAksi.appendChild(btnEdit);
      } else {
        tr.innerHTML = `<td>${u.blok_rumah}</td><td>${u.nama_warga}</td><td>${u.status}</td>`;
      }

      if (IS_ADMIN) {
        const btnDel = document.createElement('button');
        btnDel.className = 'button';
        btnDel.style.marginLeft = '6px';
        btnDel.textContent = 'Hapus';
        btnDel.onclick = ()=> deleteWarga(u.id);
        const tdAksi = tr.children[4];
        tdAksi.appendChild(btnDel);
      }

      tbody.appendChild(tr);
    });
    renderPagination({ total_pages: data.total_pages });
  }catch(e){
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="${IS_ADMIN ? 5 : 3}">Gagal memuat data</td>`;
    tbody.appendChild(tr);
  }
}

async function importWarga(){
  const msg = document.getElementById('imp_msg');
  msg.textContent='';
  const fileInput = document.getElementById('imp_file');
  
  if(!fileInput.files || fileInput.files.length===0){ msg.textContent='Pilih file terlebih dahulu'; msg.style.color='#e74c3c'; return; }
  const fd = new FormData();
  fd.append('file', fileInput.files[0]);
  
  try{
    const res = await fetch('/api/users_import.php', { method:'POST', body: fd });
    const data = await res.json().catch(()=>({ok:false,error:'Format respon tidak valid'}));
    if(!res.ok || !data.ok){ throw new Error(data.error||'Import gagal'); }
    msg.textContent = `Import berhasil: ditambah ${data.inserted}, diperbarui ${data.updated}, dilewati ${data.skipped}, IPL di-upsert ${data.ipl_upserted}`;
    msg.style.color = '#2ecc71';
    loadWarga();
  } catch(e){ msg.textContent = e.message; msg.style.color = '#e74c3c'; }
}

window.addEventListener('DOMContentLoaded', ()=> loadWarga(1));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>