<?php

namespace App\Repository\Property\Interfaces;

use Illuminate\Http\Request;

/**
 * | Created On-10-08-2022 
 * | Created By
 * ------------------------------------------------------------------------------------------
 * | Interface for Eloquent Calculator Repository
 */
interface iCalculatorRepository
{
    public function safCalculator($request);                            // Get calculated property tax
    public function getDashboardData($request);
    
}
