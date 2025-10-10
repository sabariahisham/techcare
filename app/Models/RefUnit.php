<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefUnit extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'ref_unit';

  protected $fillable = [
    'kod_unit',
    'nama_unit',
    'kod_sektor',
    'kod_bah',
    'kod_sec',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get the sektor that owns this unit
   */
  public function sektor()
  {
    return $this->belongsTo(RefSektor::class, 'kod_sektor', 'kod_sektor');
  }

  /**
   * Get the bahagian that owns this unit
   */
  public function bahagian()
  {
    return $this->belongsTo(RefBahagian::class, 'kod_bah', 'kod_bah');
  }

  /**
   * Get the seksyen that owns this unit
   */
  public function seksyen()
  {
    return $this->belongsTo(RefSeksyen::class, 'kod_sec', 'kod_sec');
  }

  /**
   * Get users in this unit
   */
  public function users()
  {
    return $this->hasMany(User::class, 'kod_unit', 'kod_unit');
  }

  /**
   * Scope to search unit
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_unit', 'LIKE', "%{$search}%")
        ->orWhere('kod_unit', 'LIKE', "%{$search}%");
    });
  }

  /**
   * Scope to filter by seksyen
   */
  public function scopeBySeksyen($query, $seksyenKod)
  {
    return $query->where('kod_sec', $seksyenKod);
  }

  /**
   * Scope to filter by bahagian
   */
  public function scopeByBahagian($query, $bahagianKod)
  {
    return $query->where('kod_bah', $bahagianKod);
  }

  /**
   * Scope to filter by sektor
   */
  public function scopeBySektor($query, $sektorKod)
  {
    return $query->where('kod_sektor', $sektorKod);
  }
}
