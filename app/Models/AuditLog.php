<?php
namespace App\Models;

use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Database\Model;
use Veen\Core\Tenancy\TenantManager;

class AuditLog extends Model
{
    protected static string $table = 'audit_logs';
    protected static bool $timestamps = false;
    protected static array $fillable = [];

    public static function record(string $action, ?string $entity = null, mixed $entityId = null, array $meta = []): void
    {
        DB::statement(
            'INSERT INTO audit_logs (tenant_id, user_id, action, entity, entity_id, meta, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
            [
                TenantManager::id(),
                Auth::id(),
                $action,
                $entity,
                $entityId !== null ? (string) $entityId : null,
                $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? 'cli',
            ]
        );
    }
}
