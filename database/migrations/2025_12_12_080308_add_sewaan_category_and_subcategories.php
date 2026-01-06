<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert Sewaan category
        $categoryId = DB::table('ref_categories')->insertGetId([
            'cat_name' => 'Sewaan',
            'cat_desc' => 'Kategori untuk isu-isu berkaitan peralatan sewaan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert subcategories for Sewaan
        $subcategories = [
            [
                'cat_id' => $categoryId,
                'subcat_name' => 'Komputer Desktop/Laptop',
                'subcat_desc' => 'Isu berkaitan komputer desktop dan laptop sewaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cat_id' => $categoryId,
                'subcat_name' => 'Peralatan Komputer (Monitor/Mouse/Keyboard/etc)',
                'subcat_desc' => 'Isu berkaitan peralatan komputer sewaan seperti monitor, mouse, keyboard dan lain-lain',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cat_id' => $categoryId,
                'subcat_name' => 'Pencetak',
                'subcat_desc' => 'Isu berkaitan pencetak sewaan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('ref_sub_categories')->insert($subcategories);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the Sewaan category ID
        $categoryId = DB::table('ref_categories')->where('cat_name', 'Sewaan')->value('id');

        if ($categoryId) {
            // Delete subcategories first (due to foreign key constraint)
            DB::table('ref_sub_categories')->where('cat_id', $categoryId)->delete();

            // Delete the category
            DB::table('ref_categories')->where('id', $categoryId)->delete();
        }
    }
};
