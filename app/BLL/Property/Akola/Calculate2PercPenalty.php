<?php

namespace App\BLL\Property\Akola;

use Carbon\Carbon;

/**
 * | Author - Anshu Kumar
 * | Created On-21-09-2023 
 * | @desc Calculation for 2 perc penalty
 */
class Calculate2PercPenalty
{
    /**
     * | @param demand 
     */
    public function calculatePenalty($demand)
    {
        $currentFy = getFY();
        $currentMonth = Carbon::now()->format('m');
        $currentFyMonths = $currentMonth - 4;                   // Start of the month april
        $monthlyBalance = 0;
        $noOfPenalMonths = 0;

        $demand = (object)$demand;
        if ($demand->fyear == $currentFy) {
            $noOfPenalMonths = 0;                               // Start of the month april
            $monthlyBalance = $demand->balance / 12;
        }

        if ($demand->fyear < $currentFy && $demand->has_partwise_paid == false) {               // if the citizen has paid the tax part wise then avert the one percent penalty Calculation
            $noOfPenalMonths = 1;                                  // Start of the month april(if the fyear is past)
            $monthlyBalance = $demand->balance;
        }

        if ($demand->fyear > $currentFy) {
            $noOfPenalMonths = 0;
            $monthlyBalance = $demand->balance / 12;
        }

        $amount = $monthlyBalance * $noOfPenalMonths;
        $penalAmt = $amount * 0.02;
        $demand->monthlyPenalty = roundFigure($penalAmt);
    }

    /**
     * | Calculate Arrear Penalty
     */
    public function calculateArrearPenalty($arrear)
    {
        $currentMonth = Carbon::now()->format('m');
        $currentFyMonths = $currentMonth - 4;
        $arrearPenalty = $arrear * $currentFyMonths * 0.02;
        return roundFigure($arrearPenalty);
    }
}
