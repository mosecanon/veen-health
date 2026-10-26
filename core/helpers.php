<?php

use Veen\Core\App;
use Veen\Core\Auth;
use Veen\Core\Config;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Session;
use Veen\Core\View;
use Veen\Core\Tenancy\TenantManager;

function env(string $key, mixed $default = null): mixed
{
    $v = $_ENV[$key] ?? getenv($key);
    if ($v === false || $v === null) return $default;
    return match (strtolower((string) $v)) {
        'true' => true, 'false' => false, 'null' => null, 'empty' => '',
        default => $v,
    };
}

function base_path(string $path = ''): string
{
    return BASE_PATH . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function view(string $name, array $data = [], int $status = 200): Response
{
    return new Response(View::make($name, $data), $status);
}

function url(string $path = ''): string
{
    return Request::basePath() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = base_path('public/assets/' . ltrim($path, '/'));
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

function route(string $name, array $params = []): string
{
    return App::router()->url($name, $params);
}

function request(): Request
{
    return App::request();
}

function redirect(string $to): Response
{
    return Response::redirect($to);
}

function back(): Response
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    // Only redirect back to our own host
    if ($ref && parse_url($ref, PHP_URL_HOST) === parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST)) {
        return Response::redirect($ref);
    }
    return Response::redirect(url('/'));
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    return Session::token();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function method_field(string $method): string
{
    return '<input type="hidden" name="_method" value="' . e(strtoupper($method)) . '">';
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Session::getFlash('_old', []);
    return $old[$key] ?? $default;
}

function errors(?string $key = null): mixed
{
    $errors = Session::getFlash('_errors', []);
    if ($key === null) return $errors;
    return $errors[$key][0] ?? null;
}

function flash(string $key): mixed
{
    return Session::getFlash($key);
}

function auth(): ?\App\Models\User
{
    return Auth::user();
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function tenant(?string $key = null, mixed $default = null): mixed
{
    return $key === null ? TenantManager::current() : TenantManager::get($key, $default);
}

function currency_meta(?string $code = null): array
{
    $code = $code ?? tenant('currency', 'USD');
    return config('currencies.' . $code, ['symbol' => $code, 'decimals' => 2, 'name' => $code]);
}

/** Money is always stored as integer minor units (cents). */
function money(int|float|string|null $minor, ?string $currency = null): string
{
    $m = currency_meta($currency);
    $d = (int) $m['decimals'];
    return $m['symbol'] . ' ' . number_format(((int) $minor) / (10 ** $d), $d);
}

function to_minor(int|float|string|null $amount, ?string $currency = null): int
{
    $d = (int) currency_meta($currency)['decimals'];
    return (int) round(((float) $amount) * (10 ** $d));
}

function from_minor(int|float|string|null $minor, ?string $currency = null): string
{
    $d = (int) currency_meta($currency)['decimals'];
    return number_format(((int) $minor) / (10 ** $d), $d, '.', '');
}

function format_minor(int|float|string|null $minor, ?string $currency = null): string
{
    return money($minor, $currency);
}

function to_major(int|float|string|null $minor, ?string $currency = null): string
{
    return from_minor($minor, $currency);
}

function abort(int $code, string $message = ''): never
{
    throw new HttpException($code, $message);
}

function abort_unless(bool $condition, int $code = 403, string $message = ''): void
{
    if (!$condition) abort($code, $message);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

function audit(string $action, ?string $entity = null, mixed $entityId = null, array $meta = []): void
{
    \App\Models\AuditLog::record($action, $entity, $entityId, $meta);
}

function initials(?string $name): string
{
    $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
    $i = '';
    foreach (array_slice($parts, 0, 2) as $p) $i .= mb_strtoupper(mb_substr($p, 0, 1));
    return $i ?: '?';
}

function active_route(string $prefix): string
{
    $path = request()->path();
    $p = '/' . trim($prefix, '/');
    return ($path === $p || str_starts_with($path, $p . '/')) ? 'active' : '';
}

function human_date(?string $date, string $format = 'd M Y'): string
{
    return $date ? date($format, strtotime($date)) : '—';
}

function age_from_dob(?string $dob): ?int
{
    if (!$dob) return null;
    return (new DateTime($dob))->diff(new DateTime('today'))->y;
}

function barcode(string $code, int $height = 42, int $moduleWidth = 2, bool $showText = true): string
{
    return \App\Services\Barcode::svg($code, $height, $moduleWidth, $showText);
}

function barcode_data_uri(string $code, int $height = 42, int $moduleWidth = 2, bool $showText = true): string
{
    return \App\Services\Barcode::dataUri($code, $height, $moduleWidth, $showText);
}

function user_can(string $ability): bool
{
    $user = auth();
    if (!$user) return false;
    $role = $user->role ?? 'guest';

    if ($role === 'super_admin' || $role === 'hospital_admin') {
        return true;
    }

    $tenantId = TenantManager::id();
    if ($tenantId) {
        $customRole = \Veen\Core\Database\DB::selectOne(
            "SELECT permissions FROM hospital_roles WHERE tenant_id = ? AND slug = ?",
            [$tenantId, $role]
        );
        if ($customRole && !empty($customRole['permissions'])) {
            $perms = json_decode($customRole['permissions'], true);
            if (is_array($perms)) {
                return in_array($ability, $perms, true);
            }
        }
    }

    $permissions = [
        'doctor' => [
            'dashboard', 'consultations', 'clinical_emr', 'patients', 'appointments', 'inpatient', 'lab_view', 'reports_view',
        ],
        'nurse' => [
            'dashboard', 'queue', 'triage', 'patients', 'appointments', 'inpatient', 'consultations_view',
        ],
        'cashier' => [
            'dashboard', 'billing', 'invoices', 'payments', 'claims', 'queue', 'patients', 'reports_view',
        ],
        'pharmacist' => [
            'dashboard', 'pharmacy', 'pharmacy_pos', 'inventory', 'patients', 'reports_view',
        ],
        'lab_tech' => [
            'dashboard', 'lab', 'laboratory', 'specimen', 'results', 'patients',
        ],
        'receptionist' => [
            'dashboard', 'patients', 'queue', 'appointments',
        ],
    ];

    $allowed = $permissions[$role] ?? [];
    return in_array($ability, $allowed, true);
}

function tenant_can(string $module): bool
{
    $tenant = TenantManager::current();
    if (!$tenant) return true;

    $settings = is_array($tenant) ? ($tenant['settings'] ?? []) : ($tenant->settings ?? []);
    if (is_string($settings)) {
        $settings = json_decode($settings, true) ?: [];
    }

    $modules = $settings['modules'] ?? null;
    if ($modules === null || !is_array($modules)) {
        return true;
    }

    return !empty($modules[$module]);
}

function authorize_access(string $ability, ?string $module = null): void
{
    if ($module && !tenant_can($module)) {
        abort(403, "The " . ucfirst($module) . " module is not enabled for your hospital plan. Please contact Veen Africa to activate this module.");
    }

    if (!user_can($ability)) {
        abort(403, "Access Denied: Your staff role (" . (auth()->role ?? 'guest') . ") does not have permission to access the " . ucfirst($ability) . " workspace.");
    }
}

function cache(?string $key = null, mixed $default = null): mixed
{
    if ($key === null) {
        return new class {
            public function get(string $key, mixed $default = null) { return \Veen\Core\Cache::get($key, $default); }
            public function set(string $key, mixed $value, int $ttl = 300) { return \Veen\Core\Cache::set($key, $value, $ttl); }
            public function remember(string $key, int $ttl, callable $cb) { return \Veen\Core\Cache::remember($key, $ttl, $cb); }
            public function forget(string $key) { return \Veen\Core\Cache::forget($key); }
            public function flush() { return \Veen\Core\Cache::flush(); }
            public function driver(): string { return \Veen\Core\Cache::driver(); }
            public function isRedis(): bool { return \Veen\Core\Cache::isRedis(); }
        };
    }
    return \Veen\Core\Cache::get($key, $default);
}

function generate_uuid(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function resolve_id_or_uuid(string $table, int|string $idOrUuid, ?int $tenantId = null): ?array
{
    $isUuid = is_string($idOrUuid) && strlen($idOrUuid) === 36 && str_contains($idOrUuid, '-');
    $field = $isUuid ? 'uuid' : 'id';
    if ($tenantId !== null) {
        $row = \Veen\Core\Database\DB::selectOne("SELECT * FROM {$table} WHERE {$field} = ? AND tenant_id = ? LIMIT 1", [$idOrUuid, $tenantId]);
        if (!$row && $isUuid) {
            return \Veen\Core\Database\DB::selectOne("SELECT * FROM {$table} WHERE id = ? AND tenant_id = ? LIMIT 1", [$idOrUuid, $tenantId]);
        }
        return $row;
    }
    $row = \Veen\Core\Database\DB::selectOne("SELECT * FROM {$table} WHERE {$field} = ? LIMIT 1", [$idOrUuid]);
    if (!$row && $isUuid) {
        return \Veen\Core\Database\DB::selectOne("SELECT * FROM {$table} WHERE id = ? LIMIT 1", [$idOrUuid]);
    }
    return $row;
}

function user_can_all_branches(): bool
{
    $u = auth();
    if (!$u) return false;
    if ($u->role === 'hospital_admin' || $u->role === 'super_admin') return true;
    return !empty($u->can_access_all_branches) || empty($u->branch_id);
}

function current_branch_id(): ?int
{
    $u = auth();
    if (!$u) return null;
    if (user_can_all_branches()) {
        $active = \Veen\Core\Session::get('active_branch_id');
        if ($active !== null && $active !== '') {
            $val = (int) $active;
            return $val === 0 ? null : $val; // 0 means all branches
        }
    }
    return $u->branch_id ? (int) $u->branch_id : null;
}

function current_branch(): ?array
{
    $bId = current_branch_id();
    if (!$bId) return null;
    $tenantId = \Veen\Core\Tenancy\TenantManager::id();
    return \Veen\Core\Database\DB::selectOne('SELECT * FROM branches WHERE id = ? AND tenant_id = ?', [$bId, $tenantId]);
}


