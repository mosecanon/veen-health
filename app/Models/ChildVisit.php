<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ChildVisit extends Model
{
    protected static string $table = 'child_visits';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'child_profile_id', 'doctor_user_id', 'visit_date',
        'age_months', 'weight_kg', 'height_cm', 'head_circumference_cm', 'muac_cm',
        'temperature_c', 'pulse_rate', 'respiratory_rate', 'spo2_percent',
        'nutritional_status', 'developmental_milestones', 'diet_and_feeding_notes',
        'clinical_notes', 'diagnoses', 'treatment_given', 'next_visit_date',
    ];
}
