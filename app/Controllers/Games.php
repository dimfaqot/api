<?php

namespace App\Controllers;

class Games extends BaseController
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
            sukses('Ok', $this->data($decode), options($decode), options(['db' => $decode['db'], 'kategori' => 'Room', 'format' => 'array']));
        }

        if ($decode['order'] == "Add") {

            $input = [
                'game'      => upper_first(clear($decode['game'])),
                'nama'       => upper_first(clear($decode['nama'])),
                'room'       => upper_first(clear($decode['room'])),
                'harga'      => angka_to_int(clear($decode['harga']))
            ];

            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }

            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('game', $input['game'])->where('nama', $input['nama'])->countAllResults() > 0) {
                gagal('Nama existed');
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

            $q['game'] = upper_first(clear($decode['game']));
            $q['nama'] = upper_first(clear($decode['nama']));
            $q['room'] = upper_first(clear($decode['room']));
            $q['harga'] = angka_to_int(clear($decode['harga']));


            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$q['id']]))->where('game', $q['game'])->where('nama', $q['nama'])->get()->getRowArray()) {
                gagal("Nama existed");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses', $this->data($decode))
                : gagal('Gagal');
        }

        if ($decode['order'] == "Delete") {
            delete($decode, ['Root']);
        }
    }

    function data($decode)
    {
        $data = db('games', $decode['db'])->orderBy('game', 'ASC')->orderBy('nama', 'ASC')->get()->getResultArray();

        return $data ? $data : [];
    }
}
