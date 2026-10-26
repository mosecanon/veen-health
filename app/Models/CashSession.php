<?php
namespace App\Models;

use Veen\Core\Database\Model;

class CashSession extends Model
{
    protected static string $table = 'cash_sessions';
    protected static array $fillable = ['branch_id', 'user_id', 'status', 'opening_float_minor', 'expected_cash_minor', 'counted_cash_minor', 'opened_at', 'closed_at', 'note'];
}

