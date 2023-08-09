<?php

namespace App\Traits\Notice;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

/***
 * @Parent - App\Http\Request\AuthUserRequest
 * Author Name-Anshu Kumar
 * Created On- 27-06-2022
 * Creation Purpose- For Validating During User Registeration
 * Coding Tested By-
 */

 trait NoticeTrait
 {
    public function generateBasicDetails($data)
    {
        return new Collection([
            ['displayString' => 'Notice Against.', 'key' => 'firm_name', 'value' => $data->firm_name??""],
            ['displayString' => 'Owner Name', 'key' => 'owner_name', 'value' => $data->owner_name??""],
            ['displayString' => 'Apply Date', 'key' => 'apply_date', 'value' => $data->apply_date??""],
            ['displayString' => 'Reason for notice', 'key' => 'notice_content', 'value' => $data->notice_content??""],
            ['displayString' => 'Document', 'key' => 'documents', 'value' => $data->documents??""],
            ['displayString' => 'Notice Type', 'key' => 'notice_type', 'value' => $data->notice_type??""],
            ['displayString' => 'Notice For', 'key' => 'module_type', 'value' => $data->module_type??""],
        ]);
    }
    public function generateCardDetails($data)
    {
        return new Collection([
            ['displayString' => 'Notice Against.', 'key' => 'firm_name', 'value' => $data->firm_name],
            ['displayString' => 'Owner Name', 'key' => 'ownerName', 'value' => $data->owner_name],
            ['displayString' => 'Application No', 'key' => 'application_no', 'value' => $data->application_no],
            ['displayString' => 'Apply date', 'key' => 'apply_date', 'value' => $data->apply_date],
            ['displayString' => 'Reason for notice', 'key' => 'notice_content', 'value' => $data->notice_content],
           
        ]);
    }

    public function generateProperty($data)
    {
        return new Collection([
            ['displayString' => 'Holding No.', 'key' => 'holding_no', 'value' => $data->holding_no??""],
            ['displayString' => 'PTN No.', 'key' => 'ptn_no', 'value' => $data->ptn_no??""],
            ['displayString' => 'Property Address', 'key' => 'address', 'value' => $data->address??""],
            ['displayString' => 'Pin Code', 'key' => 'pin_code', 'value' => $data->pin_code??""],
            ['displayString' => 'Locality', 'key' => 'locality', 'value' => $data->locality??""],
            ['displayString' => 'City', 'key' => 'city', 'value' => $data->city??""],
            ['displayString' => 'Mobile No.', 'key' => 'mobile_no', 'value' => $data->mobile_no??""],
        ]);
    }

    public function generateWater($data)
    {
        return new Collection([
            ['displayString' => 'Consumer No.', 'key' => 'holding_no', 'value' => $data->holding_no??""],
            ['displayString' => 'Holding No.', 'key' => 'holding_no', 'value' => $data->holding_no??""],
            ['displayString' => 'Property Address', 'key' => 'address', 'value' => $data->address??""],
            ['displayString' => 'Pin Code', 'key' => 'pin_code', 'value' => $data->pin_code??""],
            ['displayString' => 'Locality', 'key' => 'locality', 'value' => $data->locality??""],
            ['displayString' => 'City', 'key' => 'city', 'value' => $data->city??""],
            ['displayString' => 'Mobile No.', 'key' => 'mobile_no', 'value' => $data->mobile_no??""],
        ]);
    }

    public function generateTrade($data)
    {
        return new Collection([
            ['displayString' => 'License No.', 'key' => 'license_no', 'value' => $data->license_no??""],
            ['displayString' => 'Holding No.', 'key' => 'holding_no', 'value' => $data->holding_no??""],
            ['displayString' => 'Property Address', 'key' => 'address', 'value' => $data->address??""],
            ['displayString' => 'Pin Code', 'key' => 'pin_code', 'value' => $data->pin_code??""],
            ['displayString' => 'Locality', 'key' => 'locality', 'value' => $data->locality??""],
            ['displayString' => 'City', 'key' => 'city', 'value' => $data->city??""],
            ['displayString' => 'Mobile No.', 'key' => 'mobile_no', 'value' => $data->mobile_no??""],
        ]);
    }

    public function generateTimeline($data)
    {
        return new Collection([
            ['displayString' => 'License No.', 'key' => 'license_no', 'value' => $data->license_no??""],
            ['displayString' => 'Holding No.', 'key' => 'holding_no', 'value' => $data->holding_no??""],
            ['displayString' => 'Property Address', 'key' => 'address', 'value' => $data->address??""],
            ['displayString' => 'Pin Code', 'key' => 'pin_code', 'value' => $data->pin_code??""],
            ['displayString' => 'Locality', 'key' => 'locality', 'value' => $data->locality??""],
            ['displayString' => 'City', 'key' => 'city', 'value' => $data->city??""],
            ['displayString' => 'Mobile No.', 'key' => 'mobile_no', 'value' => $data->mobile_no??""],
        ]);
    }
 }