<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale('en');

        $user = $request->user();

        if ($user?->is_blocked && ! $user->is_admin) {
            return response()->json([
                'message' => 'Your account has been blocked by an administrator.',
            ], 403);
        }

        return $next($request);
    }
}
