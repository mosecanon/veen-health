<?php
namespace App\Models;

use Veen\Core\Database\Model;

class MaternalVisit extends Model
{
    protected static string $table = 'maternal_visits';
    protected static array $fillable = [
        'branch_id', 'maternal_profile_id', 'patient_id', 'doctor_user_id', 'visit_date',
        'visit_number', 'trimester', 'gestational_age_weeks', 'weight_kg', 'systolic_bp',
        'diastolic_bp', 'pulse_rate', 'temperature_c', 'fundal_height_cm', 'fetal_presentation',
        'fetal_lie', 'fetal_heart_rate_bpm', 'fetal_movement', 'oedema', 'urine_protein',
        'urine_glucose', 'ultrasound_findings', 'ifas_tablets_dispensed', 'iptp_sp_dose',
        'tetanus_toxoid_dose', 'deworming_given', 'mosquito_net_given', 'danger_signs_screened',
        'clinical_notes', 'next_visit_date',
    ];
}
