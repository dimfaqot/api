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


        $db = \Config\Database::connect();
        $db->transStart();

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

            $transaksi = db('transaksi', $decode['db'])->where('id', $q['transaksi_id'])->get()->getRowArray();

            if ($transaksi) {
                if ($transaksi['metode'] == "Cash" && $transaksi['is_over'] == 0) {
                    $transaksi['is_over'] = 1;
                    if (!db('transaksi', $decode['db'])->where("id", $transaksi['id'])->update($transaksi)) {
                        gagal("Update transaksi gagal");
                    }
                }
            }
        }

        $db->transComplete();
        $db->transStatus()
            ? sukses("Sukses", $q['status'])
            : gagal("Gagal");
    }
}
