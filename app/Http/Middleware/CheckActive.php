<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckActive
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->is_active == 0) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'تم تجميد هذا الحساب. يرجى مراجعة الإدارة.']);
        }

        return $next($request);
    }
}