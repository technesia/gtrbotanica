# Grand Tenjo Residence – Cluster Botanica

Website sederhana berbasis PHP + SQLite untuk dashboard keuangan, daftar warga, dan upload bukti transfer.

## Menjalankan secara lokal

- Prasyarat: PHP 8.1+ (disarankan 8.2), ekstensi SQLite aktif
- Jalankan server dev:

```
php -S 127.0.0.1:8081 -t public public/router.php
```

Buka `http://127.0.0.1:8081/` di browser.

## Struktur direktori
- `public/` : Frontend (PHP views, assets statis, router)
- `api/`    : Endpoint API (pemasukan, pengeluaran, users, finance)
- `includes/` : Auth dan koneksi DB
- `assets/` : CSS

## Deploy ke GitHub
Repo: `https://github.com/technesia/gtrbotanica`

Langkah singkat:
1. Inisialisasi git dan commit awal (sudah disiapkan oleh asisten).
2. Tambah remote `origin` ke repo GitHub Anda.
3. Push ke branch `main` menggunakan Personal Access Token (PAT).

Contoh push (PowerShell), ganti `YOUR_PAT` dengan token Anda (scope `repo`):
```
setx GH_TOKEN "YOUR_PAT"
$env:GH_TOKEN = "YOUR_PAT"
git push https://technesia:$env:GH_TOKEN@github.com/technesia/gtrbotanica.git main
```

## Jadikan Public
Di GitHub: Settings → General → Change repository visibility → Public.
Atau via API:
```
curl -H "Authorization: Bearer YOUR_PAT" -X PATCH \
  https://api.github.com/repos/technesia/gtrbotanica \
  -d '{"private": false}'
```

## Catatan
- File runtime seperti `*.sqlite` dan folder `uploads/` sudah di-ignore.
- Jika ingin menyertakan seed data, buat skrip SQL terpisah di folder root (mis. `botanica_seed.sql`).