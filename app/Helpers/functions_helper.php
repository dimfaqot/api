<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function db($tabel, $db = null)
{
    if ($db == null || $db == 'playground') {
        $db = \Config\Database::connect();
    } else {
        $db = \Config\Database::connect(strtolower(str_replace(" ", "_", $db)));
    }
    $db = $db->table($tabel);

    return $db;
}

function sukses($pesan, $data = null, $data2 = null, $data3 = null, $data4 = null)
{
    $data = [
        'status' => '200',
        'message' => $pesan,
        'data' => $data,
        'data2' => $data2,
        'data3' => $data3,
        'data4' => $data4
    ];

    echo json_encode($data);
    die;
}

function gagal($pesan)
{
    $data = [
        'status' => '400',
        'message' => "Gagal!. " . $pesan
    ];

    echo json_encode($data);
    die;
}

function bulans($req = null)
{
    $bulan = [
        ['romawi' => 'I', 'bulan' => 'Januari', 'angka' => '01', 'satuan' => 1],
        ['romawi' => 'II', 'bulan' => 'Februari', 'angka' => '02', 'satuan' => 2],
        ['romawi' => 'III', 'bulan' => 'Maret', 'angka' => '03', 'satuan' => 3],
        ['romawi' => 'IV', 'bulan' => 'April', 'angka' => '04', 'satuan' => 4],
        ['romawi' => 'V', 'bulan' => 'Mei', 'angka' => '05', 'satuan' => 5],
        ['romawi' => 'VI', 'bulan' => 'Juni', 'angka' => '06', 'satuan' => 6],
        ['romawi' => 'VII', 'bulan' => 'Juli', 'angka' => '07', 'satuan' => 7],
        ['romawi' => 'VIII', 'bulan' => 'Agustus', 'angka' => '08', 'satuan' => 8],
        ['romawi' => 'IX', 'bulan' => 'September', 'angka' => '09', 'satuan' => 9],
        ['romawi' => 'X', 'bulan' => 'Oktober', 'angka' => '10', 'satuan' => 10],
        ['romawi' => 'XI', 'bulan' => 'November', 'angka' => '11', 'satuan' => 11],
        ['romawi' => 'XII', 'bulan' => 'Desember', 'angka' => '12', 'satuan' => 12]
    ];

    $res = $bulan;
    foreach ($bulan as $i) {
        if ($i['bulan'] == $req) {
            $res = $i;
        } elseif ($i['angka'] == $req) {
            $res = $i;
        } elseif ($i['satuan'] == $req) {
            $res = $i;
        } elseif ($i['romawi'] == $req) {
            $res = $i;
        }
    }
    return $res;
}

function rekapTahunan($dbs, $tahun, $lokasi)
{

    $data = [];

    for ($bulan = 1; $bulan <= 12; $bulan++) {
        // Pemasukan
        $db = db('transaksi', $dbs);
        $db->select('*');
        if ($lokasi !== '') {
            $db->where('lokasi', $lokasi);
        }
        $masuk = $db->where("MONTH(FROM_UNIXTIME(tgl))", $bulan)
            ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun)
            ->get()->getResultArray();

        $totalMasuk = array_sum(array_column($masuk, 'biaya'));

        // Pengeluaran
        $db = db('pengeluaran', $dbs);
        $db->select('*');
        if ($lokasi !== '') {
            $db->where('lokasi', $lokasi);
        }
        $keluar = $db->whereNotIn('jenis', ["Inv", "Modal"])
            ->where("MONTH(FROM_UNIXTIME(tgl))", $bulan)
            ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun)
            ->get()->getResultArray();

        $totalKeluar = array_sum(array_column($keluar, 'biaya'));

        $data[] = [
            "bulan"  => bulans($bulan)['bulan'],
            "masuk"  => $totalMasuk,
            "keluar" => $totalKeluar
        ];
    }

    return $data;
}

function tahuns($decode)
{
    $db = db($decode['tabel'], $decode['db']);
    if (array_key_exists('lokasi', $decode)) {
        $db->where('lokasi', $decode['lokasi']);
    }
    $db->select("YEAR(FROM_UNIXTIME(tgl)) AS tahun");
    $db->groupBy("tahun");
    $db->orderBy("tahun", "ASC");

    $query = $db->get();
    $results = $query->getResultArray();
    return $results;
}

function clear($text)
{
    $text = trim($text);
    $text = htmlspecialchars($text);
    return $text;
}

function options($db, $kategori)
{
    $q = db('options', $db)->where("kategori", upper_first($kategori))->orderBy("value", "ASC")->get()->getResultArray();

    return $q;
}

function upper_first($text)
{
    if ($text == "") {
        return "";
    }
    $text = clear($text);
    $exp = explode(" ", $text);

    $val = [];
    foreach ($exp as $i) {
        $lower = strtolower($i);
        $val[] = ucfirst($lower);
    }

    return implode(" ", $val);
}

function get_data($dbs, $order, $tahun, $bulan, $jenis, $lokasi)
{

    $data = [];
    $sub_menu = ['Harian', 'Bulanan', 'Tahunan'];
    if ($order == 'laporan') {
        $jumlahHari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $tables = ['transaksi', 'pengeluaran'];
        $total = ['transaksi' => 0, 'pengeluaran' => 0];
        if ($jenis == "All") {
            foreach ($tables as $i) {
                $db = db($i, $dbs);
                $db->select('*');
                if ($lokasi !== '') {
                    $db->where('lokasi', $lokasi);
                }
                $res = $db->where("MONTH(FROM_UNIXTIME(tgl))", $bulan)
                    ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun)
                    ->get()
                    ->getResultArray();
                $tot = array_sum(array_column($res, 'biaya'));
                $data[$i] = ['total' => $tot, 'data' => $res];
            }
        }
        if ($jenis == "Harian") {
            for ($x = 1; $x <= $jumlahHari; $x++) {
                $harian = [];
                foreach ($tables as $i) {
                    $db = db($i, $dbs);
                    $db->select('*');
                    if ($lokasi !== '') {
                        $db->where('lokasi', $lokasi);
                    }
                    $res = $db->orderBy('tgl', 'ASC')
                        ->where("DAY(FROM_UNIXTIME(tgl))", $x)
                        ->where("MONTH(FROM_UNIXTIME(tgl))", $bulan)
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun)
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $harian[] = $tot;
                }

                $data[] = ['tgl' => $x, 'masuk' => $harian[0], 'keluar' => $harian[1]];
            }
        }
        if ($jenis == "Bulanan") {
            foreach (bulans() as $b) {
                $bulanan = [];
                foreach ($tables as $i) {
                    $db = db($i, $dbs);
                    $db->select('*');
                    if ($lokasi !== '') {
                        $db->where('lokasi', $lokasi);
                    }
                    $res = $db->orderBy('tgl', 'ASC')
                        ->where("MONTH(FROM_UNIXTIME(tgl))", $b['satuan'])
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun)
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $bulanan[] = $tot;
                }

                $data[] = ['tgl' => $b['bulan'], 'masuk' => $bulanan[0], 'keluar' => $bulanan[1]];
            }
        }
        if ($jenis == "Tahunan") {
            foreach (tahuns($dbs, 'transaksi', $lokasi) as $t) {
                $tahunan = [];
                foreach ($tables as $i) {
                    $db = db($i, $dbs);
                    $db->select('*');
                    if ($lokasi !== '') {
                        $db->where('lokasi', $lokasi);
                    }
                    $res = $db->orderBy('tgl', 'ASC')
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $t['tahun'])
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $tahunan[] = $tot;
                }

                $data[] = ['tgl' => $t['tahun'], 'masuk' => $tahunan[0], 'keluar' => $tahunan[1]];
            }
        }
        if ($jenis == "Backup") {
            foreach (tahuns($dbs, 'transaksi', $lokasi) as $t) {
                $tahunan = [];
                foreach ($tables as $i) {
                    $db = db($i, $dbs);
                    $db->select('*');
                    if ($lokasi !== '') {
                        $db->where('lokasi', $lokasi);
                    }
                    $res = $db->orderBy('tgl', 'ASC')
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $t['tahun'])
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $tahunan[] = $tot;
                }

                $data[] = ['tgl' => $t['tahun'], 'masuk' => $tahunan[0], 'keluar' => $tahunan[1]];

                $q = db('backup', $dbs)->where('tahun', $t['tahun'])->get()->getRowArray();

                if (!$q) {
                    $insert = [
                        'tahun' => $t['tahun'],
                        'masuk' => $tahunan[0],
                        'keluar' => $tahunan[1],
                        'saldo' => $tahunan[0] - $tahunan[1],
                        'keep' => 1
                    ];
                    db('backup', $dbs)->insert($insert);
                } else {
                    if ($q['keep'] == 0) {
                        $q['masuk'] = $tahunan[0];
                        $q['keluar'] = $tahunan[1];
                        $q['saldo'] = $tahunan[0] - $tahunan[1];
                        $q['keep'] = 1;
                        db('backup', $dbs)->where('id', $q['id'])->update($q);
                    }
                }

                $backup = db('backup', $dbs)->select('*')->orderBy('tahun', 'ASC')
                    ->get()
                    ->getResultArray();
                $tot = array_sum(array_column($backup, 'saldo'));

                sukses("Ok", $backup, $tot);
            }
        }
    } else {
        $sub_menu = [];

        if ($order == 'pengeluaran') {
            foreach (options($dbs, "Inv") as $i) {
                $sub_menu[] = $i['value'];
            }
        }
        if ($order == 'transaksi') {
            foreach (options($dbs, "Kantin") as $i) {
                $sub_menu[] = $i['value'];
            }
        }

        $db = db($order, $dbs);
        $db->select('*');
        if ($jenis !== "All") {
            $db->where('jenis', $jenis);
        }
        if ($lokasi !== '') {
            $db->where('lokasi', $lokasi);
        }
        $db->orderBy('tgl', 'ASC')
            ->where("MONTH(FROM_UNIXTIME(tgl))", $bulan)
            ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun);
        if ($order == "hutang") {
            $db->orderBy('nama', 'ASC');
        }
        $data = $db->get()
            ->getResultArray();
        $total = array_sum(array_column($data, 'biaya'));
    }

    $res = ['data' => $data, 'total' => $total, 'sub_menu' => $sub_menu];
    return $res;
}

function profile($dbs)
{
    return db('profile', $dbs)->get()->getRowArray();
}

function angka($uang)
{
    return number_format($uang, 0, ",", ".");
}

function encode_jwt($data)
{

    $jwt = JWT::encode($data, getenv("KEY_JWT"), 'HS256');

    return $jwt;
}

function decode_jwt($encode_jwt)
{
    try {

        $decoded = JWT::decode($encode_jwt, new Key(getenv("KEY_JWT"), 'HS256'));
        $arr = (array)$decoded;

        return $arr;
    } catch (\Exception $e) { // Also tried JwtException
        $data = [
            'status' => '400',
            'message' => $e->getMessage()
        ];

        echo json_encode($data);
        die;
    }
}

function random_str($length = 14)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}


function next_invoice($order = null)
{

    $db = db('nota');

    $year  = date('Y');
    $month = date('m');
    $prefix = "$year/$month/";

    // Cari no_nota terakhir berdasarkan bulan ini
    $lastNota = $db->select('no_nota')
        ->orderBy('tgl', 'DESC')
        ->get()
        ->getRowArray();

    if ($order == "inv") {
        $lastNota = $db->select('inv')
            ->orderBy('tgl', 'DESC')
            ->get()
            ->getRowArray();
    }


    $nextNumber = 1;
    if ($lastNota) {
        $parts = explode('/', $lastNota['no_nota']);
        $lastNumber = end($parts);
        $nextNumber = (int)$lastNumber + 1;
    }

    $nota = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

    if ($order == "hutang") {
        $nota = $prefix . random_str(6);
    }

    return $nota;
}


function check($decode, $role = "Root", $roles = [])
{
    if (!$decode['login'] || $decode['login'] == "" || $decode['login'] == "null") {
        gagal("Login first");
    }

    if (count($roles) === 0) {
        if ($decode['admin'] !== "Root") {
            gagal("Role disallowed");
        }
    } else {
        if (!in_array($role, $roles)) {
            gagal("Role disallowed");
        }
    }

    if (time() > ((int)$decode['time'] + 60)) {
        gagal("Token expired");
    }
}
function settings($db, $nama = null)
{
    if ($nama == null) {
        return db('settings', $db)->orderBy('nama', 'ASC')->get()->getResultArray();
    } else {
        return db('settings', $db)->where('nama', $nama)->get()->getRowArray()['value'];
    }
}

function uang_modal($db)
{

    $data = db('pengeluaran', $db)->select('*')->where('jenis', "Modal")->orderBy('tgl', 'DESC')->orderBy('tgl', 'DESC')->get()->getResultArray();

    $total = array_sum(array_column($data, 'biaya'));

    $res = ['total' => $total, 'data' => $data];

    return $res;
}

function angka_to_int($uang)
{
    $uang = str_replace("Rp. ", "", $uang);
    $uang = str_replace(".", "", $uang);
    return $uang;
}

function delete($decode, $roles = [])
{
    if (count($roles) > 0) {

        if (!in_array($decode['admin'], $roles)) {
            gagal("Role not allowed");
        }
    }
    $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

    if (!$q) {
        gagal("Id not found");
    }

    // Simpan data
    db($decode['tabel'], $decode['db'])->where('id', $q['id'])->delete()
        ? sukses('Sukses')
        : gagal('Gagal');
}

function lists($decode)
{
    $tahun = clear($decode['tahun']);
    $bulan = clear($decode['bulan']);
    $jenis = clear($decode['jenis']);

    $filters = $decode['filters'];

    $db = db($decode['tabel'], $decode['db']);
    $db->select('*');
    if ($jenis == "All") {
        $db->whereIn('jenis', $filters);
    } else {
        $db->where('jenis', $jenis);
    }

    if (array_key_exists('lokasi', $decode)) {
        $db->where('lokasi', $decode['lokasi']);
    }

    $data = $db->orderBy('updated_at', 'DESC')
        ->where("MONTH(FROM_UNIXTIME(tgl))", $bulan)
        ->where("YEAR(FROM_UNIXTIME(tgl))", $tahun)
        ->get()
        ->getResultArray();
    $total = array_sum(array_column($data, 'biaya'));


    sukses("Ok", $data, $total);
}

function cari_barang($decode)
{
    $text = clear($decode['text']);
    $filters = $decode['filters'];
    $db = db('barang', $decode['db']);

    if (array_key_exists('lokasi', $decode)) {
        $db->where('lokasi', $decode['lokasi']);
    }

    $data = $db->whereIn('jenis', $filters)->like("barang", $text, "both")->orderBy('barang', 'ASC')->limit(7)->get()->getResultArray();


    sukses("Ok", $data);
}
