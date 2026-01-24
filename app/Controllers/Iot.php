<?php

namespace App\Controllers;

class Iot extends BaseController
{

    public function general()
    {
        $json = $this->request->getJSON(true); // true = array
        if (!$json || !isset($json['jwt'])) {
            gagal("JWT tidak ditemukan di request body");
        }

        $jwt = $json['jwt'];
        $decode = decode_jwt($jwt);

        if (!isset($decode['db']) || !isset($decode['nama'])) {
            gagal("Payload JWT tidak valid");
        }



        $q = db('iot', $decode['db'])->where('nama', $decode['nama'])->get()->getRowArray();

        if (!$q) {
            gagal("Iot not found");
        }

        if ($q['status'] == 1 && $q['end'] > 0 && $q['end'] < time()) {
            $q['status'] = 0;
            $q['end'] = 0;
            $q['transaksi_id'] = 0;

            if (!db('iot', $decode['db'])->where('id', $q['id'])->update($q)) {
                gagal("Update status gagal");
            }
        }

        sukses("Sukses", $q['status']);
    }
}
