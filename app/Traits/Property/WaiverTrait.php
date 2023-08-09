<?php

namespace App\Traits\Property;

use Illuminate\Database\Eloquent\Collection;

/**
 * | Trait Created for Gettting Dynamic Waiver Details
 */
trait WaiverTrait
{
    /**
     * | Waiver Details
     */
    public function waiverDetail1($waiverList)
    {
        return [
            $waiverList['is_bill_waiver'] == false ? 'No' : 'Yes',
            $waiverList['bill_amount'],
            $waiverList['bill_waiver_amount'],
            $waiverList['is_one_percent_penalty'] == false ? 'No' : 'Yes',
            $waiverList['one_percent_penalty_amount'],
            $waiverList['one_percent_penalty_waiver_amount'],
            $waiverList['is_rwh_penalty'] == false ? 'No' : 'Yes',
            $waiverList['rwh_amount'],
            $waiverList['rwh_waiver_amount'],
        ];
    }

    public function waiverDetail2($waiverList)
    {
        return [
            $waiverList['is_bill_waiver'] == false ? 'No' : 'Yes',
            $waiverList['bill_amount'],
            $waiverList['bill_waiver_amount'],
            $waiverList['is_one_percent_penalty'] == false ? 'No' : 'Yes',
            $waiverList['one_percent_penalty_amount'],
            $waiverList['one_percent_penalty_waiver_amount']
        ];
    }

    public function waiverDetail3($waiverList)
    {
        return [
            $waiverList['is_one_percent_penalty'] == false ? 'No' : 'Yes',
            $waiverList['one_percent_penalty_amount'],
            $waiverList['one_percent_penalty_waiver_amount'],
            $waiverList['is_rwh_penalty'] == false ? 'No' : 'Yes',
            $waiverList['rwh_amount'],
            $waiverList['rwh_waiver_amount'],
        ];
    }

    public function waiverDetail4($waiverList)
    {
        return [
            $waiverList['is_bill_waiver'] == false ? 'No' : 'Yes',
            $waiverList['bill_amount'],
            $waiverList['bill_waiver_amount'],
            $waiverList['is_rwh_penalty'] == false ? 'No' : 'Yes',
            $waiverList['rwh_amount'],
            $waiverList['rwh_waiver_amount'],
        ];
    }

    public function waiverDetail5($waiverList)
    {
        return [
            $waiverList['is_bill_waiver'] == false ? 'No' : 'Yes',
            $waiverList['bill_amount'],
            $waiverList['bill_waiver_amount'],
        ];
    }

    public function waiverDetail6($waiverList)
    {
        return [
            $waiverList['is_one_percent_penalty'] == false ? 'No' : 'Yes',
            $waiverList['one_percent_penalty_amount'],
            $waiverList['one_percent_penalty_waiver_amount'],

        ];
    }

    public function waiverDetail7($waiverList)
    {
        return [
            $waiverList['is_rwh_penalty'] == false ? 'No' : 'Yes',
            $waiverList['rwh_amount'],
            $waiverList['rwh_waiver_amount'],
        ];
    }

    public function waiverDetail8($waiverList)
    {
        return [
            $waiverList['is_lateassessment_penalty'] == false ? 'No' : 'Yes',
            $waiverList['lateassessment_penalty_amount'],
            $waiverList['lateassessment_penalty_waiver_amount'],
        ];
    }

    /**
     * | Generate Card Details for Waiver
     */
    public function generateWaiverCardDtls($applicationDtl, $propertyDetail)
    {
        // $owners = collect($ownerDetails)->implode('owner_name', ',');

        $propertyDetails = new Collection([
            ['displayString' => 'Ward No', 'key' => 'wardNo', 'value' => $propertyDetail->old_ward_no],
            ['displayString' => 'Holding No', 'key' => 'safNo', 'value' => $propertyDetail->holding_no],
            ['displayString' => 'DOB', 'key' => 'dob', 'value' => $propertyDetail->dob],
            ['displayString' => 'Gender', 'key' => 'gender', 'value' => $propertyDetail->gender],
            ['displayString' => 'Is Armed Force', 'key' => 'isArmedForce', 'value' => ($propertyDetail->is_armed_force == true) ? 'Yes' : 'No'],
            ['displayString' => 'Is Specially Abled', 'key' => 'isSpeciallyAbled', 'value' => ($propertyDetail->is_specially_abled == true) ? 'Yes' : 'No'],
            ['displayString' => 'Owner', 'key' => 'ownerName', 'value' => $propertyDetail->owner_name],
            ['displayString' => 'Waiver Applied For', 'key' => 'appliedFor', 'value' => $propertyDetail->applied_for],
        ]);

        $cardElement = [
            'headerTitle' => "Waiver Details",
            'data' => $propertyDetails
        ];
        return $cardElement;
    }
}
