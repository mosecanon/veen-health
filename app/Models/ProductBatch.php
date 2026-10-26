<?php
namespace App\Models;

use Veen\Core\Database\Model;

class ProductBatch extends Model
{
    protected static string $table = 'product_batches';
    protected static array $fillable = [
        'product_id', 'supplier_id', 'batch_no', 'delivery_note_no', 'expiry_date',
        'cost_minor', 'qty_received', 'qty_on_hand', 'status',
        'received_at', 'received_by', 'approved_by', 'approved_at', 'rejection_reason'
    ];
}

