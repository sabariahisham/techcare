<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefJawatan extends Model
{
  use HasFactory;

  protected $table = 'ref_jawatan';

  protected $fillable = [
    'kod_jawatan',
    'nama_jawatan',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'deleted_at' => 'datetime',
  ];

  /**
   * Get users with this jawatan
   */
  public function users()
  {
    return $this->hasMany(User::class, 'kod_jawatan', 'kod_jawatan');
  }

  /**
   * Scope to search jawatan
   */
  public function scopeSearch($query, $search)
  {
    return $query->where(function ($q) use ($search) {
      $q->where('nama_jawatan', 'LIKE', "%{$search}%")
        ->orWhere('kod_jawatan', 'LIKE', "%{$search}%");
    });
  }

  /**
   * Scope to filter active jawatan
   */
  public function scopeActive($query)
  {
    return $query->whereNull('deleted_at');
  }
}
