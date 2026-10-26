<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Product extends Model
{
    protected static string $table = 'products';
    protected static array $fillable = [
        'category_id', 'name', 'generic_name', 'barcode', 'sku', 'form', 'strength',
        'unit', 'schedule', 'price_minor', 'wholesale_price_minor', 'tax_rate', 'reorder_level', 'is_active',
    ];
}

