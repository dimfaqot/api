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
            $data = $this->get_data($decode);

            sukses("Ok", $data, options($decode));
        }

        if ($decode['order'] == "Transaksi") {
            $message = transaksi($decode);
            if ($message == "Wl" || $message == "") {
                sukses("Sukses", ($message == "Wl" ? $this->get_data($decode) : null));
            } else {
                gagal($message);
            }
        }

        if ($decode['order'] == "Update waktu") {
            $res = [];

            foreach ($decode['datas'] as $i) {
                $temp = ['id' => $i, 'waktu' => "00:00", 'roleplay' => 'Open', 'barang' => ''];

                $q = db('transaksi', 'playground')->where('id', $i)->get()->getRowArray();
                if ($q) {
                    $temp['waktu'] = $this->hitungWaktu($q['start'], $q['end'], $q['qty']);
                    $temp['roleplay'] = $q['roleplay'];
                    $temp['divisi'] = $q['jenis'];
                    $temp['barang'] = $q['barang'];
                }
                $res[] = $temp;
            }

            $db = \Config\Database::connect();
            $db->transStart();
            $wl = db('transaksi', 'playground')->where('metode', "Wl")->get()->getResultArray();
            foreach ($wl as $i) {
                if ((int)$i['start'] <= time()) {
                    $i['metode'] = "Hutang";
                    $i['tgl'] = $i['start'];
                    if (!db('transaksi', 'playground')->where('id', $i['id'])->update($i)) {
                        gagal('Wl gagal dimulai');
                    }

                    $iot = db('iot', 'playground')->select('iot.id as id')->join('games', 'iot.id=games.iot_id')->where('games.id', $i['barang_id'])->get()->getRowArray();
                    if ($iot) {
                        $iot['status'] = 1;
                        $iot['end'] = $i['end'];
                        $iot['transaksi_id'] = $i['id'];
                        if (!db('iot', 'playground')->where('id', $iot['id'])->update($iot)) {
                            gagal("Lampu wl gagal nyala");
                        }

                        $temp = [
                            'id' => $i['id'],
                            'waktu' => $this->hitungWaktu($i['start'], $i['end'], $i['qty']),
                            'roleplay' => $i['roleplay'],
                            'divisi' => $i['jenis'],
                            'barang' => $i['barang']
                        ];

                        $res[] = $temp;
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus()) {
                return sukses("Sukses", $res);
            } else {
                return gagal("Gagal", []);
            }
        }

        if ($decode['order'] == "Bayar") {
            $range = today($decode);
            $notas = [];
            foreach ($decode['divisions'] as $i) {
                $db = ($i == "Ps" || $i == "Billiard" ? "playground" : $i);
                $temp_notas = db('transaksi', $db)->select("no_nota")->where('tgl >=', $range['start'])->where('tgl <=', $range['end'])->groupBy('no_nota')->get()->getResultArray();

                foreach ($temp_notas as $tn) {
                    if (!in_array($tn['no_nota'], $notas)) {
                        $notas[] = $tn['no_nota'];
                    }
                }
            }

            $res = [
                'data' => [],
                'range' => $range,
                'Wl' => 0,
                'Hutang' => 0,
                'sub_menu' => [] //jml hari bulan ini
            ];

            foreach (options(['db' => $decode['db'], 'kategori' => "Metode", "format" => "array"]) as $i) {
                $res[$i] = 0;
            }

            foreach ($notas as $n) {
                $temp = ['data' => [], 'total' => 0, 'identitas' => []];
                foreach ($decode['divisions'] as $i) {
                    $db = ($i == "Ps" || $i == "Billiard" ? "playground" : strtolower($i));

                    $dbb = db('transaksi', $db);
                    if ($i == "Ps" || $i == "Billiard") {
                        $dbb->where('jenis', $i);
                    }

                    $data = $dbb->where('no_nota', $n)->get()->getResultArray();
                    foreach ($data as $d) {
                        $d['divisi'] = $i;
                        $temp['identitas'] = [
                            'nama'    => $d['nama'],
                            'tgl'     => $d['tgl'],
                            'no_nota' => $d['no_nota']
                        ];
                        $temp['data'][] = $d;

                        // jumlahkan biaya langsung
                        $temp['total'] += (int)$d['biaya'];
                        $res[$d['metode']]  += (int)$d['biaya'];
                    }
                }
                $res['data'][] = $temp;
            }

            sukses("Ok", $res);
        }
        if ($decode['order'] == "Data hutang") {
            $tahun = tahuns($decode);
            $bulan = bulans();

            $users = [];
            foreach ($decode['divisions'] as $i) {
                $db = ($i == "Ps" || $i == "Billiard" ? "playground" : $i);
                $temp_users = db('transaksi', $db)
                    ->where('metode', "Hutang")
                    ->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
                    ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                    ->groupBy("user_id")
                    ->get()
                    ->getResultArray();

                foreach ($temp_users as $us) {
                    if (!in_array($us['user_id'], $users)) {
                        $users[] = $us['user_id'];
                    }
                }
            }

            $res = [
                'data' => [],
                'tahuns' => $tahun,
                'bulans' => $bulan,
                'Hutang' => 0,
                'sub_menu' => [] //jml hari bulan ini
            ];

            foreach ($users as $u) {
                $temp = ['data' => [], 'total' => 0, 'identitas' => []];
                foreach ($decode['divisions'] as $i) {
                    $db = ($i == "Ps" || $i == "Billiard" ? "playground" : strtolower($i));

                    $dbb = db('transaksi', $db);
                    if ($i == "Ps" || $i == "Billiard") {
                        $dbb->where('jenis', $i);
                    }

                    $data = $dbb->where('user_id', $u)->get()->getResultArray();
                    foreach ($data as $d) {
                        $d['divisi'] = $i;
                        $temp['identitas'] = [
                            'nama'    => $d['nama'],
                            'tgl'     => $d['tgl'],
                            'user_id' => $d['user_id'],
                            'wa' => ''
                        ];
                        $wa = db('user')->where('id', $u)->get()->getRowArray();
                        if ($wa) {
                            $temp['identitas']['wa'] = $wa['wa'];
                        }

                        $temp['data'][] = $d;

                        // jumlahkan biaya langsung
                        $temp['total'] += (int)$d['biaya'];
                        $res['total']  += (int)$d['biaya'];
                    }
                }
                $res['data'][] = $temp;
            }

            sukses("Ok", $res);
        }
    }

    function get_data($decode)
    {

        $data = [];
        foreach ($decode['divisions'] as $d) {

            if ($d == "Kantin" || $d == "Barber") {
                $data[$d] = db('barang', strtolower($d))->orderBy('barang', 'ASC')->get()->getResultArray();
            } else {
                $q = db('games', $decode['db'])->select('games.id as id, iot.id as iot_id,game,games.nama as nama,harga,room,ket,status')->join('iot', 'games.iot_id=iot.id')->where('game', $d)->orderBy('games.id', 'ASC')->get()->getResultArray();
                $val = [];

                foreach ($q as $i) {

                    $q_diskon = db('diskon', $decode['db'])->where('game_id', $i['id'])->orderBy('id', 'ASC')->get()->getResultArray();

                    $diskons = [];

                    foreach ($q_diskon as $s) {
                        $s['is_weekdays'] = is_weekdays();
                        $s['is_pelajar'] = $this->is_pelajar();
                        $diskons[] = $s;
                    }
                    $i['diskons'] = $diskons;
                    $transaksi = db('transaksi', $decode['db'])->where('barang_id', $i['id'])->where("(is_over = 0 OR metode = 'Wl')")->orderBy('tgl', 'DESC')->get()->getRowArray();
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
        return $data;
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
