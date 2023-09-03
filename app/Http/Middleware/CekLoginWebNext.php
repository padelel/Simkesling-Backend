<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
// use Tymon\JWTAuth\JWTAuth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\MyResponseBuilder as MyRB;

class CekLoginWebNext
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // if (!Auth::guard('karyawan')->check()) return redirect()->route('karyawan.form-login')->withErrors('Silahkan Login Dahulu... #1');
        // if (Auth::guard('karyawan')->user()->status_user != 'aktif') return redirect()->route('karyawan.form-login')->withErrors('Silahkan Login Dahulu... #2');
        // dd($request->headers);
        // $resp = [
        //     'success' => false,
        //     'code' => 401,
        //     'message' => 'Silahkan Login dahulu.! #1',
        //     'data' => null
        // ];
        $token = $request->headers->get('authorization');
        if ($token == null) {
            $token = $request->headers->get('Authorization');
        }
        if ($token == null) {
            // return response()->json($resp, 401);
            return
                MyRB::asError(401)
                ->withMessage('Silahkan Login dahulu.! #1')
                ->withData(null)
                ->withHttpCode(401)
                ->build();
        }
        $cek = JWTAuth::setToken($token)->check();
        if (!$cek) {
            return
                MyRB::asError(401)
                ->withMessage('Silahkan Login dahulu.! #2')
                ->withData(null)
                ->withHttpCode(401)
                ->build();
        }
        $user = JWTAuth::setToken($token)->getPayload();
        if (!$user) {
            return
                MyRB::asError(401)
                ->withMessage('Silahkan Login dahulu.! #3')
                ->withData(null)
                ->withHttpCode(401)
                ->build();
        }
        // $user = JWTAuth::setToken($token);
        // $user = JWTAuth::parseToken($token)->check();
        // $user = JWTAuth::getPayload($token);
        // $user = JWTAuth::getToken();
        // $user = JWTAuth::parseToken($token);
        // $cek = Auth::guard('mobileSambat')->user();
        // dd($user);
        return $next($request);
    }
}
