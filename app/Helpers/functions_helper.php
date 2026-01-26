<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function db($tabel, $db = null)
{
    if ($db == null || $db == 'bkw') {
        $db = \Config\Database::connect();
    } else {
        $db = \Config\Database::connect(strtolower(str_replace(" ", "_", $db)));
    }
    $db = $db->table($tabel);

    return $db;
}

function sukses($pesan, $data = null, $data2 = null, $data3 = null, $data4 = null, $data5 = null)
{
    $data = [
        'status' => '200',
        'message' => $pesan,
        'data' => $data,
        'data2' => $data2,
        'data3' => $data3,
        'data4' => $data4,
        'data5' => $data5
    ];

    echo json_encode($data);
    die;
}

function gagal($pesan)
{
    $data = [
        'status' => '400',
        'message' => $pesan
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
    $result = [];
    if ($decode['db'] == "playground" || $decode['db'] == "playbox") {
        $temp_result = [];
        foreach ($decode['divisions'] as $i) {
            $dbb = ($i == "Billiard" || $i == "Ps" ? $decode['db'] : $decode['db'] . "_" . strtolower($i));
            $db = db('transaksi', $dbb);
            if (array_key_exists('lokasi', $decode)) {
                $db->where('lokasi', $decode['lokasi']);
            }
            if ($i == "Billiard" || $i == "Ps") {
                $db->where('jenis', $i);
            }
            $db->select("YEAR(FROM_UNIXTIME(tgl)) AS tahun");
            $db->groupBy("tahun");
            $db->orderBy("tahun", "ASC");

            $query = $db->get();
            $item_results = $query->getResultArray();
            foreach ($item_results as $t) {
                if (!in_array($t['tahun'], $temp_result)) {
                    $temp_result[] = $t['tahun'];
                }
            }
        }

        foreach ($temp_result as $i) {
            $result[] = ['tahun' => $i];
        }
    } else {
        $db = db('transaksi', $decode['db']);
        if (array_key_exists('lokasi', $decode)) {
            $db->where('lokasi', $decode['lokasi']);
        }
        $db->select("YEAR(FROM_UNIXTIME(tgl)) AS tahun");
        $db->groupBy("tahun");
        $db->orderBy("tahun", "ASC");

        $query = $db->get();
        $result = $query->getResultArray();
    }
    return $result;
}

function clear($text)
{
    $text = trim($text);
    $text = htmlspecialchars($text);
    return $text;
}


function options($decode)
{

    $db = db('options');
    $db->where('db', $decode['db'])->where("kategori", $decode['kategori']);
    if (array_key_exists("order_by", $decode)) {
        $db->orderBy($decode['order_by'], 'ASC');
    } else {
        $db->orderBy("value", "ASC");
    }
    $q = $db->get()->getResultArray();

    $data = [];
    foreach ($q as $i) {
        $data[] = $i['value'];
    }

    if ($decode['format'] == "array") {
        return $data;
    } elseif ($decode['format'] == "text") {
        return implode(",", $data);
    } else {
        return $q;
    }
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

function get_data($decode)
{

    $data = [];
    $sub_menu = ['Harian', 'Bulanan', 'Tahunan'];
    if ($decode['order'] == 'laporan') {
        $jumlahHari = cal_days_in_month(CAL_GREGORIAN, $decode['bulan'], $decode['tahun']);
        $tables = ['transaksi', 'pengeluaran'];
        $total = ['transaksi' => 0, 'pengeluaran' => 0];
        if ($decode['jenis'] == "All") {
            foreach ($tables as $i) {
                $db = db($i, $decode['db']);
                $db->select('*');
                if (array_key_exists("lokasi", $decode)) {
                    $db->where('lokasi', $decode['lokasi']);
                }
                $res = $db->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
                    ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                    ->get()
                    ->getResultArray();
                $tot = array_sum(array_column($res, 'biaya'));
                $data[$i] = ['total' => $tot, 'data' => $res];
            }
        }
        if ($decode['jenis'] == "Harian") {
            for ($x = 1; $x <= $jumlahHari; $x++) {
                $harian = [];
                foreach ($tables as $i) {
                    $db = db($i, $decode['db']);
                    $db->select('*');
                    if (array_key_exists("lokasi", $decode)) {
                        $db->where('lokasi', $decode['lokasi']);
                    }
                    $res = $db->orderBy('tgl', 'ASC')
                        ->where("DAY(FROM_UNIXTIME(tgl))", $x)
                        ->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $harian[] = $tot;
                }

                $data[] = ['tgl' => $x, 'masuk' => $harian[0], 'keluar' => $harian[1]];
            }
        }
        if ($decode['jenis'] == "Bulanan") {
            foreach (bulans() as $b) {
                $bulanan = [];
                foreach ($tables as $i) {
                    $db = db($i, $decode['db']);
                    $db->select('*');
                    if (array_key_exists("lokasi", $decode)) {
                        $db->where('lokasi', $decode['lokasi']);
                    }
                    $res = $db->orderBy('tgl', 'ASC')
                        ->where("MONTH(FROM_UNIXTIME(tgl))", $b['satuan'])
                        ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun'])
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $bulanan[] = $tot;
                }

                $data[] = ['tgl' => $b['bulan'], 'masuk' => $bulanan[0], 'keluar' => $bulanan[1]];
            }
        }
        if ($decode['jenis'] == "Tahunan") {
            foreach (tahuns($decode) as $t) {
                $tahunan = [];
                foreach ($tables as $i) {
                    $db = db($i, $decode['db']);
                    $db->select('*');
                    if (array_key_exists("lokasi", $decode)) {
                        $db->where('lokasi', $decode['lokasi']);
                    }
                    $res = $db->where("YEAR(FROM_UNIXTIME(tgl))", $t['tahun'])
                        ->orderBy('tgl', 'ASC')
                        ->get()
                        ->getResultArray();
                    $tot = array_sum(array_column($res, 'biaya'));
                    $total[$i] += $tot;
                    $tahunan[] = $tot;
                }

                $data[] = ['tgl' => $t['tahun'], 'masuk' => $tahunan[0], 'keluar' => $tahunan[1]];
            }
        }
        if ($decode['jenis'] == "Backup") {
            foreach (tahuns($decode) as $t) {
                $tahunan = [];
                foreach ($tables as $i) {
                    $db = db($i, $decode['db']);
                    $db->select('*');
                    if (array_key_exists("lokasi", $decode)) {
                        $db->where('lokasi', $decode['lokasi']);
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

                $q = db('backup')->where('db', $decode['db'])->where('tahun', $t['tahun'])->get()->getRowArray();

                if (!$q) {
                    $insert = [
                        'tahun' => $t['tahun'],
                        'masuk' => $tahunan[0],
                        'keluar' => $tahunan[1],
                        'saldo' => $tahunan[0] - $tahunan[1],
                        'keep' => 1
                    ];
                    db('backup')->where('db', $decode['db'])->insert($insert);
                } else {
                    if ($q['keep'] == 0) {
                        $q['masuk'] = $tahunan[0];
                        $q['keluar'] = $tahunan[1];
                        $q['saldo'] = $tahunan[0] - $tahunan[1];
                        $q['keep'] = 1;
                        db('backup')->where('db', $decode['db'])->where('id', $q['id'])->update($q);
                    }
                }

                $backup = db('backup')->where('db', $decode['db'])->select('*')->orderBy('tahun', 'ASC')
                    ->get()
                    ->getResultArray();
                $tot = array_sum(array_column($backup, 'saldo'));

                sukses("Ok", $backup, $tot);
            }
        }
    } else {
        if ($decode['order'] == 'hutang') {
            $decode['filter'] = "by user";
            $decode['tabel'] = "transaksi";
            return get_hutang($decode);
        }

        $sub_menu = options($decode);

        $db = db($decode['tabel'], $decode['sub_db']);

        $db->select('*');
        if (array_key_exists("lokasi", $decode)) {
            $db->where('lokasi', $decode['lokasi']);
        }
        if ($decode['order'] == "Show") {
            if ($decode['kategori'] == "Inv" && ($decode['sub_db'] == "playground" || $decode['sub_db'] == "playbox")) {
                $db->where('divisi', $decode['divisi']);
            } else {
                $db->whereIn('jenis', $sub_menu);
            }
        } else {
            if ($decode['order'] == "pengeluaran") {
                $sub_menu1 = options(['db' => $decode['db'], 'kategori' => 'Inv', 'format' => 'array', 'order_by' => "id"]);
                $sub_menu2 = options(['db' => $decode['db'], 'kategori' => 'Kantin', 'format' => 'array', 'order_by' => "id"]);
                $sub_menu = array_merge($sub_menu1, $sub_menu2);
            }

            if ($decode['jenis'] !== "All") {
                $db->where('jenis', $decode['jenis']);
            }
        }
        // $db->where('db', $decode['db']);
        $db->where("MONTH(FROM_UNIXTIME(tgl))", $decode['bulan'])
            ->where("YEAR(FROM_UNIXTIME(tgl))", $decode['tahun']);
        if ($decode['order'] == "hutang") {
            $db->where('metode', 'Hutang');
            $db->orderBy('nama', 'ASC');
        }

        $db->orderBy('tgl', 'ASC');
        $data = $db->get()->getResultArray();

        $total = array_sum(array_column($data, 'biaya'));
    }

    $res = ['data' => $data, 'total' => $total, 'sub_menu' => $sub_menu];
    return $res;
}

function profile($decode)
{
    return db('profile')->where('db', $decode['db'])->get()->getRowArray();
}

function angka($uang)
{
    return number_format($uang, 0, ",", ".");
}

function decode_jwt($encode_jwt)
{
    try {

        $decoded = JWT::decode($encode_jwt, new Key(getenv("KEY_JWT"), 'HS256'));
        $data = json_decode(json_encode($decoded), true);
        return $data;
    } catch (\Exception $e) { // Also tried JwtException
        $data = [
            'status' => '400',
            'message' => $e->getMessage()
        ];

        echo json_encode($data);
        die;
    }
}

// Fungsi untuk mendekripsi string dengan kunci
function dekripsi($encryptedStr, $key = null)
{
    $key = ($key == null ? getenv("KEY_ENKRIP") : $key);
    $str = base64_decode($encryptedStr);
    $result = '';
    for ($i = 0; $i < strlen($str); $i++) {
        $result .= chr(ord($str[$i]) ^ ord($key[$i % strlen($key)]));
    }
    return $result;
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


function next_invoice($decode)
{

    $year  = date('Y');
    $month = date('m');
    $prefix = "$year-$month-";

    // Cari no_nota terakhir berdasarkan bulan ini
    $lastNota = db('transaksi', $decode['db'])->whereNotIn('metode', ["Hutang", "Wl"])
        ->orderBy('tgl', 'DESC')
        ->get()
        ->getRowArray();

    if ($decode['ket'] == "inv") {
        $lastNota = db('pengeluaran', $decode['db'])->where('jenis', 'Inv')
            ->orderBy('tgl', 'DESC')
            ->get()
            ->getRowArray();
    }


    $nextNumber = 1;
    if ($lastNota) {
        $parts = explode('-', $lastNota['no_nota']);
        $lastNumber = end($parts);
        $nextNumber = (int)$lastNumber + 1;
    }

    $nota = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

    if ($decode['ket'] == "hutang") {
        $nota = time() . random_str(5);
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

function uang_modal($decode)
{

    $data = db('pengeluaran', $decode['db'])->select('*')->where('jenis', "Modal")->orderBy('tgl', 'DESC')->orderBy('tgl', 'DESC')->get()->getResultArray();

    $total = array_sum(array_column($data, 'biaya'));

    $res = ['total' => $total, 'data' => $data];

    return $res;
}

function angka_to_int($uang)
{
    $uang = str_replace("Rp. ", "", $uang);
    $uang = str_replace(".", "", $uang);
    $uang = str_replace(",", "", $uang);
    return (int)$uang;
}

function delete($decode, $roles = [])
{
    $decode['sub_db'] = ($decode['db'] == "playground" || $decode['db'] == "playbox" ? $decode['db'] . "_" . strtolower($decode['divisi']) : $decode['db']);
    if (count($roles) > 0) {

        if (!in_array($decode['admin'], $roles)) {
            gagal("Role not allowed");
        }
    }
    $q = db($decode['tabel'], $decode['sub_db'])->where('id', $decode['id'])->get()->getRowArray();

    if (!$q) {
        gagal("Id not found");
    }

    // Simpan data
    db($decode['tabel'], $decode['sub_db'])->where('id', $q['id'])->delete()
        ? sukses('Sukses')
        : gagal('Gagal');
}


function cari_barang($decode)
{
    $text = clear($decode['text']);

    $db = db('barang', $decode['db']);

    if (array_key_exists('lokasi', $decode)) {
        $db->where('lokasi', $decode['lokasi']);
    }
    if (array_key_exists('filters', $decode)) {
        $db->whereIn('jenis', $decode['filters']);
    }
    if (array_key_exists('jenis', $decode)) {
        if ($decode['jenis'] !== "") {
            $db->where('jenis', $decode['jenis']);
        }
    }

    $data = $db->like("barang", $text, "both")->orderBy('barang', 'ASC')->limit(7)->get()->getResultArray();

    sukses("Ok", $data);
}

function cari_user($decode)
{
    $res = [];
    $text = clear($decode['text']);
    $db = db('user');

    if (array_key_exists('filters', $decode)) {
        $db->whereIn('role', $decode['filters']);
    }
    if ($decode['order'] == "customer grosir") {
        $db->whereNotIn('lokasi', [""]);
        $db->like("db", $text, "both");
    } else {
        $db->like("nama", $text, "both");
    }

    $data = $db->orderBy('nama', 'ASC')->get()->getResultArray();

    foreach ($data as $i) {
        $exp = explode(",", $i['db']);

        if ($decode['is_data'] == "karyawan") {
            if (in_array($decode['db'], $exp)) {
                $res[] = $i;
            }
        }
        if ($decode['order'] == "customer grosir") {
            $divisions = ["cafe", "playground", "batea", "iswa"];
            $match = array_intersect($exp, $divisions);

            // jika tidak ada yang sama, skip
            if (!$match) {
                continue;
            }
        }

        if ($decode['is_data'] == "hutang") {
            $total = 0;
            $dbh = db('transaksi', $decode['db']);
            $dbh->select('*');
            if (array_key_exists('lokasi', $decode)) {
                $dbh->where('lokasi', $decode['lokasi']);
            }
            $dbh->where('user_id', $i['id']);
            $dbh->where('metode', "Hutang");
            $hutangs = $dbh->get()->getResultArray();
            if ($hutangs) {
                $total = array_sum(array_column($hutangs, 'biaya'));
            }
            $i['hutang'] = $total;
            $res[] = $i;
        }
        if (count($res) == 10) {
            break;
        }
    }

    sukses("Ok", $res);
}

function today($decode)
{
    $nowHour = (int)date("H");
    $today   = date("Y-m-d");
    $yesterday = date("Y-m-d", strtotime("-1 day"));

    // override tanggal jika ada di $decode
    if (array_key_exists('tanggal', $decode)) {
        $expToday = explode("-", $today);
        $today    = $expToday[0] . "-" . $expToday[1] . "-" . $decode['tanggal'];

        $expYest  = explode("-", $yesterday);
        $yesterday = $expYest[0] . "-" . $expYest[1] . "-" . $decode['tanggal'];
    }

    if ($nowHour < 7) {
        // 00:00–06:59 → start kemarin 07:00, end hari ini 05:00
        $start = strtotime($yesterday . " 07:00:00");
        $end   = strtotime($today . " 05:00:00");
    } else {
        // 07:00–23:59 → start hari ini 07:00, end saat ini
        $start = strtotime($today . " 07:00:00");
        $end   = strtotime($today . " 23:59:59");
    }

    return ['start' => $start, 'end' => $end];
}



function get_hutang($decode)
{
    $range = today($decode);

    $db = db($decode['tabel'], $decode['db']);
    $data = [];
    $db->select("
        user_id,
        nama,
        no_nota,
        tgl,
        SUM(biaya) as biaya");
    $db->where('metode', 'Hutang');
    if ($decode['filter'] == "by user") {
        $db->groupBy('user_id');
    }
    if ($decode['filter'] == "by nota") {
        $db->where('tgl >=', $range['start'])->where('tgl <=', $range['end']);
        $db->groupBy('no_nota');
    }
    $result = $db->get()->getResultArray();

    $res = [];
    foreach ($result as $i) {
        $q = db('user')->where('id', $i['user_id'])->get()->getRowArray();
        if ($q) {
            $i['wa'] = $q['wa'];
        }
        $dbv = db('transaksi', $decode['db'])->select('*');
        if ($decode['filter'] == "by user") {
            $dbv->where('user_id', $i['user_id']);
        }
        if ($decode['filter'] == "by nota") {
            $dbv->where('no_nota', $i['no_nota']);
        }
        $val = $dbv->get()
            ->getResultArray();
        $totv = array_sum(array_column($val, 'biaya'));

        $i['data'] = $val;
        $i['biaya'] = $totv;
        $res[] = $i;
    }

    $data = [
        'data' => $res,
        'total' => array_sum(array_column($result, 'biaya')),
        'range' => $range,
        'sub_menu' => date("t") //jml hari bulan ini
    ];

    if (array_key_exists('kategori', $decode)) {
        $data['sub_menu'] = options($decode);
    }

    return $data;
}


function transaksi($decode)
{
    $dbs = $decode['db'];



    $db = \Config\Database::connect();
    $db->transStart();


    $nota = next_invoice($decode);
    $message = "";

    $tgl = time();

    foreach ($decode['datas'] as $i) {
        $i['divisi'] = (array_key_exists('divisi', $i) ? $i['divisi'] : "");
        if ($decode['db'] == "playground" || $decode['db'] == "playbox") {
            $dbs = ($i['divisi'] == "Ps" || $i['divisi'] == "Billiard" ? $decode['db'] : $decode['db'] . "_" . strtolower($i['divisi']));
        }

        if ($decode['ket'] == "bayar" || $decode['ket'] == "hutang") {
            $input = [
                "no_nota" => $nota,
                "tgl" => $tgl,
                "jenis" => $i['jenis'],
                "barang" => $i['barang'],
                "karyawan" => '',
                "barang_id" => $i['id'],
                "harga" => $i['harga'],
                "qty" => $i['qty'],
                "total" => $i['total'],
                "diskon" => $i['diskon'],
                "biaya" => $i['biaya'],
                "tipe" => $i['tipe'],
                "link" => $i['link'],
                "petugas" => $decode['petugas']
            ];

            if ($i['divisi'] == "Ps" || $i['divisi'] == "Billiard") {
                $input['start'] = ($i['metode'] == "Wl" ? $i['start'] : $tgl);
                $input['metode'] = ($i['metode'] == "Wl" ? $i['metode'] : $decode['metode']);

                $input['end'] = ($i['roleplay'] == "Open" ? 0 : $input['start'] + ((int)$i['qty'] * (60 * 60)));


                $input['is_over'] = 0;
                $input['roleplay'] = $i['roleplay'];
                $input['desc_diskons'] = $i['desc_diskons'];
                $input['dp'] = ($i['metode'] == "Wl" ? $i['dp'] : 0);
            } else {
                $input['metode'] = $decode['metode'];
            }

            $message = base_url('cetak/nota/' . $decode['db'] . "/" . $input['no_nota']);

            if ($decode['uang'] !== "") {
                $message .= "/" . $decode['uang'];
            }

            if (array_key_exists('karyawan', $i)) {
                $input['karyawan'] = $i['karyawan'];
            }

            if (array_key_exists('lokasi', $decode)) {
                $input['lokasi'] = $decode['lokasi'];
            }

            if ($decode['ket'] == "hutang") {
                $input['user_id'] = $decode['penghutang']['id'];
                $input['nama'] = $decode['penghutang']['nama'];
            } else {
                $input['uang'] = $decode['uang'];
            }

            // insert data
            $dbin = \Config\Database::connect($dbs);
            $dbi = $dbin->table($decode['tabel']);
            if (!$dbi->insert($input)) {
                gagal("Input " . $input["barang"] . " gagal");
            } else {
                $id_transaksi = $dbin->insertID();

                if ($i['divisi'] == "Ps" || $i['divisi'] == "Billiard") {
                    if ($i['metode'] !== "Wl") {
                        $iot = db('iot', $decode['db'])->where('id', $i['iot_id'])->get()->getRowArray();
                        if (!$iot) {
                            gagal("Id iot not found");
                        }
                        $iot['status'] = 1;
                        $iot['end'] = $input['end'];
                        $iot['transaksi_id'] = $id_transaksi;
                        if (!db('iot', $decode['db'])->where('id', $iot['id'])->update($iot)) {
                            gagal("Update iot gagal");
                        }
                    }
                } else {
                    // cari barang update qty
                    $barang = db('barang', $dbs)->where('id', $i['id'])->get()->getRowArray();
                    if ($i['link'] !== '' && $i['tipe'] == "Mix") {
                        if (!$barang) {
                            gagal("Id " . $i['barang'] . " not found");
                        }
                        $exp = explode(",", $barang['link']);

                        foreach ($exp as $x) {
                            $val = db('barang', $dbs)->where('id', $x)->get()->getRowArray();

                            if (!$val) {
                                gagal("Link barang id null");
                            }

                            if ($val['qty'] < (int)$i['qty']) {
                                gagal('Stok kurang');
                            }

                            $val['qty'] -= (int)$i['qty'];

                            if (!db('barang', $dbs)->where('id', $val['id'])->update($val)) {
                                gagal("Update stok gagal");
                            }
                        }
                    }

                    // update_qty
                    if ($i['tipe'] == "Count") {
                        if (!$barang) {
                            gagal("Id " . $i['barang'] . " not found");
                        }
                        if ($barang['qty'] < (int)$i['qty']) {
                            gagal('Stok kurang');
                        }
                        $barang['qty'] -= (int)$i['qty'];

                        if (!db('barang', $dbs)->where('id', $barang['id'])->update($barang)) {
                            gagal("Update stok gagal");
                        }
                    }
                }
            }



            if ($decode['ket'] == "hutang") {
                $total = 0;
                $dbh = db('transaksi', $dbs);
                $dbh->select('*');
                if (array_key_exists('lokasi', $decode)) {
                    $dbh->where('lokasi', $decode['lokasi']);
                }
                $dbh->where('user_id', $decode['penghutang']['id']);
                $dbh->where('metode', "Hutang");
                $hutangs = $dbh->get()->getResultArray();
                if ($hutangs) {
                    $total = array_sum(array_column($hutangs, 'biaya'));
                }

                $message = '<div>Total hutang ' . $decode['penghutang']['nama'] . '</div><h5>' . angka($total) . '</h5>';
            }
        }

        if ($decode['ket'] == "bayar hutang" || $decode['ket'] == "bayar transaksi") {
            $update = [
                'no_nota' => $nota,
                'metode' => $decode['metode'],
                'uang' => $decode['uang'],
                'tgl' => $tgl
            ];

            if ($i['divisi'] == "Ps" || $i['divisi'] == "Billiard") {
                $update['is_over'] = 1;
                $update['biaya'] = (int)$i['biaya'] + ($decode['ket'] == "bayar transaksi" ? (int)$i['dp'] : 0);
                if ($i['roleplay'] == "Open") {
                    $update['total'] = $update['biaya'];
                    $update['end'] = $tgl;
                    $update['qty'] = ceil(($tgl - $i['start']) / 60);
                }

                $iot = db('iot', $decode['db'])->where('transaksi_id', $i['id'])->get()->getRowArray();

                if ($iot) {
                    $iot['status'] = 0;
                    $iot['end'] = 0;
                    $iot['transaksi_id'] = 0;

                    if (!db('iot', $decode['db'])->where('id', $iot['id'])->update($iot)) {
                        gagal("Update iot gagal");
                    }
                }
            }
            if (!db($decode['tabel'], $dbs)->where('id', $i['id'])->update($update)) {
                gagal("Update hutang gagal");
            }

            $message = base_url('cetak/nota/' . $decode['db'] . '/' . $update['no_nota'] . "/" . $decode['uang']);
        }

        if ($decode['ket'] == "update pesanan") {

            if ($i['is_update'] == "true" || $i['is_update'] == "new") {
                if ($i['is_update'] == "true") {
                    $data_old = db('transaksi', $dbs)->where('id', $i['id'])->get()->getRowArray();

                    if (!$data_old) {
                        gagal('Id transaksi not found');
                    }
                }
                $barang = db('barang', $dbs)->where('id', ($i['is_update'] == "new" ? $i['id'] : $i['barang_id']))->get()->getRowArray();

                if (!$barang) {
                    gagal('Id barang not found');
                }

                if ($barang['link'] !== '' && $barang['tipe'] == "Mix") {

                    $exp = explode(",", $barang['link']);

                    foreach ($exp as $x) {
                        $brng = db('barang', $dbs)->where('id', $x)->get()->getRowArray();

                        if (!$brng) {
                            gagal("Id link barang kosong");
                        }

                        if ($i['is_update'] == "new") {
                            if ($brng['qty'] < $i['qty']) {
                                gagal("Stok " . $brng['barang'] . " kurang");
                            }
                            $brng['qty'] -= $i['qty'];
                        } else {
                            $selisih_qty = 0;
                            if ($data_old['qty'] < $i['qty']) {
                                $selisih_qty = $i['qty'] - $data_old['qty'];

                                if ($brng['qty'] < $selisih_qty) {
                                    gagal("Stok " . $brng['barang'] . " kurang");
                                }
                                $brng['qty'] -= $selisih_qty;
                            }

                            if ($data_old['qty'] > $i['qty']) {
                                $selisih_qty =  $data_old['qty'] - $i['qty'];
                                $brng['qty'] += $selisih_qty;
                            }
                        }

                        if (!db('barang', $dbs)->where('id', $brng['id'])->update($brng)) {
                            gagal("Update stok gagal");
                        }
                    }
                }
                if ($barang['tipe'] == "Count") {
                    if ($i['is_update'] == "new") {
                        if ($barang['qty'] < $i['qty']) {
                            gagal("Stok " . $barang['barang'] . " kurang");
                        }
                        $barang['qty'] -= $i['qty'];
                    } else {
                        $selisih_qty = 0;
                        if ($data_old['qty'] < $i['qty']) {
                            $selisih_qty = $i['qty'] - $data_old['qty'];

                            if ($barang['qty'] < $selisih_qty) {
                                gagal("Stok " . $i['barang'] . " kurang");
                            }
                            $barang['qty'] -= $selisih_qty;
                        }

                        if ($data_old['qty'] > $i['qty']) {
                            $selisih_qty =  $data_old['qty'] - $i['qty'];
                            $barang['qty'] += $selisih_qty;
                        }
                    }

                    if (!db('barang', $dbs)->where('id', $barang['id'])->update($barang)) {
                        gagal("Update stok gagal");
                    }
                }

                if ($i['is_update'] == "new") {
                    $new = [
                        "no_nota" => $decode['no_nota'],
                        "tgl" => $decode['tgl'],
                        "jenis" => $i['jenis'],
                        "barang" => $i['barang'],
                        "barang_id" => $i['id'],
                        "harga" => $i['harga'],
                        "qty" => $i['qty'],
                        "total" => $i['total'],
                        "diskon" => $i['diskon'],
                        "biaya" => $i['biaya'],
                        "karyawan" => '',
                        "tipe" => $i['tipe'],
                        "link" => $i['link'],
                        "metode" => 'Hutang',
                        "user_id" => $decode['penghutang']['user_id'],
                        "nama" => $decode['penghutang']['nama'],
                        "petugas" => $decode['petugas']
                    ];

                    if (array_key_exists('lokasi', $decode)) {
                        $new['lokasi'] = $decode['lokasi'];
                    }
                    if (array_key_exists('karyawan', $decode)) {
                        $new['karyawan'] = $decode['karyawan'];
                    }

                    if (!db('transaksi', $dbs)->insert($new)) {
                        gagal("Insert new data gagal");
                    }
                } else {
                    $update = [
                        'petugas' => $decode['petugas'],
                        'qty' => $i['qty'],
                        'total' => $i['total'],
                        'diskon' => $i['diskon'],
                        'biaya' => $i['biaya']
                    ];
                    if (!db('transaksi', $dbs)->where('id', $i['id'])->update($update)) {
                        gagal("Update transaksi gagal");
                    }
                }
            }
        }
    }

    $db->transComplete();
    $db->transStatus()
        ? sukses("Sukses", $message)
        : gagal("Gagal");
}


function is_weekdays(): bool
{
    date_default_timezone_set('Asia/Jakarta');
    $day    = (int)date('N'); // 1=Senin, ..., 7=Minggu
    $hour   = (int)date('H');
    $minute = (int)date('i');

    // Minggu mulai 20:01 → TRUE
    if ($day == 7 && ($hour > 20 || ($hour == 20 && $minute >= 1))) {
        return true;
    }

    // Senin, Selasa, Rabu → selalu TRUE
    if ($day >= 1 && $day <= 3) {
        return true;
    }

    // Kamis sampai 20:00 → TRUE
    if ($day == 4 && ($hour < 20 || ($hour == 20 && $minute == 0))) {
        return true;
    }

    // Selain itu → FALSE
    return false;
}


function hutang_playground($decode)
{
    $skip_nota = []; // skip nota kare is_over = 0
    $nota = db('transaksi', $decode['db'])->where('metode', "Hutang")->where('is_over', 0)->get()->getResultArray();
    foreach ($nota as $i) {
        if (!in_array($i['no_nota'], $skip_nota)) {
            $skip_nota[] = $i['no_nota'];
        }
    }

    $users = [];
    foreach ($decode['divisions'] as $i) {
        $db = ($i == "Ps" || $i == "Billiard" ? $decode['db'] : $decode['db'] . "_" . $i);
        $temp_users = db('transaksi', $db)->where('metode', "Hutang")
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
            $db = ($i == "Ps" || $i == "Billiard" ? $decode['db'] : $decode['db'] . "_" . strtolower($i));

            $dbb = db('transaksi', $db);
            if ($i == "Ps" || $i == "Billiard") {
                $dbb->where('jenis', $i);
            }
            if (count($skip_nota) > 0) {
                $dbb->whereNotIn('no_nota', $skip_nota);
            }

            $data = $dbb->where('user_id', $u)->get()->getResultArray();
            foreach ($data as $d) {
                $d['divisi'] = $i;
                if ($i == "Ps" || $i == "Billiard") {
                    $d['biaya'] -= $d['dp'];
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
        if (count($temp['data']) > 0) {
            $res['data'][] = $temp;
        }
    }
    return $res;
}

function get_data_playground($decode)
{
    // sukses($decode);
    $divisions = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);

    $data = ['total' => 0, 'masuk' => 0, 'keluar' => 0];
    $sub_menu = ['Harian', 'Bulanan', 'Tahunan'];
    if ($decode['order'] !== "laporan") {
        $sub_menu = $divisions;
    }
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
    } elseif ($decode['order'] == "laporan" && $decode['jenis'] == "All") {
        foreach ($divisions as $dv) {
            $db = ($dv == "Billiard" || $dv == "Ps" ? $decode['db'] : $decode['db'] . '_' . strtolower($dv));

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
    } else {
        if ($decode['order'] == "hutang") {
            $decode['divisions'] = $divisions;
            $data = hutang_playground($decode);
        } else {
            $decode['jenis'] = ($decode['jenis'] == "All" ? $divisions[0] : $decode['jenis']);
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
    }

    return $data;
}
