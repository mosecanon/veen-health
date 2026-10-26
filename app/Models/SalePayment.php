<?php
namespace App\Models;

use Veen\Core\Database\Model;

class SalePayment extends Model
{
    protected static string $table = 'sale_payments';
    protected static array $fillable = ['sale_id', 'method', 'amount_minor', 'reference', 'created_at'];
    protected static bool $timestamps = false;
}

