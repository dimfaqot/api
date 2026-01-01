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
            $data = [];
            foreach ($decode['datas'] as $i) {
                $temp = ['id' => $i, 'waktu' => "00:00", 'roleplay' => 'Open', 'barang' => ''];

                $q = db('transaksi', $decode['db'])->where('id', $i)->get()->getRowArray();
                if ($q) {
                    $temp['waktu'] = $this->hitung_waktu($q['start'], $q['end'], $q['qty']);
                    $temp['roleplay'] = $q['roleplay'];
                    $temp['divisi'] = $q['jenis'];
                    $temp['barang'] = $q['barang'];
                    $temp['no_nota'] = $q['no_nota'];
                    $temp['barang_id'] = $q['barang_id'];
                    $temp['biaya'] = ($q['roleplay'] == "Paket" || $q['roleplay'] == "Normal" ? $q['biaya'] : $this->hitung_biaya($q));
                }
                $res[] = $temp;
            }

            foreach ($res as $r) {
                $temp = ['data' => [], 'total' => 0, 'identitas' => []];
                foreach ($decode['divisions'] as $i) {
                    $db = ($i == "Ps" || $i == "Billiard" ? "playground" : $i);

                    $dbb = db('transaksi', $db);
                    $dbb->where('no_nota', $r['no_nota']);
                    if ($i == "Ps" || $i == "Billiard") {
                        $dbb->where('jenis', $i);
                    }
                    $q = $dbb->get()->getResultArray();
                    foreach ($q as $d) {
                        $d['divisi'] = $i;
                        if ($i == "Ps" || $i == "Billiard") {
                            if ($d['qty'] == 0) {
                                $d['biaya'] = $this->hitung_biaya($d);
                            }
                            $d['waktu'] = $this->hitung_waktu($d['start'], $d['end'], $d['qty']);
                        }
                        $temp['identitas'] = [
                            'nama'    => $d['nama'],
                            'tgl'     => $d['tgl'],
                            'user_id' => $d['user_id'],
                            'no_nota' => $r['no_nota'],
                            'wa' => ''
                        ];
                        $wa = db('user')->where('id', $d['user_id'])->get()->getRowArray();
                        if ($wa) {
                            $temp['identitas']['wa'] = $wa['wa'];
                        }

                        $temp['data'][] = $d;

                        // jumlahkan biaya langsung
                        $temp['total'] += (int)$d['biaya'];
                    }
                }

                $data[] = $temp;
            }


            $db = \Config\Database::connect();
            $db->transStart();
            $wl = db('transaksi', $decode['db'])->where('metode', "Wl")->get()->getResultArray();
            foreach ($wl as $i) {
                if ((int)$i['start'] <= time()) {
                    $i['metode'] = "Hutang";
                    $i['tgl'] = $i['start'];
                    if (!db('transaksi', $decode['db'])->where('id', $i['id'])->update($i)) {
                        gagal('Wl gagal dimulai');
                    }

                    $iot = db('iot', $decode['db'])->select('iot.id as id')->join('games', 'iot.id=games.iot_id')->where('games.id', $i['barang_id'])->get()->getRowArray();
                    if ($iot) {
                        $iot['status'] = 1;
                        $iot['end'] = $i['end'];
                        $iot['transaksi_id'] = $i['id'];
                        if (!db('iot', $decode['db'])->where('id', $iot['id'])->update($iot)) {
                            gagal("Lampu wl gagal nyala");
                        }

                        $temp = [
                            'id' => $i['id'],
                            'waktu' => $this->hitung_waktu($i['start'], $i['end'], $i['qty']),
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
                return sukses("Sukses", $res, $data);
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
                        if ($i == "Ps" || $i == "Billiard") {
                            if ($d['qty'] == 0) {
                                $d['biaya'] = $this->hitung_biaya($d);
                            }
                            $d['waktu'] = $this->hitung_waktu($d['start'], $d['end'], $d['qty']);
                        }
                        $temp['identitas'] = [
                            'nama'    => $d['nama'],
                            'tgl'     => $d['tgl'],
                            'user_id' => $d['user_id'],
                            'no_nota' => $n,
                            'wa' => ''
                        ];
                        $wa = db('user')->where('id', $d['user_id'])->get()->getRowArray();
                        if ($wa) {
                            $temp['identitas']['wa'] = $wa['wa'];
                        }

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

            $users = [];
            foreach ($decode['divisions'] as $i) {
                $db = ($i == "Ps" || $i == "Billiard" ? "playground" : $i);
                $temp_users = db('transaksi', $db)
                    ->where('metode', "Hutang")
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
                "total" => 0,
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
                        if ($i == "Ps" || $i == "Billiard") {
                            if ($d['qty'] == 0) {
                                $d['biaya'] = $this->hitung_biaya($d);
                            }
                            $d['waktu'] = $this->hitung_waktu($d['start'], $d['end'], $d['qty']);
                        }
                        $temp['identitas'] = [
                            'nama'    => $d['nama'],
                            'tgl'     => $d['tgl'],
                            'user_id' => $u,
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
        if ($decode['order'] == "lampu") {
            $q = db('games', $decode['db'])->select('games.id as id, iot.id as iot_id')->join('iot', 'games.iot_id=iot.id')->where('games.id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id games not found");
            }

            $update = ['status' => 0, 'end' => 0, 'transaksi_id' => 0];
            if (!db('iot', $decode['db'])->where('id', $q['iot_id'])->update($update)) {
                gagal("Update iot gagal");
            }
            sukses("Sukses", $this->get_data($decode));
        }

        if ($decode['order'] == "jam") {
            $db = \Config\Database::connect();
            $db->transStart();
            $transaksi = db('transaksi', $decode['db'])->where('id', $decode['id'])->get()->getRowArray();
            if (!$transaksi) {
                gagal("Id transaksi not found");
            }
            $decs_diskons = explode(",", $transaksi['desc_diskons']);

            $q_diskon = db('diskon', $decode['db'])->where('game_id', $transaksi['barang_id'])->get()->getResultArray();

            if (!$q_diskon) {
                gagal("Diskon not found");
            }
            $diskonWeekdays = in_array("Weekdays", $decs_diskons ? $q_diskon[array_search('Weekdays', array_column($q_diskon, 'nama'))]['diskon'] ?? 0 : 0);
            $diskonPelajar = in_array("Pelajar", $decs_diskons ? $q_diskon[array_search('Pelajar', array_column($q_diskon, 'nama'))]['diskon'] ?? 0 : 0);
            $diskonGirls = in_array("Girls", $decs_diskons ? $q_diskon[array_search('Pelajar', array_column($q_diskon, 'nama'))]['diskon'] ?? 0 : 0);

            $transaksi['qty'] += (int)$decode['jam'];
            $transaksi['end'] += ((int)$decode['jam'] * 60 * 60);
            $transaksi['total'] = (int)$transaksi['harga'] * $transaksi['qty'];
            $transaksi['diskon'] = ($diskonWeekdays * $transaksi['qty']) + ($diskonPelajar * $transaksi['qty']) + ($diskonGirls > 0 ? $transaksi['harga'] - $diskonGirls : $transaksi['harga']);
            $transaksi['biaya'] = (int)$transaksi['total'] - (int)$transaksi['diskon'];

            if (!db('transaksi', $decode['db'])->where('id', $decode['id'])->update($transaksi)) {
                gagal("Update transaksi gagal");
            }

            $q = db('games', $decode['db'])->select('games.id as id, iot.id as iot_id')->join('iot', 'games.iot_id=iot.id')->where('games.id', $transaksi['barang_id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id games not found");
            }

            $update = ['status' => 1, 'end' => $transaksi['end'], 'transaksi_id' => $transaksi['id']];

            if (!db('iot', $decode['db'])->where('id', $q['iot_id'])->update($update)) {
                gagal("Update iot gagal");
            }

            $db->transComplete();
            $db->transStatus()
                ? sukses("Sukses", $this->get_data($decode))
                : gagal("Gagal");
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
                        $i['waktu'] = $this->hitung_waktu($transaksi['start'], $transaksi['end'], $transaksi['qty']);
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

    function hitung_waktu(int $start, int $end, int $qty): string
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
    function hitung_biaya($q)
    {
        $tarifPerJam = $q['harga'] - $q['diskon']; // harga per jam setelah diskon
        $start       = $q['start'];                // unix timestamp

        $now = time();
        $durasiDetik = $now - $start;
        $durasiMenit = $durasiDetik / 60;

        // jika masih bermain < 1 menit
        if ($durasiMenit < 1) {
            return 1000;
        }

        // tarif per menit (float)
        $tarifPerMenit = $tarifPerJam / 60;
        $biaya = $durasiMenit * $tarifPerMenit;

        // jika tepat 60 menit → harus persis tarif per jam
        if (floor($durasiMenit) == 60) {
            $biaya = $tarifPerJam;
        }

        // jika durasi < 120 menit → minimal bayar 2 jam
        if ($durasiMenit < 120) {
            $biaya = 2 * $tarifPerJam;
        }

        // bulatkan ke atas ke ribuan
        return (int)ceil($biaya / 1000) * 1000;
    }

    function is_pelajar()
    {
        // Ambil jam saat ini (format 24 jam)
        $hour = (int)date('H');

        // Cek apakah jam antara 12 sampai 16 (karena 17:00 sudah lewat)
        return ($hour >= 12 && $hour < 17);
    }
}
