<?php
namespace App\Controllers;

use App\Models\CashSession;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class PharmacySessionController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $sessions = DB::select(
            "SELECT cs.*, u.name AS user_name
             FROM cash_sessions cs
             JOIN users u ON u.id = cs.user_id
             WHERE cs.tenant_id = ?
             ORDER BY cs.id DESC LIMIT 30",
            [$tenantId]
        );

        $activeSession = DB::selectOne(
            "SELECT * FROM cash_sessions WHERE tenant_id = ? AND user_id = ? AND status = 'open' LIMIT 1",
            [$tenantId, Auth::id()]
        );

        return view('pharmacy.sessions.index', [
            'title' => 'Cash Register Sessions',
            'sessions' => $sessions,
            'activeSession' => $activeSession,
        ]);
    }

    public function open(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $userId = Auth::id();

        // Check if already open
        $existing = DB::selectOne(
            "SELECT id FROM cash_sessions WHERE tenant_id = ? AND user_id = ? AND status = 'open' LIMIT 1",
            [$tenantId, $userId]
        );
        if ($existing) {
            return back()->with('info', 'You already have an open register session.');
        }

        $openingFloat = to_minor($request->input('opening_float', 0));

        $session = CashSession::create([
            'user_id' => $userId,
            'opened_at' => now(),
            'opening_float_minor' => $openingFloat,
            'total_sales_minor' => 0,
            'cash_sales_minor' => 0,
            'status' => 'open',
            'note' => $request->input('note'),
        ]);

        audit('register.opened', 'CashSession', $session->id, ['opening_float_minor' => $openingFloat]);

        return back()->with('success', 'Register session opened successfully.');
    }

    public function close(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $session = DB::selectOne('SELECT * FROM cash_sessions WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
        if (!$session || $session['status'] !== 'open') {
            abort(404, 'Session not found or already closed');
        }

        $closingCount = to_minor($request->input('closing_cash', 0));
        $expected = (int) $session['opening_float_minor'] + (int) $session['cash_sales_minor'];
        $diff = $closingCount - $expected;

        DB::statement(
            "UPDATE cash_sessions SET status = 'closed', closed_at = NOW(), closing_cash_minor = ?, diff_minor = ?, note = ? WHERE id = ? AND tenant_id = ?",
            [$closingCount, $diff, $request->input('note'), $session['id'], $tenantId]
        );

        audit('register.closed', 'CashSession', $session['id'], ['diff_minor' => $diff]);

        return back()->with('success', 'Register session closed.');
    }
}
