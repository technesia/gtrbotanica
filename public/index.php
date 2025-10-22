<?php require_once __DIR__ . '/../includes/header.php'; ?>
<section class="hero" style="padding-top:24px;padding-bottom:8px;">
  <h1 style="margin:0;font-weight:700;">Analytics Dashboard</h1>
  <p style="margin-top:6px;color:#4b5563;">Status pembayaran IPL warga</p>
</section>

<div class="card" style="margin-top:8px;">
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
    <button class="button" onclick="loadHome()" style="background:#a8ff9f;">Terapkan</button>
  </div>

  <div id="homeSummary" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:12px;">
    <div class="card" style="background:linear-gradient(135deg,#2ecc71,#1abc9c);color:#fff;">
      <div>Total Unit</div>
      <div id="cardTotalUnit" style="font-size:24px;font-weight:700;">0</div>
      <div id="cardTotalUnitSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,#3498db,#6c5ce7);color:#fff;">
      <div>Warga Terdaftar</div>
      <div id="cardWarga" style="font-size:24px;font-weight:700;">0</div>
      <div id="cardWargaSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,#2ecc71,#f1c40f);color:#fff;">
      <div>Sudah Bayar IPL</div>
      <div id="cardSudah" style="font-size:24px;font-weight:700;">0</div>
      <div id="cardSudahSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
    <div class="card" style="background:linear-gradient(135deg,#e74c3c,#f39c12);color:#fff;">
      <div>Belum Bayar IPL</div>
      <div id="cardBelum" style="font-size:24px;font-weight:700;">0</div>
      <div id="cardBelumSub" class="small" style="opacity:.9;">Periode ini</div>
    </div>
  </div>

  <div style="margin-top:16px;">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <div style="flex:1 1 280px;">
        <input id="searchInput" class="input" type="text" placeholder="Cari warga..." />
      </div>
      <button id="btnSudah" class="button" onclick="setMode('paid')">Sudah Bayar (<span id="countSudah">0</span>)</button>
      <button id="btnBelum" class="button secondary" onclick="setMode('unpaid')">Belum Bayar (<span id="countBelum">0</span>)</button>
    </div>
  </div>

  <div style="margin-top:12px;">
    <table class="table">
      <thead>
        <tr>
          <th>Nama Warga</th>
          <th>Blok</th>
          <th>No Rumah</th>
          <th>Total Bayar</th>
        </tr>
      </thead>
      <tbody id="homeBody">
        <tr><td colspan="4" class="small">Memuat data...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
const state = { paid: [], unpaid: [], counts: { total_unit:0, warga_terdaftar:0, sudah_bayar:0, belum_bayar:0 }, mode: 'paid', periode: {tahun: new Date().getFullYear(), bulan: 'Periode ini'} };

function formatRupiah(n){ return 'Rp ' + Number(n||0).toLocaleString('id-ID'); }

async function loadHome(){
  const year = document.getElementById('selectYear').value;
  const month = document.getElementById('selectMonth').value;
  const q = document.getElementById('searchInput').value.trim();
  const tbody = document.getElementById('homeBody');
  tbody.innerHTML = '<tr><td colspan="4" class="small">Memuat data...</td></tr>';
  try {
    let url = `/api/home.php?year=${encodeURIComponent(year)}&month=${encodeURIComponent(month)}`;
    if(q) url += `&q=${encodeURIComponent(q)}`;
    const res = await fetch(url);
    if(!res.ok) throw new Error('Gagal memuat');
    const data = await res.json();
    if(!data.ok) throw new Error(data.error||'Gagal memuat');
    state.paid = Array.isArray(data.paid) ? data.paid : [];
    state.unpaid = Array.isArray(data.unpaid) ? data.unpaid : [];
    state.counts = data.counts || state.counts;
    state.periode = data.periode || state.periode;
    document.getElementById('cardTotalUnit').textContent = state.counts.total_unit;
    document.getElementById('cardWarga').textContent = state.counts.warga_terdaftar;
    document.getElementById('cardSudah').textContent = state.counts.sudah_bayar;
    document.getElementById('cardBelum').textContent = state.counts.belum_bayar;
    const periodeText = `${state.periode.bulan} ${state.periode.tahun}`;
    document.getElementById('cardTotalUnitSub').textContent = periodeText;
    document.getElementById('cardWargaSub').textContent = periodeText;
    document.getElementById('cardSudahSub').textContent = periodeText;
    document.getElementById('cardBelumSub').textContent = periodeText;
    document.getElementById('countSudah').textContent = state.paid.length;
    document.getElementById('countBelum').textContent = state.unpaid.length;
    renderList();
  } catch (e) {
    console.error(e);
    tbody.innerHTML = '<tr><td colspan="4">Gagal memuat data.</td></tr>';
  }
}

function setMode(m){
  state.mode = m;
  const btnSudah = document.getElementById('btnSudah');
  const btnBelum = document.getElementById('btnBelum');
  if(m==='paid'){ btnSudah.classList.remove('secondary'); btnBelum.classList.add('secondary'); }
  else { btnBelum.classList.remove('secondary'); btnSudah.classList.add('secondary'); }
  renderList();
}

function renderList(){
  const tbody = document.getElementById('homeBody');
  const items = state.mode === 'paid' ? state.paid : state.unpaid;
  if(items.length === 0){
    tbody.innerHTML = '<tr><td colspan="4">Tidak ada data.</td></tr>';
    return;
  }
  tbody.innerHTML = '';
  items.forEach(it => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${it.nama_warga}</td>
      <td>${it.blok}</td>
      <td>${it.no_rumah}</td>
      <td>${it.total_bayar ? formatRupiah(it.total_bayar) : '-'}</td>
    `;
    tbody.appendChild(tr);
  });
}

window.addEventListener('DOMContentLoaded', () => {
  loadHome();
  document.getElementById('searchInput').addEventListener('input', () => {
    loadHome();
  });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>