<?php

namespace App\Controllers;

class Hutang extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);

        if ($decode['order'] == "Show") {
            $data = ($decode['db'] == "playground" || $decode['db'] == "playbox" ? $this->data($decode) : get_hutang($decode));
            sukses('Ok', get_hutang($decode));
        }
    }
    function data($decode)
    {
        $divisions = options(['db' => $decode['db'], 'kategori' => 'Divisi', 'format' => 'array', 'order_by' => "id"]);

        $skip_nota = []; // skip nota kare is_over = 0
        $nota = db('transaksi', $decode['db'])->where('metode', "Hutang")->where('is_over', 0)->get()->getResultArray();
        foreach ($nota as $i) {
            if (!in_array($i['no_nota'], $skip_nota)) {
                $skip_nota[] = $i['no_nota'];
            }
        }

        $users = [];
        foreach ($divisions as $i) {
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
            'sub_menu' =>  options(['db' => $decode['db'], 'kategori' => 'Metode', 'format' => 'array', 'order_by' => "id"])
        ];

        foreach ($users as $u) {
            $temp = ['data' => [], 'total' => 0, 'identitas' => []];
            foreach ($divisions as $i) {
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

        $temp_res = [];
        foreach ($res['data'] as $i) {
            $data = $i['identitas'];
            $data['data'] = $i['data'];
            $data['biaya'] = $i['total'];
            unset($data["total"]);
            $temp_res[] = $data;
        }

        $res['data'] = $temp_res;
        return $res;
    }
}
