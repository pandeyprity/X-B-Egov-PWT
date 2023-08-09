<?php

namespace App\BLL\Property;

use Illuminate\Support\Facades\Config;

/**
 * | Created On-15-04-2023 
 * | Created by-Anshu Kumar
 * | Created For Reading the Penalty and Rebate and Descriptions
 */

class PaymentReceiptHelper
{
    private $_penaltyRebateKeyStrings;
    public function __construct()
    {
        $this->_penaltyRebateKeyStrings = Config::get('PropertyConstaint.PENALTY_REBATE_KEY_STRINGS');
    }

    /**
     * | Read Taxes Descriptions(1.1)
     * | @param checkOtherTaxes first collection from the details
     */
    public function readDescriptions($checkOtherTaxes)
    {
        $taxes = [
            [
                "keyString" => "Holding Tax",
                "value" => $checkOtherTaxes->holding_tax
            ],
            [
                "keyString" => "Water Tax",
                "value" => $checkOtherTaxes->water_tax
            ],
            [
                "keyString" => "Education Cess",
                "value" => $checkOtherTaxes->education_cess
            ],
            [
                "keyString" => "Latrine Tax",
                "value" => $checkOtherTaxes->latrine_tax
            ]
        ];
        $filtered = collect($taxes)->filter(function ($tax, $key) {
            if ($tax['value'] > 0) {
                return $tax['keyString'];
            }
        });

        return $filtered;
    }

    /**
     * | Read Penalty Tax Details with Penalties and final payable amount(1.2)
     */
    public function readPenalyPmtAmts($lateAssessPenalty = 0, $onePercPenalty = 0, $rebate = 0, $specialRebate = 0, $firstQtrRebate = 0, $amount, $onlineRebate = 0)
    {
        $amount = [
            [
                "keyString" => $this->_penaltyRebateKeyStrings['lateAssessmentPenalty'],
                "value" => $lateAssessPenalty
            ],
            [
                "keyString" => $this->_penaltyRebateKeyStrings['onePercPenalty'],
                "value" => roundFigure((float)$onePercPenalty)
            ],
            [
                "keyString" => $this->_penaltyRebateKeyStrings['rebate'],
                "value" => roundFigure((float)$rebate)
            ],
            [
                "keyString" => $this->_penaltyRebateKeyStrings['onlineOrJskRebate'],
                "value" => roundFigure((float)$onlineRebate)
            ],
            [
                "keyString" => $this->_penaltyRebateKeyStrings['specialRebate'],
                "value" => roundFigure((float)$specialRebate)
            ],
            [
                "keyString" => $this->_penaltyRebateKeyStrings['firstQtrRebate'],
                "value" => roundFigure((float)$firstQtrRebate)
            ],
            [
                "keyString" => "Total Paid Amount",
                "value" => roundFigure((float)$amount)
            ],
            [
                "keyString" => "Remaining Amount",
                "value" => 0
            ]
        ];

        $tax = collect($amount)->filter(function ($value, $key) {
            return $value['value'] > 0;
        });

        return $tax->values();
    }


    /**
     * | Calculate Total Rebate Penalties 
     * | @param taxDetails containing all types of Penalty and Rebates
     */
    public function calculateTotalRebatePenals($taxDetails)
    {
        $totalRebatePenalties = array();

        $totalRebates = collect($taxDetails)
            ->whereIn('keyString', [
                $this->_penaltyRebateKeyStrings['rebate'],
                $this->_penaltyRebateKeyStrings['onlineOrJskRebate'],
                $this->_penaltyRebateKeyStrings['specialRebate'],
                $this->_penaltyRebateKeyStrings['firstQtrRebate'],
            ])
            ->sum('value');

        $totalPenalty = collect($taxDetails)
            ->whereIn('keyString', [
                $this->_penaltyRebateKeyStrings['lateAssessmentPenalty'],
                $this->_penaltyRebateKeyStrings['onePercPenalty']
            ])
            ->sum('value');

        $totalRebatePenalties['totalRebate'] = roundFigure($totalRebates);
        $totalRebatePenalties['totalPenalty'] = roundFigure($totalPenalty);

        return $totalRebatePenalties;
    }
}
