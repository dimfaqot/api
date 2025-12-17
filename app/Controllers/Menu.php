<?php

namespace App\Controllers;

class Menu extends BaseController
{

    public function general($jwt)
    {
        // CORS Headers
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");

        $decode = decode_jwt($jwt);

        check($decode);


        if ($decode['order'] == "Add") {
            $input = [
                'role'       => clear($decode['role']),
                'menu'       => upper_first(clear($decode['menu'])),
                'tabel'      => strtolower(clear($decode['tabel'])),
                'controller' => strtolower(clear($decode['controller'])),
                'db' => strtolower(clear($decode['db'])),
                'icon'       => strtolower(clear($decode['icon'])),
                'grup'       => upper_first(clear($decode['grup']))
            ];


            if ($input['role'] == "") {
                gagal('Role failed');
            }

            // Cek duplikat
            if (db('menu')->where('db', $decode['db'])->where('role', $input['role'])->where('menu', $input['menu'])->countAllResults() > 0) {
                gagal('Menu existed');
            }

            // Dapatkan urutan terakhir
            $last = db('menu')->where('db', $decode['db'])->select('urutan')->orderBy('urutan', 'DESC')->get()->getRowArray();
            $input['urutan'] = isset($last['urutan']) ? $last['urutan'] + 1 : 1;


            // Simpan data  
            db('menu')->insert($input)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {


            $q = db('menu')->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            $q['role'] = clear($decode['role']);
            $q['urutan'] = clear($decode['urutan']);
            $q['menu'] = upper_first(clear($decode['menu']));
            $q['tabel'] = strtolower(clear($decode['tabel']));
            $q['controller'] = strtolower(clear($decode['controller']));
            $q['icon'] = strtolower(clear($decode['icon']));
            $q['grup'] = upper_first(clear($decode['grup']));

            if (db('menu')->whereNotIn('id', [$q['id']])->where('db', $decode['db'])->where("menu", $q['menu'])->get()->getRowArray()) {
                gagal("Menu existed");
            }

            if (db('menu')->whereNotIn('id', [$q['id']])->where('db', $decode['db'])->where("urutan", $q['urutan'])->get()->getRowArray()) {
                gagal("Urutan existed");
            }

            // Simpan data
            db('menu')->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Copy") {

            $q = db('menu')->where('id', $decode['menu_id'])->get()->getRowArray();

            if (!$q) {
                gagal("Menu id not found");
            }

            if (db('menu')->where('db', $decode['db'])->where('role', $decode['role'])->where("menu", $q['menu'])->get()->getRowArray()) {
                gagal("Menu in role existed");
            }
            unset($q['id']);

            // Dapatkan urutan terakhir
            $last = db('menu')->where('db', $decode['db'])->select('urutan')->where('menu', $q['menu'])->orderBy('urutan', 'DESC')->get()->getRowArray();
            $q['urutan'] = isset($last['urutan']) ? $last['urutan'] + 1 : 1;
            $q['role'] = upper_first(clear($decode['role']));

            // Simpan data
            db('menu', $decode['db'])->insert($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }

        if ($decode['order'] == "Delete") {
            delete($decode);
        }
    }
}
