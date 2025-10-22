<?php require_once __DIR__ . '/../includes/auth.php'; require_admin(); ?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="card">
  <h2 style="margin-top:0;">Input Pengeluaran</h2>
  <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <div>
      <label for="pg_tanggal">Tanggal</label>
      <input type="date" id="pg_tanggal">
    </div>
    <div>
      <label for="pg_kategori">Kategori</label>
      <input type="text" id="pg_kategori" placeholder="Misal: Kebersihan">
    </div>
    <div style="grid-column:1 / span 2;">
      <label for="pg_deskripsi">Deskripsi</label>
      <input type="text" id="pg_deskripsi" placeholder="Misal: Pembelian cairan pembersih">
    </div>
    <div>
      <label for="pg_jumlah">Jumlah (Rp)</label>
      <input type="number" id="pg_jumlah" min="0" step="1" placeholder="0">
    </div>
    <div>
      <label for="pg_petugas">Petugas</label>
      <input type="text" id="pg_petugas" placeholder="Misal: Bendahara">
    </div>
  </div>
  <div style="margin-top:12px;display:flex;gap:8px;">
    <button class="btn" onclick="savePengeluaran()">Simpan</button>
    <a class="btn btn-outline" href="/dashboard.php">Ke Dashboard</a>
  </div>
  <p id="pg_msg" class="small" style="margin-top:8px;color:#0b7;display:none;"></p>
</div>

<div class="card">
  <h3 style="margin-top:0;">Riwayat Terbaru</h3>
  <table class="table" style="width:100%;">
    <thead>
      <tr><th>Tanggal</th><th>Kategori</th><th>Deskripsi</th><th style="text-align:right;">Jumlah</th><th>Petugas</th></tr>
    </thead>
    <tbody id="pg_list"><tr><td colspan="5" class="small">Memuat data...</td></tr></tbody>
  </table>
</div>

<script>
function formatRupiah(n){return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n||0)}
function today(){const d=new Date();return d.toISOString().slice(0,10)}

document.addEventListener('DOMContentLoaded',()=>{document.getElementById('pg_tanggal').value=today(); loadLatestPengeluaran();});

async function loadLatestPengeluaran(){
  try{
    const res = await fetch('/api/pengeluaran.php?limit=10');
    const data = await res.json();
    const tbody = document.getElementById('pg_list');
    if(!data.ok){tbody.innerHTML = `<tr><td colspan=5>${data.error||'Gagal memuat'}</td></tr>`;return}
    if(data.data.length===0){tbody.innerHTML = '<tr><td colspan=5 class="small">Belum ada data.</td></tr>';return}
    tbody.innerHTML = data.data.map(r=>`<tr>
      <td>${r.tanggal}</td>
      <td>${r.kategori||''}</td>
      <td>${r.deskripsi||''}</td>
      <td style="text-align:right;">${formatRupiah(r.jumlah)}</td>
      <td>${r.petugas||''}</td>
    </tr>`).join('');
  }catch(e){document.getElementById('pg_list').innerHTML = `<tr><td colspan=5>${e.message}</td></tr>`}
}

async function savePengeluaran(){
  const tanggal = document.getElementById('pg_tanggal').value;
  const kategori = document.getElementById('pg_kategori').value.trim();
  const deskripsi = document.getElementById('pg_deskripsi').value.trim();
  const jumlah = parseInt(document.getElementById('pg_jumlah').value||'0',10);
  const petugas = document.getElementById('pg_petugas').value.trim();
  const msg = document.getElementById('pg_msg'); msg.style.display='none';
  if(!tanggal||!(jumlah>0)){alert('Tanggal dan jumlah wajib diisi');return}
  try{
    const res = await fetch('/api/pengeluaran.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tanggal, kategori, deskripsi, jumlah, petugas})});
    const data = await res.json();
    if(!data.ok){alert(data.error||'Gagal menyimpan');return}
    msg.textContent = 'Berhasil disimpan'; msg.style.display='block';
    document.getElementById('pg_kategori').value='';
    document.getElementById('pg_deskripsi').value='';
    document.getElementById('pg_jumlah').value='';
    document.getElementById('pg_petugas').value='';
    loadLatestPengeluaran();
  }catch(e){alert(e.message)}
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>