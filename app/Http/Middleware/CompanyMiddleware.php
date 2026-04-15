<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CompanyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!in_array($user->User_Type, ['CompanyOwner', 'CompanyUser'])) {
            abort(403, 'Access denied.');
        }

        if (!$user->Company_Id) {
            abort(403, 'No company linked to your account.');
        }

        return $next($request);
    }
}
