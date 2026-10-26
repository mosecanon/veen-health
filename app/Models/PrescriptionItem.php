<?php
namespace App\Models;

use Veen\Core\Database\Model;

class PrescriptionItem extends Model
{
    protected static string $table = 'prescription_items';
    protected static array $fillable = [
        'prescription_id', 'product_id', 'medicine_name', 'dosage', 'frequency',
        'duration_days', 'qty_prescribed', 'qty_dispensed', 'instructions', 'status',
    ];
}
