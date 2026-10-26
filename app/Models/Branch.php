<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Branch extends Model
{
    protected static string $table = 'branches';
    protected static array $fillable = ['uuid', 'name', 'code', 'address', 'phone', 'is_main', 'is_active'];
}

