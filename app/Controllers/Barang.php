<?php

namespace App\Controllers;

class Barang extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode, $decode['admin'], ['Root', 'Admin', 'Advisor']);

        if ($decode['order'] == "Show") {

            sukses('Ok', $this->data($decode));
        }

        if ($decode['order'] == "Add") {

            $tipe = (clear($decode['tipe']) == "on" ? "Mix" : "Count");

            $input = [
                'jenis'      => angka_to_int(clear($decode['jenis'])),
                'barang'       => upper_first(clear($decode['barang'])),
                'petugas'       => upper_first(clear($decode['petugas'])),
                'link'       => clear($decode['link']),
                'qty'       => 0,
                'tipe' => $tipe,
                'harga'      => angka_to_int(clear($decode['harga']))
            ];

            $qty = angka_to_int(clear($decode['qty']));
            if ($qty !== "") {
                $input['qty'] = $qty;
            }

            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('barang', $input['barang'])->countAllResults() > 0) {
                gagal('Barang existed');
            }

            // Simpan data  
            db($decode['tabel'], $decode['db'])->insert($input)
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            $tipe = (clear($decode['tipe']) == "on" ? "Mix" : "Count");

            $q['jenis'] = angka_to_int(clear($decode['jenis']));
            $q['link'] = clear($decode['link']);
            $q['barang'] = upper_first(clear($decode['barang']));
            $q['petugas'] = upper_first(clear($decode['petugas']));
            $q['tipe'] = $tipe;
            $q['harga'] = angka_to_int(clear($decode['harga']));

            $qty = angka_to_int(clear($decode['qty']));
            if ($qty !== "") {
                $q['qty'] = $qty;
            }

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$q['id']]))->where("barang", $q['barang'])->get()->getRowArray()) {
                gagal("Barang existed");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }
        if ($decode['order'] == "Delete") {

            if ($decode['admin'] !== "Root") {
                gagal("Role not allowed");
            }

            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->delete()
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }
    }

    function data($decode)
    {
        $val = db($decode['tabel'], $decode['db'])->orderBy("barang", "ASC")->get()->getResultArray();
        $data = [];

        foreach ($val as $i) {
            if ($i['link'] == "") {
                $i['barangs'] = "";
            } else {

                $temp_barangs = [];
                $ids = explode(",", $i['link']);

                foreach ($ids as $t) {
                    foreach ($val as $x) {
                        if ($t == $x['id']) {
                            $temp_barangs[] = $x['barang'];
                        }
                    }
                }
                $i['barangs'] = implode(",", $temp_barangs);
            }
            $data[] = $i;
        }

        return $data;
    }
}
