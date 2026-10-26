<?php
namespace App\Models;

use Veen\Core\Database\Model;

class Display extends Model
{
    protected static string $table = 'displays';
    protected static array $fillable = ['branch_id', 'name', 'queue_ids', 'voice_enabled', 'voice_lang', 'announcement_template', 'chime', 'repeat_count', 'ticker_text'];
}

