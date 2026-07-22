<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // عرض صفحة الدخول
    public function showLogin()
    {
        return view('auth.login');

    }

    // معالجة عملية الدخول
    public function login(Request $request)
    {
        // التحقق من المدخلات
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // محاولة تسجيل الدخول
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // التحقق  إذا كان الحساب موقوف قبل تحويله
            if (Auth::user()->is_active == 0) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'عذراً، هذا الحساب موقوف.',
                ]);
            }

            return redirect()->intended('dashboard');
        }

        // في حال فشل كلمة المرور
        return back()->withErrors([
            'email' => 'البيانات المدخلة غير صحيحة.',
        ])->onlyInput('email');
    }

    // تسجيل الخروج
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}