<?php
namespace App\Models;

use Veen\Core\Database\Model;

class User extends Model
{
    protected static string $table = 'users';
    protected static array $fillable = ['uuid', 'branch_id', 'can_access_all_branches', 'name', 'email', 'phone', 'role', 'title', 'is_active'];

    public function getRoleLabelAttribute(): string
    {
        return config('permissions.labels.' . $this->role, ucfirst((string) $this->role));
    }

    public function setPassword(string $plain): static
    {
        $this->attributes['password'] = password_hash($plain, PASSWORD_DEFAULT);
        return $this;
    }

    public function toArray(): array
    {
        $a = $this->attributes;
        unset($a['password']);
        return $a;
    }
}
