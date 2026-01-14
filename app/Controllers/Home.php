<?php

namespace App\Controllers;

class Home extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);
        $decode['sub_db'] = $decode['db'];

        check($decode);

        if ($decode['order'] == 'Menu') {
            $divisi = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);
            $decode['divisions'] = $divisi;
            sukses("Ok", tahuns($decode), bulans(), $divisi);
        } else {
            if ($decode['jenis'] == "Unlock") {
                $this->unlock($decode);
            } else {
                $data = ($decode['db'] == "playground" || $decode['db'] == "playbox" ? $this->data($decode) : get_data($decode));
                if ($decode['db'] == "playground" || $decode['db'] == "playbox") {
                    sukses("Ok", $data);
                } else {
                    sukses("Ok", $data['data'], $data['total'], $data['sub_menu']);
                }
            }
        }
    }

    function data($decode)
    {
        // sukses($decode);
        $divisions = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);

        $data = ['total' => 0, 'masuk' => 0, 'keluar' => 0];
        $sub_menu = ['Harian', 'Bulanan', 'Tahunan'];

        $jumlahHari = cal_days_in_month(CAL_GREGORIAN, $decode['bulan'], $decode['tahun']);
        $data['sub_menu'] = $sub_menu;
        $tables = ['transaksi', 'pengeluaran'];
        if ($decode['order'] == "laporan" && $decode['jenis'] == "Harian") {
            for ($x = 1; $x <= $jumlahHari; $x++) {
                $temp_data = [];
                foreach ($divisions as $dv) {
                    $db = ($dv == "Billiard" || $dv == "Ps" ? $decode['db'] : $decode['db'] . '_' . strtolower($dv));

                    $temp_1 = [];
                    foreach ($tables as $i) {
                        $judul = $i == "transaksi" ? "masuk" : "keluar";
                        $dbb = db($i, $db);
                        $dbb->select('*');

                        if (array_key_exists("lokasi", $decode)) {
                            $dbb->where('lokasi', $decode['lokasi']);
                        }
                        if ($dv == "Billiard" || $dv == "Ps") {
                            $dbb->where(($i == "transaksi" ? "jenis" : "divisi"), $dv);
                        }
                        $res = $dbb->orderBy('tgl', 'ASC')
                            ->where("DAY(FROM_UNIXTIME(tgl))", $x)
                            ->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
                            ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                            ->get()
                            ->getResultArray();
                        $tot = array_sum(array_column($res, 'biaya'));
                        $data['total'] += (int)$tot;
                        $data[$judul] += (int)$tot;
                        $temp_1[$judul] = $tot;
                    }
                    $temp_data[] = ['divisi' => $dv, 'masuk' => $temp_1['masuk'], 'keluar' => $temp_1['keluar'], 'total' => $temp_1['masuk'] - $temp_1['keluar']];
                }
                $data['data'][] = ['tgl' => $x, 'data' => $temp_data];
            }
        } elseif ($decode['order'] == "laporan" && $decode['jenis'] == "Tahunan") {
            $decode['divisions'] = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);

            foreach (tahuns($decode) as $t) {
                $temp_data = [];
                foreach ($divisions as $dv) {
                    $db = ($dv == "Billiard" || $dv == "Ps" ? $decode['db'] : $decode['db'] . '_' . strtolower($dv));

                    $temp_1 = [];
                    foreach ($tables as $i) {
                        $judul = $i == "transaksi" ? "masuk" : "keluar";
                        $dbb = db($i, $db);
                        $dbb->select('*');

                        if (array_key_exists("lokasi", $decode)) {
                            $dbb->where('lokasi', $decode['lokasi']);
                        }
                        if ($dv == "Billiard" || $dv == "Ps") {
                            $dbb->where(($i == "transaksi" ? "jenis" : "divisi"), $dv);
                        }

                        $dbb->where("YEAR(FROM_UNIXTIME(tgl))", $t['tahun']);
                        $res = $dbb->orderBy('tgl', 'ASC')

                            ->get()
                            ->getResultArray();
                        $tot = array_sum(array_column($res, 'biaya'));
                        $data['total'] += (int)$tot;
                        $data[$judul] += (int)$tot;
                        $temp_1[$judul] = $tot;
                    }
                    $temp_data[] = ['divisi' => $dv, 'masuk' => $temp_1['masuk'], 'keluar' => $temp_1['keluar'], 'total' => $temp_1['masuk'] - $temp_1['keluar']];
                }
                $data['data'][] = ['tahun' => $t['tahun'], 'data' => $temp_data];
            }
        } elseif ($decode['order'] == "laporan" && $decode['jenis'] == "Bulanan") {
            foreach (bulans() as $b) {
                $temp_data = [];
                foreach ($divisions as $dv) {
                    $db = ($dv == "Billiard" || $dv == "Ps" ? $decode['db'] : $decode['db'] . '_' . strtolower($dv));

                    $temp_1 = [];
                    foreach ($tables as $i) {
                        $judul = $i == "transaksi" ? "masuk" : "keluar";
                        $dbb = db($i, $db);
                        $dbb->select('*');

                        if (array_key_exists("lokasi", $decode)) {
                            $dbb->where('lokasi', $decode['lokasi']);
                        }
                        if ($dv == "Billiard" || $dv == "Ps") {
                            $dbb->where(($i == "transaksi" ? "jenis" : "divisi"), $dv);
                        }
                        $res = $dbb->orderBy('tgl', 'ASC')
                            ->where("MONTH(FROM_UNIXTIME(tgl))", $b['satuan'])
                            ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                            ->get()
                            ->getResultArray();
                        $tot = array_sum(array_column($res, 'biaya'));
                        $data['total'] += (int)$tot;
                        $data[$judul] += (int)$tot;
                        $temp_1[$judul] = $tot;
                    }
                    $temp_data[] = ['divisi' => $dv, 'masuk' => $temp_1['masuk'], 'keluar' => $temp_1['keluar'], 'total' => $temp_1['masuk'] - $temp_1['keluar']];
                }
                $data['data'][] = ['bulan' => $b['bulan'], 'data' => $temp_data];
            }
        } else {
            $judul = ($decode['order'] == "transaksi" ? "masuk" : "keluar");

            $dbb = ($decode['jenis'] == "Billiard" || $decode['jenis'] == "Ps" ? $decode['db'] : $decode['db'] . '_' . strtolower($decode['jenis']));
            $temp_data = [];


            $db = db($decode['order'], $dbb);
            $db->select('*');
            if (array_key_exists("lokasi", $decode)) {
                $db->where('lokasi', $decode['lokasi']);
            }
            if ($decode['jenis'] == "Billiard" || $decode['jenis'] == "Ps") {
                $db->where(($decode['order'] == "transaksi" ? "jenis" : "divisi"), $decode['jenis']);
            }
            $res = $db->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
                ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                ->get()
                ->getResultArray();
            $tot = array_sum(array_column($res, 'biaya'));
            $data['total'] += (int)$tot;
            $data[($decode['order'] == "transaksi" ? "masuk" : "keluar")] += (int)$tot;
            $temp_data[] = ['judul' => ($decode['order'] == "transaksi" ? "Masuk" : "Keluar"), 'total' => $tot, 'data' => $res];

            $data['data'][] = ['divisi' => $decode['db'], 'data' => $temp_data];
        }

        return $data;
    }

    function unlock($decode)
    {
        $keep = ($decode['keep'] == 0 ? 1 : 0);

        $q = db('backup', $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

        if (!$q) {
            gagal("Id not found");
        }

        $q['keep'] = $keep;

        if (db('backup', $decode['db'])->where('id', $q['id'])->update($q)) {
            $backup = db('backup', $decode['db'])->select('*')->orderBy('tahun', 'ASC')
                ->get()
                ->getResultArray();
            $tot = array_sum(array_column($backup, 'saldo'));

            sukses("Ok", $backup, $tot);
        } else {
            gagal("Unlock gagal");
        }
    }
}
