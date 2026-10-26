<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ServicePoint extends Model
{
    protected static string $table = 'service_points';
    protected static array $fillable = ['branch_id', 'queue_id', 'name', 'announce_as', 'staff_user_id', 'is_active'];
}

