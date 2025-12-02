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

            sukses('Ok', tahuns($decode), bulans(), options($decode), options(['db' => $decode['db'], 'kategori' => 'Metode', 'format' => 'array']));
        }

        if ($decode['order'] == "Transaksi") {
        }
        if ($decode['order'] == "Hutang") {

            $db = \Config\Database::connect();
            $db->transStart();
            $nota = next_invoice($decode);

            $tgl = time();

            sukses($decode['datas'][0]['barang']);

            foreach ($decode['datas'] as $i) {
                $db->table($decode['tabel'], $decode['db'])->insert([
                    "no_nota" => $nota,
                    "tgl" => $tgl,
                    "jenis" => $i['jenis'],
                    "barang" => $i['barang'],
                    "karyawan" => $i['karyawan'],
                    "barang_id" => $i['id'],
                    "harga" => $i['harga'],
                    "qty" => $i['qty'],
                    "total" => $i['total'],
                    "diskon" => $i['diskon'],
                    "biaya" => $i['biaya'],
                    "petugas" => $decode['petugas'],
                    "nama" => $decode['penghutang']['nama'],
                    "user_id" => $decode['penghutang']['id'],
                    'metode' => "Hutang"
                ]);

                $barang = db('barang', $decode['db'])->where('id', $i['id'])->get()->getRowArray();
                if (!$barang) {
                    gagal("Id " . $i['barang'] . " not found");
                }
                if ($barang['link'] !== '' && $barang['tipe'] == "Mix") {
                    $exp = explode(",", $barang['link']);

                    foreach ($exp as $x) {
                        $val = db('barang', $decode['db'])->where('id', $x)->get()->getRowArray();

                        if (!$val) {
                            gagal("Link barang id null");
                        }

                        if ($val['qty'] < (int)$i['qty']) {
                            gagal('Stok kurang');
                        }

                        $val['qty'] -= (int)$i['qty'];

                        if (!db('barang', $decode['db'])->where('id', $val['id'])->update($val)) {
                            gagal("Update stok gagal");
                        }
                    }
                }

                if ($barang['tipe'] == "Count") {
                    if ($barang['qty'] < (int)$i['qty']) {
                        gagal('Stok kurang');
                    }
                    $barang['qty'] -= (int)$i['qty'];

                    if (!db('barang', $decode['db'])->where('id', $barang['id'])->update($barang)) {
                        gagal("Update stok gagal");
                    }
                }
            }

            $total = 0;
            $dbh = db('transaksi', $decode['db']);
            $dbh->select('*');
            if (array_key_exists('lokasi', $decode)) {
                $dbh->where('lokasi', $decode['lokasi']);
            }
            $dbh->where('user_id', $decode['penghutang']['id']);
            $dbh->where('metode', "Hutang");
            $hutangs = $dbh->get()->getResultArray();
            if ($hutangs) {
                $total = array_sum(array_column($hutangs, 'biaya'));
            }

            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses", '<div>TOTAL HUTANG</div><h5>' . angka($total) . '</h5>')
                : gagal("Gagal");
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
        if ($decode['order'] == "Cari User") {
            cari_user($decode);
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
