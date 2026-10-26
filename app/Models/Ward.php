<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Ward extends Model
{
    protected static string $table = 'wards';
    protected static array $fillable = [
        'branch_id', 'name', 'code', 'type', 'floor_number', 'is_active',
    ];
}