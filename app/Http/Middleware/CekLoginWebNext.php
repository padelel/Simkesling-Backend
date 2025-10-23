<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\MyResponseBuilder as MyRB;

class CekLoginWebNext
{
    public function handle(Request $request, Closure $next): Response
    {
        // PENTING: Izinkan preflight request (OPTIONS) untuk lewat tanpa cek token.
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // Ambil token dari Authorization Bearer atau fallback ke cookie 'token'
        $token = $request->bearerToken();
        if (!$token) {
            $token = $request->cookie('token');
        }

        if (!$token) {
            return MyRB::asError(401)
                ->withMessage('Silahkan Login dahulu.! #1')
                ->withHttpCode(401)
                ->build();
        }

        try {
            if (!JWTAuth::setToken($token)->check()) {
                return MyRB::asError(401)
                    ->withMessage('Sesi tidak valid. Silahkan login kembali. #2')
                    ->withHttpCode(401)
                    ->build();
            }
        } catch (\Exception $e) {
            return MyRB::asError(401)
                ->withMessage('Token tidak valid atau kedaluwarsa.')
                ->withHttpCode(401)
                ->build();
        }
        
        return $next($request);
    }
}