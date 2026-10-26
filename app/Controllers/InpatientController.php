<?php
namespace App\Controllers;

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Patient;
use App\Models\Ward;
use App\Services\Sequence;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class InpatientController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();

        // 1. Wards and their beds
        $wards = DB::select("SELECT * FROM wards WHERE tenant_id = ? AND is_active = 1 ORDER BY id ASC", [$tenantId]);
        $beds = DB::select(
            "SELECT b.*, w.name AS ward_name, w.code AS ward_code,
                    p.id AS patient_id, p.first_name, p.last_name, p.mrn, p.sex, p.dob,
                    a.id AS admission_id, a.admission_no, a.admission_date, a.admission_reason
             FROM beds b
             JOIN wards w ON w.id = b.ward_id
             LEFT JOIN admissions a ON (a.bed_id = b.id AND a.status = 'admitted')
             LEFT JOIN patients p ON p.id = a.patient_id
             WHERE b.tenant_id = ?
             ORDER BY b.ward_id, b.bed_number ASC",
            [$tenantId]
        );

        // Group beds by ward_id and calculate per-ward occupancy
        $wardBeds = [];
        $wardStats = [];
        foreach ($wards as $w) {
            $wardStats[$w['id']] = [
                'total_beds' => 0,
                'occupied_beds' => 0,
                'available_beds' => 0,
                'occupancy_rate' => 0,
            ];
        }

        foreach ($beds as $b) {
            $wid = (int) $b['ward_id'];
            $wardBeds[$wid][] = $b;
            if (!isset($wardStats[$wid])) {
                $wardStats[$wid] = ['total_beds' => 0, 'occupied_beds' => 0, 'available_beds' => 0, 'occupancy_rate' => 0];
            }
            $wardStats[$wid]['total_beds']++;
            if ($b['status'] === 'occupied') {
                $wardStats[$wid]['occupied_beds']++;
            } else {
                $wardStats[$wid]['available_beds']++;
            }
        }

        foreach ($wardStats as $wid => &$ws) {
            $ws['occupancy_rate'] = $ws['total_beds'] > 0 ? round(($ws['occupied_beds'] / $ws['total_beds']) * 100) : 0;
        }
        unset($ws);

        // 2. Active Admissions
        $activeAdmissions = DB::select(
            "SELECT a.*, p.first_name, p.last_name, p.mrn, p.sex, p.dob, p.phone,
                    w.name AS ward_name, b.bed_number, u.name AS doctor_name
             FROM admissions a
             JOIN patients p ON p.id = a.patient_id
             JOIN beds b ON b.id = a.bed_id
             JOIN wards w ON w.id = b.ward_id
             LEFT JOIN users u ON u.id = a.admitting_doctor_id
             WHERE a.tenant_id = ? AND a.status = 'admitted'
             ORDER BY a.admission_date DESC",
            [$tenantId]
        );

        // 3. Available beds for quick admission dropdown
        $availableBeds = DB::select(
            "SELECT b.id, b.bed_number, b.daily_rate_minor, w.name AS ward_name
             FROM beds b
             JOIN wards w ON w.id = b.ward_id
             WHERE b.tenant_id = ? AND b.status = 'available'
             ORDER BY w.name, b.bed_number",
            [$tenantId]
        );

        $totalBeds = count($beds);
        $occupiedBeds = count(array_filter($beds, fn($b) => $b['status'] === 'occupied'));
        $occupancyRate = $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0;

        return view('inpatient.index', [
            'title' => 'Inpatient Wards & Bed Board (IPD)',
            'wards' => $wards,
            'wardBeds' => $wardBeds,
            'wardStats' => $wardStats,
            'activeAdmissions' => $activeAdmissions,
            'availableBeds' => $availableBeds,
            'stats' => [
                'total_beds' => $totalBeds,
                'occupied_beds' => $occupiedBeds,
                'available_beds' => $totalBeds - $occupiedBeds,
                'occupancy_rate' => $occupancyRate,
            ],
        ]);
    }

    public function storeWard(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $name = trim((string) $request->input('name'));
        $code = strtoupper(trim((string) $request->input('code')));
        $type = $request->input('type', 'general_male');
        $floor = (int) $request->input('floor_number', 1);
        $pricePerDay = to_minor((float) $request->input('price_per_day', 0));
        $numBeds = max(1, min(50, (int) $request->input('number_of_beds', 1)));

        if (empty($name) || empty($code)) {
            return back()->with('error', 'Ward name and code are required.');
        }

        $wardId = DB::insert(
            "INSERT INTO wards (tenant_id, name, code, type, floor_number, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())",
            [$tenantId, $name, $code, $type, $floor]
        );

        // Auto-create initial beds
        for ($i = 1; $i <= $numBeds; $i++) {
            $bedNumber = sprintf('%s-B%02d', $code, $i);
            DB::insert(
                "INSERT INTO beds (tenant_id, ward_id, bed_number, daily_rate_minor, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'available', NOW(), NOW())",
                [$tenantId, $wardId, $bedNumber, $pricePerDay]
            );
        }

        return back()->with('success', "Ward '{$name}' created with {$numBeds} beds at " . format_minor($pricePerDay, tenant('currency')) . " / day.");
    }

    public function admit(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $patientId = (int) $request->input('patient_id');
        $bedId = (int) $request->input('bed_id');
        $diagnosis = trim($request->input('admitting_diagnosis') ?: 'Inpatient Admission');

        $patient = Patient::find($patientId);
        if (!$patient) return back()->with('error', 'Patient not found.');

        $bed = Bed::where('id', $bedId)->first();
        if (!$bed || $bed->status !== 'available') {
            return back()->with('error', 'Selected bed is already occupied or unavailable.');
        }

        $admNo = Sequence::next('admission', 'ADM-');

        $adm = Admission::create([
            'admission_no'        => $admNo,
            'patient_id'          => $patient->id,
            'bed_id'              => $bed->id,
            'admitting_doctor_id' => auth()->id ?? 1,
            'admission_date'      => now(),
            'status'              => 'admitted',
            'admission_reason'    => $diagnosis,
        ]);

        $bed->update([
            'status' => 'occupied',
        ]);

        return back()->with('success', "Patient {$patient->full_name} admitted to Bed {$bed->bed_number} (Admission #{$admNo}).");
    }

    public function discharge(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $adm = Admission::find($id);
        if (!$adm) abort(404);

        $summary = trim($request->input('discharge_summary') ?: 'Discharged stable.');

        $now = now();
        $adm->update([
            'discharge_date'      => $now,
            'status'              => 'discharged',
            'discharge_summary'   => $summary,
        ]);

        // Free the bed
        if ($adm->bed_id) {
            DB::statement("UPDATE beds SET status = 'available' WHERE id = ? AND tenant_id = ?", [$adm->bed_id, $tenantId]);
        }

        // Calculate Bed Accommodation Days and post to bill
        $ward = Ward::find($adm->ward_id);
        if ($ward && $ward->daily_rate_minor > 0) {
            $admTime = strtotime($adm->admission_date);
            $days = max(1, (int) ceil((time() - $admTime) / 86400));
            $totalBedCharge = $days * $ward->daily_rate_minor;

            $inv = DB::selectOne("SELECT * FROM invoices WHERE tenant_id = ? AND patient_id = ? AND status = 'unpaid' ORDER BY id DESC LIMIT 1", [$tenantId, $adm->patient_id]);
            if ($inv) {
                InvoiceItem::create([
                    'invoice_id' => $inv['id'],
                    'item_type' => 'bed_charge',
                    'item_id' => $ward->id,
                    'description' => "Bed: {$ward->name} ({$days} days @ " . format_minor($ward->daily_rate_minor, tenant('currency')) . "/day)",
                    'quantity' => $days,
                    'unit_price_minor' => $ward->daily_rate_minor,
                    'total_minor' => $totalBedCharge,
                ]);
                $newSub = $inv['subtotal_minor'] + $totalBedCharge;
                $newTotal = $inv['total_minor'] + $totalBedCharge;
                $newBal = $newTotal - $inv['paid_minor'];
                DB::statement("UPDATE invoices SET subtotal_minor = ?, total_minor = ?, balance_minor = ?, updated_at = NOW() WHERE id = ?", [$newSub, $newTotal, $newBal, $inv['id']]);
            }
        }

        return back()->with('success', "Patient discharged successfully. Bed freed for next patient.");
    }
}