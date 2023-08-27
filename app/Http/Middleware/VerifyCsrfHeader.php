<?php
/**
 * This middleware verifies the X-MIRATIME-XSRF-PROTECTION header, as part of
 * this project's measure to mitigate XSRF attacks.
 * 
 * See README.md, the "Required Request Headers" section for more information.
 */

namespace App\Http\Middleware;

use App\Exceptions\HttpException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCsrfHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('X-MIRATIME-XSRF-PROTECTION') !== '1') {
            throw new HttpException(403, 'POTENTIAL_XSRF');
        }
        
        return $next($request);
    }
}
