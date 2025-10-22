<?php require_once __DIR__ . '/../includes/header.php'; ?>
<?php $view = $_GET['view'] ?? 'laporan'; $heroTitle = ($view === 'warga') ? 'Daftar Warga Cluster Botanica' : 'Master Data Cluster Botanica'; $heroSub = ($view === 'warga') ? 'Update per Agustus 2024' : 'Laporan keuangan bulanan dan tahunan'; ?>
<section class="hero" style="padding-top:24px;padding-bottom:8px;">
  <h1 style="margin:0;font-weight:700;<?= '' ?>"><?= htmlspecialchars($heroTitle) ?></h1>
  <p style="margin-top:6px;color:#4b5563;<?= '' ?>"><?= htmlspecialchars($heroSub) ?></p>
</section>

<div id="sectionLaporan" class="card" style="margin-top:8px;">
  <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
    <select id="selectYear" class="input" style="max-width:120px;">
      <option value="2023">2023</option>
      <option value="2024">2024</option>
      <option value="2025" selected>2025</option>
    </select>
    <select id="selectMonth" class="input" style="max-width:160px;">
      <option value="all" selected>Semua Bulan</option>
      <option value="januari">Januari</option>
      <option value="februari">Februari</option>
      <option value="maret">Maret</option>
      <option value="april">April</option>
      <option value="mei">Mei</option>
      <option value="juni">Juni</option>
      <option value="juli">Juli</option>
      <option value="agustus">Agustus</option>
      <option value="september">September</option>
      <option value="oktober">Oktober</option>
      <option value="november">November</option>
      <option value="desember">Desember</option>
    </select>
    <button class="button" onclick="loadDashboard()">Terapkan</button>
  </div>

  <div id="summaryCards" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px;">
    <div class="card" style="background:linear-gradient(135deg,#16a085,#2ecc71);color:#fff;">
      <div>Pendapatan</div>
      <div id="cardPendapatan" style="font-size:24px;font-weight:700;">Rp 0</div>
      <div id="cardPendapatanSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,#e74c3c,#f39c12);color:#fff;">
      <div>Pengeluaran</div>
      <div id="cardPengeluaran" style="font-size:24px;font-weight:700;">Rp 0</div>
      <div id="cardPengeluaranSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,#2980b9,#6c5ce7);color:#fff;">
      <div>Saldo</div>
      <div id="cardSaldo" style="font-size:24px;font-weight:700;">Rp 0</div>
      <div id="cardSaldoSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
  </div>

  <div style="margin-top:16px;">
    <table class="table">
      <thead>
        <tr>
          <th>Tanggal</th>
          <th>Kategori</th>
          <th>Deskripsi</th>
          <th>Pendapatan</th>
          <th>Pengeluaran</th>
          <th>Saldo</th>
        </tr>
      </thead>
      <tbody id="transBody">
        <tr><td colspan="6" class="small">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Daftar Warga (read-only untuk user biasa) -->
<div id="sectionWarga" class="card" style="margin-top:16px; display:none;">
  <div style="display:flex;align-items:center;justify-content:space-between;">
    <h3 style="margin:0;">Daftar Warga</h3>
  </div>
  <div style="margin-top:8px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
    <input id="wargaSearch" class="input" placeholder="Cari nama/blok..." style="max-width:220px;" />
    <button class="button secondary" onclick="loadWargaDashboard(1)">Cari</button>
  </div>
  <table class="table">
    <thead>
      <tr><th>Blok</th><th>Nama</th><th>Status</th></tr>
    </thead>
    <tbody id="wargaDashBody">
      <tr><td colspan="3" class="small">Memuat data...</td></tr>
    </tbody>
  </table>
  <div id="wargaDashPagination" style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;"></div>
</div>

<script>
function formatRupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }
function formatDate(str){
  if(!str) return '-';
  const d = new Date(str);
  if (isNaN(d)) {
    const parts = String(str).split('-');
    if (parts.length === 3) return `${parts[2]}/${parts[1]}/${parts[0]}`;
    return str;
  }
  const dd = String(d.getDate()).padStart(2,'0');
  const mm = String(d.getMonth()+1).padStart(2,'0');
  const yyyy = d.getFullYear();
  return `${dd}/${mm}/${yyyy}`;
}

async function loadDashboard(){
  const year = document.getElementById('selectYear').value;
  const month = document.getElementById('selectMonth').value;
  const tbody = document.getElementById('transBody');
  tbody.innerHTML = '<tr><td colspan="6" class="small">Memuat data...</td></tr>';
  try {
    const res = await fetch(`/api/finance.php?kind=dashboard&year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}`);
    if(!res.ok){ throw new Error('Gagal memuat'); }
    const data = await res.json();
    const s = data.summary || { pendapatan_total:0, total_pengeluaran:0, saldo:0, periode:{tahun:year, bulan:'Periode ini'} };
    document.getElementById('cardPendapatan').textContent = formatRupiah(s.pendapatan_total ?? s.pendapatan_ipl ?? 0);
    document.getElementById('cardPengeluaran').textContent = formatRupiah(s.total_pengeluaran);
    document.getElementById('cardSaldo').textContent = formatRupiah(s.saldo);
    const periodeText = (s.periode?.bulan || 'Periode ini');
    document.getElementById('cardPendapatanSub').textContent = periodeText;
    document.getElementById('cardPengeluaranSub').textContent = periodeText;
    document.getElementById('cardSaldoSub').textContent = periodeText;

    const items = Array.isArray(data.items) ? data.items : [];
    if(items.length === 0){
      tbody.innerHTML = `<tr><td colspan="6">Tidak ada transaksi untuk ${periodeText} ${year||''}</td></tr>`;
    } else {
      tbody.innerHTML = '';
      items.forEach(function(r){
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${formatDate(r.tanggal)}</td><td>${r.kategori||''}</td><td>${(r.deskripsi||'')}</td><td style="text-align:right;">${formatRupiah(r.pendapatan||0)}</td><td style="text-align:right;">${formatRupiah(r.pengeluaran||0)}</td><td style="text-align:right;">${formatRupiah(r.saldo||0)}</td>`;
        tbody.appendChild(tr);
      });
    }
  } catch (e) {
    tbody.innerHTML = '<tr><td colspan="6" class="small">Gagal memuat data.</td></tr>';
  }
}

async function loadWargaDashboard(page){
  const q = document.getElementById('wargaSearch').value.trim();
  const tbody = document.getElementById('wargaDashBody');
  const pag = document.getElementById('wargaDashPagination');
  tbody.innerHTML = '<tr><td colspan="3" class="small">Memuat data...</td></tr>';
  pag.innerHTML = '';
  try{
    const url = new URL('/api/users.php', location.origin);
    if(q) url.searchParams.set('q', q);
    url.searchParams.set('page', String(page||1));
    url.searchParams.set('limit', '10');
    const res = await fetch(url.toString());
    const data = await res.json();
    const rows = Array.isArray(data.users) ? data.users : [];
    if(rows.length===0){ tbody.innerHTML = '<tr><td colspan="3" class="small">Data tidak ditemukan</td></tr>'; }
    else{
      tbody.innerHTML = '';
      rows.forEach(function(r){
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${r.blok_rumah||r.blok||''}</td><td>${r.nama_warga||r.nama||''}</td><td>${r.status||''}</td>`;
        tbody.appendChild(tr);
      });
    }
    const totalPages = Number(data.total_pages||1);
    const current = Number(data.page||1);
    const windowSize = 5;
    const windowStart = Math.floor((current-1)/windowSize)*windowSize + 1;
    const windowEnd = Math.min(windowStart + windowSize - 1, totalPages);

    // Prev (shift window)
    const prev = document.createElement('button'); prev.className='button secondary'; prev.textContent='Prev';
    prev.disabled = windowStart <= 1;
    prev.addEventListener('click', function(){ const target = Math.max(windowStart - windowSize, 1); loadWargaDashboard(target); });
    pag.appendChild(prev);

    for(let i=windowStart;i<=windowEnd;i++){
      const b = document.createElement('button');
      b.className = (i===current) ? 'button' : 'button secondary';
      b.textContent = String(i);
      b.addEventListener('click', function(){ loadWargaDashboard(i); });
      pag.appendChild(b);
    }

    // Next (shift window)
    const next = document.createElement('button'); next.className='button secondary'; next.textContent='Next';
    next.disabled = windowEnd >= totalPages;
    next.addEventListener('click', function(){ const target = Math.min(windowStart + windowSize, totalPages); loadWargaDashboard(target); });
    pag.appendChild(next);
  }catch(e){
    tbody.innerHTML = '<tr><td colspan="3" class="small">Gagal memuat daftar warga</td></tr>';
  }
}

function switchView(view){
  const secLap = document.getElementById('sectionLaporan');
  const secWar = document.getElementById('sectionWarga');
  if(!secLap || !secWar) return;
  if(view === 'warga'){
    secLap.style.display = 'none';
    secWar.style.display = '';
    loadWargaDashboard(1);
  } else {
    secLap.style.display = '';
    secWar.style.display = 'none';
    loadDashboard();
  }
}

document.addEventListener('DOMContentLoaded', function(){
  const params = new URLSearchParams(location.search);
  const initialView = params.get('view') || 'laporan';
  switchView(initialView);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>