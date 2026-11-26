<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kata Sandi Default Akun Siswa
    |--------------------------------------------------------------------------
    |
    | Kata sandi awal untuk akun siswa hasil impor dan tombol "Buat ulang kata
    | sandi awal". Siswa wajib menggantinya saat login pertama. Nilainya diatur
    | di file .env (STUDENT_DEFAULT_PASSWORD) agar tidak tersimpan di repository.
    | Bila kosong, sistem membuat kata sandi acak untuk setiap siswa.
    |
    */

    'default_password' => env('STUDENT_DEFAULT_PASSWORD'),

];
