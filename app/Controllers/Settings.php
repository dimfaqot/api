<?php

namespace App\Controllers;

class Settings extends BaseController
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
                'nama'       => strtolower(clear($decode['nama'])),
                'value'       => upper_first(clear($decode['value']))
            ];


            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('nama', $input['nama'])->countAllResults() > 0) {
                gagal('Setting existed');
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

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$decode['id']]))->where("nama", $q['nama'])->get()->getRowArray()) {
                gagal("Setting existed");
            }

            $q['nama'] = strtolower(clear($decode['nama']));
            $q['value'] = upper_first(clear($decode['value']));


            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Delete") {

            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->delete()
                ? sukses('Sukses')
                : gagal('Gagal');
        }
    }
}
