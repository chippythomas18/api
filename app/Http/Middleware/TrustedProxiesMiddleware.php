<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class TrustedProxiesMiddleware
{

    /**
     *  use 0.0.0.0/0 if you trust any proxy, otherwise replace it with your proxy ips
     * 
     * @var string[]
     */
    protected $trustedProxies = [
        '0.0.0.0/0'
    ];

    public function handle(Request $request, \Closure $next)
    {
        Request::setTrustedProxies($this->trustedProxies,Request::HEADER_X_FORWARDED_TRAEFIK);
        return $next($request);
    }
}
