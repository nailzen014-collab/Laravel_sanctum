# Laporan LKPD - Catatan Belajar Pribadi

Nama: ____________________  
Kelas: ____________________  
Tanggal: __________________

## Ringkasan

Project terdiri dari dua aplikasi Laravel:

| Bagian | Lokasi | Alamat |
| --- | --- | --- |
| API (Sanctum) | `laravel_sanctum_api` | `http://127.0.0.1:8003/api` |
| Website | `catatan-belajar-web` | `http://127.0.0.1:8000` |

Website memakai HTTP client di sisi server untuk memanggil API. Token disimpan di session server (`api_token`) dan tidak pernah ditampilkan di halaman.

---

## 1. Bangun API dan aturan datanya

### Data

`users`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| id | bigint | primary key |
| name | varchar | wajib |
| email | varchar | wajib, format email, unik |
| password | varchar | wajib, minimal 8 karakter, disimpan sebagai hash |
| timestamps | datetime | created_at, updated_at |

`catatans`

| Kolom | Tipe | Aturan |
| --- | --- | --- |
| id | bigint | primary key |
| user_id | bigint | foreign key ke `users.id`, diisi server dari token |
| judul | varchar(100) | wajib, maksimal 100 karakter |
| isi | text | wajib |
| timestamps | datetime | created_at, updated_at |

### Endpoint

| Method | Endpoint | Auth | Fungsi |
| --- | --- | --- | --- |
| POST | `/api/register` | - | Buat akun, langsung dapat token |
| POST | `/api/login` | - | Verifikasi akun, dapat token baru |
| GET | `/api/me` | Bearer token | Profil user yang sedang login |
| POST | `/api/logout` | Bearer token | Cabut token yang sedang dipakai |
| GET | `/api/categories` | Bearer token | Daftar catatan milik user (alias `/api/posts`) |
| POST | `/api/posts` | Bearer token | Tambah catatan |
| GET | `/api/posts` | Bearer token | Daftar catatan milik user |
| GET | `/api/posts/{id}` | Bearer token | Detail catatan milik user |

Contoh response register (201):

```json
{
  "status": true,
  "message": "User registered successfully",
  "data": { "token": "1|....", "name": "Siswa LKPD" }
}
```

Contoh response validasi gagal (422):

```json
{
  "status": false,
  "message": "Validation error",
  "errors": { "email": ["Email sudah terdaftar."] }
}
```

### Perlindungan

- Route `/api/me`, `/api/logout`, `/api/categories`, dan `/api/posts*` memakai middleware `auth:sanctum`.
- Tanpa token atau token yang sudah dicabut, API membalas `401 Unauthenticated`.
- Catatan milik akun lain dibalas `404 Catatan tidak ditemukan.` (bukan 403), karena query dibatasi ke user pemilik token.
- Password di-hash dengan bcrypt dan tidak pernah muncul di response.

---

## 2. Website yang terhubung

Halaman yang tersedia:

| Halaman | URL | Isi |
| --- | --- | --- |
| Register | `/register` | nama, email, password, konfirmasi password |
| Login | `/login` | email, password |
| Dashboard | `/dashboard` | nama user, jumlah catatan, form judul + isi, daftar catatan, tombol Logout |
| Detail catatan | `/catatan/{id}` | judul, tanggal, isi lengkap |

Alur:

1. User mengisi form di website.
2. Form dikirim ke controller Laravel (bukan AJAX langsung dari browser).
3. Controller memanggil API dengan `Illuminate\Support\Facades\Http` melalui `App\Services\CatatanApi`.
4. Token dari API disimpan di session server, lalu dipakai sebagai `Authorization: Bearer <token>`.
5. Jika API membalas 401, session dibersihkan dan user diarahkan kembali ke halaman login.

Tampilan menggunakan kartu, warna latar kertas, dan layout responsif. Pesan kesalahan dari API ditampilkan di bawah field terkait, misalnya "Email sudah terdaftar." atau "Judul catatan maksimal 100 karakter.".

---

## 3. Uji dua akun: A dan B

Prediksi ditulis sebelum pengujian, lalu dibandingkan dengan hasil aktual. Pengujian dilakukan lewat Postman CLI (`docs/postman_collection.json`) dan lewat form website.

| No | Skenario | Prediksi sebelum uji | Hasil aktual | Kesimpulan |
| --- | --- | --- | --- | --- |
| 1 | Register valid (akun A) | 201, dapat token dan nama | `201 Created`, `status: true`, token & nama ada | Sesuai |
| 2 | Register email duplikat | 422, error pada `email` | `422`, `errors.email: ["Email sudah terdaftar."]` | Sesuai |
| 3 | Profil tanpa token | 401 | `401 Unauthenticated` | Sesuai |
| 4 | Tambah catatan valid (A) | 201, `user_id` diisi server | `201 Created`, catatan id 7 milik A | Sesuai |
| 5 | Login B, lihat catatan A | Daftar B hanya milik B; detail A 404 | B melihat 1 catatan (miliknya), akses catatan A `404 Catatan tidak ditemukan.` | Sesuai |
| 6 | Logout A, lalu akses profil | 200 saat logout, token dicabut, `/me` jadi 401 | Logout `200`, `/me` setelah logout `401` | Sesuai |

Bukti uji:

- Register: `201`, response `{"status":true,"message":"User registered successfully","data":{"token":"...","name":"Siswa LKPD"}}`.
- Email duplikat: `422`, `{"status":false,"message":"Validation error","errors":{"email":["Email sudah terdaftar."]}}`.
- Tidak ada token: `401`, `{"message":"Unauthenticated."}`.
- Tambah catatan: `201`, `{"status":true,"message":"Catatan berhasil dibuat","data":{"id":7,...}}`.
- Isolasi data: akun B hanya mendapat catatan miliknya; detail catatan akun A dibalas `404`.
- Logout: `200`, lalu `/api/me` dengan token lama dibalas `401`.

Uji form website:

| Skenario | Hasil |
| --- | --- |
| Register valid | Redirect ke dashboard, sapaan "Halo, Siti" muncul |
| Judul & isi dikosongkan | Tetap di dashboard, muncul "Judul catatan wajib diisi." dan "Isi catatan wajib diisi." |
| Judul 120 karakter | Muncul "Judul catatan maksimal 100 karakter." |
| Simpan catatan valid | Catatan muncul di daftar, jumlah catatan bertambah |
| Login password salah | Muncul "Unauthorized" |
| Register email duplikat | Muncul "Email sudah terdaftar." |
| Konfirmasi password beda | Muncul "Konfirmasi password tidak cocok." |
| Logout | Kembali ke halaman login |
| Buka dashboard tanpa sesi | Dialihkan ke halaman login |

Ringkasan Postman CLI: 16 request, seluruh assertion lulus (0 failed), rata-rata response 229 ms.

---

## 4. Jelaskan alasannya

**1. Apa perbedaan register dan login?**

Register membuat user baru: mengecek email belum pernah dipakai, menyimpan password sebagai hash, lalu menerbitkan token. Login tidak membuat user baru; ia mencocokkan email dan password dengan data yang sudah ada (`Auth::attempt`), lalu menerbitkan token baru. Jadi register untuk pendaftaran pertama, login untuk membuka sesi berikutnya.

**2. Mengapa user_id catatan tidak dikirim server dari sisi client?**

Karena data dari client mudah dimanipulasi. Kalau client mengirim `user_id`, orang bisa mengaku sebagai pengguna lain hanya dengan mengganti angka. Karena itu server mengambil pemilik dari token yang valid (`$request->user()`), sehingga `user_id` selalu sesuai dengan pemilik token dan tidak bisa dipalsukan.

**3. Bukti apa yang menunjukkan login benar-benar membuat catatan atas nama pemiliknya?**

Setiap query catatan dimulai dari relasi milik user token:

```php
$request->user()->catatans()->create($validated);
$request->user()->catatans()->latest()->get();
$request->user()->catatans()->find($id);
```

Akibatnya: (a) catatan yang dibuat otomatis berisi `user_id` pemilik token, (b) daftar catatan hanya berisi catatan user tersebut, dan (c) detail catatan milik orang lain dibalas 404. Uji akun B membuktikan hal ini: B tidak melihat catatan A, dan akses langsung ke id catatan A ditolak.

---

## 5. Kumpulan hasil

- API: `laravel_sanctum_api` (endpoint, model `Catatan`, migration `catatans`).
- Website: `catatan-belajar-web` (controller, service HTTP client, view register/login/dashboard).
- Collection Postman: `laravel_sanctum_api/docs/postman_collection.json`.
- README instalasi dan alamat API: `laravel_sanctum_api/README.md` dan `catatan-belajar-web/README.md`.

Langkah menjalankan dua project:

```bash
# terminal 1
cd laravel_sanctum_api
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8003

# terminal 2
cd catatan-belajar-web
php artisan serve --host=127.0.0.1 --port=8000
```

Untuk versi PDF, lampirkan screenshot: (1) halaman register, (2) halaman login, (3) dashboard berisi catatan, (4) Postman register sukses, (5) Postman email duplikat 422, (6) Postman akses tanpa token 401.
