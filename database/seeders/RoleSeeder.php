<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! in_array(config('app.env'), ['local', 'testing'], true)) {
            return;
        }

        $accounts = [
            [
                'name' => 'Admin Demo',
                'email' => 'admin@ruangbelajar.test',
                'password' => 'AdminRuang123!',
                'role' => 'admin',
            ],
            [
                'name' => 'Siswa Demo',
                'email' => 'siswa@ruangbelajar.test',
                'password' => 'SiswaRuang123!',
                'role' => 'student',
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => $account['password'],
                    'role' => $account['role'],
                ],
            );
        }

    }
}
