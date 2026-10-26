<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Ticket extends Model
{
    protected static string $table = 'tickets';
    protected static array $fillable = ['branch_id', 'queue_id', 'patient_id', 'customer_name', 'priority', 'notes'];
}

