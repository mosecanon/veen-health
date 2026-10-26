<?php
namespace App\Controllers;

use Veen\Core\Database\DB;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class DisplayController extends Controller
{
    public function show(Request $request, string $token): Response
    {
        $display = DB::selectOne('SELECT * FROM displays WHERE token = ?', [$token]);
        if (!$display) {
            abort(404, 'Display screen not found');
        }

        $tenant = TenantManager::find((int) $display['tenant_id']);
        TenantManager::set($tenant);
        $today = today();

        // Currently called or serving tickets
        $serving = DB::select(
            "SELECT t.*, q.name AS queue_name, q.color AS queue_color, q.prefix AS queue_prefix,
                    sp.name AS service_point_name, sp.announce_as,
                    p.first_name, p.last_name
             FROM tickets t
             JOIN queues q ON q.id = t.queue_id
             JOIN service_points sp ON sp.id = t.service_point_id
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE t.tenant_id = ? AND t.issued_on = ? AND t.status IN ('called', 'serving')
             ORDER BY t.called_at DESC LIMIT 8",
            [$tenant['id'], $today]
        );

        // Next waiting tickets summary
        $waiting = DB::select(
            "SELECT t.id, t.code, t.priority, q.name AS queue_name, q.color AS queue_color
             FROM tickets t
             JOIN queues q ON q.id = t.queue_id
             WHERE t.tenant_id = ? AND t.issued_on = ? AND t.status = 'waiting'
             ORDER BY t.priority DESC, t.id ASC LIMIT 10",
            [$tenant['id'], $today]
        );

        // Max event id for initial poll offset
        $maxEvent = DB::selectOne('SELECT MAX(id) AS m FROM ticket_events WHERE tenant_id = ?', [$tenant['id']]);
        $initialEventId = (int) ($maxEvent['m'] ?? 0);

        return view('display.tv', [
            'display' => $display,
            'tenant' => $tenant,
            'serving' => $serving,
            'waiting' => $waiting,
            'initialEventId' => $initialEventId,
        ]);
    }

    public function poll(Request $request, string $token): Response
    {
        $display = DB::selectOne('SELECT * FROM displays WHERE token = ?', [$token]);
        if (!$display) {
            return Response::json(['error' => 'Display not found'], 404);
        }

        $tenant = TenantManager::find((int) $display['tenant_id']);
        TenantManager::set($tenant);
        $today = today();

        $sinceId = (int) $request->query('since_id', 0);

        // Query new call/recall events since last poll
        $events = DB::select(
            "SELECT te.id, te.type, te.created_at,
                    t.code AS ticket_code, t.number AS ticket_number, t.priority,
                    COALESCE(p.first_name, t.customer_name, '') AS customer_name,
                    q.name AS queue_name, q.color AS queue_color,
                    COALESCE(sp.announce_as, sp.name) AS room_name,
                    sp.name AS service_point_name
             FROM ticket_events te
             JOIN tickets t ON t.id = te.ticket_id
             JOIN queues q ON q.id = t.queue_id
             LEFT JOIN service_points sp ON sp.id = te.service_point_id
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE te.tenant_id = ? AND te.id > ? AND te.type IN ('called', 'recalled')
             ORDER BY te.id ASC",
            [$tenant['id'], $sinceId]
        );

        $template = $display['announcement_template'] ?: 'Ticket {code}, please proceed to {room}';
        $announcements = [];
        $maxId = $sinceId;

        foreach ($events as $ev) {
            if ($ev['id'] > $maxId) {
                $maxId = (int) $ev['id'];
            }
            $text = str_replace(
                ['{code}', '{room}', '{patient}', '{department}'],
                [$ev['ticket_code'], $ev['room_name'] ?? 'Counter', $ev['customer_name'] ?? 'Patient', $ev['queue_name']],
                $template
            );
            $announcements[] = [
                'id' => $ev['id'],
                'type' => $ev['type'],
                'ticket_code' => $ev['ticket_code'],
                'room_name' => $ev['room_name'] ?? 'Counter',
                'queue_color' => $ev['queue_color'],
                'speech_text' => $text,
            ];
        }

        // Current serving list for DOM update
        $serving = DB::select(
            "SELECT t.id, t.code, t.priority, t.status, t.called_at,
                    q.name AS queue_name, q.color AS queue_color,
                    sp.name AS service_point_name,
                    COALESCE(p.first_name, t.customer_name) AS customer_name
             FROM tickets t
             JOIN queues q ON q.id = t.queue_id
             JOIN service_points sp ON sp.id = t.service_point_id
             LEFT JOIN patients p ON p.id = t.patient_id
             WHERE t.tenant_id = ? AND t.issued_on = ? AND t.status IN ('called', 'serving')
             ORDER BY t.called_at DESC LIMIT 8",
            [$tenant['id'], $today]
        );

        // Next waiting list
        $waiting = DB::select(
            "SELECT t.id, t.code, t.priority, q.name AS queue_name, q.color AS queue_color
             FROM tickets t
             JOIN queues q ON q.id = t.queue_id
             WHERE t.tenant_id = ? AND t.issued_on = ? AND t.status = 'waiting'
             ORDER BY t.priority DESC, t.id ASC LIMIT 10",
            [$tenant['id'], $today]
        );

        return Response::json([
            'max_event_id' => $maxId,
            'announcements' => $announcements,
            'serving' => $serving,
            'waiting' => $waiting,
            'voice_enabled' => (bool) $display['voice_enabled'],
            'voice_lang' => $display['voice_lang'] ?: 'en-GB',
            'chime' => (bool) $display['chime'],
            'repeat_count' => (int) ($display['repeat_count'] ?: 1),
            'time' => date('H:i:s'),
        ]);
    }
}
