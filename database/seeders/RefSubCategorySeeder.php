<?php

namespace Database\Seeders;

use App\Models\RefCategory;
use App\Models\RefSubCategory;
use Illuminate\Database\Seeder;

class RefSubCategorySeeder extends Seeder
{
    public function run(): void
    {
        $mapping = [
            'Peralatan Komputer' => [
                'Komputer Desktop', 'Laptop', 'Monitor', 'Keyboard/Mouse',
                'Pencetak', 'Scanner', 'Aksesori (USB, Headset, Cable, Projector)'
            ],
            'Rangkaian (Network)' => [
                'Sambungan Internet (LAN/WiFi)', 'VPN/Remote Access',
                'Switch/Router', 'Firewall/Security', 'Gangguan Rangkaian'
            ],
            'Akses Sistem/Akaun' => [
                'Login ID/Password Reset', 'Akaun Dikunci', 'Pendaftaran Akaun Baru',
                'Tukar Peranan/Hak Akses', 'Sistem Tidak Berfungsi'
            ],
            'Perisian & Aplikasi' => [
                'Sistem Dalaman (HRMIS, ePerolehan, dll.)',
                'Microsoft Office (Word, Excel, Outlook, PowerPoint)',
                'Browser (Chrome, Edge, Firefox)',
                'Email (Outlook/Webmail)',
                'Antivirus/Security Software',
                'Perisian Pihak Ketiga (Adobe, AutoCAD, dll.)'
            ],
            'Percetakan & Imbasan' => [
                'Printer Offline', 'Paper Jam', 'Tidak Boleh Cetak/Scan',
                'Printer Sharing/Network Printer', 'Toner/Cartridge'
            ],
            'Telefon & Komunikasi' => [
                'Telefon IP/PABX', 'VoIP', 'Webex/Zoom/MS Teams',
                'Emel Rasmi', 'SMS Gateway'
            ],
            'Keselamatan ICT' => [
                'Virus/Malware', 'Phishing/Spam', 'Kebocoran Data',
                'Akses Tidak Sah', 'Insiden Keselamatan'
            ],
            'Pelayan & Storan' => [
                'Server Down', 'Backup & Restore',
                'Database Issue (MySQL, MSSQL, Oracle)',
                'Storage/Disk Full', 'Virtual Machine (VM)'
            ],
            'Permintaan ICT' => [
                'Permohonan Peralatan ICT Baru', 'Permohonan Akses Sistem',
                'Permohonan Upgrade Software', 'Permohonan Upgrade Hardware',
                'Permohonan Domain/Hosting'
            ],
            'Lain-lain' => [
                'Pertanyaan Umum ICT', 'Bantuan Teknikal Lain', 'Aduan Tidak Spesifik'
            ],
        ];

        foreach ($mapping as $catName => $subcats) {
            $category = RefCategory::where('cat_name', $catName)->first();
            if ($category) {
                foreach ($subcats as $subcat) {
                    RefSubCategory::firstOrCreate([
                        'cat_id'      => $category->id,
                        'subcat_name' => $subcat,
                    ]);
                }
            }
        }
    }
}

