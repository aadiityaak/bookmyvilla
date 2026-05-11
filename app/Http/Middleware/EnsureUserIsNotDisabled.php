<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotDisabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isDisabled()) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')->withErrors([
                'email' => __('Your account has been disabled.'),
            ]);
        }

        return $next($request);
    }
}

