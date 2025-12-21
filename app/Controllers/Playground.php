<?php

namespace App\Controllers;

class Playground extends BaseController
{

    public function transaksi($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode, $decode['admin'], ['Root', 'Kasir']);
        $decode['db'] = ($decode['divisi'] == "Kantin" || $decode['divisi'] == "Barber" ? strtolower($decode['divisi']) : $decode['db']);

        // transaksi= simpan data baik bayar langsung maupun hutang
        // Hutang= membayar hutang yang datanya sudah ada
        if ($decode['order'] == "Show") {
            $data = [];
            if ($decode['db'] == "kantin" || $decode['db'] == "barber") {
                $data = db('barang', $decode['db'])->orderBy('barang', 'ASC')->get()->getResultArray();
            } else {
                $data = db('games', $decode['db'])->where('game', $decode['divisi'])->orderBy('id', 'ASC')->get()->getResultArray();
            }
            sukses("Ok", $data, options($decode));
        }
    }
}
