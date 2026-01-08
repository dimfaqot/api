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
        check($decode);

        if ($decode['order'] == 'Menu') {
            $divisi = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);
            $decode['divisions'] = $divisi;
            sukses("Ok", tahuns($decode), bulans());
        } else {
            if ($decode['jenis'] == "Unlock") {
                $this->unlock($decode);
            } else {
                $data = (($decode['db'] == "playground" || $decode['db'] == "playbox") && $decode['order'] == "laporan" ? $this->data($decode) : get_data($decode));
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
        foreach ($divisions as $dv) {
            $db = ($dv == "Billiard" || $dv == "Ps" ? $decode['db'] : $decode['db'] . '_' . strtolower($dv));
            // sukses($decode);
            $tables = ['transaksi', 'pengeluaran'];
            if ($decode['jenis'] == "All") {
                $temp_data = [];
                foreach ($tables as $i) {
                    $dbb = db($i, $db);
                    $dbb->select('*');
                    if (array_key_exists("lokasi", $decode)) {
                        $dbb->where('lokasi', $decode['lokasi']);
                    }
                    if ($dv == "Billiard" || $dv == "Ps") {
                        $dbb->where(($i == "transaksi" ? "jenis" : "divisi"), $dv);
                    }
                    $res = $dbb->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $data['total'] += (int)$tot;
                    $data[($i == "transaksi" ? "masuk" : "keluar")] += (int)$tot;
                    $temp_data[] = ['judul' => ($i == "transaksi" ? "Masuk" : "Keluar"), 'total' => $tot, 'data' => $res];
                }
                $data['data'][] = ['divisi' => $dv, 'data' => $temp_data];
            }
            // if ($decode['jenis'] == "Harian") {
            //     for ($x = 1; $x <= $jumlahHari; $x++) {
            //         $harian = [];
            //         foreach ($tables as $i) {
            //             $db = db($i, $decode['db']);
            //             $db->select('*');
            //             if (array_key_exists("lokasi", $decode)) {
            //                 $db->where('lokasi', $decode['lokasi']);
            //             }
            //             if ($dv == "Billiard" || $dv == "Ps") {
            //                 $db->where('jenis', $dv);
            //             }
            //             $res = $db->orderBy('tgl', 'ASC')
            //                 ->where("DAY(FROM_UNIXTIME(tgl))", $x)
            //                 ->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
            //                 ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
            //                 ->get()
            //                 ->getResultArray();
            //             $tot = array_sum(array_column($res, 'biaya'));
            //             $total[$i] += $tot;
            //             $harian[] = $tot;
            //         }

            //         $data[] = ['tgl' => $x, 'masuk' => $harian[0], 'keluar' => $harian[1]];
            //     }
            // }
            // if ($decode['jenis'] == "Bulanan") {
            //     foreach (bulans() as $b) {
            //         $bulanan = [];
            //         foreach ($tables as $i) {
            //             $db = db($i, $decode['db']);
            //             $db->select('*');
            //             if (array_key_exists("lokasi", $decode)) {
            //                 $db->where('lokasi', $decode['lokasi']);
            //             }
            //             $res = $db->orderBy('tgl', 'ASC')
            //                 ->where("MONTH(FROM_UNIXTIME(tgl))", $b['satuan'])
            //                 ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
            //                 ->get()
            //                 ->getResultArray();
            //             $tot = array_sum(array_column($res, 'biaya'));
            //             $total[$i] += $tot;
            //             $bulanan[] = $tot;
            //         }

            //         $data[$dv][] = ['tgl' => $b['bulan'], 'masuk' => $bulanan[0], 'keluar' => $bulanan[1]];
            //     }
            // }
            // if ($decode['jenis'] == "Tahunan") {
            //     foreach (tahuns($decode) as $t) {
            //         $tahunan = [];
            //         foreach ($tables as $i) {
            //             $db = db($i, $decode['db']);
            //             $db->select('*');
            //             if (array_key_exists("lokasi", $decode)) {
            //                 $db->where('lokasi', $decode['lokasi']);
            //             }
            //             $res = $db->where("YEAR(FROM_UNIXTIME(tgl))", $t['tahun'])
            //                 ->orderBy('tgl', 'ASC')
            //                 ->get()
            //                 ->getResultArray();
            //             $tot = array_sum(array_column($res, 'biaya'));
            //             $total[$i] += $tot;
            //             $tahunan[] = $tot;
            //         }

            //         $data[$dv] = ['tgl' => $t['tahun'], 'masuk' => $tahunan[0], 'keluar' => $tahunan[1]];
            //     }
            // }
            // if ($decode['jenis'] == "Backup") {
            //     foreach (tahuns($decode) as $t) {
            //         $tahunan = [];
            //         foreach ($tables as $i) {
            //             $db = db($i, $decode['db']);
            //             $db->select('*');
            //             if (array_key_exists("lokasi", $decode)) {
            //                 $db->where('lokasi', $decode['lokasi']);
            //             }
            //             $res = $db->orderBy('tgl', 'ASC')
            //                 ->where("YEAR(FROM_UNIXTIME(tgl))", $t['tahun'])
            //                 ->get()
            //                 ->getResultArray();
            //             $tot = array_sum(array_column($res, 'biaya'));
            //             $total[$i] += $tot;
            //             $tahunan[] = $tot;
            //         }

            //         $data[$dv] = ['tgl' => $t['tahun'], 'masuk' => $tahunan[0], 'keluar' => $tahunan[1]];

            //         $q = db('backup')->where('db', $decode['db'])->where('tahun', $t['tahun'])->get()->getRowArray();

            //         if (!$q) {
            //             $insert = [
            //                 'tahun' => $t['tahun'],
            //                 'masuk' => $tahunan[0],
            //                 'keluar' => $tahunan[1],
            //                 'saldo' => $tahunan[0] - $tahunan[1],
            //                 'keep' => 1
            //             ];
            //             db('backup')->where('db', $decode['db'])->insert($insert);
            //         } else {
            //             if ($q['keep'] == 0) {
            //                 $q['masuk'] = $tahunan[0];
            //                 $q['keluar'] = $tahunan[1];
            //                 $q['saldo'] = $tahunan[0] - $tahunan[1];
            //                 $q['keep'] = 1;
            //                 db('backup')->where('db', $decode['db'])->where('id', $q['id'])->update($q);
            //             }
            //         }

            //         $backup = db('backup')->where('db', $decode['db'])->select('*')->orderBy('tahun', 'ASC')
            //             ->get()
            //             ->getResultArray();
            //         $tot = array_sum(array_column($backup, 'saldo'));

            //         sukses("Ok", $backup, $tot);
            //     }
            // }
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
