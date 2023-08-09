<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PropParamRoadTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('prop_param_road_types')->insert(
        ["prop_road_typ_id"=>"1",'range_from_sqft' => "40.00","effective_date"=>"2016-04-01"],
        ["prop_road_typ_id"=>"2",'range_from_sqft' => "20.00","range_upto_sqft"=>"39.99","effective_date"=>"2016-04-01"],
        ["prop_road_typ_id"=>"3",'range_from_sqft' => "0.09","range_upto_sqft"=>"19.99","effective_date"=>"2016-04-01"],
        ["prop_road_typ_id"=>"2",'range_from_sqft' => "40.00","effective_date"=>"2022-04-01"],
        ["prop_road_typ_id"=>"3",'range_from_sqft' => "0.09","range_upto_sqft"=>"39.99","effective_date"=>"2022-04-01"]);
    }
}