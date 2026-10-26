<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ProductCategory extends Model
{
    protected static string $table = 'product_categories';
    protected static array $fillable = ['name'];
}

