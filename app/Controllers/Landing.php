<?php

namespace App\Controllers;

class Landing extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        if ($decode['order'] == "Show") {
            sukses('Ok', profile($decode));
        }
    }
}
