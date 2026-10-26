<?php
namespace App\Models;

use Veen\Core\Database\Model;

class TicketEvent extends Model
{
    protected static string $table = 'ticket_events';
    protected static array $fillable = ['ticket_id', 'type', 'service_point_id', 'user_id', 'created_at'];
    protected static bool $timestamps = false;
}

