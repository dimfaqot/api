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

        if ($decode['order'] == "Show") {
            $data = [];
            foreach ($decode['divisions'] as $d) {

                if ($d == "Kantin" || $d == "Barber") {
                    $data[$d] = db('barang', strtolower($d))->orderBy('barang', 'ASC')->get()->getResultArray();
                } else {
                    $q = db('games', $decode['db'])->select('games.id as id, iot.id as iot_id,game,games.nama as nama,harga,room,ket,status')->join('iot', 'games.iot_id=iot.id')->where('game', $d)->orderBy('games.id', 'ASC')->get()->getResultArray();
                    $val = [];

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

                        $q_diskon = db('diskon', $decode['db'])->where('game_id', $i['id'])->orderBy('id', 'ASC')->get()->getResultArray();

                        $diskons = [];

                        foreach ($q_diskon as $s) {
                            $s['is_weekdays'] = is_weekdays();
                            $s['is_pelajar'] = $this->is_pelajar();
                            $diskons[] = $s;
                        }
                        $i['diskons'] = $diskons;
                        $transaksi = db('transaksi', $decode['db'])->where('metode', 'Hutang')->where('barang_id', $i['id'])->orderBy('tgl', 'DESC')->get()->getRowArray();
                        if ($transaksi) {
                            $i['transaksi_id'] = $transaksi['id'];
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
                            $i['roleplay'] = $transaksi['roleplay'];
                            $i['waktu'] = $this->hitungWaktu($transaksi['start'], $transaksi['end'], $transaksi['qty']);
                        } else {
                            $i['transaksi_id'] = '';
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
                            $i['roleplay'] = "";
                        }
                        $val[] = $i;
                    }

                    $data[$d] = $val;
                }
            }
            sukses("Ok", $data, options($decode));
        }

        if ($decode['order'] == "Transaksi") {
            transaksi($decode);
        }
        if ($decode['order'] == "Update waktu") {
            $res = [];

            foreach ($decode['datas'] as $i) {
                $i['waktu'] = "00:00";
                $q = db('transaksi', strtolower($i['divisi']))->where('id', $i['id'])->get()->getRowArray();
                if ($q) {
                    $i['waktu'] = $this->hitungWaktu($q['start'], $q['end'], $q['qty']);
                }
                $res[] = $i;
            }

            sukses("Sukses", $res);
        }
    }

    function hitungWaktu(int $start, int $end, int $qty): string
    {
        if ($qty > 0) {
            // sisa waktu
            $diff = $end - time();
            if ($diff < 0) return "00:00";

            $hours   = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);

            return "-" . sprintf("%02d:%02d", $hours, $minutes); // contoh: 01:08
        } else {
            $diff = time() - $start;
            if ($diff < 0) return "00:00";

            $hours   = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);

            // format dua digit jam:menit
            return sprintf("%02d:%02d", $hours, $minutes);
        }
    }

    function is_pelajar()
    {
        // Ambil jam saat ini (format 24 jam)
        $hour = (int)date('H');

        // Cek apakah jam antara 12 sampai 16 (karena 17:00 sudah lewat)
        return ($hour >= 12 && $hour < 17);
    }
}
