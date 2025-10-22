<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="card">
  <h2>Rangkuman Keuangan IPL</h2>
  <div id="summary">
    <div class="small">Memuat ringkasan...</div>
  </div>
  <button class="button" onclick="loadSummary()" style="margin-top:12px;">Refresh</button>
</div>
<script>
function formatRupiah(n){ return (n||0).toLocaleString('id-ID'); }
async function loadSummary(){
  try{
    const res = await fetch('/api/finance.php?kind=summary');
    const data = await res.json();
    const s = data.summary || { total_ipl:0, total_pemasukan_lain:0, total_pengeluaran:0, saldo_total:0 };
    document.getElementById('summary').innerHTML = `
      <table class="table">
        <tbody>
          <tr><th>Total Pemasukan IPL</th><td>Rp ${formatRupiah(s.total_ipl)}</td></tr>
          <tr><th>Total Pemasukan Lain</th><td>Rp ${formatRupiah(s.total_pemasukan_lain)}</td></tr>
          <tr><th>Total Pengeluaran</th><td>Rp ${formatRupiah(s.total_pengeluaran)}</td></tr>
          <tr><th>Saldo Total</th><td><strong>Rp ${formatRupiah(s.saldo_total)}</strong></td></tr>
        </tbody>
      </table>
    `;
  }catch(e){
    document.getElementById('summary').innerHTML = '<div class="small">Gagal memuat ringkasan.</div>';
  }
}
window.addEventListener('DOMContentLoaded', loadSummary);
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>