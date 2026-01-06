<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefGred extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'ref_gred';

  protected $fillable = [
    'nama_gred',
    'kod_kump_khidmat',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get the kumpulan khidmat that owns this gred
   */
  public function khidmat()
  {
    return $this->belongsTo(RefKhidmat::class, 'kod_kump_khidmat', 'kod_kump_khidmat');
  }

  /**
   * Get users with this gred
   */
  public function users()
  {
    return $this->hasMany(User::class, 'gred_id');
  }

  /**
   * Scope to search gred
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_gred', 'LIKE', "%{$search}%")
        ->orWhere('kod_kump_khidmat', 'LIKE', "%{$search}%");
    });
  }

  /**
   * Scope to filter by khidmat
   */
  public function scopeByKhidmat($query, $khidmatKod)
  {
    return $query->where('kod_kump_khidmat', $khidmatKod);
  }

  /**
   * Scope to filter active gred
   */
  public function scopeActive($query)
  {
    return $query->whereNull('deleted_at');
  }
}
