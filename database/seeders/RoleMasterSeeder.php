<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role_masters')->insert([
            'RoleName' => Str::random(10),
            'Icon' => Str::random(10),
            'Routes' => Str::random(10)
        ]);
    }
}
