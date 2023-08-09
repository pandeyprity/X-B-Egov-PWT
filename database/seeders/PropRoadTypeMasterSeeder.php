<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PropRoadTypeMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('prop_road_type_masters')->insert(["id"=>1,'road_type' => "Principal Main Road"],
                                    ["id"=>2,'road_type' => "Main Road"],
                                    ["id"=>3,'road_type' => "Other"],
                                    ["id"=>4,'road_type' => "No Road"]);
    }
}
