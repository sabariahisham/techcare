<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefSektor extends Model
{
  use HasFactory;

  protected $table = 'ref_sektor';

  protected $fillable = [
    'kod_sektor',
    'nama_sektor',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get users in this sektor
   */
  public function users()
  {
    return $this->hasMany(User::class, 'kod_sektor', 'kod_sektor');
  }

  /**
   * Get bahagian under this sektor
   */
  public function bahagian()
  {
    return $this->hasMany(RefBahagian::class, 'kod_sektor', 'kod_sektor');
  }

  /**
   * Scope to search sektor
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_sektor', 'LIKE', "%{$search}%")
        ->orWhere('kod_sektor', 'LIKE', "%{$search}%");
    });
  }

  /**
   * Scope to filter active sektor
   */
  public function scopeActive($query)
  {
    return $query->whereNull('deleted_at');
  }
}
