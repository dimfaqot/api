<?php

namespace App\Controllers;

class Playground extends BaseController
{

    public function transaksi($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode, $decode['admin'], ['Root', 'Kasir']);
        $decode['db'] = ($decode['divisi'] == "Kantin" || $decode['divisi'] == "Barber" ? strtolower($decode['divisi']) : $decode['db']);

        // transaksi= simpan data baik bayar langsung maupun hutang
        // Hutang= membayar hutang yang datanya sudah ada
        if ($decode['order'] == "Show") {
            $data = [];
            if ($decode['db'] == "kantin" || $decode['db'] == "barber") {
                $data = db('barang', $decode['db'])->orderBy('barang', 'ASC')->get()->getResultArray();
            } else {
                $q = db('games', $decode['db'])->select('games.id as id,game,games.nama as nama,harga,room,ket,status')->join('iot', 'games.iot_id=iot.id')->where('game', $decode['divisi'])->orderBy('games.id', 'ASC')->get()->getResultArray();
                $data = [];

                foreach ($q as $i) {
                    $wl = db('wl', $decode['db'])->where('game_id', $i['id'])->get()->getRowArray();
                    if ($wl) {
                        $i['user_id'] = $wl['user_id'];
                        $i['user'] = $wl['nama'];
                        $i['booking'] = $wl['booking'];
                        $i['dp'] = $wl['dp'];
                    }

                    $diskon = db('diskon', $decode['db'])->where('game_id', $i['id'])->orderBy('id', 'ASC')->get()->getResultArray();
                    $i['data'] = $diskon;

                    $data[] = $i;
                }
            }
            sukses("Ok", $data, options($decode));
        }
    }
}
