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
        $decode = decode_jwt($jwt);
        $decode['sub_db'] = $decode['db'];
        if ($decode['db'] == "playground" || $decode['db'] !== "playbox") {
            if ($decode['divisi'] !== "Billiard" && $decode['divisi'] !== "Ps") {
                $decode['sub_menu'] = $decode['db'] . "_" . strtolower($decode['divisi']);
            }
        }
        sukses($decode);
        check($decode, $decode['admin'], ['Root', 'Advisor']);

        if ($decode['order'] == "Show") {
            $divisi = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);
            $decode['divisions'] = $divisi;


            $tahuns = count(tahuns($decode)) == 0 ? [["tahun" => date("Y")]] : tahuns($decode);
            sukses("Ok",  get_data($decode), $tahuns, bulans(), $divisi);
        }


        if ($decode['order'] == "Add") {

            $jenis = upper_first(clear($decode['jenis']));
            $barang = upper_first(clear($decode['barang']));
            $harga = angka_to_int(clear($decode['harga']));
            $qty = angka_to_int(clear($decode['qty']));
            $diskon = angka_to_int(clear($decode['diskon']));
            $total = angka_to_int(clear($decode['total']));
            $biaya = angka_to_int(clear($decode['biaya']));
            $pj = upper_first(clear($decode['pj']));

            $db = \Config\Database::connect();
            $db->transStart();

            if ($diskon > $biaya) {
                gagal("Diskon over");
            }

            $input = [

                'tgl' => time(),
                'jenis' => $jenis,
                'barang' => $barang,
                'harga'       => $harga,
                'qty'       => $qty,
                'total'       => $total,
                'diskon'       => $diskon,
                'biaya'       => $biaya,
                'pj'       => $pj,
                'petugas'       => upper_first(clear($decode['petugas'])),
                'updated_at'       => time()
            ];


            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }

            // Simpan data  
            db($decode['tabel'], $decode['sub_db'])->insert($input);

            $db->transComplete();

            return $db->transStatus()
                ? sukses('Sukses', get_data($decode))
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {

            $harga = angka_to_int(clear($decode['harga']));
            $qty = angka_to_int(clear($decode['qty']));
            $diskon = angka_to_int(clear($decode['diskon']));
            $total = angka_to_int(clear($decode['total']));
            $biaya = angka_to_int(clear($decode['biaya']));
            $pj = upper_first(clear($decode['pj']));
            $jenis = upper_first(clear($decode['jenis']));
            $barang = upper_first(clear($decode['barang']));

            $db = \Config\Database::connect();
            $db->transStart();

            // Ambil data lama dari pengeluaran
            $q = db($decode['tabel'], $decode['sub_db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) return gagal("Id not found");
            if ($diskon > $biaya) return gagal("Diskon over");


            $q['jenis'] = $jenis;
            $q['barang'] = $barang;
            $q['harga'] = $harga;
            $q['qty'] = $qty;
            $q['total'] = $total;
            $q['diskon'] = $diskon;
            $q['biaya'] = $biaya;
            $q['pj'] = upper_first(clear($decode['pj']));
            $q['petugas'] = upper_first(clear($decode['petugas']));
            $q['updated_at'] = time();


            // Simpan data
            if (!db($decode['tabel'], $decode['sub_db'])->where('id', $q['id'])->update($q)) {
                gagal("Update gagal");
            }

            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses", get_data($decode))
                : gagal("Gagal");
        }

        if ($decode['order'] == "Delete") {

            $roles = ['Root', 'Advisor'];

            if (!in_array($decode['admin'], $roles)) {
                gagal("Role not allowed");
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Ambil data lama dari pengeluaran
            $q = db($decode['tabel'], $decode['sub_db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) return gagal("Id not found");


            if (!db($decode['tabel'], $decode['sub_db'])->where('id', $q['id'])->delete()) {
                gagal("Delete gagal");
            }



            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses", get_data($decode))
                : gagal("Gagal");
        }
    }
}
