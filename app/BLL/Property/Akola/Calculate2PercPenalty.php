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

        $demand = (object)$demand;
        if ($demand->fyear == $currentFy) {
            $noOfPenalMonths = $currentMonth - 4;                   // Start of the month april
            $monthlyBalance = $demand->balance / 12;
        }

        if ($demand->fyear < $currentFy) {
            $noOfPenalMonths = 12;                                  // Start of the month april(if the fyear is past)
            $monthlyBalance = $demand->balance / 12;
        }

        if ($demand->fyear > $currentFy) {
            $noOfPenalMonths = 0;
            $monthlyBalance = $demand->balance / 12;
        }

        $amount = $monthlyBalance * $noOfPenalMonths;
        $penalAmt = $amount * 0.02;
        $demand->monthlyPenalty = roundFigure($penalAmt);
    }
}
