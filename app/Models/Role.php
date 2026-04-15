<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends SpatieRole
{
    use HasFactory;

    protected $fillable = ['name', 'guard_name', 'company_id', 'created_by'];

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');
        $params = ['name' => $attributes['name'], 'guard_name' => $attributes['guard_name']];

        if (isset($attributes['company_id'])) {
            $params['company_id'] = $attributes['company_id'];
        }

        $role = static::where('name', $attributes['name'])
            ->where('guard_name', $attributes['guard_name'])
            ->where('company_id', $attributes['company_id'])
            ->first();

        if ($role) {
            throw new \Spatie\Permission\Exceptions\RoleAlreadyExists();
        }

        return static::query()->create($attributes);
    }

    public static function findByName(string $name, $guardName = null): SpatieRole
    {
        $guardName = $guardName ?? config('auth.defaults.guard');
        $role = static::where('name', $name)
            ->where('guard_name', $guardName)
            ->where('company_id', auth()->user()->Company_Id ?? null)
            ->first();

        if (!$role) {
            throw new \Spatie\Permission\Exceptions\RoleDoesNotExist();
        }

        return $role;
    }
}
