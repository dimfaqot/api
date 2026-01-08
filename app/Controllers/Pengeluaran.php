<?php

namespace App\Controllers;

class Pengeluaran extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);
        $decode['sub_db'] = ($decode['db'] == "playground" || $decode['db'] == "playbox" ? $decode['db'] . "_" . strtolower($decode['divisi']) : $decode['db']);

        check($decode, $decode['admin'], ['Root', 'Admin', 'Advisor']);

        if ($decode['order'] == "Show") {
            $divisi = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);
            $decode['divisions'] = $divisi;

            $barangs = [];
            if ($decode['db'] == "playground" || $decode['db'] == "playbox") {
                $barangs = db('barang', $decode['sub_db'])->orderBy('barang', 'ASC')->get()->getResultArray();
            } else {
                $barangs = db('barang', $decode['db'])->orderBy('barang', 'ASC')->get()->getResultArray();
            }
            $tahuns = count(tahuns($decode)) == 0 ? [["tahun" => date("Y")]] : tahuns($decode);
            sukses("Ok",  get_data($decode), $tahuns, bulans(), array_values(array_diff($divisi, ["Ps", "Billiard"])), $barangs);
        }


        if ($decode['order'] == "Add") {

            $barang_id = clear($decode['barang_id']);
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
            $barang = db('barang', $decode['sub_db'])->where('id', $barang_id)->get()->getRowArray();

            if (!$barang) {
                gagal("Barang not found");
            }

            $input = [

                'tgl' => time(),
                'jenis' => $barang['jenis'],
                'barang' => $barang['barang'],
                'divisi' => $decode['divisi'],
                'barang_id' => $barang['id'],
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

            if ($barang['tipe'] == "Count") {
                $barang['qty'] += (int)$input['qty'];
                if (!db('barang', $decode['sub_db'])->where('id', $barang['id'])->update($barang)) {
                    gagal("Update qty gagal");
                }
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

            $db = \Config\Database::connect();
            $db->transStart();

            // Ambil data lama dari pengeluaran
            $q = db($decode['tabel'], $decode['sub_db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) return gagal("Id not found");
            if ($diskon > $biaya) return gagal("Diskon over");

            $barang    = db('barang', $decode['sub_db'])->where('id', $q['barang_id'])->get()->getRowArray();
            if (!$barang)    return gagal("Barang not found");

            // Update stok jika qty berubah
            if ($barang['tipe'] == "Count" && array_key_exists("qty", $decode)) {
                if ($qty > $q['qty']) {
                    $barang['qty'] += (int)$qty - (int)$q['qty'];
                } elseif ($qty < $q['qty']) {
                    $barang['qty'] -= (int)$q['qty'] - (int)$qty;
                }

                if (!db('barang', $decode['sub_db'])->where('id', $barang['id'])->update($barang)) {
                    return gagal("Update qty gagal");
                }
            }

            $q['jenis'] = $barang['jenis'];
            $q['barang'] = $barang['barang'];
            $q['barang_id'] = $barang['id'];
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

            $barang    = db('barang', $decode['sub_db'])->where('id', $q['barang_id'])->get()->getRowArray();
            if (!$barang)    return gagal("Barang not found");

            // Update stok jika qty berubah
            if ($barang['tipe'] == "Count") {
                $barang['qty'] -= (int) $q['qty'];
                if ((int)$barang['qty'] < 0) {
                    gagal("Barang minus");
                }

                if (!db('barang', $decode['sub_db'])->where('id', $barang['id'])->update($barang)) {
                    return gagal("Update qty gagal");
                }
            }

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
