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
        $decode['sub_db'] = ($decode['db'] == "playground" || $decode['db'] == "playbox" ? $decode['db'] . "_" . strtolower($decode['divisi']) : $decode['db']);

        if ($decode['order'] == "Show") {

            $status_qty = db('settings')->where('db', $decode['db'])->where('nama', 'qty')->get()->getRowArray();
            $divisi = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);
            $divisi = array_values(array_diff($divisi, ["Ps", "Billiard"]));
            sukses('Ok', $this->data($decode), $status_qty['value'], options($decode), $divisi);
        }

        if ($decode['order'] == "Add") {

            $tipe = (clear($decode['tipe']) == "on" ? "Mix" : "Count");

            $input = [
                'jenis'      => upper_first(clear($decode['jenis'])),
                'barang'       => upper_first(clear($decode['barang'])),
                'petugas'       => upper_first(clear($decode['petugas'])),
                'link'       => clear($decode['link']),
                'qty'       => 0,
                'tipe' => $tipe,
                'harga'      => angka_to_int(clear($decode['harga']))
            ];

            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }
            if (array_key_exists('qty', $decode)) {
                $input['qty'] = $decode['qty'];
            }

            // Cek duplikat
            if (db($decode['tabel'], $decode['sub_db'])->where('barang', $input['barang'])->countAllResults() > 0) {
                gagal('Barang existed');
            }

            // Simpan data  
            db($decode['tabel'], $decode['sub_db'])->insert($input)
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['sub_db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            $tipe = (clear($decode['tipe']) == "on" ? "Mix" : "Count");

            $q['jenis'] = upper_first(clear($decode['jenis']));
            $q['link'] = clear($decode['link']);
            $q['barang'] = upper_first(clear($decode['barang']));
            $q['petugas'] = upper_first(clear($decode['petugas']));
            $q['tipe'] = $tipe;
            $q['harga'] = angka_to_int(clear($decode['harga']));

            if (array_key_exists('qty', $decode)) {
                $q['qty'] = $decode['qty'];
            }

            if ((db($decode['tabel'], $decode['sub_db'])->whereNotIn('id', [$q['id']]))->where("barang", $q['barang'])->get()->getRowArray()) {
                gagal("Barang existed");
            }

            // Simpan data
            db($decode['tabel'], $decode['sub_db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }

        if ($decode['order'] == "Delete") {
            delete($decode, ['Root']);
        }
    }

    function data($decode)
    {

        $db = db($decode['tabel'], $decode['sub_db']);
        if (array_key_exists('lokasi', $decode)) {
            $db->where('lokasi', $decode['lokasi']);
        }

        $val = $db->orderBy("barang", "ASC")->get()->getResultArray();
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
