<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ResponseConst as ControllersResponseConst;
use Illuminate\Http\Request;

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
        $this->r = new ControllersResponseConst();
    }

    function testApi(Request $request)
    {
        // $this->r;
        // return $resp;
        return $this->r->resp(200);
    }
}
