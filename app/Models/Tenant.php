<?php
namespace App\Models;

use Veen\Core\Database\Model;

/** Central model (not tenant-scoped). Only used by the platform console & middleware. */
class Tenant extends Model
{
    protected static string $table = 'tenants';
    protected static bool $tenantScoped = false;
    protected static array $fillable = [
        'name', 'slug', 'domain', 'country_code', 'currency', 'timezone', 'locale', 'plan_id',
        'status', 'trial_ends_at', 'email', 'phone', 'address', 'tax_pin', 'primary_color', 'logo',
    ];
}
