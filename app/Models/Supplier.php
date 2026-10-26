<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Supplier extends Model
{
    protected static string $table = 'suppliers';
    protected static array $fillable = ['name', 'contact_person', 'phone', 'email', 'address'];
}

