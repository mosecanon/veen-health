<?php
namespace App\Controllers;

use App\Models\Patient;
use App\Models\Queue;
use App\Models\ServicePoint;
use App\Models\Ticket;
use App\Models\TicketEvent;
use App\Services\Sequence;
use Veen\Core\Auth;
use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class QueueController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $today = today();

        $queues = DB::select(
            "SELECT q.*,
                    (SELECT COUNT(*) FROM tickets t WHERE t.queue_id = q.id AND t.issued_on = ? AND t.status = 'waiting') AS waiting_count,
                    (SELECT COUNT(*) FROM tickets t WHERE t.queue_id = q.id AND t.issued_on = ? AND t.status IN ('called','serving')) AS serving_count,
                    (SELECT COUNT(*) FROM tickets t WHERE t.queue_id = q.id AND t.issued_on = ? AND t.status = 'completed') AS completed_count
             FROM queues q
             WHERE q.tenant_id = ? AND q.is_active = 1
             ORDER BY q.sort_order ASC",
            [$today, $today, $today, $tenantId]
        );

        $servicePoints = DB::select(
            "SELECT sp.*, q.name AS queue_name, q.color AS queue_color, q.prefix AS queue_prefix,
                    u.name AS staff_name,
                    (SELECT code FROM tickets t WHERE t.service_point_id = sp.id AND t.status IN ('called','serving') AND t.issued_on = ? ORDER BY t.id DESC LIMIT 1) AS current_ticket_code
             FROM service_points sp
             INNER JOIN queues q ON q.id = sp.queue_id
             LEFT JOIN users u ON u.id = sp.staff_user_id
             WHERE sp.tenant_id = ? AND sp.is_active = 1
             ORDER BY q.sort_order ASC, sp.name ASC",
            [$today, $tenantId]
        );

        $displays = DB::select('SELECT * FROM displays WHERE tenant_id = ?', [$tenantId]);

        return view('queue.index', [
            'title' => 'Queue & Flow Management',
            'queues' => $queues,
            'servicePoints' => $servicePoints,
            'displays' => $displays,
        ]);
    }

    public function kiosk(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $today = today();

        $queues = DB::select(
            "SELECT q.*,
                    (SELECT COUNT(*) FROM tickets t WHERE t.queue_id = q.id AND t.issued_on = ? AND t.status = 'waiting') AS waiting_count
             FROM queues q
             WHERE q.tenant_id = ? AND q.is_active = 1
             ORDER BY q.sort_order ASC",
            [$today, $tenantId]
        );

        $recentTicket = null;
        $recentId = $request->query('ticket_id');
        if ($recentId) {
            $recentTicket = DB::selectOne(
                "SELECT t.*, q.name AS queue_name, p.first_name, p.last_name, p.mrn
                 FROM tickets t
                 JOIN queues q ON q.id = t.queue_id
                 LEFT JOIN patients p ON p.id = t.patient_id
                 WHERE (t.uuid = ? OR t.id = ?) AND t.tenant_id = ?",
                [$recentId, $recentId, $tenantId]
            );
        }

        return view('queue.kiosk', [
            'title' => 'Ticket Kiosk / Reception',
            'queues' => $queues,
            'recentTicket' => $recentTicket,
        ]);
    }

    public function issueTicket(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $data = $this->validate($request, [
            'queue_id'      => 'required|integer',
            'patient_id'    => 'nullable',
            'customer_name' => 'nullable|string|max:150',
            'priority'      => 'nullable|in:0,1,2',
            'notes'         => 'nullable|string|max:255',
        ]);

        $queue = Queue::find((int) $data['queue_id']);
        if (!$queue) {
            abort(404, 'Queue not found');
        }

        $patientName = $data['customer_name'] ?? null;
        if (!empty($data['patient_id'])) {
            $p = Patient::find($data['patient_id']);
            if ($p) {
                $patientName = $p->first_name . ' ' . $p->last_name;
            }
        } elseif (!empty($request->input('first_name')) && !empty($request->input('last_name'))) {
            // Quick-register new patient from ticket kiosk
            $p = new Patient();
            $p->fill([
                'first_name' => trim((string) $request->input('first_name')),
                'last_name'  => trim((string) $request->input('last_name')),
                'phone'      => trim((string) $request->input('phone', '')),
                'sex'        => in_array($request->input('sex'), ['male', 'female', 'other'], true) ? $request->input('sex') : 'female',
                'dob'        => $request->input('dob') ?: null,
                'national_id'=> $request->input('national_id') ?: null,
                'allergies'  => $request->input('allergies') ?: null,
            ]);
            $p->forceFill([
                'mrn' => Sequence::mrn(),
                'branch_id' => $queue->branch_id ?? 1,
            ]);
            $p->save();
            $data['patient_id'] = $p->id;
            $patientName = $p->first_name . ' ' . $p->last_name;
        }

        $ticket = DB::transaction(function () use ($queue, $data, $patientName, $tenantId) {
            $seq = Sequence::ticket($queue);
            $now = now();
            $today = today();

            DB::statement(
                'INSERT INTO tickets (tenant_id, branch_id, queue_id, patient_id, number, code, customer_name, priority, status, issued_on, notes, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId, $queue->branch_id ?? 1, $queue->id, $data['patient_id'] ?? null,
                    $seq['number'], $seq['code'], $patientName, (int) ($data['priority'] ?? 0),
                    'waiting', $today, $data['notes'] ?? null, Auth::id(), $now, $now,
                ]
            );

            $ticketId = (int) DB::connection()->lastInsertId();

            DB::statement(
                'INSERT INTO ticket_events (tenant_id, ticket_id, type, user_id, created_at) VALUES (?, ?, ?, ?, ?)',
                [$tenantId, $ticketId, 'issued', Auth::id(), $now]
            );

            return DB::selectOne('SELECT * FROM tickets WHERE id = ?', [$ticketId]);
        });

        if ($request->wantsJson()) {
            return Response::json([
                'success' => true,
                'ticket' => $ticket,
                'message' => "Ticket {$ticket['code']} issued successfully",
            ]);
        }

        return redirect(route('queue.kiosk', ['ticket_id' => $ticket['uuid'] ?? $ticket['id']]))
            ->with('success', "Ticket {$ticket['code']} issued!");
    }

    public function console(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $today = today();

        $sp = DB::selectOne(
            'SELECT sp.*, q.name AS queue_name, q.color AS queue_color, q.prefix AS queue_prefix
             FROM service_points sp
             JOIN queues q ON q.id = sp.queue_id
             WHERE (sp.uuid = ? OR sp.id = ?) AND sp.tenant_id = ?',
            [$id, $id, $tenantId]
        );
        if (!$sp) {
            abort(404, 'Service point not found');
        }

        // Active ticket in this room
        $currentTicket = DB::selectOne(
            "SELECT t.*, p.first_name, p.last_name, p.mrn, p.phone, p.dob, p.sex, p.allergies
             FROM tickets t
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE t.service_point_id = ? AND t.status IN ('called', 'serving') AND t.issued_on = ? AND t.tenant_id = ?
             ORDER BY t.id DESC LIMIT 1",
            [$sp['id'], $today, $tenantId]
        );

        // Waiting queue for this service point's queue
        $waitingTickets = DB::select(
            "SELECT t.*, p.first_name, p.last_name, p.mrn
             FROM tickets t
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE t.queue_id = ? AND t.status = 'waiting' AND t.issued_on = ? AND t.tenant_id = ?
             ORDER BY t.priority DESC, t.id ASC LIMIT 30",
            [$sp['queue_id'], $today, $tenantId]
        );

        // All active service points for transferring
        $otherServicePoints = DB::select(
            'SELECT sp.*, q.name AS queue_name FROM service_points sp JOIN queues q ON q.id = sp.queue_id WHERE sp.tenant_id = ? AND sp.id != ? AND sp.is_active = 1 ORDER BY q.sort_order ASC, sp.name ASC',
            [$tenantId, $sp['id']]
        );

        // Completed tickets today at this service point
        $completedToday = DB::select(
            "SELECT t.*, p.first_name, p.last_name FROM tickets t LEFT JOIN patients p ON p.id = t.patient_id WHERE t.service_point_id = ? AND t.status = 'completed' AND t.issued_on = ? AND t.tenant_id = ? ORDER BY t.completed_at DESC LIMIT 10",
            [$sp['id'], $today, $tenantId]
        );

        return view('queue.console', [
            'title' => $sp['name'] . ' — Staff Console',
            'sp' => $sp,
            'currentTicket' => $currentTicket,
            'waitingTickets' => $waitingTickets,
            'otherServicePoints' => $otherServicePoints,
            'completedToday' => $completedToday,
        ]);
    }

    public function callNext(Request $request, int|string $spId): Response
    {
        $tenantId = TenantManager::id();
        $today = today();

        $sp = DB::selectOne('SELECT * FROM service_points WHERE id = ? AND tenant_id = ?', [$spId, $tenantId]);
        if (!$sp) abort(404, 'Service point not found');

        // Check if there's already an active ticket
        $active = DB::selectOne(
            "SELECT id FROM tickets WHERE service_point_id = ? AND status IN ('called','serving') AND issued_on = ? AND tenant_id = ?",
            [$spId, $today, $tenantId]
        );
        if ($active) {
            return back()->with('error', 'Please complete or skip the current ticket before calling the next one.');
        }

        // Find next waiting ticket (highest priority first, then oldest)
        $next = DB::selectOne(
            "SELECT * FROM tickets WHERE queue_id = ? AND status = 'waiting' AND issued_on = ? AND tenant_id = ? ORDER BY priority DESC, id ASC LIMIT 1",
            [$sp['queue_id'], $today, $tenantId]
        );

        if (!$next) {
            return back()->with('info', 'No tickets currently waiting in this queue.');
        }

        $now = now();
        DB::transaction(function () use ($next, $sp, $tenantId, $now) {
            DB::statement(
                "UPDATE tickets SET status = 'called', service_point_id = ?, served_by = ?, called_at = ?, call_count = call_count + 1, updated_at = ? WHERE id = ? AND tenant_id = ?",
                [$sp['id'], Auth::id(), $now, $now, $next['id'], $tenantId]
            );

            DB::statement(
                'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$tenantId, $next['id'], 'called', $sp['id'], Auth::id(), $now]
            );
        });

        return back()->with('success', "Called ticket {$next['code']}");
    }

    public function recall(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $ticket = DB::selectOne('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
        if (!$ticket) abort(404, 'Ticket not found');

        $now = now();
        DB::statement(
            'UPDATE tickets SET call_count = call_count + 1, updated_at = ? WHERE id = ? AND tenant_id = ?',
            [$now, $ticket['id'], $tenantId]
        );

        DB::statement(
            'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $ticket['id'], 'recalled', $ticket['service_point_id'], Auth::id(), $now]
        );

        return back()->with('success', "Recalled ticket {$ticket['code']} (Call # " . ($ticket['call_count'] + 1) . ')');
    }

    public function start(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $ticket = DB::selectOne('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
        if (!$ticket) abort(404, 'Ticket not found');

        $now = now();
        DB::statement(
            "UPDATE tickets SET status = 'serving', started_at = ?, updated_at = ? WHERE id = ? AND tenant_id = ?",
            [$now, $now, $ticket['id'], $tenantId]
        );

        DB::statement(
            'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $ticket['id'], 'started', $ticket['service_point_id'], Auth::id(), $now]
        );

        return back()->with('success', "Service started for ticket {$ticket['code']}");
    }

    public function complete(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $ticket = DB::selectOne('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
        if (!$ticket) abort(404, 'Ticket not found');

        $now = now();
        DB::statement(
            "UPDATE tickets SET status = 'completed', completed_at = ?, updated_at = ? WHERE id = ? AND tenant_id = ?",
            [$now, $now, $ticket['id'], $tenantId]
        );

        DB::statement(
            'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $ticket['id'], 'completed', $ticket['service_point_id'], Auth::id(), $now]
        );

        return back()->with('success', "Ticket {$ticket['code']} completed");
    }

    public function skip(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $ticket = DB::selectOne('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
        if (!$ticket) abort(404, 'Ticket not found');

        $now = now();
        DB::statement(
            "UPDATE tickets SET status = 'skipped', updated_at = ? WHERE id = ? AND tenant_id = ?",
            [$now, $ticket['id'], $tenantId]
        );

        DB::statement(
            'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$tenantId, $ticket['id'], 'skipped', $ticket['service_point_id'], Auth::id(), $now]
        );

        return back()->with('info', "Ticket {$ticket['code']} marked as skipped");
    }

    public function transfer(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $ticket = DB::selectOne('SELECT * FROM tickets WHERE id = ? AND tenant_id = ?', [$id, $tenantId]);
        if (!$ticket) abort(404, 'Ticket not found');

        $targetSpId = (int) $request->input('target_service_point_id');
        $targetSp = DB::selectOne('SELECT * FROM service_points WHERE id = ? AND tenant_id = ?', [$targetSpId, $tenantId]);
        if (!$targetSp) abort(404, 'Target room not found');

        $now = now();
        DB::transaction(function () use ($ticket, $targetSp, $tenantId, $now) {
            // Complete previous phase
            DB::statement(
                "UPDATE tickets SET status = 'completed', completed_at = ?, updated_at = ? WHERE id = ? AND tenant_id = ?",
                [$now, $now, $ticket['id'], $tenantId]
            );

            // Create new ticket transferred into the target queue with priority preserved
            $targetQueue = Queue::find((int) $targetSp['queue_id']);
            $seq = Sequence::ticket($targetQueue);

            DB::statement(
                'INSERT INTO tickets (tenant_id, branch_id, queue_id, patient_id, number, code, customer_name, priority, status, service_point_id, issued_on, transferred_from_id, notes, created_by, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $tenantId, $ticket['branch_id'], $targetQueue->id, $ticket['patient_id'],
                    $seq['number'], $seq['code'], $ticket['customer_name'], $ticket['priority'],
                    'waiting', $targetSp['id'], today(), $ticket['id'],
                    'Transferred from ' . $ticket['code'], Auth::id(), $now, $now,
                ]
            );

            $newTicketId = (int) DB::connection()->lastInsertId();

            DB::statement(
                'INSERT INTO ticket_events (tenant_id, ticket_id, type, service_point_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$tenantId, $newTicketId, 'transferred', $targetSp['id'], Auth::id(), $now]
            );
        });

        return back()->with('success', "Patient transferred to {$targetSp['name']}");
    }

    public function printTicket(Request $request, int|string $id): Response
    {
        $tenantId = TenantManager::id();
        $ticket = DB::selectOne(
            "SELECT t.*, q.name AS queue_name, p.first_name, p.last_name, p.mrn
             FROM tickets t
             JOIN queues q ON q.id = t.queue_id
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE t.id = ? AND t.tenant_id = ?",
            [$id, $tenantId]
        );
        if (!$ticket) abort(404, 'Ticket not found');

        return view('queue.print_ticket', [
            'ticket' => $ticket,
            'tenant' => TenantManager::current(),
        ]);
    }
}
