<?php require_once __DIR__ . '/../includes/header.php'; ?>
<div class="auth-wrapper">
  <div class="card auth-card">
    <h2 style="margin-top:0;">Login Admin</h2>
    <form id="login-form" class="form" onsubmit="return false;">
        <label>Username</label>
        <input class="input" type="text" id="username" placeholder="Masukkan username" autocomplete="username" required />
        <label>Password</label>
        <input class="input" type="password" id="password" placeholder="Masukkan password" autocomplete="current-password" required />
        <button class="button" onclick="doLogin()">Login</button>
        <p id="login-msg" class="small"></p>
    </form>
  </div>
</div>
<script>
async function doLogin(){
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  const msg = document.getElementById('login-msg');
  msg.textContent = '';
  try {
    const res = await fetch('/api/auth.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password })
    });
    if(!res.ok){
      const err = await res.json().catch(()=>({error:'Login gagal'}));
      msg.textContent = err.error || 'Login gagal';
      msg.style.color = '#e74c3c';
      return;
    }
    const data = await res.json();
    msg.textContent = 'Berhasil login';
    msg.style.color = '#2ecc71';
    const params = new URLSearchParams(window.location.search);
    const next = params.get('next') || '/index.php';
    setTimeout(()=>{ window.location.href = next; }, 500);
  } catch (e) {
    msg.textContent = 'Terjadi kesalahan jaringan';
    msg.style.color = '#e74c3c';
  }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>