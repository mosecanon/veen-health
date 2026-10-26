<?php
namespace App\Models;

use Veen\Core\Database\Model;

class BillableService extends Model
{
    protected static string $table = 'billable_services';
    protected static array $fillable = [
        'category', 'code', 'name', 'price_minor', 'is_active',
    ];
}