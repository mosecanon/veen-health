<?php
namespace App\Controllers;

use App\Models\User;
use Veen\Core\Auth;
use Veen\Core\Cache;
use Veen\Core\Database\DB;
use Veen\Core\HttpException;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = TenantManager::id();
        $tenant = TenantManager::find($tenantId);
        $user = DB::selectOne("SELECT * FROM users WHERE id = ?", [Auth::id()]);
        if (!$user) abort(401);

        $isAdmin = user_can('hospital_admin') || (auth() && auth()->role === 'hospital_admin') || (auth() && auth()->role === 'super_admin');

        $countries = DB::select("SELECT * FROM countries ORDER BY name ASC");

        return view('settings.index', [
            'title' => 'Settings & Profile Management',
            'tenant' => $tenant,
            'user' => $user,
            'isAdmin' => $isAdmin,
            'countries' => $countries,
        ]);
    }

    public function updateCompany(Request $request): Response
    {
        $tenantId = TenantManager::id();
        if (!user_can('hospital_admin') && (auth()->role !== 'hospital_admin' && auth()->role !== 'super_admin')) {
            abort(403, 'Only Hospital Administrators can update company branding and facility settings.');
        }

        $data = $request->validate([
            'name' => 'required|min:2|max:150',
            'phone' => 'nullable|max:30',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|max:255',
            'tax_pin' => 'nullable|max:50',
            'primary_color' => 'nullable|max:9',
            'currency' => 'nullable|max:3',
            'timezone' => 'nullable|max:64',
        ]);

        $name = trim($data['name']);
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;
        $email = !empty($data['email']) ? trim($data['email']) : null;
        $address = !empty($data['address']) ? trim($data['address']) : null;
        $taxPin = !empty($data['tax_pin']) ? trim($data['tax_pin']) : null;
        $primaryColor = !empty($data['primary_color']) ? trim($data['primary_color']) : '#14b8a6';
        $currency = !empty($data['currency']) ? strtoupper(trim($data['currency'])) : 'USD';
        $timezone = !empty($data['timezone']) ? trim($data['timezone']) : 'UTC';

        // Handle Logo Upload
        $logoPath = null;
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo'];
            $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

            if (!in_array($file['type'], $allowedTypes) && !in_array($ext, $allowedExts)) {
                return back()->with('error', 'Invalid logo file format. Please upload a PNG, JPG, WebP, or SVG image.');
            }

            if ($file['size'] > 4 * 1024 * 1024) {
                return back()->with('error', 'Logo file size exceeds maximum limit of 4MB.');
            }

            $uploadDir = BASE_PATH . '/public/assets/uploads/logos';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $filename = 'logo_' . $tenantId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . '/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $logoPath = 'uploads/logos/' . $filename;
            }
        }

        if ($logoPath) {
            DB::statement(
                "UPDATE tenants SET name = ?, phone = ?, email = ?, address = ?, tax_pin = ?, primary_color = ?, currency = ?, timezone = ?, logo = ?, updated_at = NOW() WHERE id = ?",
                [$name, $phone, $email, $address, $taxPin, $primaryColor, $currency, $timezone, $logoPath, $tenantId]
            );
        } else {
            DB::statement(
                "UPDATE tenants SET name = ?, phone = ?, email = ?, address = ?, tax_pin = ?, primary_color = ?, currency = ?, timezone = ?, updated_at = NOW() WHERE id = ?",
                [$name, $phone, $email, $address, $taxPin, $primaryColor, $currency, $timezone, $tenantId]
            );
        }

        // Refresh active tenant context
        TenantManager::set(TenantManager::find($tenantId));
        if (class_exists(Cache::class)) {
            Cache::forget("tenant_{$tenantId}");
        }

        audit('tenant.settings_updated', 'Tenant', $tenantId, ['name' => $name, 'currency' => $currency]);

        return redirect(route('settings.index'))->with('success', 'Hospital branding and facility settings updated successfully.');
    }

    public function updateProfile(Request $request): Response
    {
        $userId = Auth::id();
        $user = DB::selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) abort(401);

        $data = $request->validate([
            'name' => 'required|min:2|max:150',
            'phone' => 'nullable|max:30',
            'title' => 'nullable|max:60',
        ]);

        $name = trim($data['name']);
        $phone = !empty($data['phone']) ? trim($data['phone']) : null;
        $title = !empty($data['title']) ? trim($data['title']) : null;

        // Note: Email is strictly locked and cannot be edited by the user
        DB::statement(
            "UPDATE users SET name = ?, phone = ?, title = ?, updated_at = NOW() WHERE id = ?",
            [$name, $phone, $title, $userId]
        );

        audit('user.profile_updated', 'User', $userId, ['name' => $name]);

        return redirect(route('settings.index'))->with('success', 'Your personal profile details have been saved.');
    }

    public function updatePassword(Request $request): Response
    {
        $userId = Auth::id();
        $user = DB::selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) abort(401);

        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('password');
        $confirmation = (string) $request->input('password_confirmation');

        if (!password_verify($currentPassword, $user['password'])) {
            return back()->with('error', 'The current password you provided does not match our records.');
        }

        if (strlen($newPassword) < 8) {
            return back()->with('error', 'New password must be at least 8 characters in length.');
        }

        if ($newPassword !== $confirmation) {
            return back()->with('error', 'New password and confirmation password do not match.');
        }

        DB::statement(
            "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $userId]
        );

        audit('user.password_changed', 'User', $userId, []);

        return redirect(route('settings.index'))->with('success', 'Your password has been changed successfully.');
    }

    public function flushCache(Request $request): Response
    {
        $tenantId = TenantManager::id();
        if (!user_can('hospital_admin') && (auth()->role !== 'hospital_admin' && auth()->role !== 'super_admin')) {
            abort(403, 'Only Hospital Administrators can flush the system cache.');
        }

        Cache::flush();
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }

        audit('system.cache_flushed', 'Tenant', $tenantId, [
            'flushed_by' => Auth::id(),
            'driver' => Cache::driver()
        ]);

        if ($request->wantsJson()) {
            return Response::json([
                'success' => true,
                'message' => 'System cache successfully flushed and reset.',
                'driver' => Cache::driver(),
            ]);
        }

        return redirect(route('settings.index'))->with('success', 'System cache successfully flushed and reset (' . strtoupper(Cache::driver()) . ' cache cleared).');
    }
}
