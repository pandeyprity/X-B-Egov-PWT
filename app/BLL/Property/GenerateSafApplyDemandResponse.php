<?php

namespace App\BLL\Property;

/**
 * | Created On-13-04-2023 
 * | Created by-Anshu Kumar
 * | Created For- Response Demand Response for Saf Apply Form
 */

class GenerateSafApplyDemandResponse
{
    /**
     * | @param CalculatedDemand Response
     * | @param isResidential Property Residential Status
     */
    public function generateResponse($calculatedDemand, $isResidential)
    {
        $finalResponseDemand = array();
        $responseQuaterlyTaxes = array();
        $amounts = array();
        $demand = $calculatedDemand['demand'];
        $details = $calculatedDemand['details'];

        foreach ($details as $detail) {
            $quaterlyTax = [
                "quarterYear" => $detail['fyear'],
                "qtr" => $detail['qtr'],
                "dueDate" => $detail['due_date'],
                "rwhPenalty" => $detail['rwhPenalty'],
                "onePercPenalty" => $detail['onePercPenalty'],
                "onePercPenaltyTax" => $detail['onePercPenaltyTax'],
                "arv" => $detail['arv'],
                "holdingTax" => $detail['holding_tax'],
                "waterTax" => $detail['water_tax'],
                "educationTax" => $detail['education_cess'],
                "healthCess" => $detail['health_cess'],
                "latrineTax" => $detail['latrine_tax'],
                "additionTax" => $detail['additional_tax'],
                "totalTax" => $detail['amount'],
                "ruleSet" => $detail['ruleSet'],
                "adjustAmount" => $detail['adjust_amount'],
                "balance" => $detail['balance'],
            ];

            array_push($responseQuaterlyTaxes, $quaterlyTax);
        }

        $amounts['totalTax'] = $demand['totalTax'];
        $amounts['totalOnePercPenalty'] = $demand['totalOnePercPenalty'];
        $amounts['totalQuarters'] = $demand['totalQuarters'];
        $amounts['fromQuarterYear'] = $demand['dueFromFyear'];
        $amounts['fromQuarter'] = $demand['dueFromQtr'];
        $amounts['toQuarterYear'] = $demand['dueToFyear'];
        $amounts['toQuarter'] = $demand['dueToQtr'];
        $amounts['isResidential'] = $isResidential;            // to write
        $amounts['lateAssessmentStatus'] = $demand['lateAssessmentStatus'];
        $amounts['lateAssessmentPenalty'] = $demand['lateAssessmentPenalty'];
        $amounts['totalDemand'] = $demand['totalDemand'];
        $amounts['payableAmount'] = $demand['payableAmount'];
        $amounts['rebates'] = $demand['rebates'];
        $amounts['rebatePerc'] = $demand['rebatePerc'];
        $amounts['rebateAmt'] = $demand['rebateAmt'];
        $amounts['specialRebatePerc'] = $demand['specialRebatePerc'];
        $amounts['specialRebateAmt'] = $demand['specialRebateAmt'];

        $finalResponseDemand['amounts'] = $amounts;
        $finalResponseDemand['details'] = collect($responseQuaterlyTaxes)->groupBy('ruleSet');
        return $finalResponseDemand;
    }
}
