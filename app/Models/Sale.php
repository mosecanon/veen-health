<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Sale extends Model
{
    protected static string $table = 'sales';
    protected static array $fillable = ['branch_id', 'cash_session_id', 'receipt_no', 'patient_id', 'customer_name', 'customer_phone', 'prescription_ref', 'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'paid_minor', 'change_minor', 'status', 'void_reason', 'user_id'];
}

