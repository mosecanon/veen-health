<?php
namespace Veen\Core;

use Veen\Core\Tenancy\TenantManager;

/**
 * High-performance Cache Manager for Veen Africa HMS
 * Native Redis / Predis RESP Socket Driver with File Fallback.
 * Ensures zero-dependency, sub-millisecond page rendering and tenant data isolation.
 */
class Cache
{
    private static $socket = null;
    private static ?bool $redisAvailable = null;
    private static string $host = '127.0.0.1';
    private static int $port = 6379;
    private static float $timeout = 0.5;

    public static function init(?string $host = null, ?int $port = null): void
    {
        if ($host) self::$host = $host;
        if ($port) self::$port = $port;
    }

    /**
     * Get isolated cache key prefixed with tenant ID
     */
    public static function prefixKey(string $key): string
    {
        $tenantId = TenantManager::id();
        $prefix = $tenantId ? "veen:tenant_{$tenantId}:" : "veen:central:";
        return $prefix . ltrim($key, ':');
    }

    /**
     * Connect to Redis via TCP socket or PECL Redis
     */
    private static function getConnection()
    {
        if (self::$redisAvailable === false) {
            return null;
        }

        if (self::$socket !== null && is_resource(self::$socket)) {
            return self::$socket;
        }

        $host = env('REDIS_HOST', self::$host);
        $port = (int) env('REDIS_PORT', self::$port);

        $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, self::$timeout, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            self::$redisAvailable = false;
            return null;
        }

        stream_set_timeout($fp, 1);
        self::$socket = $fp;
        self::$redisAvailable = true;
        return self::$socket;
    }

    /**
     * Execute Redis Command using RESP protocol
     */
    private static function executeCommand(array $args): mixed
    {
        $conn = self::getConnection();
        if (!$conn) return null;

        // Construct RESP Array
        $cmd = '*' . count($args) . "\r\n";
        foreach ($args as $arg) {
            $str = (string) $arg;
            $cmd .= '$' . strlen($str) . "\r\n" . $str . "\r\n";
        }

        $written = @fwrite($conn, $cmd);
        if ($written === false) {
            self::$socket = null;
            self::$redisAvailable = false;
            return null;
        }

        return self::parseResponse($conn);
    }

    /**
     * Parse RESP server response
     */
    private static function parseResponse($conn): mixed
    {
        $line = fgets($conn);
        if ($line === false) {
            self::$socket = null;
            return null;
        }

        $type = $line[0];
        $payload = substr($line, 1, -2);

        switch ($type) {
            case '+': // Simple string
                return $payload;
            case '-': // Error
                return null;
            case ':': // Integer
                return (int) $payload;
            case '$': // Bulk string
                $length = (int) $payload;
                if ($length === -1) return null;
                $data = '';
                $remaining = $length;
                while ($remaining > 0) {
                    $chunk = fread($conn, min($remaining, 8192));
                    if ($chunk === false) break;
                    $data .= $chunk;
                    $remaining -= strlen($chunk);
                }
                fread($conn, 2); // Read trailing \r\n
                return $data;
            case '*': // Array
                $count = (int) $payload;
                if ($count === -1) return null;
                $items = [];
                for ($i = 0; $i < $count; $i++) {
                    $items[] = self::parseResponse($conn);
                }
                return $items;
            default:
                return null;
        }
    }

    /**
     * Retrieve item from cache
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $fullKey = self::prefixKey($key);

        if (self::getConnection()) {
            $raw = self::executeCommand(['GET', $fullKey]);
            if ($raw !== null) {
                $decoded = json_decode((string) $raw, true);
                return $decoded !== null ? $decoded : $raw;
            }
        }

        // File cache fallback
        return self::getFile($fullKey, $default);
    }

    /**
     * Check if item exists in cache
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Store item in cache with TTL in seconds
     */
    public static function set(string $key, mixed $value, int $ttlSeconds = 300): bool
    {
        $fullKey = self::prefixKey($key);
        $encoded = is_string($value) || is_numeric($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (self::getConnection()) {
            $res = self::executeCommand(['SET', $fullKey, $encoded, 'EX', (string) $ttlSeconds]);
            if ($res !== null) {
                return true;
            }
        }

        // File cache fallback
        return self::setFile($fullKey, $value, $ttlSeconds);
    }

    /**
     * Get or set cache via closure
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed
    {
        $val = self::get($key);
        if ($val !== null) {
            return $val;
        }

        $fresh = $callback();
        self::set($key, $fresh, $ttlSeconds);
        return $fresh;
    }

    /**
     * Delete item from cache
     */
    public static function forget(string $key): bool
    {
        $fullKey = self::prefixKey($key);

        if (self::getConnection()) {
            self::executeCommand(['DEL', $fullKey]);
        }

        self::forgetFile($fullKey);
        return true;
    }

    /**
     * Flush tenant cache
     */
    public static function flush(): bool
    {
        $tenantId = TenantManager::id();
        $pattern = $tenantId ? "veen:tenant_{$tenantId}:*" : "veen:*";

        if (self::getConnection()) {
            $keys = self::executeCommand(['KEYS', $pattern]);
            if (is_array($keys) && !empty($keys)) {
                self::executeCommand(array_merge(['DEL'], $keys));
            }
        }

        // Clean file cache
        $dir = BASE_PATH . '/storage/cache';
        if (is_dir($dir)) {
            $files = glob($dir . '/*.json');
            foreach ($files as $f) {
                @unlink($f);
            }
        }

        return true;
    }

    /**
     * Active Cache Driver Name
     */
    public static function driver(): string
    {
        return self::getConnection() ? 'redis' : 'file';
    }

    public static function isRedis(): bool
    {
        return self::getConnection() !== null;
    }

    // --- File Fallback Helpers ---

    private static function getFilePath(string $fullKey): string
    {
        $safeName = md5($fullKey) . '.json';
        return BASE_PATH . '/storage/cache/' . $safeName;
    }

    private static function getFile(string $fullKey, mixed $default = null): mixed
    {
        $path = self::getFilePath($fullKey);
        if (!is_file($path)) return $default;

        $content = @file_get_contents($path);
        if (!$content) return $default;

        $data = json_decode($content, true);
        if (!isset($data['expires_at']) || time() > $data['expires_at']) {
            @unlink($path);
            return $default;
        }

        return $data['value'] ?? $default;
    }

    private static function setFile(string $fullKey, mixed $value, int $ttlSeconds): bool
    {
        $dir = BASE_PATH . '/storage/cache';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $path = self::getFilePath($fullKey);
        $payload = [
            'expires_at' => time() + $ttlSeconds,
            'value' => $value,
        ];

        return (bool) @file_put_contents($path, json_encode($payload));
    }

    private static function forgetFile(string $fullKey): void
    {
        $path = self::getFilePath($fullKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
