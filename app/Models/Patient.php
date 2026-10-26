<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Patient extends Model
{
    protected static string $table = 'patients';
    protected static bool $softDeletes = true;
    protected static array $fillable = [
        'uuid', 'mrn', 'branch_id', 'parent_patient_id', 'is_child', 'relationship_to_parent',
        'first_name', 'last_name', 'other_names', 'sex', 'dob', 'phone', 'email',
        'national_id', 'insurance_provider', 'insurance_number', 'address', 'blood_group',
        'allergies', 'next_of_kin_name', 'next_of_kin_phone',
    ];

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . ($this->other_names ? $this->other_names . ' ' : '') . $this->last_name);
    }

    public function getAgeAttribute(): ?int
    {
        return age_from_dob($this->dob);
    }

    public function save(): bool
    {
        $res = parent::save();
        if ($res) {
            $tId = $this->tenant_id ?: \Veen\Core\Tenancy\TenantManager::id();
            if ($tId) {
                \Veen\Core\Cache::set("maternal_anc_ver_{$tId}", ((int)\Veen\Core\Cache::get("maternal_anc_ver_{$tId}", 1)) + 1, 86400);
                \Veen\Core\Cache::set("kids_cwc_ver_{$tId}", ((int)\Veen\Core\Cache::get("kids_cwc_ver_{$tId}", 1)) + 1, 86400);
            }
        }
        return $res;
    }
}
