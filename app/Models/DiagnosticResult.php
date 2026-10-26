<?php
namespace App\Models;

use Veen\Core\Database\Model;

class DiagnosticResult extends Model
{
    protected static string $table = 'diagnostic_results';
    protected static array $fillable = [
        'order_id', 'parameter_name', 'observed_value', 'reference_range', 'unit', 'is_abnormal', 'technician_notes',
    ];
}