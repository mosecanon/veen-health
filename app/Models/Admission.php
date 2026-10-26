<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Admission extends Model
{
    protected static string $table = 'admissions';
    protected static array $fillable = [
        'admission_no', 'patient_id', 'bed_id', 'admitting_doctor_id',
        'admission_date', 'discharge_date', 'admission_reason', 'discharge_summary', 'status',
    ];
}