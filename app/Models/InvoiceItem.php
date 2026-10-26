<?php
namespace App\Models;

use Veen\Core\Database\Model;

class InvoiceItem extends Model
{
    protected static string $table = 'invoice_items';
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'invoice_id', 'service_id', 'item_name', 'qty', 'unit_price_minor', 'total_minor',
    ];
}