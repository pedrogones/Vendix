<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectClientFromFilament
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('admin/*')) {
            return $next($request);
        }

        $user = Auth::user();

        if ($user) {
            if (!$user->hasAnyRole()) {
                $user->assignRole('Cliente');
            }

            if ($user->roles->count() === 1 && $user->hasRole('Cliente')) {
                return redirect()->route('initial-page');
            }
        }

        return $next($request);
    }

}
