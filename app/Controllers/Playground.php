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
        $decode['db'] = ($decode['db'] == "playground" ? strtolower($decode['divisi']) : $decode['db']);
        // transaksi= simpan data baik bayar langsung maupun hutang
        // Hutang= membayar hutang yang datanya sudah ada
        if ($decode['order'] == "Show") {

            sukses("Ok", db('barang', $decode['db'])->orderBy('barang')->get()->getResultArray(), options($decode));
        }
    }
}
