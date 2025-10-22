<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
  <h2>Input IPL</h2>
  <form class="form" id="ipl-form" onsubmit="return false;">
    <label>Nama Warga</label>
    <input class="input" id="nama_warga" placeholder="Nama Warga" required />
    <label>Blok</label>
    <input class="input" id="blok" placeholder="Contoh: BA1" required />
    <label>No. Rumah</label>
    <input class="input" id="no_rumah" placeholder="Nomor rumah" required />
    <label>Status Hunian</label>
    <select id="status" class="input">
      <option value="huni">Huni</option>
      <option value="tidak_huni">Tidak Huni</option>
    </select>
    <label>Tahun</label>
    <select id="tahun" class="input">
      <option value="2023">2023</option>
      <option value="2024">2024</option>
      <option value="2025" selected>2025</option>
    </select>
    <label>Bulan</label>
    <select id="bulan" class="input">
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
      <option value="desember">Desember</option>
    </select>
    <label>Nominal (Rp)</label>
    <input class="input" type="number" id="nominal" min="0" step="1000" value="0" required />
    <button class="button" onclick="submitIPL()">Simpan IPL</button>
    <p id="msg" class="small"></p>
  </form>
</div>
<script>
async function submitIPL(){
  const msg = document.getElementById('msg');
  msg.textContent = '';
  try{
    const payload = {
      nama_warga: document.getElementById('nama_warga').value.trim(),
      blok: document.getElementById('blok').value.trim(),
      no_rumah: document.getElementById('no_rumah').value.trim(),
      status: document.getElementById('status').value,
      tahun: parseInt(document.getElementById('tahun').value, 10) || new Date().getFullYear(),
      bulan: document.getElementById('bulan').value,
      nominal: parseInt(document.getElementById('nominal').value, 10) || 0,
    };
    const res = await fetch('/api/ipl.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
    const data = await res.json();
    if(!res.ok){ throw new Error(data.error||'Gagal menyimpan IPL'); }
    msg.textContent = 'Tersimpan'; msg.style.color = '#2ecc71';
  }catch(e){ msg.textContent = e.message; msg.style.color = '#e74c3c'; }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>