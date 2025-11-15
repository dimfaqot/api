<?php

namespace App\Controllers;

class Settings extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        if (!$decode['login'] || $decode['login'] == "" || $decode['login'] == "null") {
            gagal("Login first");
        }

        if ($decode['order'] == "Add") {
            $input = [
                'nama'       => strtolower(clear($decode['nama'])),
                'value'       => upper_first(clear($decode['value']))
            ];


            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('nama', $input['nama'])->countAllResults() > 0) {
                gagal('Setting existed');
            }


            // Simpan data  
            db($decode['tabel'], $decode['db'])->insert($input)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$decode['id']]))->where("nama", $q['nama'])->get()->getRowArray()) {
                gagal("Setting existed");
            }

            $q['nama'] = strtolower(clear($decode['nama']));
            $q['value'] = upper_first(clear($decode['value']));


            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Delete") {

            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->delete()
                ? sukses('Sukses')
                : gagal('Gagal');
        }
    }


    public function copy_table($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $tabel = clear($this->request->getVar('tabel'));

        $db_old = db(($tabel == "transaksi" || $tabel == "hutang" ? "penjualan" : $tabel), getenv('OLD_DB'));
        $old = $db_old->orderBy('id', 'ASC')->get()->getResultArray();
        $insert = [];
        $db = db_connect();
        $db->transStart();
        foreach ($old as $k => $i) {
            if ($tabel == "pengeluaran") {
                $data = [
                    'tgl' => $i['tgl'],
                    'jenis' => $i['kategori'],
                    'barang' => $i['barang'],
                    'barang_id' => 0,
                    'harga' => $i['harga'],
                    'qty' => $i['qty'],
                    'total' => $i['qty'] * $i['harga'],
                    'diskon' => $i['diskon'],
                    'biaya' => $i['total'],
                    'pj' => $i['petugas'],
                    'petugas' => $i['petugas'],
                    'updated_at' => $i['tgl']
                ];
                $insert[] = $data;
            } elseif ($tabel == "transaksi" && $i['ket'] !== "Hutang") {

                $data = [
                    'tgl' => $i['tgl'],
                    'jenis' => '',
                    'barang' => $i['barang'],
                    'barang_id' => 0,
                    'harga' => $i['harga'],
                    'qty' => $i['qty'],
                    'total' => $i['qty'] * $i['harga'],
                    'diskon' => $i['diskon'],
                    'biaya' => $i['total'],
                    'petugas' => $i['petugas']
                ];
                $insert[] = $data;
            } elseif ($tabel == "hutang" && $i['ket'] == 'Hutang') {
                $no_nota = next_invoice('hutang');
                $data = [
                    'no_nota' => $no_nota,
                    'tgl' => $i['tgl'],
                    'jenis' => '',
                    'barang' => $i['barang'],
                    'barang_id' => 0,
                    'harga' => $i['harga'],
                    'qty' => $i['qty'],
                    'total' => $i['qty'] * $i['harga'],
                    'diskon' => $i['diskon'],
                    'biaya' => $i['total'],
                    'petugas' => $i['petugas'],
                    'nama' => $i['pembeli'],
                    'user_id' => $i['user_id'],
                    'tipe' => ''
                ];
                $insert[] = $data;
                // db($tabel, 'cafe')->insert($i);
            }
        }
        // dd(count($insert));
        // $last = array_slice($insert, 6000, 500, true);
        // foreach ($insert as $i) {

        //     db($tabel, 'cafe')->insert($i);
        // }
        $db->transComplete();

        if (!$db->transStatus()) {
            gagal('Copy gagal');
        }

        sukses('Copy sukses');
    }
}
