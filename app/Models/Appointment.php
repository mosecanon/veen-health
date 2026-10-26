<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Appointment extends Model
{
    protected static string $table = 'appointments';
    protected static array $fillable = [
        'patient_id', 'doctor_user_id', 'appointment_date', 'start_time',
        'end_time', 'type', 'title', 'notes', 'status', 'reminder_sent',
    ];
}
