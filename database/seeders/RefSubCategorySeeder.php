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
        'Aplikasi' => [
            'Akan ditentukan semula',
        ],

        'Perkakasan & Perkhidmatan ICT' => [
            'Komputer Desktop/Laptop',
            'Peralatan Komputer (Monitor/Mouse/Keyboard/etc)',
            'Pencetak',
            'Perisian (Software)',
            'Sidang Video',
            'Sokongan Teknikal (Mesyuarat/Majlis/Acara Rasmi/etc)',
        ],

        'Keselamatan ICT' => [
            'Antivirus',
            'Serangan Siber',
            'Firewall/WAF',
            'VPN/PRA',
            'Prot-On (File Encryption)',
        ],

        'Pangkalan Data & Sysadmin' => [
            'Pangkalan Data',
            'Sistem Aplikasi (Backend)',
            'Integrasi Sistem',
        ],

        'Pusat Data' => [
            'Pusat Data',
            'Server',
            'Backup dan Restore',
        ],

        'Rangkaian' => [
            'Rangkaian (Wired dan Wifi)',
            'Perkhidmatan Rangkaian (DHCP, DNS, AD)',
            'Server File Sharing dan MOFStore',
        ],

        'Emel/SSO' => [
            'Emel MyGovUC',
            'SSO SatuID',
            'SSO TERAS',
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

