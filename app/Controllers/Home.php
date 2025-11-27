<?php

namespace App\Controllers;

class Home extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);

        if ($decode['order'] == 'Menu') {
            sukses("Ok", tahuns($decode), bulans());
        } else {
            if ($decode['jenis'] == "Unlock") {
                $this->unlock($decode);
            } else {
                $data = get_data($decode);
                sukses("Ok", $data['data'], $data['total'], $data['sub_menu']);
            }
        }
    }

    function unlock($decode)
    {
        $keep = ($decode['keep'] == 0 ? 1 : 0);

        $q = db('backup', $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

        if (!$q) {
            gagal("Id not found");
        }

        $q['keep'] = $keep;

        if (db('backup', $decode['db'])->where('id', $q['id'])->update($q)) {
            $backup = db('backup', $decode['db'])->select('*')->orderBy('tahun', 'ASC')
                ->get()
                ->getResultArray();
            $tot = array_sum(array_column($backup, 'saldo'));

            sukses("Ok", $backup, $tot);
        } else {
            gagal("Unlock gagal");
        }
    }
}
