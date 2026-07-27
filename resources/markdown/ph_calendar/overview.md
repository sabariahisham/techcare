# 1. Projek Overview: Pengurusan Cuti Umum Malaysia

Sistem ini direka menggunakan **Laravel 12** dan **PHP 8.4** untuk mengautomasikan kutipan data cuti kelepasan am Malaysia dari `publicholidays.com.my`, serta menyediakan antara muka CRUD penuh untuk pengurusan manual.

## Objektif Utama
*   Mengekstrak data cuti am (Nama Cuti & Tarikh) mengikut tahun secara dinamik melalui web scraping.
*   Menguruskan skop kebolehpakaian cuti: **Cuti Nasional (Semua Negeri)**, **Sebahagian Negeri Sahaja**, atau dengan **Negeri Berkecuali (Except)**.
*   Menyediakan borang CRUD (Tambah, Edit, Padam) melalui antara muka admin Bootstrap Sneat.
*   Menyediakan fungsi automasi jadual scraping bulanan tanpa intervensi manual.

## Seni Bina Data (Data Architecture)

Sistem menggunakan **JSON columns** terus pada jadual `public_holidays` — tiada jadual pivot atau Many-to-Many.

| Column | Type | Keterangan |
|---|---|---|
| `states` | `JSON / NULL` | `NULL` = Cuti Nasional. Array negeri = Cuti spesifik negeri |
| `states_except` | `JSON / NULL` | Array negeri yang **dikecualikan**. Opsional |

### Logik Negeri
- **Nasional:** Pilih "Nasional" → `states = null` dalam DB
- **Spesifik Negeri:** Pilih negeri tertentu → `states = ["Johor", "Selangor"]`
- **Nasional + Berkecuali:** `states = null`, `states_except = ["Sabah", "Sarawak"]`

## Keperluan Sistem & Stack

| Layer | Teknologi | Versi |
|---|---|---|
| Backend Framework | Laravel | 12.x |
| Programming Language | PHP | 8.4+ |
| Frontend UI | Bootstrap (Sneat Admin Template) | 5.x |
| Multi-select UI | Select2 | 4.x |
| Client Validation | FormValidation.js | 1.x |
| HTML Parser (Scraper) | `voku/simple_html_dom` | — |
| Audit Trail | `owen-it/laravel-auditing` | — |

## Pemasangan Pakej Keperluan
```bash
composer require voku/simple_html_dom
```
