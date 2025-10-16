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
        // =================== TAMBAHAN PENTING ===================
        // Jika ini adalah preflight request (OPTIONS), langsung lanjutkan
        // tanpa perlu cek token.
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }
        // ========================================================

        $token = $request->bearerToken(); // Cara yang lebih sederhana untuk mendapatkan token

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
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return MyRB::asError(401)
                ->withMessage('Sesi telah berakhir. Silahkan login kembali.')
                ->withHttpCode(401)
                ->build();
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return MyRB::asError(401)
                ->withMessage('Token tidak valid. Silahkan login kembali.')
                ->withHttpCode(401)
                ->build();
        }
        
        return $next($request);
    }
}
