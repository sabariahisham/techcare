<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // Rename column
          
            $table->renameColumn('phone_number', 'tel_bimbit');
            $table->renameColumn('office_phone_number', 'tel_pejabat');
            $table->renameColumn('position', 'jawatan');
            $table->renameColumn('scheme_and_grade', 'gln_jawatan');   


            // Tambah column baru
            $table->string('sektor')->nullable()->after('tel_pejabat');
            $table->string('seksyen')->nullable()->after('agency');
            $table->string('unit')->nullable()->after('seksyen');
           
           
            // Soft delete
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
        // Rename balik ke asal
      
        $table->renameColumn('tel_bimbit', 'phone_number');
        $table->renameColumn('tel_pejabat', 'office_phone_number');
        $table->renameColumn('jawatan', 'position');
        $table->renameColumn('gln_jawatan', 'scheme_and_grade');

        // Buang column yang ditambah
        $table->dropColumn(['sektor', 'seksyen', 'unit']);

        // Buang soft deletes (deleted_at)
        $table->dropSoftDeletes();
       });
    }
};