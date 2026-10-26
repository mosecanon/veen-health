<?php
namespace App\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $selectedDate = $request->query('date', date('Y-m-d'));
        $month = (int) $request->query('month', date('n'));
        $year = (int) $request->query('year', date('Y'));

        // Appointments for the calendar month
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $appointments = DB::select(
            "SELECT a.*, p.first_name, p.last_name, p.mrn, p.phone, p.national_id,
                    u.name AS doctor_name, u.title AS doctor_title
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.doctor_user_id
             WHERE a.tenant_id = ? AND a.appointment_date BETWEEN ? AND ?
             ORDER BY a.appointment_date ASC, a.start_time ASC",
            [$tenantId, $startDate, $endDate]
        );

        // Group appointments by date for calendar render
        $calendarDays = [];
        foreach ($appointments as $app) {
            $calendarDays[$app['appointment_date']][] = $app;
        }

        // Today's appointments for urgent desk review
        $today = date('Y-m-d');
        $todayAppointments = DB::select(
            "SELECT a.*, p.first_name, p.last_name, p.mrn, p.phone,
                    u.name AS doctor_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.doctor_user_id
             WHERE a.tenant_id = ? AND a.appointment_date = ?
             ORDER BY a.start_time ASC",
            [$tenantId, $today]
        );

        $doctors = DB::select(
            "SELECT id, name, title FROM users WHERE tenant_id = ? AND role = 'doctor' AND is_active = 1 ORDER BY name ASC",
            [$tenantId]
        );

        return view('appointments.index', [
            'title' => 'Appointments & Patient Reservations Calendar',
            'appointments' => $appointments,
            'calendarDays' => $calendarDays,
            'todayAppointments' => $todayAppointments,
            'doctors' => $doctors,
            'selectedDate' => $selectedDate,
            'currentMonth' => $month,
            'currentYear' => $year,
            'todayDate' => $today,
        ]);
    }

    public function store(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $patientId = (int) $request->input('patient_id');
        $doctorId = $request->input('doctor_user_id') ? (int) $request->input('doctor_user_id') : null;
        $date = $request->input('appointment_date');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time') ?: null;
        $type = $request->input('type', 'consultation');
        $title = trim((string) $request->input('title', 'Clinical Consultation'));
        $notes = trim((string) $request->input('notes', ''));

        if (!$patientId || !$date || !$startTime) {
            return back()->with('error', 'Please select a patient, date, and appointment time.');
        }

        $patient = Patient::find($patientId);
        if (!$patient) return back()->with('error', 'Selected patient not found.');

        Appointment::create([
            'patient_id'       => $patientId,
            'doctor_user_id'   => $doctorId,
            'appointment_date' => $date,
            'start_time'       => $startTime,
            'end_time'         => $endTime,
            'type'             => $type,
            'title'            => $title,
            'notes'            => $notes,
            'status'           => 'scheduled',
            'reminder_sent'    => 0,
        ]);

        return back()->with('success', "Appointment scheduled successfully for {$patient->full_name} on " . date('d M Y', strtotime($date)) . " at {$startTime}.");
    }

    public function updateStatus(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $app = Appointment::where('id', $id)->first();
        if (!$app) abort(404);

        $status = $request->input('status', 'confirmed');
        $validStatuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
        if (in_array($status, $validStatuses)) {
            $app->update(['status' => $status]);
        }

        return back()->with('success', "Appointment status updated to " . ucfirst(str_replace('_', ' ', $status)) . ".");
    }

    public function todayReminders(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $today = date('Y-m-d');

        $appointments = DB::select(
            "SELECT a.id, a.start_time, a.title, a.type, a.status,
                    p.first_name, p.last_name, p.mrn, p.phone,
                    u.name AS doctor_name
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             LEFT JOIN users u ON u.id = a.doctor_user_id
             WHERE a.tenant_id = ? AND a.appointment_date = ? AND a.status IN ('scheduled', 'confirmed')
             ORDER BY a.start_time ASC",
            [$tenantId, $today]
        );

        return Response::json([
            'count' => count($appointments),
            'appointments' => $appointments,
        ]);
    }
}
