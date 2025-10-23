<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\MyResponseBuilder as MyRB;

class AuthController extends Controller
{
    /**
     * Return authenticated user based on JWT from Bearer or HttpOnly cookie.
     */
    public function me(Request $request)
    {
        $token = $request->bearerToken() ?: $request->cookie('token');
        if (!$token) {
            return MyRB::asError(401)
                ->withMessage('Silahkan Login dahulu.! #me')
                ->withHttpCode(401)
                ->build();
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user) {
                return MyRB::asError(401)
                    ->withMessage('Sesi tidak valid. Silahkan login kembali.')
                    ->withHttpCode(401)
                    ->build();
            }

            return MyRB::asSuccess(200)
                ->withMessage('Berhasil mendapatkan user yang terautentikasi.')
                ->withData($user)
                ->build();
        } catch (\Exception $e) {
            return MyRB::asError(401)
                ->withMessage('Token tidak valid atau kedaluwarsa.')
                ->withHttpCode(401)
                ->build();
        }
    }

    /**
     * Refresh JWT and set new HttpOnly cookie.
     */
    public function refresh(Request $request)
    {
        $token = $request->bearerToken() ?: $request->cookie('token');
        if (!$token) {
            return MyRB::asError(401)
                ->withMessage('Silahkan Login dahulu.! #refresh')
                ->withHttpCode(401)
                ->build();
        }

        try {
            $newToken = JWTAuth::setToken($token)->refresh();
            $ttl = config('jwt.ttl', 60);

            [$secure, $sameSite] = $this->cookieOptions();

            $successResponse = MyRB::asSuccess(200)
                ->withMessage('Token berhasil di-refresh.')
                ->withData(['token' => $newToken])
                ->build();

            return response($successResponse)->cookie(
                'token',
                $newToken,
                $ttl,
                '/',
                null,
                $secure,
                true, // HttpOnly
                false,
                $sameSite
            );
        } catch (\Exception $e) {
            return MyRB::asError(401)
                ->withMessage('Tidak dapat melakukan refresh token.')
                ->withHttpCode(401)
                ->build();
        }
    }

    /**
     * Logout by invalidating token and clearing cookie.
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken() ?: $request->cookie('token');
        if ($token) {
            try {
                JWTAuth::setToken($token)->invalidate();
            } catch (\Exception $e) {
                // ignore
            }
        }

        $successResponse = MyRB::asSuccess(200)
            ->withMessage('Logout berhasil.')
            ->withData(null)
            ->build();

        // Hapus cookie token
        return response($successResponse)->withoutCookie('token');
    }

    /**
     * Resolve cookie options based on environment.
     * In production: SameSite=None + Secure=true (cross-site cookie)
     * In local/test: SameSite=Lax + Secure=false (untuk dev satu-origin; lintas-origin HTTP tidak didukung)
     */
    private function cookieOptions(): array
    {
        $isProd = app()->environment('production');
        $secure = $isProd ? true : false;
        $sameSite = $isProd ? 'None' : 'Lax';
        return [$secure, $sameSite];
    }
}