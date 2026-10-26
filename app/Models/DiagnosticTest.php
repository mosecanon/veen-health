<?php
namespace App\Models;

use Veen\Core\Database\Model;

class DiagnosticTest extends Model
{
    protected static string $table = 'diagnostic_tests';
    protected static array $fillable = [
        'code', 'name', 'category', 'specimen_type', 'normal_range', 'units', 'price_minor', 'turnaround_minutes', 'is_active',
    ];
}