<?php

namespace App\Http\Middleware;

use Closure;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $locale = ($request->hasHeader("X-Localization")) ? strtolower($request->header("X-Localization")) : "en";

        app('translator')->setLocale($locale);

        return $next($request);
    }
}
