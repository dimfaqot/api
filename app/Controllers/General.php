<?php

namespace App\Controllers;

class General extends BaseController
{

    public function copy()
    {
        $dbs = ['batea', 'grosir', 'nineclean', 'playbox', 'cafe'];
        foreach ($dbs as $i) {
            $data = db('profile', $i)->get()->getRowArray();

            $data['db'] = $i;
            unset($data['id']);
            db('profile')->insert($data);
        }
    }
    public function data($dbs, $tabel, $tanggal = "", $lokasi = "", $customer_id = "")
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");


        $total = 0;
        // Ambil data dari database
        $db = db($tabel, $dbs);
        $db->select('*');

        // jika 4 dan tgl y maka tgl == tgl berarti yang kie-4 adalah lokasi

        if ($tabel == "barang") {
            $db;
            if ($tanggal !== "") {
                $db->where('lokasi', str_replace("%", " ", $tanggal));
            }
            $data = $db->orderBy('barang', 'ASC')->get()->getResultArray();
        } else {
            // Parse tanggal: format YYYY-MM-DD[-customer_id]
            $exp = explode("-", $tanggal);
            [$tahun, $bulan, $tgl] = array_pad($exp, 3, '');

            // Validasi minimal
            if ($tahun == "") {
                gagal("Format tanggal tidak valid. minimal harus ada tahun");
                return;
            }
            // Filter waktu
            $db->where("YEAR(FROM_UNIXTIME(tgl))", $tahun);

            // validasi bulan jika count $exp > 1
            if ($bulan !== '') {
                $db->where("MONTH(FROM_UNIXTIME(tgl))", $bulan);
            }
            // validasi tanggal jika $order == y dan $tgl !== ""
            if ($tgl !== '') {
                $db->where("DAY(FROM_UNIXTIME(tgl))", $tgl);
            }
            // validasi customer_id jika $order == n dan $tgl !== ""
            if ($customer_id !== '') {
                $db->where(($dbs == "grosir" ? "customer_id" : "user_id"), $customer_id);
            }
            if ($lokasi !== '') {
                $db->where("lokasi", str_replace("%", " ",  $lokasi));
            }

            // Ambil dan hitung
            $data = $db->get()->getResultArray();
            $total = array_sum(array_column($data, 'biaya'));
        }

        sukses("Sukses", $data, $total);
    }
    public function rangkuman($dbs, $tahun, $lokasi = '')
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $data = rekapTahunan($dbs, $tahun, $lokasi);

        sukses("Sukses", $data);
    }

    public function profile($dbs)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        $db = db('profile', $dbs);
        $profile = $db->get()->getRowArray();

        $db = db('pengeluaran', $dbs);
        $data =  $db->select('*')->where('jenis', 'Modal')->orderBy('tgl', 'ASC')->get()->getResultArray();
        $total = array_sum(array_column($data, 'biaya'));

        sukses('Sukses', $profile, $data, $total);
    }

    public function menu($menu = '')
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        $db = db('menu', 'bkw');
        if ($menu !== '') {
            $db->where('menu', $menu);
        }
        $profile = $db->orderBy('menu', 'ASC')->get()->getResultArray();

        $data = [];
        foreach ($profile as $i) {
            $sub = db('sub_menu', 'bkw')->where('menu_id', $i['id'])->get()->getResultArray();

            if ($sub) {
                foreach ($sub as $s) {
                    $q_unit = db('unit', 'bkw')->where('sub_menu_id', $s['id'])->get()->getResultArray();
                    $s['unit'] = ($q_unit ? $q_unit : []);
                    $i['sub_menu'][] = $s;
                }
            } else {
                $i['sub_menu'] = [];
            }


            $data[] = $i;
        }
        sukses('Sukses', $data);
    }
}
