<?php
namespace App\Models;

use Veen\Core\Database\Model;

class StockMovement extends Model
{
    protected static string $table = 'stock_movements';
    protected static array $fillable = ['product_id', 'batch_id', 'type', 'qty', 'reference_type', 'reference_id', 'note', 'user_id', 'created_at'];
    protected static bool $timestamps = false;
}

