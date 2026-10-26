<?php
namespace App\Models;

use Veen\Core\Database\Model;

class DiagnosticOrder extends Model
{
    protected static string $table = 'diagnostic_orders';
    protected static array $fillable = [
        'encounter_id', 'patient_id', 'doctor_user_id', 'test_id', 'order_no',
        'priority', 'clinical_indication', 'status',
        'sample_collected_at', 'sample_collected_by', 'verified_at', 'verified_by', 'released_at',
    ];
}