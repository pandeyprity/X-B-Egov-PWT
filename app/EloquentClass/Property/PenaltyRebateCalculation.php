<?php

namespace App\EloquentClass\Property;

use App\Models\Property\PropActiveSafsOwner;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;

/**
 * | Created On-31-01-2023 
 * | Created By-Anshu Kumar
 * | Calculation for the Penalty and Rebate of Property and SAF
 * | Status-Open
 */

class PenaltyRebateCalculation
{
    public $_todayDate;
    public $_currentQuarter;

    public function __construct()
    {
        $this->_todayDate = Carbon::now();
        $this->_currentQuarter = calculateQtr($this->_todayDate->format('Y-m-d'));
    }
    /**
     * | One Percent Penalty Calculation
     * | @param quarterDueDate The floor/Property Due Date
     */
    public function calcOnePercPenalty($quarterDueDate)
    {
        $currentDate = Carbon::now();
        $currentDueDate = Carbon::now()->lastOfQuarter()->floorMonth();
        $quarterDueDate = Carbon::parse($quarterDueDate)->floorMonth();
        $diffInMonths = $quarterDueDate->diffInMonths($currentDate);
        if ($quarterDueDate >= $currentDueDate)                                       // Means the quarter due date is on current quarter or next quarter
            $onePercPenalty = 0;
        else
            $onePercPenalty = $diffInMonths;

        return $onePercPenalty;
    }

    /**
     * | Read Rebate
     * | @param currentQuarter Current Date Quarter
     * | @param loggedInUserType Logged In User type
     * | @param mLastQuarterDemand Last Quarter Demand
     * | @param ownerDetails First owner Details
     */
    public function readRebates($currentQuarter, $loggedInUserType, $mLastQuarterDemand, $ownerDetails, $totalDemand, $totalDuesList)
    {
        $rebatePenalMstrs = Config::get('PropertyConstaint.REBATE_PENAL_MASTERS');
        $currentDate = Carbon::now();
        $firstQtrRebate = collect($rebatePenalMstrs)->where('id', 2)->first();
        $citizenRebate = collect($rebatePenalMstrs)->where('id', 3)->first();
        $jskRebate = collect($rebatePenalMstrs)->where('id', 4)->first();
        $speciallyAbledRebate = collect($rebatePenalMstrs)->where('id', 6)->first();

        $rebates = array();
        $rebate1 = 0;
        $rebate = 0;
        $rebateAmount = 0;
        $seniorCitizen = 60;
        $specialRebateAmt = 0;

        if ($currentQuarter == 1) {                                                         // Rebate On Financial Year Payment On 1st Quarter
            $rebate1 += $firstQtrRebate['perc'];
            $rebateValue = roundFigure(($mLastQuarterDemand * 5) / 100);
            $rebateAmount += $rebateValue;
            array_push($rebates, [
                "rebateTypeId" => $firstQtrRebate['id'],
                "rebateType" => $firstQtrRebate['key'],
                "rebatePerc" => $firstQtrRebate['perc'],
                "rebateAmount" =>  roundFigure($rebateAmount),
                "keyString" => $firstQtrRebate['value']
            ]);
        }

        if ($loggedInUserType == 'Citizen') {                                         // In Case of Citizen
            $rebate1 += $citizenRebate['perc'];
            $rebateValue = roundFigure(($mLastQuarterDemand * $citizenRebate['perc']) / 100);
            $rebateAmount += $rebateValue;
            array_push($rebates, [
                "rebateTypeId" => $citizenRebate['id'],
                "rebateType" => $citizenRebate['key'],
                "rebatePerc" => $citizenRebate['perc'],
                "rebateAmount" => roundFigure($rebateValue),
                "keyString" => $citizenRebate['value']
            ]);
        }
        if ($loggedInUserType == 'JSK') {                                              // In Case of JSK
            $rebate1 += $jskRebate['perc'];
            $rebateValue = roundFigure(($mLastQuarterDemand * $jskRebate['perc']) / 100);
            $rebateAmount += $rebateValue;
            array_push($rebates, [
                "rebateTypeId" => $jskRebate['id'],
                "rebateType" => $jskRebate['key'],
                "rebatePerc" => $jskRebate['perc'],
                "rebateAmount" => roundFigure($rebateValue),
                "keyString" => $jskRebate['value']
            ]);
        }

        if (isset($ownerDetails)) {
            $years = $currentDate->diffInYears(Carbon::parse($ownerDetails['dob']));
            if (
                $ownerDetails['is_armed_force'] == 1 || $ownerDetails['is_specially_abled'] == 1 ||
                $ownerDetails['gender']  == 'Female' || $ownerDetails['gender'] == 'Transgender'  || $years >= $seniorCitizen
            ) {
                $rebate += $speciallyAbledRebate['perc'];
                $specialRebateAmt = roundFigure(($totalDemand * $speciallyAbledRebate['perc']) / 100);
                array_push($rebates, [
                    "rebateType" => $speciallyAbledRebate['key'],
                    "rebatePerc" => $speciallyAbledRebate['perc'],
                    "rebateAmount" => roundFigure($specialRebateAmt),
                    "keyString" => $speciallyAbledRebate['value']
                ]);
            }
        }

        $totalDuesList['rebates'] = $rebates;
        $totalDuesList['rebatePerc'] = $rebate1;
        $totalDuesList['rebateAmt'] = roundFigure($rebateAmount);
        $totalDuesList['specialRebatePerc'] = $rebate;
        $totalDuesList['specialRebateAmt'] = roundFigure($specialRebateAmt);

        return $totalDuesList;
    }
}
