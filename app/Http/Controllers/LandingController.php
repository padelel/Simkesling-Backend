<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\MyResponseBuilder as MyRB;
use App\MyUtils as MyUtils;

class LandingController extends Controller
{
    //
    public function __construct()
    {
        // $this->r = [
        //     'success' => false,
        //     'code' => 401,
        //     'message' => 'Upps..',
        //     'data' => null
        // ]
    }

    function testApi(Request $request)
    {
        // $this->r;
        // return $resp;
        // return $this->r->resp(200);
        // $token = $request->headers->get('authorization');
        // if ($token == null) {
        //     $token = $request->headers->get('Authorization');
        // }
        // $user = JWTAuth::setToken($token)->getPayload();
        $user = MyUtils::getPayloadToken($request, true) ?? '';
        dd($user);
        return 'a';
    }

    function prosesLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username'
            => 'required',
            'password'
            => 'required',
        ]);

        if ($validator->fails()) {
            return
                MyRB::asError(400)
                ->withMessage('Form Tidak Sesuai.!')
                ->withData($validator->errors()->toArray())
                ->build();
        }

        $form_username = $request->username;
        $form_password = $request->password;

        $token = auth()->guard('webnext')->attempt(['username' => $form_username, 'password' => $form_password]);
        if (!$token) {
            return
                MyRB::asError(401)
                ->withMessage('Login Gagal, Username atau Password Salah.!')
                ->withData(null)
                ->build();
        }

        $user = auth()->guard('webnext')->user();
        return
            MyRB::asSuccess(200)
            ->withMessage('Sukses Login.!')
            ->withData(['user' => $user, 'token' => $token])
            ->build();
    }
}
