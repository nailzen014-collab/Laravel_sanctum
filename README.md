# Catatan Belajar API

API Laravel Sanctum untuk website catatan belajar pribadi. Dibuat untuk tugas LKPD: pengguna bisa mendaftar, login, melihat profil, menambah catatan, dan membaca catatan miliknya sendiri.

- Alamat API lokal: `http://127.0.0.1:8003/api`
- Frontend: `../catatan-belajar-web` (Laravel terpisah, berjalan di `http://127.0.0.1:8000`)
- Collection Postman: [`docs/postman_collection.json`](docs/postman_collection.json)
- Laporan uji dan penjelasan: [`LAPORAN.md`](LAPORAN.md)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate

# sesuaikan koneksi database di .env, lalu:
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8003
```

Database yang dipakai project ini: `api_sanctum_laravel` (MySQL).

## Endpoint

| Method | Endpoint | Butuh token | Keterangan |
| --- | --- | --- | --- |
| POST | `/api/register` | Tidak | Daftar akun: `name`, `email`, `password`, `confirm_password` |
| POST | `/api/login` | Tidak | Login: `email`, `password` |
| GET | `/api/me` | Ya | Profil user dari token + jumlah catatan |
| GET | `/api/user` | Ya | Data user (endpoint lama, tetap dipertahankan) |
| POST | `/api/logout` | Ya | Cabut token yang sedang dipakai |
| GET | `/api/categories` | Ya | Daftar catatan milik user (alias dari `/api/posts`) |
| GET | `/api/posts` | Ya | Daftar catatan milik user |
| POST | `/api/posts` | Ya | Tambah catatan: `judul`, `isi` |
| GET | `/api/posts/{id}` | Ya | Detail catatan milik user |

Semua endpoint yang butuh token memakai header:

```
Authorization: Bearer <token>
Accept: application/json
```

## Aturan data

- Password disimpan sebagai hash (bcrypt), tidak pernah dikembalikan di response.
- `user_id` catatan **tidak** dikirim dari client. Server mengambil pemilik dari token (`$request->user()`), lalu semua query catatan dibatasi ke user tersebut.
- Validasi register: nama wajib, email valid dan unik, password minimal 8 karakter dan harus sama dengan konfirmasi.
- Validasi catatan: judul wajib (maksimal 100 karakter), isi wajib.

## Struktur database

`users`: `id`, `name`, `email`, `password`, `timestamps`

`catatans`: `id`, `user_id` (foreign key ke users), `judul` (100), `isi` (text), `timestamps`

`personal_access_tokens`: tabel bawaan Sanctum untuk token.

## Uji cepat dengan Postman CLI

```bash
postman collection run docs/postman_collection.json
```

Collection berisi 16 request: alur normal (register, login, profil, catatan, logout) dan uji negatif (email duplikat, password pendek, konfirmasi salah, judul kosong/terlalu panjang, akses tanpa token).
# Laravel_sanctum-frontend-
