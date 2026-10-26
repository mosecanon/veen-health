<?php
namespace App\Controllers;

use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $fy = $request->query('fy', '2025_2026');

        // Resolve Financial Year Date Ranges
        switch ($fy) {
            case '2026_2027':
                $start = '2026-07-01 00:00:00';
                $end = '2027-06-30 23:59:59';
                $fyLabel = 'FY 2026/2027 (Jul 2026 - Jun 2027)';
                break;
            case '2024_2025':
                $start = '2024-07-01 00:00:00';
                $end = '2025-06-30 23:59:59';
                $fyLabel = 'FY 2024/2025 (Jul 2024 - Jun 2025)';
                break;
            case 'cy_2026':
                $start = '2026-01-01 00:00:00';
                $end = '2026-12-31 23:59:59';
                $fyLabel = 'Calendar Year 2026 (Jan - Dec 2026)';
                break;
            case 'all_time':
                $start = '2020-01-01 00:00:00';
                $end = '2030-12-31 23:59:59';
                $fyLabel = 'All-Time Cumulative';
                break;
            case '2025_2026':
            default:
                $start = '2025-07-01 00:00:00';
                $end = '2026-06-30 23:59:59';
                $fyLabel = 'FY 2025/2026 (Jul 2025 - Jun 2026)';
                break;
        }

        // 1. Total Patients
        $totalPatientsAllTime = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM patients WHERE tenant_id = ? AND deleted_at IS NULL", [$tenantId])['c'] ?? 0);
        $totalPatientsFY = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM patients WHERE tenant_id = ? AND created_at BETWEEN ? AND ? AND deleted_at IS NULL", [$tenantId, $start, $end])['c'] ?? 0);

        // 2. Clinical Consultations & Doctor Encounters
        $totalConsultations = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM clinical_encounters WHERE tenant_id = ? AND created_at BETWEEN ? AND ?", [$tenantId, $start, $end])['c'] ?? 0);

        // 3. Laboratory Diagnostics Completed
        $totalLabOrders = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM diagnostic_orders WHERE tenant_id = ? AND created_at BETWEEN ? AND ?", [$tenantId, $start, $end])['c'] ?? 0);
        $verifiedLabOrders = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM diagnostic_orders WHERE tenant_id = ? AND status = 'verified' AND created_at BETWEEN ? AND ?", [$tenantId, $start, $end])['c'] ?? 0);

        // 4. Inpatient Admissions
        $totalAdmissions = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM admissions WHERE tenant_id = ? AND admission_date BETWEEN ? AND ?", [$tenantId, $start, $end])['c'] ?? 0);
        $dischargedAdmissions = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM admissions WHERE tenant_id = ? AND status = 'discharged' AND admission_date BETWEEN ? AND ?", [$tenantId, $start, $end])['c'] ?? 0);

        // 5. Financial Billing & Invoicing
        $invoiceBilled = (int) (DB::selectOne("SELECT COALESCE(SUM(total_minor), 0) AS s FROM invoices WHERE tenant_id = ? AND created_at BETWEEN ? AND ?", [$tenantId, $start, $end])['s'] ?? 0);
        $invoiceCollected = (int) (DB::selectOne("SELECT COALESCE(SUM(paid_minor), 0) AS s FROM invoices WHERE tenant_id = ? AND created_at BETWEEN ? AND ?", [$tenantId, $start, $end])['s'] ?? 0);
        $invoiceOutstanding = max(0, $invoiceBilled - $invoiceCollected);

        // 6. Pharmacy OTC POS & Margin
        $otcSalesBilled = (int) (DB::selectOne("SELECT COALESCE(SUM(total_minor), 0) AS s FROM sales WHERE tenant_id = ? AND status = 'completed' AND created_at BETWEEN ? AND ?", [$tenantId, $start, $end])['s'] ?? 0);

        // Profit Calculation from Sale Items vs Wholesale Price
        $pharmacyProfitRow = DB::selectOne(
            "SELECT COALESCE(SUM(si.line_total_minor), 0) AS total_revenue,
                    COALESCE(SUM(si.qty * COALESCE(p.wholesale_price_minor, 0)), 0) AS total_cogs
             FROM sale_items si
             JOIN products p ON p.id = si.product_id
             JOIN sales s ON s.id = si.sale_id
             WHERE si.tenant_id = ? AND s.status = 'completed' AND s.created_at BETWEEN ? AND ?",
            [$tenantId, $start, $end]
        );

        $pharmacyRevenue = (int) ($pharmacyProfitRow['total_revenue'] ?? $otcSalesBilled);
        $pharmacyCogs = (int) ($pharmacyProfitRow['total_cogs'] ?? 0);
        $pharmacyGrossProfit = max(0, $pharmacyRevenue - $pharmacyCogs);
        $pharmacyMarginPct = $pharmacyRevenue > 0 ? round(($pharmacyGrossProfit / $pharmacyRevenue) * 100, 1) : 0;

        // Combined Financial Totals
        $grossRevenue = $invoiceBilled + $otcSalesBilled;
        $totalCollected = $invoiceCollected + $otcSalesBilled;

        // 7. Monthly Trend Breakdown for the Financial Year
        $monthlyTrend = DB::select(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym,
                    COUNT(DISTINCT id) AS inv_count,
                    COALESCE(SUM(total_minor), 0) AS billed,
                    COALESCE(SUM(paid_minor), 0) AS collected
             FROM invoices
             WHERE tenant_id = ? AND created_at BETWEEN ? AND ?
             GROUP BY ym
             ORDER BY ym ASC",
            [$tenantId, $start, $end]
        );

        // 8. Multi-Branch Operational & Financial Performance
        $branchBreakdown = DB::select(
            "SELECT b.id, b.uuid, b.name, b.code, b.is_main,
                    (SELECT COUNT(*) FROM patients p WHERE p.branch_id = b.id AND p.tenant_id = b.tenant_id AND p.deleted_at IS NULL) AS patient_count,
                    (SELECT COUNT(*) FROM users u WHERE u.branch_id = b.id AND u.tenant_id = b.tenant_id AND u.is_active = 1) AS staff_count,
                    (SELECT COUNT(*) FROM sales s WHERE s.branch_id = b.id AND s.tenant_id = b.tenant_id AND s.status = 'completed' AND s.created_at BETWEEN ? AND ?) AS sales_count,
                    (SELECT COALESCE(SUM(s.total_minor), 0) FROM sales s WHERE s.branch_id = b.id AND s.tenant_id = b.tenant_id AND s.status = 'completed' AND s.created_at BETWEEN ? AND ?) AS sales_volume
             FROM branches b
             WHERE b.tenant_id = ? AND b.is_active = 1
             ORDER BY b.is_main DESC, b.name ASC",
            [$start, $end, $start, $end, $tenantId]
        );

        return view('reports.index', [
            'title' => 'Financial Year & Hospital Operations Report',
            'fy' => $fy,
            'fyLabel' => $fyLabel,
            'branchBreakdown' => $branchBreakdown,
            'metrics' => [
                'total_patients_all_time' => $totalPatientsAllTime,
                'total_patients_fy'       => $totalPatientsFY,
                'total_consultations'     => $totalConsultations,
                'total_lab_orders'        => $totalLabOrders,
                'verified_lab_orders'     => $verifiedLabOrders,
                'total_admissions'        => $totalAdmissions,
                'discharged_admissions'   => $dischargedAdmissions,
                'gross_revenue'           => $grossRevenue,
                'total_collected'         => $totalCollected,
                'invoice_outstanding'     => $invoiceOutstanding,
                'pharmacy_revenue'        => $pharmacyRevenue,
                'pharmacy_cogs'           => $pharmacyCogs,
                'pharmacy_gross_profit'   => $pharmacyGrossProfit,
                'pharmacy_margin_pct'     => $pharmacyMarginPct,
            ],
            'monthlyTrend' => $monthlyTrend,
        ]);
    }
}
