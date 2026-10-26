<?php
namespace Veen\Core;

class Request
{
    public array $attributes = [];
    private ?array $json = null;
    private static ?string $basePath = null;

    public function __construct(
        public array $query = [],
        public array $post = [],
        public array $server = [],
        public array $files = [],
    ) {
        $type = $server['CONTENT_TYPE'] ?? $server['HTTP_CONTENT_TYPE'] ?? '';
        if (str_contains($type, 'application/json')) {
            $decoded = json_decode((string) file_get_contents('php://input'), true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_FILES);
    }

    /** Base URL path the app is mounted on, e.g. "/veen-hms" or "" */
    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }
        $configured = env('APP_BASE');
        if ($configured !== null && $configured !== '') {
            return self::$basePath = rtrim($configured, '/');
        }
        if (PHP_SAPI === 'cli-server' || PHP_SAPI === 'cli') {
            return self::$basePath = '';
        }
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if (str_ends_with($script, '/public') && !str_starts_with($uri, $script)) {
            $script = substr($script, 0, -7);
        }
        return self::$basePath = rtrim($script, '/');
    }

    public function method(): string
    {
        $method = strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST') {
            $override = strtoupper((string) ($this->post['_method'] ?? $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? ''));
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }
        return $method;
    }

    public function path(): string
    {
        $uri = rawurldecode(parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $base = self::basePath();
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return '/' . trim($uri, '/');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $source = $this->all();
        return $source[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->json ?? []);
    }

    public function only(string ...$keys): array
    {
        $all = $this->all();
        return array_intersect_key($all, array_flip($keys));
    }

    public function has(string $key): bool
    {
        $v = $this->input($key);
        return $v !== null && $v !== '';
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function header(string $name): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$key] ?? null;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    public function wantsJson(): bool
    {
        return $this->isAjax() || str_contains($this->header('Accept') ?? '', 'application/json') || $this->json !== null;
    }

    public function host(): string
    {
        return strtolower(explode(':', $this->server['HTTP_HOST'] ?? 'localhost')[0]);
    }

    public function validate(array $rules): array
    {
        return Validator::validate($this, $rules);
    }
}
