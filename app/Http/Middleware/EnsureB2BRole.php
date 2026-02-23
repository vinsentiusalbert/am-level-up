<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureB2BRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('home')->withErrors([
                'email' => 'Silakan login terlebih dahulu.',
            ]);
        }

        if (Auth::user()->role !== 'b2b') {
            Auth::logout();

            return redirect()->route('home')->withErrors([
                'email' => 'Akses hanya untuk user dengan role b2b.',
            ]);
        }

        return $next($request);
    }
}

