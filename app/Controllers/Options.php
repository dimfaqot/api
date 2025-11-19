<?php

namespace App\Controllers;

class Options extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);

        if ($decode['order'] == "Add") {
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
        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$decode['id']]))->where("kategori", $q['kategori'])->where('value', $decode['value'])->get()->getRowArray()) {
                gagal("Option existed");
            }

            $q['kategori'] = upper_first(clear($decode['kategori']));
            $q['value'] = upper_first(clear($decode['value']));


            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Delete") {
            delete($decode);
        }
    }
}
