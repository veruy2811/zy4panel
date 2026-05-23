<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken() ?: $request->header('X-API-TOKEN');
        abort_if(blank($plain), 401, 'API token required.');

        $token = ApiToken::with('user')->where('token', hash('sha256', $plain))->first();
        abort_if(! $token || ! $token->user || ! $token->user->is_active, 401, 'Invalid API token.');

        $token->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
