<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleUserLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */

    public function run()
    {
        DB::table('role_user_logs')->insert([
            'RoleID' => '1',
            'Flag' => '0',
            'CreationDate' => '2022-06-17',
            'CreatedBy' => Str::random(10)
        ]);
    }
}
