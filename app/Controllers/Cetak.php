<?php

namespace App\Controllers;

class Cetak extends BaseController
{

    public function general($jwt)
    {
        $decode = decode_jwt($jwt);

        if ($decode['order'] == "laporan") {
            $rangkuman = [];
            $tables = ['transaksi', 'pengeluaran'];
            $total = ['transaksi' => 0, 'pengeluaran' => 0];
            foreach (bulans() as $b) {
                if ($b['angka'] <= $decode['bulan']) {
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

                    $rangkuman[] = ['bulan' => $b['bulan'], 'masuk' => $bulanan[0], 'keluar' => $bulanan[1], 'total' => $bulanan[0] - $bulanan[1]];
                }
            }


            $data = get_data($decode);
            // dd($data);
            $profile = profile($decode);
            $set = [
                'mode' => 'utf-8',
                'format' => [210, 330],
                'orientation' => 'P',
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_top' => 5,
                'margin_bottom' => 5
            ];

            $judul1 = "LAPORAN " . strtoupper(($decode['jenis'] == "All" ? "detail" : $decode['jenis'])) . " " . strtoupper($profile['nama']) . " ";
            $judul2 = ($decode['jenis'] == "All" || $decode['jenis'] == "Harian" || $decode['jenis'] == "Bulanan" ? "BULAN " . strtoupper(bulans($decode['bulan'])['bulan']) . " TAHUN " . $decode['tahun'] : "TAHUN " . $decode['tahun']);
            $dbs = ($decode['db'] == "nineclean" ? "9clean" : $decode['db']);
            $img = "https://" . $dbs . ".walisongosragen.com/logo.png";
            // Dapatkan konten HTML
            $logo = '<img width="90" src="' . $img . '" alt="KOP"/>';
            $mpdf = new \Mpdf\Mpdf($set);
            $html = view('cetak/laporan', ['judul1' => $judul1, 'judul2' => $judul2, 'data' => $data, 'logo' => $logo, 'bulan' => bulans($decode['bulan'])['bulan'], 'order' => $decode['order'], 'jenis' => $decode['jenis'], 'rangkuman' => $rangkuman]); // view('pdf_template') mengacu pada file view yang akan dirender menjadi PDF

            // Setel konten HTML ke mPDF
            $mpdf->WriteHTML($html);

            // Output PDF ke browser
            $this->response->setHeader('Content-Type', 'application/pdf');
            $mpdf->Output($judul1 . $judul2 . '.pdf', 'I');
        }
    }

    public function nota($db, $no_nota)
    {
        $data = [];
        if ($db == "playground") {
            $divisions = options(['db' => $db, 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);

            foreach ($divisions as $i) {
                $dbs = ($i == "Ps" || $i == "Billiard" ? $db : strtolower($i));
                $dbt = db('transaksi', $dbs);
                if ($i == "Ps" || $i == "Billiard") {
                    $dbt->where('jenis', $i);
                }
                $q = $dbt->where('no_nota', $no_nota)->get()->getResultArray();
                foreach ($q as $row) {
                    $data[] = $row;
                }
            }
        } else {

            $data = db('transaksi', $db)->where('no_nota', $no_nota)->whereNotIn('metode', ['Hutang'])->get()->getResultArray();
        }
        if (!$data) {
            gagal("No. nota tidak ditemukan");
        }

        $uang = (int)$data[0]['uang'];



        if (count($data) == 0) {
            echo '<h2 style="font-family: Arial, sans-serif;text-align:center">Data tidak ada</h2>';
            die;
        }
        $h = (count($data) == 1 ? 0 : count($data) * 4);

        $set = [
            'mode' => 'utf-8',
            'format' => [80, 90 + $h],
            'orientation' => 'P',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0
        ];

        $mpdf = new \Mpdf\Mpdf($set);
        $mpdf->SetAutoPageBreak(true);

        $judul = "NOTA " . $no_nota;
        // Dapatkan konten HTML
        // $logo = '<img width="90" src="logo.png" alt="KOP"/>';
        $html = view('cetak/nota', ['judul' => $judul, 'data' => $data, 'no_nota' => $no_nota, 'decode' => ['db' => $db], "uang" => $uang]); // view('pdf_template') mengacu pada file view yang akan dirender menjadi PDF

        // Setel konten HTML ke mPDF
        $mpdf->WriteHTML($html);

        // Output PDF ke browser
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output($judul . '.pdf', 'I');
    }
}
