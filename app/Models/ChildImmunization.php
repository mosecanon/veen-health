<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ChildImmunization extends Model
{
    protected static string $table = 'child_immunizations';
    protected static array $fillable = [
        'patient_id', 'child_profile_id', 'vaccine_code', 'vaccine_name',
        'target_age_description', 'status', 'dose_number', 'administered_date',
        'batch_number', 'administered_by', 'adverse_reaction', 'next_due_date', 'notes',
    ];

    /** Standard National / WHO Child Immunization Schedule */
    public const SCHEDULE = [
        ['code' => 'BCG', 'name' => 'BCG (Tuberculosis)', 'age' => 'At Birth', 'dose' => 1],
        ['code' => 'OPV_0', 'name' => 'Oral Polio Vaccine 0 (OPV 0)', 'age' => 'At Birth', 'dose' => 1],
        ['code' => 'OPV_1', 'name' => 'Oral Polio Vaccine 1 (OPV 1)', 'age' => '6 Weeks', 'dose' => 1],
        ['code' => 'PENTA_1', 'name' => 'Pentavalent 1 (DTP-HepB-Hib)', 'age' => '6 Weeks', 'dose' => 1],
        ['code' => 'PCV_1', 'name' => 'Pneumococcal Conjugate 1 (PCV 10)', 'age' => '6 Weeks', 'dose' => 1],
        ['code' => 'ROTA_1', 'name' => 'Rotavirus Vaccine 1 (Rota 1)', 'age' => '6 Weeks', 'dose' => 1],
        ['code' => 'OPV_2', 'name' => 'Oral Polio Vaccine 2 (OPV 2)', 'age' => '10 Weeks', 'dose' => 2],
        ['code' => 'PENTA_2', 'name' => 'Pentavalent 2 (DTP-HepB-Hib)', 'age' => '10 Weeks', 'dose' => 2],
        ['code' => 'PCV_2', 'name' => 'Pneumococcal Conjugate 2 (PCV 10)', 'age' => '10 Weeks', 'dose' => 2],
        ['code' => 'ROTA_2', 'name' => 'Rotavirus Vaccine 2 (Rota 2)', 'age' => '10 Weeks', 'dose' => 2],
        ['code' => 'OPV_3', 'name' => 'Oral Polio Vaccine 3 (OPV 3)', 'age' => '14 Weeks', 'dose' => 3],
        ['code' => 'IPV', 'name' => 'Inactivated Polio Vaccine (IPV)', 'age' => '14 Weeks', 'dose' => 1],
        ['code' => 'PENTA_3', 'name' => 'Pentavalent 3 (DTP-HepB-Hib)', 'age' => '14 Weeks', 'dose' => 3],
        ['code' => 'PCV_3', 'name' => 'Pneumococcal Conjugate 3 (PCV 10)', 'age' => '14 Weeks', 'dose' => 3],
        ['code' => 'VIT_A_1', 'name' => 'Vitamin A Supplementation (100,000 IU)', 'age' => '6 Months', 'dose' => 1],
        ['code' => 'MR_1', 'name' => 'Measles-Rubella Vaccine 1 (MR 1)', 'age' => '9 Months', 'dose' => 1],
        ['code' => 'YELLOW_FEVER', 'name' => 'Yellow Fever Vaccine', 'age' => '9 Months', 'dose' => 1],
        ['code' => 'VIT_A_2', 'name' => 'Vitamin A Supplementation (200,000 IU)', 'age' => '12 Months', 'dose' => 2],
        ['code' => 'DEWORMING_1', 'name' => 'Deworming (Albendazole / Mebendazole)', 'age' => '12 Months', 'dose' => 1],
        ['code' => 'MR_2', 'name' => 'Measles-Rubella Vaccine 2 (MR 2)', 'age' => '18 Months', 'dose' => 2],
    ];
}
