<?php

namespace Database\Seeders;

use App\Models\RefCategory;
use Illuminate\Database\Seeder;

class RefCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
    ['cat_name' => 'Aplikasi', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan aplikasi dan sistem dalaman.'],
     
    ['cat_name' => 'Perkakasan & Perkhidmatan ICT', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan komputer, peralatan ICT, dan sokongan teknikal.'],
     
    ['cat_name' => 'Keselamatan ICT', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan keselamatan siber dan perlindungan data.'],
     
    ['cat_name' => 'Pangkalan Data & Sysadmin', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan pengurusan pangkalan data dan sistem backend.'],
     
    ['cat_name' => 'Pusat Data', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan server, pusat data, dan backup sistem.'],
     
    ['cat_name' => 'Rangkaian', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan sambungan internet, wifi, dan perkhidmatan rangkaian.'],
     
    ['cat_name' => 'Emel/SSO', 
     'cat_desc' => 'Keterangan kategori aduan berkaitan akaun emel dan sistem Single Sign-On (SSO).'],
];

        foreach ($categories as $category) {
            RefCategory::firstOrCreate(['cat_name' => $category['cat_name']], $category);
        }
    }
}
