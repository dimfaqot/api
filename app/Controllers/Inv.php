<?php

namespace App\Controllers;

class Inv extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);
        $decode['tahun'] = date('Y');
        $decode['bulan'] = date('n');
        $decode['jenis'] = "All";

        check($decode, $decode['admin'], ['Root', 'Admin', 'Advisor']);

        if ($decode['order'] == "Show") {

            sukses("Ok",  get_data($decode), tahuns($decode), bulans());
        }


        if ($decode['order'] == "Add") {
            $harga = angka_to_int(clear($decode['harga']));
            $qty = angka_to_int(clear($decode['qty']));
            $diskon = angka_to_int(clear($decode['diskon']));
            $pj = upper_first(clear($decode['pj']));

            $db = \Config\Database::connect();
            $db->transStart();

            if ($diskon > ($harga * $qty)) {
                gagal("Diskon over");
            }

            $input = [

                'tgl' => time(),
                'jenis' => upper_first(clear($decode['jenis'])),
                'barang' => upper_first(clear($decode['barang'])),
                'barang_id' => 0,
                'harga'       => $harga,
                'qty'       => $qty,
                'total'       => $harga * $qty,
                'diskon'       => $diskon,
                'biaya'       => ($harga * $qty) - $diskon,
                'pj'       => $pj,
                'petugas'       => upper_first(clear($decode['petugas'])),
                'updated_at'       => time()
            ];


            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }

            // Simpan data  
            db($decode['tabel'], $decode['db'])->insert($input);

            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses",  get_data($decode), tahuns($decode), bulans())
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {

            $harga = angka_to_int($decode['harga']);
            $qty = angka_to_int($decode['qty']);
            $total = angka_to_int($decode['harga']) * angka_to_int($decode['qty']);
            $diskon = angka_to_int($decode['diskon']);
            $biaya = $total - $diskon;

            $db = \Config\Database::connect();
            $db->transStart();

            // Ambil data lama
            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) return gagal("Id not found");
            if ($diskon > $total) return gagal("Diskon over");

            $q['jenis'] = upper_first(clear($decode['jenis']));
            $q['barang'] = upper_first(clear($decode['barang']));
            $q['barang_id'] = 0;
            $q['harga'] = $harga;
            $q['qty'] = $qty;
            $q['total'] = $total;
            $q['diskon'] = $diskon;
            $q['biaya'] = $biaya;
            $q['pj'] = upper_first(clear($decode['pj']));
            $q['petugas'] = upper_first(clear($decode['petugas']));
            $q['updated_at'] = time();


            // Simpan data
            if (!db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)) {
                gagal("Update gagal");
            }

            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses",  get_data($decode), tahuns($decode), bulans())
                : gagal("Gagal");
        }

        if ($decode['order'] == "Delete") {
            delete($decode);
        }
    }
}
