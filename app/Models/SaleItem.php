<?php
namespace App\Models;

use Veen\Core\Database\Model;

class SaleItem extends Model
{
    protected static string $table = 'sale_items';
    protected static array $fillable = ['sale_id', 'product_id', 'batch_id', 'name', 'qty', 'unit_price_minor', 'tax_rate', 'tax_minor', 'line_total_minor'];
    protected static bool $timestamps = false;
}

