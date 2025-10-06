<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission;

class Module extends Model
{
  use HasFactory;

  protected $table = 'module_list';

  protected $guarded = ['id'];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
        $model->uuid = Uuid::uuid4()->toString();
    });
  }

  public function permissions()
  {
    return $this->hasMany(Permission::class);
  }
}
