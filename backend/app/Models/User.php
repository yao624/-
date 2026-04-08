<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'meta_user_role');
    }

    public function getRoleInfoAttribute()
    {
        $role = $this->roles()->first();

        if (!$role) {
            return null;
        }

        $permissions = $role->permissions->map(function ($permission) {
            $parts = explode('.', $permission->name);
            return [
                'id' => $permission->id,
                'name' => $parts[0] ?? $permission->name,
                'actions' => [$parts[1] ?? null],
            ];
        })->filter(function ($item) {
            return $item['actions'][0] !== null;
        })->groupBy('name')->map(function ($groupedPermissions, $resource) use ($role) {
            $actions = $groupedPermissions->flatMap(function ($permission) {
                return $permission['actions'];
            })->unique()->values()->all();

            return [
                'id' => $groupedPermissions->first()['id'],
                'roleId' => $role->id,
                'name' => $resource,
                'actions' => $actions,
            ];
        })->values()->all();

        return [
            'id' => $role->id,
            'name' => $role->name,
            'describe' => $role->name, // 或者其他描述字段，如果有的话
            'permissions' => $permissions,
        ];
    }

    public function fbAccounts()
    {
        return $this->belongsToMany(FbAccount::class, 'fb_account_user', 'user_id', 'fb_account_id');
    }

    public function proxies()
    {
        return $this->hasMany(Proxy::class);
    }

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    public function subidMappings()
    {
        return $this->hasMany(SubidMapping::class);
    }

    public function fbAdAccounts()
    {
        return $this->belongsToMany(FbAdAccount::class)->using(CustomPivot::class);
    }

    public function fbAdTemplates()
    {
        return $this->hasMany(FbAdTemplate::class);
    }

    public function rules()
    {
        return $this->hasMany(Rule::class);
    }

    public function searchBookmarks()
    {
        return $this->hasMany(SearchBookmark::class);
    }

    public function hasRole($roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }
}
