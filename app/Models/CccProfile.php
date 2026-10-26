<?php
namespace App\Models;

use Veen\Core\Database\Model;

class CccProfile extends Model
{
    protected static string $table = 'ccc_profiles';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'ccc_number', 'enrollment_date', 'entry_point',
        'who_stage_baseline', 'current_who_stage', 'baseline_cd4', 'current_cd4',
        'baseline_viral_load', 'current_viral_load', 'viral_load_suppressed',
        'current_regimen', 'line_of_treatment', 'art_start_date', 'tb_screening_status',
        'pmtct_active', 'treatment_buddy_name', 'treatment_buddy_phone', 'status',
    ];
}
