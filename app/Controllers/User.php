<?php

namespace App\Controllers;

class User extends BaseController
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
                'role '       => upper_first(clear($decode['role'])),
                'nama'       => upper_first(clear($decode['nama'])),
                'username'       => ($decode['username'] == '' ? strtolower(random_str(6)) : $decode['username']),
                'wa'       => clear($decode['wa']),
                'password'       => password_hash(settings($decode['db'], 'password'), PASSWORD_DEFAULT)
            ];

            if (preg_match('/[ +\-]/', $input['wa'])) {
                gagal("Format wa salah");
            }


            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('nama', $input['nama'])->countAllResults() > 0) {
                gagal('Nama existed');
            }
            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('username', $input['username'])->countAllResults() > 0) {
                gagal('Username existed');
            }
            // Cek duplikat
            if (db($decode['tabel'], $decode['db'])->where('wa', $input['wa'])->countAllResults() > 0) {
                gagal('No. wa existed');
            }


            // Simpan data  
            db($decode['tabel'], $decode['db'])->insert($input)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Edit") {


            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            if (preg_match('/[ +\-]/', $decode['wa'])) {
                gagal("Format wa salah");
            }

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$decode['id']]))->where("nama", $q['nama'])->get()->getRowArray()) {
                gagal("Nama existed");
            }

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$decode['id']]))->where("username", $q['username'])->get()->getRowArray()) {
                gagal("Username existed");
            }

            if ((db($decode['tabel'], $decode['db'])->whereNotIn('id', [$decode['id']]))->where("wa", $q['wa'])->get()->getRowArray()) {
                gagal("No. wa existed");
            }

            if ($decode['password'] !== "") {
                if (!password_verify($decode['password'], $q['password'])) {
                    $q['password'] = password_hash($decode['password'], PASSWORD_DEFAULT);
                }
            }

            $q['role'] = upper_first(clear($decode['role']));
            $q['nama'] = upper_first(clear($decode['nama']));
            $q['username'] = clear($decode['username']);
            $q['wa'] = clear($decode['wa']);


            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->update($q)
                ? sukses('Sukses')
                : gagal('Gagal');
        }
        if ($decode['order'] == "Delete") {

            $q = db($decode['tabel'], $decode['db'])->where('id', $decode['id'])->get()->getRowArray();

            if (!$q) {
                gagal("Id not found");
            }

            // Simpan data
            db($decode['tabel'], $decode['db'])->where('id', $q['id'])->delete()
                ? sukses('Sukses')
                : gagal('Gagal');
        }
    }
}
