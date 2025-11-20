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

        check($decode, $decode['admin'], ['Root', 'Admin', 'Advisor']);

        if ($decode['order'] == "Show") {
            $this->data($decode);
        }


        if ($decode['order'] == "Add") {

            $barang_id = clear($decode['barang_id']);
            $harga = angka_to_int(clear($decode['harga']));
            $qty = angka_to_int(clear($decode['qty']));
            $diskon = angka_to_int(clear($decode['diskon']));
            $pj = upper_first(clear($decode['pj']));

            $db = \Config\Database::connect();
            $db->transStart();

            $barang = db('barang', $decode['db'])->where('id', $barang_id)->get()->getRowArray();

            if ($diskon > ($harga * $qty)) {
                gagal("Diskon over");
            }
            if (!$barang) {
                gagal("Barang not found");
            }

            $input = [

                'tgl' => time(),
                'jenis' => $barang['jenis'],
                'barang' => $barang['barang'],
                'barang_id' => $barang['id'],
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

            if ($barang['tipe'] == "Count") {
                $barang['qty'] += (int)$input['qty'];
                if (!db('barang', $decode['db'])->where('id', $barang['id'])->update($barang)) {
                    gagal("Update qty gagal");
                }
            }

            // Simpan data  
            db($decode['tabel'], $decode['db'])->insert($input);

            $db->transComplete();

            return $db->transStatus()
                ? sukses('Sukses', $this->data($decode))
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

            $barang    = db('barang', $decode['db'])->where('id', $q['barang_id'])->get()->getRowArray();
            if (!$barang)    return gagal("Barang not found");

            // Update stok jika qty berubah
            if ($barang['tipe'] == "Count") {
                if ($q['qty'] != $qty) {
                    $barang['qty'] -= $qty;
                    if (!db('barang', $decode['db'])->where('id', $barang['id'])->update($barang)) {
                        return gagal("Update qty gagal");
                    }
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
            if (!db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)) {
                gagal("Update gagal");
            }

            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses", $this->data($decode))
                : gagal("Gagal");
        }

        if ($decode['order'] == "Delete") {

            $roles = ['Admin', 'Root'];

            if (!in_array($decode['admin'], $roles)) {
                gagal("Role not allowed");
            }

            $db = \Config\Database::connect();
            $db->transStart();

            // Ambil data lama
            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) return gagal("Id not found");

            $barang    = db('barang', $decode['db'])->where('id', $q['barang_id'])->get()->getRowArray();
            if (!$barang)    return gagal("Barang not found");

            // Update stok jika qty berubah
            if ($barang['tipe'] == "Count") {
                $barang['qty'] -=  $q['qty'];
                if (!db('barang', $decode['db'])->where('id', $barang['id'])->update($barang)) {
                    return gagal("Update qty gagal");
                }
            }

            if (!db($decode['tabel'], $decode['db'])->where('id', $q['id'])->delete()) {
                gagal("Delete gagal");
            }

            $db->transComplete();

            return $db->transStatus()
                ? sukses("Sukses", $this->data($decode))
                : gagal("Gagal");
        }

        if ($decode['order'] == "Cari Barang") {
            cari_barang($decode);
        }
        if ($decode['order'] == "Lists") {
            lists($decode);
        }
    }

    function data($decode)
    {

        $filters = [];
        foreach (options($decode['db'], "Kantin") as $i) {
            $filters[] = $i['value'];
        }

        $db = db($decode['tabel'], $decode['db']);
        $db->select('*');
        if (array_key_exists('lokasi', $decode)) {
            $db->where('lokasi', $decode['lokasi']);
        }
        $db->whereIn('jenis', $filters);
        $data = $db->orderBy('tgl', 'DESC')
            ->where("MONTH(FROM_UNIXTIME(tgl))", date('n'))
            ->where("YEAR(FROM_UNIXTIME(tgl))", date('Y'))
            ->orderBy("tgl", "DESC")->get()->getResultArray();
        $total = array_sum(array_column($data, 'biaya'));
        sukses("Ok", $data, $total, tahuns($decode), bulans());
    }
}
