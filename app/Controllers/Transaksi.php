<?php

namespace App\Controllers;

class Transaksi extends BaseController
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
            $data = db('barang', $decode['db'])->whereIn('jenis', options(['db' => $decode['db'], 'kategori' => 'Kantin', 'format' => 'array']))->get()->getResultArray();
            $customer_grosir = db('customer_grosir')->orderBy('id', 'ASC')->get()->getResultArray();
            sukses('Ok', tahuns($decode), bulans(), options($decode), options(['db' => $decode['db'], 'kategori' => 'Metode', 'format' => 'array']), $data, $customer_grosir);
        }
        // transaksi= simpan data baik bayar langsung maupun hutang
        // Hutang= membayar hutang yang datanya sudah ada
        if ($decode['order'] == "Transaksi") {

            transaksi($decode);
        }

        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

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

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$q['id']]))->where("barang", $q['barang'])->get()->getRowArray()) {
                gagal("Barang existed");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }

        if ($decode['order'] == "Delete") {
            delete($decode, ['Root']);
        }

        if ($decode['order'] == "Cari Barang") {
            cari_barang($decode);
        }
        if ($decode['order'] == "Cari User" || $decode['order'] == "customer grosir") {
            cari_user($decode);
        }
        if ($decode['order'] == "pencuci") {
            $q = db('user')->where('role', 'Admin')->orderBy('nama', 'ASC')->get()->getResultArray();

            $data = [];

            foreach ($q as $i) {
                $exp = explode(",", $i['db']);
                if (in_array($decode['db'], $exp)) {
                    $data[] = $i;
                }
            }

            sukses("Sukses", $data);
        }
        if ($decode['order'] == "Simpan User") {
            $input = [
                'nama'      => upper_first(clear($decode['nama'])),
                'wa'       => clear($decode['wa']),
                'role'       => "Member",
                'username'       => random_str(10),
                'password'       => password_hash(settings($decode['db'], 'password'), PASSWORD_DEFAULT)
            ];

            if (db($decode['tabel'])->where('wa', $decode['wa'])->get()->getRowArray()) {
                gagal("Wa existed");
            }
            if (db($decode['tabel'])->where('nama', $decode['nama'])->get()->getRowArray()) {
                gagal("Nama existed");
            }

            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }
            if (array_key_exists('input_db', $decode)) {
                $input['db'] = $decode['input_db'];
            }

            if (!db($decode['tabel'])->insert($input)) {
                gagal("Insert gagal");
            }

            sukses("Sukses");
        }
    }

    function data($decode)
    {
        $db = db($decode['tabel'], $decode['db']);
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
