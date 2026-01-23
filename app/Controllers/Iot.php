<?php

namespace App\Controllers;

class Iot extends BaseController
{

    public function general()
    {
        $jwt = $this->request->getVar('jwt');
        $decode = decode_jwt($jwt);

        $q = db('iot', $decode['db'])->where('nama', $decode['nama'])->get()->getRowArray();

        if (!$q) {
            gagal("Iot not found");
        }
        $new_status = $q['status'];
        if ($q['status'] == 1 && $q['end'] > 0) {
            $new_status = ($q['end'] < time() ? 0 : 1);
        }
    }
}
