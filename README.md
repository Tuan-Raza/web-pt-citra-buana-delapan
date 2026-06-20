# 📦 Inventory v3.5 — PT Citra Buana Delapan

### 🏬 Sistem Manajemen Inventori Berbasis Web | Stationery & Daily Essentials
Sistem stok barang dengan kontrol akses berbasis peran (*role-based access*) untuk Admin Gudang dan Staff, dibangun dengan **Native PHP & MySQL**.

![Status](https://img.shields.io/badge/Status-Under%20Development-yellow?style=for-the-badge)
![Version](https://img.shields.io/badge/Version-3.5-blue?style=for-the-badge)
![License](https://img.shields.io/badge/License-Private-lightgrey?style=for-the-badge)

---

## 🧾 Tentang Proyek

**Inventory v3.5** adalah sistem manajemen stok yang dikembangkan khusus untuk **PT Citra Buana Delapan**, perusahaan yang bergerak di bidang *stationery* dan kebutuhan harian. Sistem ini dirancang untuk menggantikan pencatatan manual dengan alur kerja digital yang terstruktur, memisahkan akses antara **Admin Gudang** (kontrol penuh, khusus desktop) dan **Staff** (operasional harian, *responsive* di HP maupun laptop).

---

## 🎯 Fitur Utama

- 🔐 **Autentikasi Berbasis Peran:** Login terpisah untuk *Admin Gudang* dan *Pengguna (Staff)* dengan tab switching pada satu halaman.
- 🖥️ **Akses Adaptif per Perangkat:** Dashboard Admin hanya bisa diakses dari layar ≥ 1024px (PC/laptop); akses dari HP otomatis ditolak demi keamanan data master.
- 📊 **Dashboard Admin:** CRUD data barang master, manajemen akun staff & reset sandi, serta monitoring log aktivitas karyawan.
- 📱 **Portal Staff Responsif:** Cek stok (*read-only*), input mutasi barang masuk/keluar dengan *quick input*, dan update stok master otomatis ke database.
- ✍️ **Registrasi Akun (`daftar.php`):** Pendaftaran akun baru dengan alur persetujuan berbasis role.
- 🔑 **Lupa Sandi (`lupa_sandi.php`):** Reset password mandiri dengan UI split-panel yang konsisten dengan halaman login.

---

## 🖼️ Tampilan Aplikasi

<div align="center">
  <img src="./screenshots/login-page.png" alt="Halaman Login Inventory v3.5" width="650"/>
  <p><i>Halaman Login — Split Panel UI dengan pemilihan role Admin Gudang / Pengguna Staff</i></p>
</div>

---

## 🔄 Alur Sistem (System Flow)

```mermaid
flowchart TD
    Start([Mulai]) --> Login[Halaman Login]
    Login --> InputData[/User Input: Username & Password/]
    InputData --> Validasi{Validasi Akun}

    Validasi -->|Gagal| ErrorMsg[Kembali ke Login + Pesan Error]
    ErrorMsg --> Login

    Validasi -->|Sukses| CekRole{Cek Role Session}

    CekRole -->|Role: Admin| CekLayar{Cek Perangkat Layar}
    CekLayar -->|"< 1024px (HP)"| Tolak[Akses Ditolak]
    Tolak --> Login
    CekLayar -->|">= 1024px (PC)"| DashAdmin[Dashboard Admin]
    DashAdmin --> A1[CRUD Data Barang Master]
    DashAdmin --> A2[CRUD Manajemen Akun Staff & Reset Sandi]
    DashAdmin --> A3[Monitoring Log Aktivitas Karyawan]

    CekRole -->|Role: Staff| PortalStaff[Portal Staff Responsif]
    PortalStaff --> CekStok[Cek Stok - Read Only]
    PortalStaff --> InputMutasi[Input Mutasi Masuk/Keluar]
    InputMutasi --> QuickInput[Proses Quick Input]
    QuickInput --> UpdateDB[Update Stok Master Otomatis di DB]
    UpdateDB --> LogAdmin[Kirim Info Log ke Dashboard Admin]
    PortalStaff --> Pengaturan[Pengaturan Akun Pribadi]
```

> 💡 Diagram di atas otomatis ter-render di GitHub karena menggunakan format Mermaid.

---

## 🛠️ Tech Stack

![](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)
![](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)

---

## 📂 Struktur Proyek

```
inventory-v3.5/
├── login.php
├── daftar.php
├── lupa_sandi.php
├── admin/
│   ├── dashboard.php
│   ├── barang.php
│   ├── akun_staff.php
│   └── log_aktivitas.php
├── staff/
│   ├── dashboard.php
│   ├── cek_stok.php
│   ├── mutasi.php
│   └── pengaturan_akun.php
├── assets/
│   ├── css/shared.css
│   └── js/shared.js
├── config/
│   └── database.php
└── screenshots/
    └── login-page.png
```

---

## ⚙️ Instalasi & Setup

<details>
  <summary><b>📥 Cara Menjalankan Secara Lokal</b></summary>
  <br>

  1. Clone repository ini ke folder htdocs (XAMPP) atau folder server lokal Anda.
  2. Import database melalui phpMyAdmin (file `.sql` ada di folder `database/`).
  3. Sesuaikan kredensial koneksi database di `config/database.php`.
  4. Jalankan Apache & MySQL melalui XAMPP/Laragon.
  5. Akses `http://localhost/inventory-v3.5/login.php` di browser.

</details>

---

## 🚧 Roadmap

- [x] Halaman Login (Split Panel UI, role-based)
- [x] Halaman Registrasi (`daftar.php`)
- [x] Halaman Lupa Sandi (`lupa_sandi.php`)
- [ ] Dashboard Admin (CRUD Barang & Akun Staff)
- [ ] Portal Staff Responsif
- [ ] Modul Monitoring Log Aktivitas

---

## 👨‍💻 Developer

**Raza Ikhsan Al Fitrah**
Founder & CEO of [Azaadesigns ID](https://azaadesigns-id.vercel.app/) · Full-Stack Developer · Workflow Automation Engineer

- 📧 azzyycans@gmail.com
- 📸 [@raaa_zaaaa](https://www.instagram.com/raaa_zaaaa/)

---

<div align="center">
  <sub>© 2026 PT Citra Buana Delapan — Internal Use System</sub>
</div>
