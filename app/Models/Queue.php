<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Queue extends Model
{
    protected static string $table = 'queues';
    protected static array $fillable = ['branch_id', 'name', 'prefix', 'color', 'description', 'sort_order', 'is_active'];
}

