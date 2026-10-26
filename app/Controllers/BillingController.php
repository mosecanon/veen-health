<?php
namespace App\Controllers;

use App\Models\BillableService;
use App\Models\InsuranceClaim;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Services\Barcode;
use App\Services\Sequence;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class BillingController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();

        // 1. Invoices
        $invoices = DB::select(
            "SELECT i.*, (i.total_minor - i.paid_minor) AS balance_minor,
                    p.first_name, p.last_name, p.mrn, p.phone, p.insurance_provider AS patient_insurance
             FROM invoices i
             JOIN patients p ON p.id = i.patient_id
             WHERE i.tenant_id = ?
             ORDER BY i.id DESC LIMIT 50",
            [$tenantId]
        );

        // 2. Metrics
        $totalBilled = (int) (DB::selectOne("SELECT COALESCE(SUM(total_minor), 0) AS s FROM invoices WHERE tenant_id = ?", [$tenantId])['s'] ?? 0);
        $totalCollected = (int) (DB::selectOne("SELECT COALESCE(SUM(paid_minor), 0) AS s FROM invoices WHERE tenant_id = ?", [$tenantId])['s'] ?? 0);
        $totalOutstanding = (int) (DB::selectOne("SELECT COALESCE(SUM(total_minor - paid_minor), 0) AS s FROM invoices WHERE tenant_id = ? AND status != 'paid'", [$tenantId])['s'] ?? 0);
        $pendingClaimsCount = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM insurance_claims WHERE tenant_id = ? AND status = 'submitted'", [$tenantId])['c'] ?? 0);

        // 3. Billable services catalog
        $services = DB::select("SELECT * FROM billable_services WHERE tenant_id = ? AND is_active = 1 ORDER BY category, name", [$tenantId]);
        $insurances = DB::select("SELECT * FROM insurance_providers WHERE tenant_id = ? AND is_active = 1", [$tenantId]);

        return view('billing.index', [
            'title' => 'Patient Invoicing & Billing',
            'invoices' => $invoices,
            'services' => $services,
            'insurances' => $insurances,
            'metrics' => [
                'total_billed' => $totalBilled,
                'total_collected' => $totalCollected,
                'total_outstanding' => $totalOutstanding,
                'pending_claims' => $pendingClaimsCount,
            ],
        ]);
    }

    public function createInvoice(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $patientId = (int) $request->input('patient_id');

        $patient = Patient::find($patientId);
        if (!$patient) return back()->with('error', 'Patient not found.');

        $invNo = Sequence::next('invoice', 'INV-');

        $inv = Invoice::create([
            'invoice_no'     => $invNo,
            'patient_id'     => $patient->id,
            'subtotal_minor' => 0,
            'tax_minor'      => 0,
            'discount_minor' => 0,
            'total_minor'    => 0,
            'paid_minor'     => 0,
            'status'         => 'unpaid',
            'due_date'       => date('Y-m-d', strtotime('+30 days')),
        ]);

        // Add Consultation fee if selected
        if ($serviceId = $request->input('service_id')) {
            $srv = DB::selectOne("SELECT * FROM billable_services WHERE id = ? AND tenant_id = ?", [$serviceId, $tenantId]);
            if ($srv) {
                InvoiceItem::create([
                    'invoice_id'       => $inv->id,
                    'service_id'       => $srv['id'],
                    'item_name'        => $srv['name'],
                    'qty'              => 1,
                    'unit_price_minor' => $srv['price_minor'],
                    'total_minor'      => $srv['price_minor'],
                ]);
                $inv->update([
                    'subtotal_minor' => $srv['price_minor'],
                    'total_minor'    => $srv['price_minor'],
                ]);
            }
        }

        return redirect(route('billing.show', ['id' => $inv->uuid ?? $inv->id]))->with('success', "Invoice {$invNo} created for {$patient->full_name}.");
    }

    public function show(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $invoice = DB::selectOne(
            "SELECT i.*, (i.total_minor - i.paid_minor) AS balance_minor,
                    p.first_name, p.last_name, p.mrn, p.phone, p.email, p.address, p.insurance_provider,
                    p.insurance_provider AS insurer_name
             FROM invoices i
             JOIN patients p ON p.id = i.patient_id
             WHERE (i.uuid = ? OR i.id = ?) AND i.tenant_id = ?",
            [$id, $id, $tenantId]
        );
        if (!$invoice) abort(404, 'Invoice not found');

        $items = DB::select("SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC", [$invoice['id']]);
        $services = DB::select("SELECT * FROM billable_services WHERE tenant_id = ? AND is_active = 1", [$tenantId]);

        return view('billing.show', [
            'title' => 'Invoice ' . $invoice['invoice_no'],
            'invoice' => $invoice,
            'items' => $items,
            'services' => $services,
        ]);
    }

    public function addItem(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $inv = Invoice::find($id);
        if (!$inv) abort(404);

        $desc = trim($request->input('description') ?: 'Clinical Service');
        $qty = max(1, (int) $request->input('quantity', 1));
        $price = to_minor((float) $request->input('unit_price', 0), tenant('currency'));
        $total = $qty * $price;

        InvoiceItem::create([
            'invoice_id'       => $inv->id,
            'item_name'        => $desc,
            'qty'              => $qty,
            'unit_price_minor' => $price,
            'total_minor'      => $total,
        ]);

        $newSub = $inv->subtotal_minor + $total;
        $newTotal = $inv->total_minor + $total;
        $newBal = $newTotal - $inv->paid_minor;
        $inv->update([
            'subtotal_minor' => $newSub,
            'total_minor'    => $newTotal,
            'status'         => $newBal <= 0 ? 'paid' : ($inv->paid_minor > 0 ? 'partial' : 'unpaid'),
        ]);

        return back()->with('success', "Item '{$desc}' added to invoice.");
    }

    public function recordPayment(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $inv = Invoice::find($id);
        if (!$inv) abort(404);

        $amountMinor = to_minor((float) $request->input('amount', 0), tenant('currency'));
        if ($amountMinor <= 0) return back()->with('error', 'Please enter a valid payment amount.');

        $newPaid = $inv->paid_minor + $amountMinor;
        $newBal = max(0, $inv->total_minor - $newPaid);
        $newStatus = $newBal == 0 ? 'paid' : 'partial';

        $inv->update([
            'paid_minor' => $newPaid,
            'status'     => $newStatus,
        ]);

        return back()->with('success', "Payment of " . format_minor($amountMinor, tenant('currency')) . " recorded successfully.");
    }

    public function claims(Request $request): Response
    {
        $tenantId = TenantManager::id();

        $claims = DB::select(
            "SELECT c.*, ins.name AS insurer_name, ins.code AS insurer_code,
                    p.first_name, p.last_name, p.mrn, inv.invoice_no, inv.total_minor AS inv_total
             FROM insurance_claims c
             JOIN insurance_providers ins ON ins.id = c.provider_id
             JOIN patients p ON p.id = c.patient_id
             LEFT JOIN invoices inv ON inv.id = c.invoice_id
             WHERE c.tenant_id = ?
             ORDER BY c.id DESC",
            [$tenantId]
        );

        $insurances = DB::select("SELECT * FROM insurance_providers WHERE tenant_id = ? AND is_active = 1", [$tenantId]);

        return view('billing.claims', [
            'title' => 'Insurance Claims & Third-Party Payers',
            'claims' => $claims,
            'insurances' => $insurances,
        ]);
    }

    public function submitClaim(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $patientId = (int) $request->input('patient_id');
        $patient = Patient::find($patientId);
        $insurerId = (int) $request->input('insurance_provider_id');
        $invId = $request->input('invoice_id') ? (int) $request->input('invoice_id') : null;
        $policyNo = trim($request->input('policy_number') ?: '');
        $preauth = trim($request->input('preauthorization_code') ?: '');
        $amountMinor = to_minor((float) $request->input('amount', 0), tenant('currency'));

        $claimNo = Sequence::next('claim', 'CLM-');

        InsuranceClaim::create([
            'claim_no'              => $claimNo,
            'provider_id'           => $insurerId,
            'patient_id'            => $patientId,
            'invoice_id'            => $invId,
            'policy_number'         => $policyNo,
            'member_name'           => $patient ? $patient->full_name : 'Patient',
            'preauth_code'          => $preauth,
            'claim_amount_minor'    => $amountMinor,
            'approved_amount_minor' => 0,
            'status'                => 'submitted',
            'submitted_at'          => now(),
        ]);

        return back()->with('success', "Insurance claim {$claimNo} submitted successfully.");
    }

    public function adjudicateClaim(Request $request, int|string $id): Response
    {
        $claim = InsuranceClaim::where('id', $id)->first();
        if (!$claim) abort(404);

        $status = $request->input('status', 'approved');
        $approvedMinor = to_minor((float) $request->input('approved_amount', 0), tenant('currency'));

        $claim->update([
            'status'                => $status,
            'approved_amount_minor' => $status === 'approved' ? $approvedMinor : 0,
            'settled_at'            => $status === 'settled' ? now() : null,
            'rejection_reason'      => $status === 'rejected' ? $request->input('notes') : null,
        ]);

        return back()->with('success', "Claim {$claim->claim_no} marked as {$status}.");
    }
}