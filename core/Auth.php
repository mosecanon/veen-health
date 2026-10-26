<?php
namespace Veen\Core;

use App\Models\User;
use Veen\Core\Database\DB;

class Auth
{
    private static ?User $user = null;
    private static bool $loaded = false;

    public static function attempt(string $email, string $password, string $ip): bool
    {
        $email = strtolower(trim($email));

        $recent = DB::selectOne(
            'SELECT COUNT(*) AS c FROM login_attempts WHERE (email = ? OR ip = ?) AND created_at > (NOW() - INTERVAL 15 MINUTE)',
            [$email, $ip]
        );
        if ((int) ($recent['c'] ?? 0) >= (int) config('app.max_login_attempts', 5)) {
            throw new HttpException(429, 'Too many failed login attempts. Try again in 15 minutes.');
        }

        $row = DB::selectOne('SELECT * FROM users WHERE email = ? LIMIT 1', [$email]);
        if (!$row || !password_verify($password, $row['password']) || !(int) $row['is_active']) {
            DB::statement('INSERT INTO login_attempts (email, ip, created_at) VALUES (?, ?, NOW())', [$email, $ip]);
            return false;
        }

        if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
            DB::statement('UPDATE users SET password = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $row['id']]);
        }
        DB::statement('DELETE FROM login_attempts WHERE email = ?', [$email]);
        DB::statement('UPDATE users SET last_login_at = NOW() WHERE id = ?', [$row['id']]);

        Session::regenerate();
        Session::put('user_id', (int) $row['id']);
        self::$user = User::hydrate($row);
        self::$loaded = true;
        return true;
    }

    public static function login(User $user): void
    {
        Session::put('user_id', (int) $user->id);
        self::$user = $user;
        self::$loaded = true;
    }

    public static function user(): ?User
    {
        if (!self::$loaded) {
            self::$loaded = true;
            $id = Session::get('user_id');
            if ($id) {
                $row = DB::selectOne('SELECT * FROM users WHERE id = ? AND is_active = 1 LIMIT 1', [$id]);
                self::$user = $row ? User::hydrate($row) : null;
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        return self::user()?->id ? (int) self::user()->id : null;
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::destroy();
    }

    public static function hasRole(string ...$roles): bool
    {
        $u = self::user();
        return $u !== null && in_array($u->role, $roles, true);
    }

    public static function can(string $permission): bool
    {
        $u = self::user();
        if (!$u) return false;
        $perms = config('permissions.roles.' . $u->role, []);
        if (in_array('*', $perms, true) || in_array($permission, $perms, true)) {
            return true;
        }
        // wildcard e.g. "pharmacy.*"
        foreach ($perms as $p) {
            if (str_ends_with($p, '.*') && str_starts_with($permission, substr($p, 0, -1))) {
                return true;
            }
        }
        return false;
    }
}
