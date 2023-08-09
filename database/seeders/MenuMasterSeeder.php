<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menu_masters')->insert([
            'Serial' => '1',
            'Description' => Str::random(10),
            'MenuString' => Str::random(10),
            'ControllerName' => Str::random(10),
            'ViewName' => Str::random(10),
            'Icon' => Str::random(10),
            'TopLevel' => '2'
        ]);
    }
}
