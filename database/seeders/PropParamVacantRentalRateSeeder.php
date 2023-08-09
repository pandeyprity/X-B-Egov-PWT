<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PropParamVacantRentalRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('prop_param_vacant_rental_rates')->insert(
        ["prop_road_typ_id"=>"1",'rate' => "2.50","ulb_type_id"=>"1","effective_date"=>'2016-04-01'],
        ["prop_road_typ_id"=>"1",'rate' => "2.00","ulb_type_id"=>"2","effective_date"=>'2016-04-01'],
        ["prop_road_typ_id"=>"1",'rate' => "1.50","ulb_type_id"=>"3","effective_date"=>'2016-04-01'],

        ["prop_road_typ_id"=>"2",'rate' => "2.00","ulb_type_id"=>"1","effective_date"=>'2016-04-01'],
        ["prop_road_typ_id"=>"2",'rate' => "1.50","ulb_type_id"=>"2","effective_date"=>'2016-04-01'],
        ["prop_road_typ_id"=>"2",'rate' => "1.00","ulb_type_id"=>"3","effective_date"=>'2016-04-01'],

        ["prop_road_typ_id"=>"3",'rate' => "1.50","ulb_type_id"=>"1","effective_date"=>'2016-04-01'],
        ["prop_road_typ_id"=>"3",'rate' => "1.00","ulb_type_id"=>"2","effective_date"=>'2016-04-01'],
        ["prop_road_typ_id"=>"3",'rate' => "0.50","ulb_type_id"=>"3","effective_date"=>'2016-04-01'],

        ["prop_road_typ_id"=>"2",'rate' => "3.50","ulb_type_id"=>"1","effective_date"=>"2022-04-01"],
        ["prop_road_typ_id"=>"2",'rate' => "3.00","ulb_type_id"=>"2","effective_date"=>"2022-04-01"],
        ["prop_road_typ_id"=>"2",'rate' => "2.00","ulb_type_id"=>"3","effective_date"=>"2022-04-01"],

        ["prop_road_typ_id"=>"3",'rate' => "3.00","ulb_type_id"=>"1","effective_date"=>"2022-04-01"],
        ["prop_road_typ_id"=>"3",'rate' => "2.00","ulb_type_id"=>"2","effective_date"=>"2022-04-01"],
        ["prop_road_typ_id"=>"3",'rate' => "1.50","ulb_type_id"=>"3","effective_date"=>"2022-04-01"]);
    }
}