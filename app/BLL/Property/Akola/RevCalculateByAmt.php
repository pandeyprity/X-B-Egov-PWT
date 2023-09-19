<?php

namespace App\BLL\Property\Akola;

use Exception;
use Illuminate\Support\Facades\Config;

/**
 * | Created On-16-09-2023 
 * | Author - Anshu Kumar
 * | Get the value of all the taxes by Reverse Calculating amount
 */
class RevCalculateByAmt
{
    private $_reqAmt;
    private $_arrearTillFyear;
    public $_GRID;
    public $_stateTaxDtls;              // To calculate the State Educationtax perc and professional Tax Perc
    private $_stateTaxPerc;
    private $_professionalTaxPerc;
    private $_ulbTotalTaxes;

    // Initializing function 
    public function __construct()
    {
        $this->_arrearTillFyear = Config::get('akola-property-constant.ARREAR_TILL_FYEAR');
        $this->_ulbTotalTaxes = Config::get('akola-property-constant.ULB_TOTAL_TAXES');
    }

    /**
     * | Get the destination Taxes By The amount
     */
    public function calculateRev($amount)
    {
        $this->_reqAmt = $amount;

        $totalUlbTaxPercs = $this->calculateTotalTaxPerc();
        $taxableValue = roundFigure(($this->_reqAmt * 100) / $totalUlbTaxPercs);
        $taxes = [
            "general_tax" => roundFigure($taxableValue * 0.30),
            "road_tax" => roundFigure($taxableValue * 0.03),
            "firefighting_tax" => roundFigure($taxableValue * 0.02),
            "education_tax" => roundFigure($taxableValue * 0.02),
            "water_tax" => roundFigure($taxableValue * 0.02),
            "cleanliness_tax" => roundFigure($taxableValue * 0.02),
            "sewarage_tax" => roundFigure($taxableValue * 0.02),
            "tree_tax" => roundFigure($taxableValue * 0.01),
            // State Taxes
            "state_education_tax" => roundFigure(($taxableValue * $this->_stateTaxPerc) / 100),
            "professional_tax" => roundFigure(($taxableValue * $this->_professionalTaxPerc) / 100),
            "paid_status" => 0,
            "is_arrear" => true,
            "fyear" => $this->_arrearTillFyear
        ];

        $taxes['total_tax'] = round($taxes['general_tax'] + $taxes['road_tax'] + $taxes['firefighting_tax'] + $taxes['education_tax'] + $taxes['water_tax'] + $taxes['cleanliness_tax'] +
            $taxes['sewarage_tax'] + $taxes['tree_tax'] + $taxes['state_education_tax'] + $taxes['professional_tax']);

        $taxes['balance'] = $taxes['total_tax'];
        $this->_GRID = $taxes;
    }


    /**
     * | Total State Taxes Calculation
     */
    public function calculateTotalTaxPerc()
    {
        if ($this->_stateTaxDtls['alv'] == 0)
            throw new Exception("The given ALV is 0 cant able to calculate");
        $this->_stateTaxPerc = roundFigure(($this->_stateTaxDtls['state_education_tax'] / $this->_stateTaxDtls['alv']) * 100);
        $this->_professionalTaxPerc = ($this->_stateTaxDtls['professional_tax'] / $this->_stateTaxDtls['alv']) * 100;
        $totalPerc = $this->_ulbTotalTaxes + $this->_stateTaxPerc + $this->_professionalTaxPerc;
        return $totalPerc;
    }
}
