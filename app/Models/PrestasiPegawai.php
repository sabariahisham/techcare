<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestasiPetugas extends Model
{
    /**
     * The table associated with the model.
     * This is a database view, not a regular table.
     *
     * @var string
     */
    protected $table = 'prestasi_petugas';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The primary key associated with the table.
     * Views typically don't have primary keys, but we can set this for compatibility.
     *
     * @var string
     */
    protected $primaryKey = null;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tiket_diterima' => 'integer',
        'tiket_diselesai' => 'integer',
        'tiket_dalam_tindakan' => 'integer',
        'tiket_lewat_sla' => 'integer',
        'tiket_selesai_dalam_sla' => 'integer',
        'high' => 'integer',
        'medium' => 'integer',
        'low' => 'integer',
    ];
}
