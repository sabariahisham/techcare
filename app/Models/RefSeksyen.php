<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefSeksyen extends Model
{
  use HasFactory;

  protected $table = 'ref_seksyen';

  protected $fillable = [
    'kod_sec',
    'nama_sec',
    'kod_bah',
    'kod_sektor',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the sektor that owns this seksyen
   */
  public function sektor()
  {
    return $this->belongsTo(RefSektor::class, 'kod_sektor', 'kod_sektor');
  }

  /**
   * Get the bahagian that owns this seksyen
   */
  public function bahagian()
  {
    return $this->belongsTo(RefBahagian::class, 'kod_bah', 'kod_bah');
  }

  /**
   * Get users in this seksyen
   */
  public function users()
  {
    return $this->hasMany(User::class, 'kod_sec', 'kod_sec');
  }

  /**
   * Get units under this seksyen
   */
  public function units()
  {
    return $this->hasMany(RefUnit::class, 'kod_sec', 'kod_sec');
  }

  /**
   * Scope to search seksyen
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_sec', 'LIKE', "%{$search}%")
        ->orWhere('kod_sec', 'LIKE', "%{$search}%");
    });
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
