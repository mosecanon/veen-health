<?php
namespace App\Services;

use Veen\Core\Database\DB;
use Veen\Core\Tenancy\TenantManager;

/**
 * Concurrency-safe per-tenant counters (row lock). Call inside a transaction
 * when the number must be consistent with other writes.
 */
class Sequence
{
    public static function next(string $name): int
    {
        $tenantId = TenantManager::id() ?: throw new \RuntimeException('Tenant context required');
        $ownTx = !DB::connection()->inTransaction();
        if ($ownTx) DB::connection()->beginTransaction();
        try {
            DB::statement('INSERT IGNORE INTO tenant_sequences (tenant_id, name, value) VALUES (?, ?, 0)', [$tenantId, $name]);
            $row = DB::selectOne('SELECT value FROM tenant_sequences WHERE tenant_id = ? AND name = ? FOR UPDATE', [$tenantId, $name]);
            $next = (int) $row['value'] + 1;
            DB::statement('UPDATE tenant_sequences SET value = ? WHERE tenant_id = ? AND name = ?', [$next, $tenantId, $name]);
            if ($ownTx) DB::connection()->commit();
            return $next;
        } catch (\Throwable $e) {
            if ($ownTx && DB::connection()->inTransaction()) DB::connection()->rollBack();
            throw $e;
        }
    }

    /** e.g. MRN: "VDH-000123" */
    public static function mrn(): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-z]/i', '', (string) TenantManager::get('slug', 'P')), 0, 3));
        return $prefix . '-' . str_pad((string) self::next('mrn'), 6, '0', STR_PAD_LEFT);
    }

    /** e.g. receipt "R2610-00042" (year+month). */
    public static function receipt(): string
    {
        return 'R' . date('ym') . '-' . str_pad((string) self::next('receipt'), 5, '0', STR_PAD_LEFT);
    }

    /** e.g. ticket: "P001", "C004" (daily counter per queue). */
    public static function ticket(\App\Models\Queue $queue): array
    {
        $key = 'ticket_' . $queue->id . '_' . date('Ymd');
        $num = self::next($key);
        $code = $queue->prefix . str_pad((string) $num, 3, '0', STR_PAD_LEFT);
        return ['number' => $num, 'code' => $code];
    }
}
