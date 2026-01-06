<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RefSkim extends Model
{
  use HasFactory;

  protected $table = 'ref_skim';

  protected $fillable = [
    'nama_skim',
    'kod_skim',
  ];

  protected $casts = [
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get users with this skim
   */
  public function users()
  {
    return $this->hasMany(User::class, 'kod_skim', 'kod_skim');
  }
}
