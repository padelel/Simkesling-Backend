<?php

namespace App;

use Exception;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class MyUtils
{
    static function getPayloadToken(Request $request, $parse = false)
    {
        try {
            $token = $request->headers->get('authorization');
            if ($token == null) {
                $token = $request->headers->get('Authorization');
            }
            if ($token == null) {
                return null;
            }
            
            // Remove 'Bearer ' prefix if present
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
            
            $user = JWTAuth::setToken($token)->getPayload();
            if ($parse) {
                return (object) $user->get();
            }
            return $user;
        } catch (Exception $ex) {
            return null;
        }
    }
}
