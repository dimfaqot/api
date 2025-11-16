<?php

namespace App\Controllers;

class Menu extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);


        if ($decode['order'] == "Add") {
            $input = [
                'role'       => clear($decode['role']),
                'menu'       => upper_first(clear($decode['menu'])),
                'tabel'      => strtolower(clear($decode['tabel'])),
                'controller' => clear($decode['controller']),
                'icon'       => strtolower(clear($decode['icon'])),
                'grup'       => upper_first(clear($decode['grup']))
            ];


            if ($input['role'] == "") {
                gagal('Role failed');
            }

            // Cek duplikat
            if (db('menu', $decode['db'])->where('role', $input['role'])->where('menu', $input['menu'])->countAllResults() > 0) {
                gagal('Menu existed');
            }

            // Dapatkan urutan terakhir
            $last = db('menu', $decode['db'])->select('urutan')->orderBy('urutan', 'DESC')->get()->getRowArray();
            $input['urutan'] = isset($last['urutan']) ? $last['urutan'] + 1 : 1;


            // Simpan data  
            db('menu', $decode['db'])->insert($input)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {


            $q = db('menu', $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            $q['role'] = clear($decode['role']);
            $q['urutan'] = clear($decode['urutan']);
            $q['menu'] = upper_first(clear($decode['menu']));
            $q['tabel'] = strtolower(clear($decode['tabel']));
            $q['controller'] = clear($decode['controller']);
            $q['icon'] = strtolower(clear($decode['icon']));
            $q['grup'] = upper_first(clear($decode['grup']));

            if (db('menu', $decode['db'])->whereNotIn('id', [$q['id']])->where("menu", $q['menu'])->get()->getRowArray()) {
                gagal("Menu existed");
            }

            if (db('menu', $decode['db'])->whereNotIn('id', [$q['id']])->where("urutan", $q['urutan'])->get()->getRowArray()) {
                gagal("Urutan existed");
            }

            // Simpan data
            db('menu', $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Delete") {

            $q = db('menu', $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            // Simpan data
            db('menu', $decode['db'])->where('id', $q['id'])->delete()
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
