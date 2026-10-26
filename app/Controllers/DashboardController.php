<?php
namespace App\Controllers;

use App\Models\Display;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Queue;
use App\Models\Sale;
use App\Models\ServicePoint;
use App\Models\Ticket;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $today = today();

        // 1. Patient KPIs
        $totalPatients = (int) (DB::selectOne('SELECT COUNT(*) AS c FROM patients WHERE tenant_id = ?', [$tenantId])['c'] ?? 0);
        $todayPatients = (int) (DB::selectOne('SELECT COUNT(*) AS c FROM patients WHERE tenant_id = ? AND DATE(created_at) = ?', [$tenantId, $today])['c'] ?? 0);

        // 2. Queue KPIs
        $waitingTickets = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ? AND issued_on = ? AND status = 'waiting'", [$tenantId, $today])['c'] ?? 0);
        $servingTickets = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ? AND issued_on = ? AND status IN ('called', 'serving')", [$tenantId, $today])['c'] ?? 0);
        $completedTickets = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM tickets WHERE tenant_id = ? AND issued_on = ? AND status = 'completed'", [$tenantId, $today])['c'] ?? 0);

        // 3. Pharmacy OTC KPIs
        $pharmacyToday = DB::selectOne(
            "SELECT COUNT(*) AS tx_count, COALESCE(SUM(total_minor), 0) AS revenue_minor FROM sales WHERE tenant_id = ? AND DATE(created_at) = ? AND status = 'completed'",
            [$tenantId, $today]
        );
        $salesCountToday = (int) ($pharmacyToday['tx_count'] ?? 0);
        $salesRevenueToday = (int) ($pharmacyToday['revenue_minor'] ?? 0);

        // 4. Pharmacy Low Stock & Expiring
        $lowStockCount = (int) (DB::selectOne(
            'SELECT COUNT(*) AS c FROM products WHERE tenant_id = ? AND is_active = 1 AND stock_qty <= reorder_level',
            [$tenantId]
        )['c'] ?? 0);

        $expiringCount = (int) (DB::selectOne(
            'SELECT COUNT(*) AS c FROM product_batches WHERE tenant_id = ? AND qty_on_hand > 0 AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)',
            [$tenantId]
        )['c'] ?? 0);

        // 5. Recent Tickets
        $recentTickets = DB::select(
            "SELECT t.*, q.name AS queue_name, q.color AS queue_color, sp.name AS service_point_name,
                    p.first_name, p.last_name, p.mrn
             FROM tickets t
             LEFT JOIN queues q ON q.id = t.queue_id
             LEFT JOIN service_points sp ON sp.id = t.service_point_id
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE t.tenant_id = ? AND t.issued_on = ?
             ORDER BY t.id DESC LIMIT 8",
            [$tenantId, $today]
        );

        // 6. Recent Sales
        $recentSales = DB::select(
            "SELECT s.*, u.name AS cashier_name, p.first_name, p.last_name
             FROM sales s
             LEFT JOIN users u ON u.id = s.user_id
             LEFT JOIN patients p ON p.id = s.patient_id
             WHERE s.tenant_id = ?
             ORDER BY s.id DESC LIMIT 6",
            [$tenantId]
        );

        // 7. Active Queues & Service Points
        $queues = DB::select(
            "SELECT q.*, 
                    (SELECT COUNT(*) FROM tickets t WHERE t.queue_id = q.id AND t.issued_on = ? AND t.status = 'waiting') AS waiting_count,
                    (SELECT COUNT(*) FROM tickets t WHERE t.queue_id = q.id AND t.issued_on = ? AND t.status = 'serving') AS serving_count
             FROM queues q
             WHERE q.tenant_id = ? AND q.is_active = 1
             ORDER BY q.sort_order ASC",
            [$today, $today, $tenantId]
        );

        // 8. Default TV Display Token (for fast link)
        $display = DB::selectOne('SELECT id, name, token FROM displays WHERE tenant_id = ? LIMIT 1', [$tenantId]);

        return view('dashboard.index', [
            'title' => 'Hospital Overview',
            'totalPatients' => $totalPatients,
            'todayPatients' => $todayPatients,
            'waitingTickets' => $waitingTickets,
            'servingTickets' => $servingTickets,
            'completedTickets' => $completedTickets,
            'salesCountToday' => $salesCountToday,
            'salesRevenueToday' => $salesRevenueToday,
            'lowStockCount' => $lowStockCount,
            'expiringCount' => $expiringCount,
            'recentTickets' => $recentTickets,
            'recentSales' => $recentSales,
            'queues' => $queues,
            'display' => $display,
        ]);
    }
}
