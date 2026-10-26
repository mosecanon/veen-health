<?php
namespace App\Controllers;

use Veen\Core\Auth;
use Veen\Core\Request;
use Veen\Core\Response;
use Veen\Core\Tenancy\TenantManager;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return view('auth.login', [
            'title' => 'Sign In — Veen Health',
        ]);
    }

    public function login(Request $request): Response
    {
        $validated = $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($validated['email'], $validated['password'], $request->ip())) {
            return back()->withErrors(['email' => ['Invalid email or password.']])->withInput($request->all());
        }

        $user = Auth::user();
        if ($user->role === 'super_admin') {
            return redirect(route('platform.dashboard'))->with('success', 'Welcome to Veen Africa Platform Admin');
        }
        if ($user->role === 'doctor') {
            return redirect(route('consultations.index'))->with('success', 'Welcome, Dr. ' . $user->name . '. Doctor Consultation Desk active.');
        }
        if ($user->role === 'pharmacist') {
            return redirect(route('pharmacy.pos'))->with('success', 'Pharmacy POS ready. Welcome, ' . $user->name);
        }
        if ($user->role === 'lab_tech') {
            return redirect(route('lab.index'))->with('success', 'LIMS Laboratory ready. Welcome, ' . $user->name);
        }
        if ($user->role === 'cashier') {
            return redirect(route('billing.index'))->with('success', 'Cashier & Invoicing ready. Welcome, ' . $user->name);
        }

        return redirect(route('dashboard'))->with('success', 'Welcome back, ' . $user->name);
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        return redirect(route('login'))->with('info', 'You have been signed out.');
    }
}
