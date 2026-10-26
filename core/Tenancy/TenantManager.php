<?php
namespace Veen\Core\Tenancy;

use Veen\Core\Database\DB;

class TenantManager
{
    private static ?array $tenant = null;

    public static function set(?array $tenant): void
    {
        self::$tenant = $tenant;
        if ($tenant && !empty($tenant['id'])) {
            self::clearCache((int) $tenant['id']);
        }
        if ($tenant && !empty($tenant['timezone'])) {
            date_default_timezone_set($tenant['timezone']);
            DB::syncTimezone();
        }
    }

    public static function clearCache(?int $id = null): void
    {
        if (class_exists(\Veen\Core\Cache::class)) {
            if ($id) {
                \Veen\Core\Cache::forget("tenant_find_{$id}");
            }
        }
    }

    public static function setContext(int|array|null $tenantOrId): void
    {
        if (is_int($tenantOrId)) {
            self::set(self::find($tenantOrId));
        } else {
            self::set($tenantOrId);
        }
    }

    public static function id(): ?int
    {
        return isset(self::$tenant['id']) ? (int) self::$tenant['id'] : null;
    }

    public static function current(): ?array
    {
        return self::$tenant;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$tenant[$key] ?? $default;
    }

    public static function find(int|string $id): ?array
    {
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');
        $field = $isUuid ? 'uuid' : 'id';
        if (class_exists(\Veen\Core\Cache::class)) {
            return \Veen\Core\Cache::remember("tenant_find_{$id}", 120, function () use ($id, $field) {
                return DB::selectOne("SELECT t.*, c.name AS country_name FROM tenants t LEFT JOIN countries c ON c.code = t.country_code WHERE t.{$field} = ?", [$id]);
            });
        }
        return DB::selectOne("SELECT t.*, c.name AS country_name FROM tenants t LEFT JOIN countries c ON c.code = t.country_code WHERE t.{$field} = ?", [$id]);
    }

    /** Resolve tenant from custom domain or "{slug}.APP_DOMAIN" subdomain. */
    public static function resolveFromHost(string $host): ?array
    {
        $row = DB::selectOne('SELECT * FROM tenants WHERE domain = ? LIMIT 1', [$host]);
        if ($row) return $row;

        $root = strtolower((string) env('APP_DOMAIN', ''));
        if ($root && str_ends_with($host, '.' . $root)) {
            $slug = substr($host, 0, -strlen('.' . $root));
            return DB::selectOne('SELECT * FROM tenants WHERE slug = ? LIMIT 1', [$slug]);
        }
        return null;
    }

    /** Run a callback within a specific tenant context (CLI/seeders/jobs). */
    public static function run(array $tenant, callable $callback): mixed
    {
        $previous = self::$tenant;
        self::$tenant = $tenant;
        try {
            return $callback();
        } finally {
            self::$tenant = $previous;
        }
    }
}
