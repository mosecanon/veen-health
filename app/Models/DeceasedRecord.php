<?php
namespace App\Models;

use Veen\Core\Database\Model;

class DeceasedRecord extends Model
{
    protected static string $table = 'deceased_records';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'admission_id', 'body_tag_number', 'deceased_full_name',
        'death_type', 'date_of_death', 'time_of_death', 'place_of_death', 'gender',
        'estimated_or_actual_age', 'certifying_doctor_id', 'certifying_doctor_name',
        'certifying_doctor_license', 'cause_of_death_immediate', 'cause_of_death_underlying',
        'contributory_cause', 'certificate_of_death_number', 'next_of_kin_name',
        'next_of_kin_phone', 'next_of_kin_relationship', 'cold_room_chamber', 'tray_number',
        'preservation_status', 'body_condition', 'personal_effects', 'autopsy_required',
        'autopsy_status', 'autopsy_date', 'pathologist_name', 'autopsy_findings',
        'status', 'release_date', 'collector_name', 'collector_national_id',
        'collector_phone', 'collector_relationship', 'burial_permit_number',
        'hearse_registration', 'funeral_home_name', 'release_notes',
        'storage_fee_per_day', 'total_charges', 'payment_status',
    ];

    /** Calculate days in mortuary cold room storage */
    public static function calculateStorageDays(string $dateOfDeath, ?string $releaseDate = null): int
    {
        $start = strtotime($dateOfDeath);
        $end = $releaseDate ? strtotime($releaseDate) : time();
        return max(1, (int) ceil(($end - $start) / 86400));
    }
}
