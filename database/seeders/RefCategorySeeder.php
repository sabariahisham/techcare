<?php

namespace Database\Seeders;

use App\Models\RefCategory;
use Illuminate\Database\Seeder;

class RefCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['cat_name' => 'Peralatan Komputer',  'cat_desc' => 'Komputer Desktop, Laptop, Monitor, Keyboard/Mouse, Pencetak, Scanner, Aksesori (USB, Headset, Cable, Projector)'],
            ['cat_name' => 'Rangkaian (Network)', 'cat_desc' => 'Sambungan Internet (LAN/WiFi), VPN/Remote Access, Switch/Router, Firewall/Security, Gangguan Rangkaian'],
            ['cat_name' => 'Akses Sistem/Akaun',  'cat_desc' => 'Login ID/Password Reset, Akaun Dikunci, Pendaftaran Akaun Baru, Tukar Peranan/Hak Akses, Sistem Tidak Berfungsi'],
            ['cat_name' => 'Perisian & Aplikasi', 'cat_desc' => 'Sistem Dalaman (HRMIS, ePerolehan, dll.), Microsoft Office, Browser, Email, Antivirus/Security Software, Perisian Pihak Ketiga'],
            ['cat_name' => 'Percetakan & Imbasan','cat_desc' => 'Printer Offline, Paper Jam, Tidak Boleh Cetak/Scan, Printer Sharing/Network Printer, Toner/Cartridge'],
            ['cat_name' => 'Telefon & Komunikasi','cat_desc' => 'Telefon IP/PABX, VoIP, Webex/Zoom/MS Teams, Emel Rasmi, SMS Gateway'],
            ['cat_name' => 'Keselamatan ICT',     'cat_desc' => 'Virus/Malware, Phishing/Spam, Kebocoran Data, Akses Tidak Sah, Insiden Keselamatan'],
            ['cat_name' => 'Pelayan & Storan',    'cat_desc' => 'Server Down, Backup & Restore, Database Issue (MySQL, MSSQL, Oracle), Storage/Disk Full, Virtual Machine (VM)'],
            ['cat_name' => 'Permintaan ICT',      'cat_desc' => 'Permohonan Peralatan ICT Baru, Permohonan Akses Sistem, Permohonan Upgrade Software/Hardware, Permohonan Domain/Hosting'],
            ['cat_name' => 'Lain-lain',           'cat_desc' => 'Pertanyaan Umum ICT, Bantuan Teknikal Lain, Aduan Tidak Spesifik'],
        ];

        foreach ($categories as $category) {
            RefCategory::firstOrCreate(['cat_name' => $category['cat_name']], $category);
        }
    }
}
