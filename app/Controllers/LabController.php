<?php
namespace App\Controllers;

use App\Models\DiagnosticOrder;
use App\Models\DiagnosticResult;
use App\Models\DiagnosticTest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\Sequence;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class LabController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();

        // 1. Pending & In-progress tests queue
        $pendingResults = DB::select(
            "SELECT r.*, o.order_no, o.priority, o.clinical_indication, o.created_at AS order_date, o.status AS order_status,
                    t.name AS test_name, t.code AS test_code, t.category AS test_category, t.specimen_type, t.price_minor,
                    p.id AS patient_id, p.first_name, p.last_name, p.mrn, p.sex, p.dob,
                    u.name AS doctor_name
             FROM diagnostic_results r
             JOIN diagnostic_orders o ON o.id = r.order_id
             JOIN diagnostic_tests t ON t.id = o.test_id
             JOIN patients p ON p.id = o.patient_id
             LEFT JOIN users u ON u.id = o.doctor_user_id
             WHERE o.tenant_id = ? AND o.status != 'verified'
             ORDER BY CASE o.priority WHEN 'stat' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END ASC, r.id DESC",
            [$tenantId]
        );

        // 2. Verified Results
        $verifiedResults = DB::select(
            "SELECT r.*, o.order_no, o.verified_at, t.name AS test_name, t.code AS test_code, t.category AS test_category,
                    p.first_name, p.last_name, p.mrn, u.name AS verified_by_name
             FROM diagnostic_results r
             JOIN diagnostic_orders o ON o.id = r.order_id
             JOIN diagnostic_tests t ON t.id = o.test_id
             JOIN patients p ON p.id = o.patient_id
             LEFT JOIN users u ON u.id = o.verified_by
             WHERE o.tenant_id = ? AND o.status = 'verified'
             ORDER BY o.verified_at DESC LIMIT 20",
            [$tenantId]
        );

        // 3. Tests Catalog
        $testsCatalog = DB::select("SELECT * FROM diagnostic_tests WHERE tenant_id = ? ORDER BY category, name", [$tenantId]);

        return view('lab.index', [
            'title' => 'Laboratory Information Management (LIMS)',
            'pendingResults' => $pendingResults,
            'verifiedResults' => $verifiedResults,
            'testsCatalog' => $testsCatalog,
        ]);
    }

    public function collectSpecimen(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $result = DiagnosticResult::where('id', $id)->first();
        if (!$result) abort(404);

        DB::statement(
            "UPDATE diagnostic_orders SET status = 'sample_collected', sample_collected_at = NOW(), sample_collected_by = ? WHERE id = ? AND tenant_id = ?",
            [auth()->id ?? 1, $result->order_id, $tenantId]
        );

        return back()->with('success', "Specimen accessioned into laboratory.");
    }

    public function recordResult(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $result = DiagnosticResult::where('id', $id)->first();
        if (!$result) abort(404);

        $val = trim($request->input('result_value') ?: '');
        $isAbnormal = (int) $request->input('is_abnormal', 0);
        $findings = trim($request->input('findings') ?: '');

        $result->update([
            'observed_value'   => $val,
            'is_abnormal'      => $isAbnormal,
            'technician_notes' => $findings,
        ]);

        DB::statement(
            "UPDATE diagnostic_orders SET status = 'processing', updated_at = NOW() WHERE id = ? AND tenant_id = ?",
            [$result->order_id, $tenantId]
        );

        return back()->with('success', "Result for test recorded. Ready for verification.");
    }

    public function verifyResult(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $result = DiagnosticResult::where('id', $id)->first();
        if (!$result) abort(404);

        DB::statement(
            "UPDATE diagnostic_orders SET status = 'verified', verified_at = NOW(), verified_by = ? WHERE id = ? AND tenant_id = ?",
            [auth()->id ?? 1, $result->order_id, $tenantId]
        );

        // Automatically add to patient's invoice if open invoice exists
        $order = DB::selectOne("SELECT * FROM diagnostic_orders WHERE id = ?", [$result->order_id]);
        if ($order) {
            $test = DB::selectOne("SELECT * FROM diagnostic_tests WHERE id = ?", [$order['test_id']]);
            if ($test) {
                $inv = DB::selectOne("SELECT * FROM invoices WHERE tenant_id = ? AND patient_id = ? AND status = 'unpaid' ORDER BY id DESC LIMIT 1", [$tenantId, $order['patient_id']]);
                if ($inv) {
                    InvoiceItem::create([
                        'invoice_id' => $inv['id'],
                        'item_type' => 'diagnostic',
                        'item_id' => $test['id'],
                        'description' => 'Lab: ' . $test['name'],
                        'quantity' => 1,
                        'unit_price_minor' => $test['price_minor'],
                        'total_minor' => $test['price_minor'],
                    ]);
                    $newSub = $inv['subtotal_minor'] + $test['price_minor'];
                    $newTotal = $inv['total_minor'] + $test['price_minor'];
                    $newBal = $newTotal - $inv['paid_minor'];
                    DB::statement("UPDATE invoices SET subtotal_minor = ?, total_minor = ?, balance_minor = ?, updated_at = NOW() WHERE id = ?", [$newSub, $newTotal, $newBal, $inv['id']]);
                }
            }
        }

        return back()->with('success', "Lab result verified and released to doctor EMR.");
    }
}