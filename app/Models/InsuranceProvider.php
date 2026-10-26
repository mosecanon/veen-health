<?php
namespace App\Models;

use Veen\Core\Database\Model;

class InsuranceProvider extends Model
{
    protected static string $table = 'insurance_providers';
    protected static array $fillable = [
        'name', 'code', 'phone', 'email', 'coverage_rules', 'is_active',
    ];
}