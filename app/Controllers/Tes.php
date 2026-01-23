<?php

namespace App\Controllers;

class Tes extends BaseController
{

    public function tes()
    {
        sukses(getenv("KEY_JWT"));
    }
}
