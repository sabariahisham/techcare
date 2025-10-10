<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefBahagian extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'ref_bahagian';

  protected $fillable = [
    'kod_bah',
    'nama_bah',
    'singkat_bah',
    'kod_sektor',
    'status',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
    'status' => 'string',
  ];

  /**
   * Get the sektor that owns this bahagian
   */
  public function sektor()
  {
    return $this->belongsTo(RefSektor::class, 'kod_sektor', 'kod_sektor');
  }

  /**
   * Get users in this bahagian
   */
  public function users()
  {
    return $this->hasMany(User::class, 'kod_bah', 'kod_bah');
  }

  /**
   * Get seksyen under this bahagian
   */
  public function seksyen()
  {
    return $this->hasMany(RefSeksyen::class, 'kod_bah', 'kod_bah');
  }

  /**
   * Scope to search bahagian
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_bah', 'LIKE', "%{$search}%")
        ->orWhere('kod_bah', 'LIKE', "%{$search}%")
        ->orWhere('singkat_bah', 'LIKE', "%{$search}%");
    });
  }

  /**
   * Scope to filter active bahagian
   */
  public function scopeActive($query)
  {
    return $query->where('status', 'active')->whereNull('deleted_at');
  }

  /**
   * Scope to filter by sektor
   */
  public function scopeBySektor($query, $sektorKod)
  {
    return $query->where('kod_sektor', $sektorKod);
  }
}
