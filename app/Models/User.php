<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Jetstream\HasProfilePhoto;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use HasPermissions;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'id_pgn_ldap',
        'nama',
        'email',
        'password',
        'no_kp',
        'kodjantina',
        'kod_skim',
        'kod_kump_khidmat',
        'kod_jwtn',
        'kod_jawatan',
        'nama_gred',
        'tel_no_p',
        'tel_no_hp',
        'kod_sektor',
        'kod_bah',
        'kod_sec',
        'kod_unit',
        'tarikh_lapormof',
        'wf_ramadhan',
        'hiraki',
        'lastlogin_at',
        'kodaktif',
        'dataaktif',
        'hadir_kurang_jam',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'lastlogin_at' => 'datetime',
            'tarikh_lapormof' => 'date',
            'kodaktif' => 'boolean',
            'kod_sektor' => 'integer',
            'kod_bah' => 'integer',
            'kod_sec' => 'integer',
            'kod_unit' => 'integer',
            'wf_ramadhan' => 'integer',
            'hiraki' => 'integer',
            'dataaktif' => 'integer',
            'hadir_kurang_jam' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Boot function from Laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'full_name',
        'display_role',
        'status_badge',
        'jawatan_name',
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return $this->nama ?? 'Unknown User';
    }

    /**
     * Get the user's display role.
     *
     * @return string
     */
    public function getDisplayRoleAttribute(): string
    {
        return $this->roles->first()?->name ?? 'No Role';
    }

    /**
     * Get the user's status badge.
     *
     * @return array
     */
    public function getStatusBadgeAttribute(): array
    {
        return [
            'status' => $this->kodaktif ? 'Active' : 'Inactive',
            'class' => $this->kodaktif ? 'badge bg-label-success' : 'badge bg-label-secondary',
        ];
    }

    /**
     * Get the user's jawatan name.
     *
     * @return string
     */
    public function getJawatanNameAttribute(): string
    {
        return $this->jawatan?->nama_jawatan ?? 'No Jawatan';
    }

    /**
     * Check if user is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->kodaktif;
    }

    /**
     * Check if user is inactive.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return !$this->kodaktif;
    }

    /**
     * Activate the user.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['kodaktif' => true]);
    }

    /**
     * Deactivate the user.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        return $this->update(['kodaktif' => false]);
    }

    /**
     * Check if user has verified their email.
     *
     * @return bool
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Check if this is user's first time login.
     *
     * @return bool
     */
    public function isFirstTimeLogin(): bool
    {
        // This field doesn't exist in current DB structure
        // You may need to add this field or use another logic
        return false;
    }

    /**
     * Mark first time login as completed.
     *
     * @return bool
     */
    public function completeFirstLogin(): bool
    {
        // This field doesn't exist in current DB structure
        // You may need to add this field or use another logic
        return true;
    }

    /**
     * Update last login information.
     *
     * @param string|null $ip
     * @return bool
     */
    public function updateLastLogin(?string $ip = null): bool
    {
        return $this->update([
            'lastlogin_at' => now(),
            // Note: last_login_ip field doesn't exist in current DB structure
        ]);
    }

    /**
     * Scope to filter active users.
     */
    public function scopeActive($query)
    {
        return $query->where('kodaktif', true);
    }

    /**
     * Scope to filter inactive users.
     */
    public function scopeInactive($query)
    {
        return $query->where('kodaktif', false);
    }

    /**
     * Scope to filter verified users.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to filter unverified users.
     */
    public function scopeUnverified($query)
    {
        return $query->whereNull('email_verified_at');
    }

    /**
     * Scope to search users.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nama', 'LIKE', "%{$search}%")
              ->orWhere('email', 'LIKE', "%{$search}%")
              ->orWhere('no_kp', 'LIKE', "%{$search}%")
              ->orWhere('tel_no_hp', 'LIKE', "%{$search}%")
              ->orWhere('tel_no_p', 'LIKE', "%{$search}%")
              ->orWhere('id_pgn_ldap', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope to filter users by role.
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Get user's primary role name.
     *
     * @return string|null
     */
    public function getPrimaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Check if user has any of the given roles.
     *
     * @param array|string $roles
     * @return bool
     */
    public function hasAnyRole($roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles->pluck('name')->intersect($roles)->isNotEmpty();
    }

    /**
     * Check if user is super admin (has Administrator role).
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('Administrator');
    }

    /**
     * Get formatted contact information.
     *
     * @return array
     */
    public function getContactInfo(): array
    {
        return [
            'email' => $this->email,
            'mobile_phone' => $this->tel_no_hp,
            'office_phone' => $this->tel_no_p,
            'ic_number' => $this->no_kp,
            'ldap_id' => $this->id_pgn_ldap,
        ];
    }

    /**
     * Get formatted work information.
     *
     * @return array
     */
    public function getWorkInfo(): array
    {
        return [
            'kod_sektor' => $this->kod_sektor,
            'kod_bah' => $this->kod_bah,
            'kod_sec' => $this->kod_sec,
            'kod_unit' => $this->kod_unit,
            'jawatan' => $this->jawatan?->nama_jawatan ?? $this->kod_jawatan,
            'kod_jwtn' => $this->kod_jwtn,
            'kod_skim' => $this->kod_skim,
            'kod_kump_khidmat' => $this->kod_kump_khidmat,
            'nama_gred' => $this->nama_gred,
            'hiraki' => $this->hiraki,
        ];
    }

    /**
     * Get user statistics.
     *
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total_logins' => 0, // You can track this with a separate model/table
            'last_login' => $this->lastlogin_at?->format('d M Y, h:i A'),
            'member_since' => $this->created_at->format('d M Y'),
            'roles_count' => $this->roles->count(),
            'permissions_count' => $this->getAllPermissions()->count(),
            'reporting_date' => $this->tarikh_lapormof?->format('d M Y'),
            'data_active' => $this->dataaktif,
        ];
    }

    /**
     * Check if user is male.
     *
     * @return bool
     */
    public function isMale(): bool
    {
        return $this->kodjantina === '1' || $this->kodjantina === 'L';
    }

    /**
     * Check if user is female.
     *
     * @return bool
     */
    public function isFemale(): bool
    {
        return $this->kodjantina === '2' || $this->kodjantina === 'P';
    }

    /**
     * Scope to filter users by sector.
     */
    public function scopeBySector($query, $sector)
    {
        return $query->where('kod_sektor', $sector);
    }

    /**
     * Scope to filter users by department.
     */
    public function scopeByDepartment($query, $department)
    {
        return $query->where('kod_bah', $department);
    }

    /**
     * Scope to filter users by section.
     */
    public function scopeBySection($query, $section)
    {
        return $query->where('kod_sec', $section);
    }

    /**
     * Scope to filter users by unit.
     */
    public function scopeByUnit($query, $unit)
    {
        return $query->where('kod_unit', $unit);
    }

    /**
     * Get the sektor relationship.
     */
    public function sektor()
    {
        return $this->belongsTo(RefSektor::class, 'kod_sektor', 'kod_sektor');
    }

    /**
     * Get the bahagian relationship.
     */
    public function bahagian()
    {
        return $this->belongsTo(RefBahagian::class, 'kod_bah', 'kod_bah');
    }

    /**
     * Get the seksyen relationship.
     */
    public function seksyen()
    {
        return $this->belongsTo(RefSeksyen::class, 'kod_sec', 'kod_sec');
    }

    /**
     * Get the unit relationship.
     */
    public function unit()
    {
        return $this->belongsTo(RefUnit::class, 'kod_unit', 'kod_unit');
    }

    /**
     * Get the jawatan relationship.
     */
    public function jawatan()
    {
        return $this->belongsTo(RefJawatan::class, 'kod_jawatan', 'kod_jawatan');
    }

    public function skim()
    {
        return $this->belongsTo(RefSkim::class, 'kod_skim', 'kod_skim');
    }


}
