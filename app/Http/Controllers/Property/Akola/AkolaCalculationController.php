<?php

namespace App\Http\Controllers\Property\Akola;

use App\BLL\Property\Akola\TaxCalculator;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;

/**
 * | Author-Anshu Kumar
 * | Created for the akola proeperty calculation
 */
class AkolaCalculationController extends Controller
{
    public function __construct()
    {
    }

    /**
     * | Calculate function
     */
    public function calculate(Request $req)
    {
        try {
            $taxCalculator = new TaxCalculator($req);
            $taxCalculator->calculateTax();
            $taxes = $taxCalculator->_GRID;
            return responseMsgs(true, "Calculated Tax", $taxes);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), []);
        }
    }
}
