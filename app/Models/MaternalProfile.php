<?php
namespace App\Models;

use Veen\Core\Database\Model;

class MaternalProfile extends Model
{
    protected static string $table = 'maternal_profiles';
    protected static array $fillable = [
        'branch_id', 'patient_id', 'anc_number', 'gravida', 'parity', 'living_children',
        'miscarriages', 'stillbirths', 'c_sections', 'lnmp', 'edd', 'blood_group',
        'rhesus_factor', 'hb_baseline', 'hiv_status', 'on_pmtct_art', 'syphilis_vdrl',
        'hep_b_surface_ag', 'blood_sugar_fasting', 'urinalysis_baseline', 'status',
        'delivery_date', 'birth_plan_hospital', 'birth_plan_transport', 'birth_plan_donor',
        'birth_plan_contact_phone', 'high_risk_pregnancy', 'risk_factors',
    ];

    /** Calculate Gestational Age in weeks from LNMP to a given date (defaults to today) */
    public static function calculateGestationalAge(string $lnmpDate, ?string $targetDate = null): array
    {
        $lnmpTime = strtotime($lnmpDate);
        $targetTime = $targetDate ? strtotime($targetDate) : time();
        $diffDays = max(0, (int) floor(($targetTime - $lnmpTime) / 86400));
        $weeks = floor($diffDays / 7);
        $days = $diffDays % 7;
        
        $trimester = 'first';
        if ($weeks >= 28) {
            $trimester = 'third';
        } elseif ($weeks >= 14) {
            $trimester = 'second';
        }

        return [
            'total_days' => $diffDays,
            'weeks'      => (int)$weeks,
            'days'       => (int)$days,
            'formatted'  => "{$weeks}w {$days}d",
            'trimester'  => $trimester,
        ];
    }

    /** Calculate Estimated Date of Delivery (EDD) using Naegele's rule: LNMP + 280 days */
    public static function calculateEdd(string $lnmpDate): string
    {
        return date('Y-m-d', strtotime($lnmpDate . ' + 280 days'));
    }
}
