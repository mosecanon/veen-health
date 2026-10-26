<?php
namespace Veen\Core\Database;

use Veen\Core\HttpException;
use Veen\Core\Tenancy\TenantManager;

/**
 * Eloquent-style active record.
 *
 * TENANT SAFETY: models are tenant-scoped by default. Every query gets
 * `WHERE table.tenant_id = <current tenant>` and every insert has tenant_id
 * forced from the current tenant context. If no tenant is set, queries FAIL
 * CLOSED (exception) instead of leaking data across hospitals.
 */
abstract class Model implements \JsonSerializable, \ArrayAccess
{
    protected static string $table = '';
    protected static array $fillable = [];
    protected static bool $tenantScoped = true;
    protected static bool $timestamps = true;
    protected static bool $softDeletes = false;
    protected static array $casts = [];

    protected static array $uuidTables = [
        'tenants', 'users', 'branches', 'patients', 'clinical_encounters',
        'diagnostic_orders', 'diagnostic_results', 'admissions', 'invoices',
        'insurance_claims', 'sales', 'product_batches', 'suppliers',
        'cash_sessions', 'appointments', 'hr_leaves', 'tickets', 'service_points', 'wards',
        'products', 'product_categories',
        'child_profiles', 'child_visits', 'child_immunizations', 'maternal_profiles', 'maternal_visits',
        'deceased_records', 'radiology_orders', 'ccc_profiles', 'ccc_visits'
    ];

    public static function tableHasUuid(): bool
    {
        return in_array(static::$table, self::$uuidTables, true);
    }

    protected array $attributes = [];
    protected array $original = [];
    public bool $exists = false;

    public static function table(): string
    {
        return static::$table;
    }

    public static function isTenantScoped(): bool
    {
        return static::$tenantScoped;
    }

    public static function query(): QueryBuilder
    {
        $q = new QueryBuilder(static::$table, static::class);
        if (static::$tenantScoped) {
            $tenantId = TenantManager::id();
            if (!$tenantId) {
                throw new \RuntimeException('Tenant context required to query ' . static::class);
            }
            $q->where(static::$table . '.tenant_id', $tenantId);
        }
        if (static::$softDeletes) {
            $q->whereNull(static::$table . '.deleted_at');
        }
        return $q;
    }

    public static function __callStatic(string $method, array $args): mixed
    {
        return static::query()->$method(...$args);
    }

    public static function find(int|string|null $id): ?static
    {
        if ($id === null || $id === '') return null;
        $isUuid = is_string($id) && strlen($id) === 36 && str_contains($id, '-');
        if ($isUuid && !static::tableHasUuid()) {
            return null;
        }
        $field = ($isUuid && static::tableHasUuid()) ? static::$table . '.uuid' : static::$table . '.id';
        $res = static::query()->where($field, $id)->first();
        if (!$res && $isUuid && static::tableHasUuid()) {
            return static::query()->where(static::$table . '.id', $id)->first();
        }
        return $res;
    }

    public static function findOrFail(int|string|null $id): static
    {
        return static::find($id) ?? throw new HttpException(404);
    }

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function create(array $data): static
    {
        $m = new static();
        $m->fill($data);
        $m->save();
        return $m;
    }

    public static function hydrate(array $row): static
    {
        $m = new static();
        $m->attributes = $row;
        $m->original = $row;
        $m->exists = true;
        return $m;
    }

    public function fill(array $data): static
    {
        foreach ($data as $k => $v) {
            if (in_array($k, static::$fillable, true)) {
                $this->attributes[$k] = $v === '' ? null : $v;
            }
        }
        return $this;
    }

    /** Set attribute bypassing $fillable (for trusted, server-side values). */
    public function forceFill(array $data): static
    {
        foreach ($data as $k => $v) {
            $this->attributes[$k] = $v;
        }
        return $this;
    }

    public function save(): bool
    {
        $now = date('Y-m-d H:i:s');
        if ($this->exists) {
            $dirty = [];
            foreach ($this->attributes as $k => $v) {
                if ($k === 'id' || $k === 'tenant_id') continue;
                if (!array_key_exists($k, $this->original) || $this->original[$k] !== $v) {
                    $dirty[$k] = $v;
                }
            }
            if (!$dirty) return true;
            if (static::$timestamps) $dirty['updated_at'] = $now;
            static::query()->where(static::$table . '.id', $this->attributes['id'])->update($dirty);
            $this->attributes = array_merge($this->attributes, $dirty);
            $this->original = $this->attributes;
            return true;
        }

        $data = $this->attributes;
        unset($data['id']);
        if (static::tableHasUuid() && empty($data['uuid']) && function_exists('generate_uuid')) {
            $data['uuid'] = generate_uuid();
            $this->attributes['uuid'] = $data['uuid'];
        }
        if (static::$tenantScoped) {
            $tenantId = TenantManager::id() ?: throw new \RuntimeException('Tenant context required to create ' . static::class);
            $data['tenant_id'] = $tenantId;
        }
        if (static::$timestamps) {
            $data['created_at'] = $data['created_at'] ?? $now;
            $data['updated_at'] = $now;
        }
        $id = (new QueryBuilder(static::$table))->insert($data);
        $this->attributes = array_merge($data, ['id' => $id]);
        $this->original = $this->attributes;
        $this->exists = true;
        return true;
    }

    public function update(array $data): bool
    {
        return $this->fill($data)->save();
    }

    public function delete(): bool
    {
        if (!$this->exists) return false;
        $q = static::query()->where(static::$table . '.id', $this->attributes['id']);
        if (static::$softDeletes) {
            $q->update(['deleted_at' => date('Y-m-d H:i:s')]);
        } else {
            $q->delete();
        }
        $this->exists = false;
        return true;
    }

    public function refresh(): static
    {
        $fresh = static::find($this->attributes['id']);
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->original;
        }
        return $this;
    }

    public function getKey(): mixed { return $this->attributes['id'] ?? null; }
    public function toArray(): array { return $this->attributes; }
    public function jsonSerialize(): mixed { return $this->toArray(); }

    public function __get(string $key): mixed
    {
        $accessor = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
        if (method_exists($this, $accessor)) {
            return $this->$accessor();
        }
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void { $this->attributes[$key] = $value; }
    public function __isset(string $key): bool { return isset($this->attributes[$key]); }

    public function offsetExists(mixed $o): bool { return isset($this->attributes[$o]); }
    public function offsetGet(mixed $o): mixed { return $this->__get($o); }
    public function offsetSet(mixed $o, mixed $v): void { $this->attributes[$o] = $v; }
    public function offsetUnset(mixed $o): void { unset($this->attributes[$o]); }
}
