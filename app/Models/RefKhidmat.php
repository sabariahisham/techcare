<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefKhidmat extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'ref_khidmat';

  protected $fillable = [
    'kod_kump_khidmat',
    'nama_kumpulan_khidmat',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get users in this kumpulan khidmat
   */
  public function users()
  {
    return $this->hasMany(User::class, 'khidmat_id');
  }

  /**
   * Get gred under this kumpulan khidmat
   */
  public function gred()
  {
    return $this->hasMany(RefGred::class, 'kod_kump_khidmat', 'kod_kump_khidmat');
  }

  /**
   * Scope to search khidmat
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_kumpulan_khidmat', 'LIKE', "%{$search}%")
        ->orWhere('kod_kump_khidmat', 'LIKE', "%{$search}%");
    });
  }

  /**
   * Scope to filter active khidmat
   */
  public function scopeActive($query)
  {
    return $query->whereNull('deleted_at');
  }
}
