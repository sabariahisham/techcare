<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class Module extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'module_list';

  protected $guarded = ['id'];

  protected static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
        $model->uuid = Uuid::uuid4()->toString();
        // Automatically set created_by when creating a new module
        if (Auth::check()) {
            $model->created_by = Auth::id();
        }
    });

    static::updating(function ($model) {
        // Automatically set updated_by when updating a module
        if (Auth::check()) {
            $model->updated_by = Auth::id();
        }
    });

    static::deleting(function ($model) {
        // Automatically set deleted_by when soft deleting a module
        if (Auth::check() && $model->isForceDeleting() === false) {
            $model->deleted_by = Auth::id();
            $model->save();
        }
    });
  }

  public function permissions()
  {
    return $this->hasMany(Permission::class);
  }

  /**
   * Get the user who created this module.
   */
  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }

  /**
   * Get the user who last updated this module.
   */
  public function updater()
  {
    return $this->belongsTo(User::class, 'updated_by');
  }

  /**
   * Get the user who deleted this module.
   */
  public function deleter()
  {
    return $this->belongsTo(User::class, 'deleted_by');
  }
}
