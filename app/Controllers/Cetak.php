<?php

namespace App\Controllers;

class Cetak extends BaseController
{

    public function nota($dbs, $no_nota)
    {
        $no_nota = str_replace("-", '/', $no_nota);

        $data = db('nota', $dbs)->where('no_nota', $no_nota)->get()->getResultArray();
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
        $html = view('guest/nota', ['judul' => $judul, 'data' => $data, 'no_nota' => $no_nota]); // view('pdf_template') mengacu pada file view yang akan dirender menjadi PDF

        // Setel konten HTML ke mPDF
        $mpdf->WriteHTML($html);

        // Output PDF ke browser
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output($judul . '.pdf', 'I');
    }

    public function laporan($dbs, $order, $tahun, $bulan, $jenis, $lokasi = '')
    {
        $rangkuman = [];
        $tables = ['transaksi', 'pengeluaran'];
        $total = ['transaksi' => 0, 'pengeluaran' => 0];
        foreach (bulans() as $b) {
            if ($b['angka'] <= $bulan) {
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

                $rangkuman[] = ['bulan' => $b['bulan'], 'masuk' => $bulanan[0], 'keluar' => $bulanan[1], 'total' => $bulanan[0] - $bulanan[1]];
            }
        }


        $data = get_data($dbs, $order, $tahun, $bulan, $jenis, $lokasi);
        // dd($data);
        $profile = profile($dbs);
        $set = [
            'mode' => 'utf-8',
            'format' => [210, 330],
            'orientation' => 'P',
            'margin_left' => 5,
            'margin_right' => 5,
            'margin_top' => 5,
            'margin_bottom' => 5
        ];

        $judul1 = "LAPORAN " . strtoupper(($jenis == "All" ? "detail" : $jenis)) . " " . strtoupper($profile['nama']) . " ";
        $judul2 = ($jenis == "All" || $jenis == "Harian" || $jenis == "Bulanan" ? "BULAN " . strtoupper(bulans($bulan)['bulan']) . " TAHUN " . $tahun : "TAHUN " . $tahun);
        $dbs = ($dbs == "nineclean" ? "9clean" : $dbs);
        $img = "https://" . $dbs . ".walisongosragen.com/logo.png";
        // Dapatkan konten HTML
        $logo = '<img width="90" src="' . $img . '" alt="KOP"/>';
        $mpdf = new \Mpdf\Mpdf($set);
        $html = view('cetak/laporan', ['judul1' => $judul1, 'judul2' => $judul2, 'data' => $data, 'logo' => $logo, 'bulan' => bulans($bulan)['bulan'], 'order' => $order, 'jenis' => $jenis, 'rangkuman' => $rangkuman]); // view('pdf_template') mengacu pada file view yang akan dirender menjadi PDF

        // Setel konten HTML ke mPDF
        $mpdf->WriteHTML($html);

        // Output PDF ke browser
        $this->response->setHeader('Content-Type', 'application/pdf');
        $mpdf->Output($judul1 . $judul2 . '.pdf', 'I');
    }
}
