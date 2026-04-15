<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ClearBookingSession
{
    public function handle(Request $request, Closure $next)
    {
        // Clear booking session when navigating away from booking page
        if (!$request->is('booking*') && session()->has('booking_search')) {
            session()->forget('booking_search');
        }

        return $next($request);
    }
}
