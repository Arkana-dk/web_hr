<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Memeriksa apakah pengguna terautentikasi dan apakah perannya sesuai
        if (auth()->check() && (auth()->user()->hasRole($role) || auth()->user()->role === $role)) {
        return $next($request);

        }

        // Jika tidak sesuai, kembalikan ke halaman sebelumnya
        return back();
    }
}

