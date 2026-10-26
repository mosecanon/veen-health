<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Prescription extends Model
{
    protected static string $table = 'prescriptions';
    protected static array $fillable = [
        'prescription_no', 'patient_id', 'encounter_id', 'doctor_user_id',
        'notes', 'status',
    ];
}
