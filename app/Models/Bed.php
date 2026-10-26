<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Bed extends Model
{
    protected static string $table = 'beds';
    protected static array $fillable = [
        'ward_id', 'bed_number', 'daily_rate_minor', 'status',
    ];
}