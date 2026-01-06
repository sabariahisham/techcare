<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto_increment

            $table->uuid('uuid');
            $table->string('id_pgn_ldap', 191)->default('');
            $table->string('email', 255)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255)->nullable();
            $table->string('nama', 255);
            $table->string('no_kp', 12)->default('');

            $table->string('kodjantina', 2)->nullable()->comment('L=Lelaki, P=Perempuan');
            $table->string('kod_skim', 5)->nullable();
            $table->string('kod_kump_khidmat', 30)->nullable();
            $table->string('kod_jwtn', 100)->default('');
            $table->string('kod_jawatan', 255)->nullable();
            $table->string('nama_gred', 20)->default('');

            $table->string('tel_no_p', 12)->default('');
            $table->string('tel_no_hp', 12)->default('');

            $table->integer('kod_sektor')->nullable();
            $table->integer('kod_bah')->nullable();
            $table->integer('kod_sec')->nullable();
            $table->integer('kod_unit')->nullable();

            $table->string('remember_token', 100)->nullable();
            $table->date('tarikh_lapormof')->nullable();

            $table->integer('wf_ramadhan')->default(1);
            $table->integer('hiraki')->nullable()->comment('Sorting perjawatan dibahagian');

            $table->timestamp('lastlogin_at')->useCurrent();
            $table->tinyInteger('kodaktif')->default(1);
            $table->integer('dataaktif')->default(1)->comment('link dengan tb:01_hari(db:cams) utk kira jumlah hari kerja 366 hari setahun');
            $table->integer('hadir_kurang_jam')->nullable()->comment('Dummy fields untuk table mysql.cams.attendance_daily_bah');

            $table->string('created_by', 191)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->string('updated_by', 191)->nullable();
            $table->timestamp('updated_at')->useCurrent();

            $table->string('deleted_by', 191)->nullable();
            $table->timestamp('deleted_at')->nullable();

            // Index & unique keys
            $table->unique('uuid');
            $table->unique('id');

            // Nota: Laravel tak benarkan multiple primary key by default,
            // jadi kita letakkan primary hanya untuk 'id'
        });
    }

    /**
     * Undur balik migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
