<?php
namespace App\Models;

use Veen\Core\Database\Model;

class InsuranceClaim extends Model
{
    protected static string $table = 'insurance_claims';
    protected static array $fillable = [
        'claim_no', 'provider_id', 'patient_id', 'invoice_id',
        'policy_number', 'member_name', 'preauth_code', 'claim_amount_minor',
        'approved_amount_minor', 'status', 'rejection_reason', 'submitted_at', 'settled_at',
    ];
}
