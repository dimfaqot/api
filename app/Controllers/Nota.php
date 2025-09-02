<?php

namespace App\Controllers;

class Nota extends BaseController
{
    public function by_no_nota($db, $no_nota)
    {

        $no_nota = str_replace("-", "/", $no_nota);
        $db = db("nota", $db);
        $data = $db->where('no_nota', $no_nota)->orderBy('barang', 'ASC')->get()->getResultArray();

        $data = $db
            ->select('*')->where('no_nota', $no_nota)->orderBy('barang', 'ASC')->get()->getResultArray();

        $total = array_sum(array_column($data, 'biaya'));
        sukses("Sukses", $data, $total);
    }
}
