<?php

namespace App\Controllers;

class Hutang extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);

        if ($decode['order'] == "show") {
            $input = [
                'kategori'       => upper_first(clear($decode['kategori'])),
                'value'       => upper_first(clear($decode['value']))
            ];


            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('kategori', $input['kategori'])->where('value', $input['value'])->countAllResults() > 0) {
                gagal('Option existed');
            }


            // Simpan data  
            db($decode['tabel'], $decode['db'])->insert($input)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
    }
}
