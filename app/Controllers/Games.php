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
        if ($decode['order'] == "Add Diskon") {

            if (angka_to_int($decode['diskon']) > angka_to_int($decode['harga'])) {
                gagal('Diskon terlalu besar');
            }
            $input = [
                'nama'      => upper_first(clear($decode['nama'])),
                'diskon'       => angka_to_int(clear($decode['diskon'])),
                'game_id'       => clear($decode['id'])
            ];

            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }

            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('game_id', $input['game_id'])->where('nama', $input['nama'])->countAllResults() > 0) {
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
        $val = db($decode['tabel'], $decode['db'])->orderBy('game', 'ASC')->orderBy('nama', 'ASC')->get()->getResultArray();

        $data = [];

        foreach ($val as $i) {
            $temp_diskon = db('diskon', $decode['db'])->where('game_id', $i['id'])->get()->getResultArray();
            $diskon = [];

            $total = 0;
            foreach ($temp_diskon as $d) {
                if ($d['nama'] === "Weekday" && ! is_weekday()) {
                    // lewati jika nama Weekday tapi bukan hari kerja
                    continue;
                }
                $total += $i['diskon'];
                $diskon[] = $d;
            }

            $i['total_diskon'] = $total;
            $i['biaya'] = $i['harga'] - $total;
            $i['diskon'] = $diskon;

            $data[] = $i;
        }

        return $data;
    }
}
