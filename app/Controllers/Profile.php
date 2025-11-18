<?php

namespace App\Controllers;

class Profile extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);

        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            $q['nama'] = upper_first(clear($decode['nama']));
            $q['pendiri'] = upper_first(clear($decode['pendiri']));
            $q['tgl_berdiri'] = strtotime(clear($decode['tgl_berdiri']));
            $q['sub_unit'] = $decode['sub_unit'];
            $q['manager'] = upper_first(clear($decode['manager']));
            $q['modal_asal'] = upper_first(clear($decode['modal_asal']));

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Detail") {
            sukses("Ok", uang_modal($decode['db']));
        }
    }
}
