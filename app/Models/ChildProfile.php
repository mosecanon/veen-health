<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ChildProfile extends Model
{
    protected static string $table = 'child_profiles';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'birth_weight_kg', 'birth_length_cm', 'birth_head_circ_cm',
        'gestational_age_weeks', 'delivery_mode', 'apgar_score_1min', 'apgar_score_5min',
        'place_of_birth', 'birth_complications', 'feeding_method', 'blood_group',
        'special_needs_or_allergies',
    ];
}
