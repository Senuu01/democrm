<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SimpleAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $authenticated = Session::get('authenticated');
        $userEmail = Session::get('user_email');
        
        \Log::info('SimpleAuth Middleware Check', [
            'authenticated' => $authenticated,
            'user_email' => $userEmail,
            'session_data' => Session::all()
        ]);
        
        if (!$authenticated) {
            \Log::info('User not authenticated, redirecting to login');
            return redirect()->route('login')->with('error', 'Please login to access this page.');
        }

        return $next($request);
    }
}