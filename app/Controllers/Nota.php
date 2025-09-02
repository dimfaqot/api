<?php

namespace App\Controllers;

class Nota extends BaseController
{
    public function by_no_nota($db, $no_nota)
    {

        // ✅ Tambahkan CORS Header
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        // Format nota
        $no_nota = str_replace("-", "/", $no_nota);

        // Ambil data dari database
        $db = db("nota", $db);
        $data = $db
            ->select('*')
            ->where('no_nota', $no_nota)
            ->orderBy('barang', 'ASC')
            ->get()
            ->getResultArray();

        // Hitung total biaya
        $total = array_sum(array_column($data, 'biaya'));

        sukses("Sukses", $data, $total);
    }
}
