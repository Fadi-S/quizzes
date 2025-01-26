<?php

namespace App\Http\Middleware;

use App\Enums\ApiKeyRole;
use App\Models\ApiKey;
use App\Models\Game;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKeyIsValidForAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = ApiKey::current();
        if ($apiKey === null || $apiKey->role !== ApiKeyRole::Admin) {
            abort(401, "Unauthorized");
        }

        return $next($request);
    }
}
