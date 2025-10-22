<?php require_once __DIR__ . '/../includes/auth.php'; require_admin(); ?>
<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="card">
  <h2 style="margin-top:0;">Input Pemasukan</h2>
  <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
    <div>
      <label for="pm_tanggal">Tanggal</label>
      <input type="date" id="pm_tanggal">
    </div>
    <div>
      <label for="pm_jumlah">Jumlah (Rp)</label>
      <input type="number" id="pm_jumlah" min="0" step="1" placeholder="0">
    </div>
    <div style="grid-column:1 / span 2;">
      <label for="pm_keterangan">Keterangan</label>
      <input type="text" id="pm_keterangan" placeholder="Misal: Donasi warganet">
    </div>
  </div>
  <div style="margin-top:12px;display:flex;gap:8px;">
    <button class="btn" onclick="savePemasukan()">Simpan</button>
    <a class="btn btn-outline" href="/dashboard.php">Ke Dashboard</a>
  </div>
  <p id="pm_msg" class="small" style="margin-top:8px;color:#0b7;display:none;"></p>
</div>

<div class="card">
  <h3 style="margin-top:0;">Riwayat Terbaru</h3>
  <table class="table" style="width:100%;">
    <thead>
      <tr><th>Tanggal</th><th>Keterangan</th><th style="text-align:right;">Jumlah</th></tr>
    </thead>
    <tbody id="pm_list"><tr><td colspan="3" class="small">Memuat data...</td></tr></tbody>
  </table>
</div>

<script>
function formatRupiah(n){return new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',maximumFractionDigits:0}).format(n||0)}
function today(){const d=new Date();return d.toISOString().slice(0,10)}

document.addEventListener('DOMContentLoaded',()=>{
  document.getElementById('pm_tanggal').value = today();
  loadLatest();
});

async function loadLatest(){
  try{
    const res = await fetch('/api/pemasukan.php?limit=10');
    const data = await res.json();
    const tbody = document.getElementById('pm_list');
    if(!data.ok){tbody.innerHTML = `<tr><td colspan=3>${data.error||'Gagal memuat'}</td></tr>`;return}
    if(data.data.length===0){tbody.innerHTML = '<tr><td colspan=3 class="small">Belum ada data.</td></tr>';return}
    tbody.innerHTML = data.data.map(r=>`<tr>
      <td>${r.tanggal}</td>
      <td>${r.keterangan||''}</td>
      <td style="text-align:right;">${formatRupiah(r.jumlah)}</td>
    </tr>`).join('');
  }catch(e){document.getElementById('pm_list').innerHTML = `<tr><td colspan=3>${e.message}</td></tr>`}
}

async function savePemasukan(){
  const tanggal = document.getElementById('pm_tanggal').value;
  const jumlah = parseInt(document.getElementById('pm_jumlah').value||'0',10);
  const keterangan = document.getElementById('pm_keterangan').value.trim();
  const msg = document.getElementById('pm_msg');
  msg.style.display='none';
  if(!tanggal||!(jumlah>0)){alert('Tanggal dan jumlah wajib diisi');return}
  try{
    const res = await fetch('/api/pemasukan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({tanggal, jumlah, keterangan})});
    const data = await res.json();
    if(!data.ok){alert(data.error||'Gagal menyimpan');return}
    msg.textContent = 'Berhasil disimpan'; msg.style.display='block';
    document.getElementById('pm_jumlah').value='';
    document.getElementById('pm_keterangan').value='';
    loadLatest();
  }catch(e){alert(e.message)}
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>