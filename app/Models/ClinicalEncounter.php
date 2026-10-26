<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ClinicalEncounter extends Model
{
    protected static string $table = 'clinical_encounters';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'ticket_id', 'doctor_user_id', 'triage_nurse_id',
        'encounter_type', 'status', 'triage_category',
        'presenting_complaints', 'history_of_illness', 'past_medical_history', 'physical_examination',
        'systolic_bp', 'diastolic_bp', 'pulse_rate', 'temperature_c', 'respiratory_rate', 'spo2_percent',
        'weight_kg', 'height_cm', 'bmi',
        'working_diagnosis', 'confirmed_diagnosis', 'icd10_code',
        'treatment_plan', 'doctor_notes', 'follow_up_date',
    ];
}