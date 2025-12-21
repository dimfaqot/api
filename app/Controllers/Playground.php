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
                        $i['user_id_wl'] = $wl['user_id'];
                        $i['nama_wl'] = $wl['nama'];
                        $i['booking'] = $wl['booking'];
                        $i['dp'] = $wl['dp'];
                    } else {
                        $i['user_id_wl'] = "";
                        $i['nama_wl'] = "";
                        $i['booking'] = "";
                        $i['dp'] = "";
                    }

                    $diskons = db('diskon', $decode['db'])->where('game_id', $i['id'])->orderBy('id', 'ASC')->get()->getResultArray();
                    $i['diskons'] = $diskons;
                    $transaksi = db('transaksi', $decode['db'])->where('metode', 'Hutang')->where('barang_id', $i['id'])->orderBy('tgl', 'DESC')->get()->getRowArray();
                    if ($transaksi) {
                        $i['qty'] = $transaksi['qty'];
                        $i['total'] = $transaksi['total'];
                        $i['diskon'] = $transaksi['diskon'];
                        $i['biaya'] = $transaksi['biaya'];
                        $i['no_nota'] = $transaksi['no_nota'];
                        $i['metode'] = $transaksi['metode'];
                        $i['user_id'] = $transaksi['user_id'];
                        $i['user'] = $transaksi['nama'];
                        $i['start'] = $transaksi['start'];
                        $i['end'] = $transaksi['end'];
                        $i['is_over'] = $transaksi['is_over'];
                        $i['is_open'] = $i['qty'] == 0 ? "Open" : "Reguler";
                        $i['waktu'] = $this->hitungWaktu($transaksi['start'], $transaksi['end'], $transaksi['qty']);
                    } else {
                        $i['qty'] = '';
                        $i['total'] = '';
                        $i['diskon'] = '';
                        $i['biaya'] = '';
                        $i['no_nota'] = '';
                        $i['metode'] = '';
                        $i['user_id'] = '';
                        $i['user'] = '';
                        $i['start'] = '';
                        $i['end'] = '';
                        $i['is_over'] = 0;
                        $i['is_open'] = "";
                        $i['waktu'] = "";
                    }
                    $data[] = $i;
                }
            }
            sukses("Ok", $data, options($decode));
        }
    }

    function hitungWaktu(int $start, int $end, int $qty): string
    {
        if ($qty > 0) {
            $diff = time() - $start;
            if ($diff < 0) return "00:00";

            $hours   = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);

            // format dua digit jam:menit
            return sprintf("%02d:%02d", $hours, $minutes);
        } else {
            // sisa waktu
            $diff = $end - time();
            if ($diff < 0) return "00:00";

            $hours   = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);

            return "-" . sprintf("%02d:%02d", $hours, $minutes); // contoh: 01:08
        }
    }
}
