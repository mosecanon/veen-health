<?php
namespace App\Models;

use Veen\Core\Database\Model;

class CccVisit extends Model
{
    protected static string $table = 'ccc_visits';
    protected static array $fillable = [
        'branch_id', 'ccc_profile_id', 'patient_id', 'doctor_user_id', 'visit_date',
        'weight_kg', 'height_cm', 'bmi', 'cd4_count', 'viral_load_copies_ml',
        'who_stage', 'adherence_pill_count_percent', 'regimen_dispensed',
        'months_dispensed', 'tb_screened_this_visit', 'next_appointment_date',
        'clinical_notes',
    ];
}
