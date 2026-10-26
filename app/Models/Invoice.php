<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Invoice extends Model
{
    protected static string $table = 'invoices';
    protected static array $fillable = [
        'patient_id', 'encounter_id', 'invoice_no',
        'subtotal_minor', 'discount_minor', 'tax_minor', 'total_minor', 'paid_minor',
        'status', 'due_date',
    ];
}