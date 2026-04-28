<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads user's active_mode from session (client|freelancer)
 * and makes it available to all views via a shared variable.
 * Also enforces route guards — client routes block freelancer mode and vice versa.
 */
class DetectUserMode
{
    public function handle(Request $request, Closure $next, string $required = null): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Determine active mode — session wins, fallback to DB, fallback to 'client'
        $mode = session('active_mode')
            ?? $user->active_mode
            ?? 'client';

        // Persist to session
        session(['active_mode' => $mode]);

        // Share with all views
        view()->share('userMode', $mode);
        view()->share('isClient',     $mode === 'client');
        view()->share('isFreelancer', $mode === 'freelancer');

        // Optional: enforce route-level guard
        if ($required && $mode !== $required) {
            // Redirect to correct dashboard instead of 403
            return match ($mode) {
                'freelancer' => redirect()->route('freelancer.dashboard'),
                default      => redirect()->route('client.dashboard'),
            };
        }

        return $next($request);
    }
}
