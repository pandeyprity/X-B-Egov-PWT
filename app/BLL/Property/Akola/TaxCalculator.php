<?php

namespace App\BLL\Property\Akola;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * | Author - Anshu Kumar
 * | Created On-12-08-2023 
 * | Status-Closed
 */
class TaxCalculator
{
    private $_REQUEST;
    private array $_calculatorParams;
    private array $_floorsTaxes;
    public array $_GRID;
    private $_pendingYrs;
    private $_carbonToday;
    private $_propFyearFrom;
    private $_agingPerc;
    private $_maintancePerc;
    /**
     * | Initialization
     */
    public function __construct(Request $req)
    {
        $this->_REQUEST = $req;
        $this->_carbonToday = Carbon::now();
    }

    /**
     * | Calculate Tax
     */
    public function calculateTax()
    {
        $this->readCalculatorParams();

        $this->generateFloorWiseTax();

        $this->generateVacantWiseTax();

        $this->generateAnnuallyTaxes();

        $this->generateFyearWiseTaxes();

        // dd($this->_GRID);
    }

    /**
     * | Read Params
     */
    public function readCalculatorParams()
    {
        $this->_propFyearFrom = Carbon::parse($this->_REQUEST->propertyDate)->format('Y');
        $currentFYear = $this->_carbonToday->format('Y');
        $this->_pendingYrs = ($currentFYear - $this->_propFyearFrom) + 1;                      // Read Total FYears
        $propMonth = Carbon::parse($this->_REQUEST->propertyDate)->format('m');

        if ($propMonth > 3)
            $this->_GRID['pendingYrs'] = $this->_pendingYrs;

        if ($propMonth < 4) {
            $this->_GRID['pendingYrs'] = $this->_pendingYrs + 1;
            $this->_propFyearFrom = $this->_propFyearFrom - 1;
        }

        $this->_calculatorParams = [
            'areaOfPlot' => $this->_REQUEST->areaOfPlot * 0.092903,
            'category' => $this->_REQUEST->category,
            'propertyDate' => $this->_REQUEST->propertyDate,
            'floors' => $this->_REQUEST->floors
        ];

        $this->_agingPerc = 5;
        $this->_maintancePerc = 10;
    }

    /**
     * | Calculate Floor Wise Calculation
     */
    public function generateFloorWiseTax()
    {
        if ($this->_REQUEST->propertyType != 4) {
            foreach ($this->_REQUEST->floors as $key => $item) {
                $item = (object)$item;
                $floorBuildupArea = roundFigure($item->builupArea * 0.092903);
                $alv = roundFigure($floorBuildupArea * 220);
                $maintance10Perc = roundFigure(($alv * $this->_maintancePerc) / 100);
                $valueAfterMaintanance = roundFigure($alv - $maintance10Perc);
                $aging = roundFigure(($valueAfterMaintanance * $this->_agingPerc) / 100);
                $taxValue = roundFigure($valueAfterMaintanance - $aging);

                // Municipal Taxes
                $generalTax = roundFigure($taxValue * 0.30);
                $roadTax = roundFigure($taxValue * 0.03);
                $firefightingTax = roundFigure($taxValue * 0.02);
                $educationTax = roundFigure($taxValue * 0.02);
                $waterTax = roundFigure($taxValue * 0.02);
                $cleanlinessTax = roundFigure($taxValue * 0.02);
                $sewerageTax = roundFigure($taxValue * 0.02);
                $treeTax = roundFigure($taxValue * 0.01);

                $isCommercial = ($item->usageType == 1) ? false : true;

                $stateTaxes = $this->readStateTaxes($alv, $isCommercial);                   // Read State Taxes

                $this->_floorsTaxes[$key] = [
                    'floorKey' => $key,
                    'floorNo' => $item->floorNo,
                    'buildupAreaInSqmt' => $floorBuildupArea,
                    'alv' => $alv,
                    'maintancePerc' => $this->_maintancePerc,
                    'maintantance10Perc' => $maintance10Perc,
                    'valueAfterMaintance' => $valueAfterMaintanance,
                    'agingPerc' => $this->_agingPerc,
                    'agingAmt' => $aging,
                    'taxValue' => $taxValue,
                    'generalTax' => $generalTax,
                    'roadTax' => $roadTax,
                    'firefightingTax' => $firefightingTax,
                    'educationTax' => $educationTax,
                    'waterTax' => $waterTax,
                    'cleanlinessTax' => $cleanlinessTax,
                    'sewerageTax' => $sewerageTax,
                    'treeTax' => $treeTax,
                    'isCommercial' => $isCommercial,
                    'stateEducationTaxPerc' => $stateTaxes['educationTaxPerc'],
                    'stateEducationTax' => $stateTaxes['educationTax'],
                    'professionalTaxPerc' => $stateTaxes['professionalTaxPerc'],
                    'professionalTax' => $stateTaxes['professionalTax'],
                ];
            }

            $this->_GRID['floorsTaxes'] = $this->_floorsTaxes;
        }
    }

    /**
     * | Calculate Vacant wise Tax
     */
    public function generateVacantWiseTax()
    {
        if ($this->_REQUEST->propertyType == 4) {
            $alv = roundFigure($this->_calculatorParams['areaOfPlot'] * 11);
            $maintance10Perc = roundFigure(($alv * $this->_maintancePerc) / 100);
            $valueAfterMaintanance = roundFigure($alv - $maintance10Perc);
            $aging = roundFigure(($valueAfterMaintanance * $this->_agingPerc) / 100);
            $taxValue = roundFigure($valueAfterMaintanance - $aging);

            // Municipal Taxes
            $generalTax = roundFigure($taxValue * 0.30);
            $roadTax = roundFigure($taxValue * 0.03);
            $firefightingTax = roundFigure($taxValue * 0.02);
            $educationTax = roundFigure($taxValue * 0.02);
            $waterTax = roundFigure($taxValue * 0.02);
            $cleanlinessTax = roundFigure($taxValue * 0.02);
            $sewerageTax = roundFigure($taxValue * 0.02);
            $treeTax = roundFigure($taxValue * 0.01);

            $isCommercial = false;

            $stateTaxes = $this->readStateTaxes($alv, $isCommercial);                   // Read State Taxes

            $this->_floorsTaxes[0] = [
                'floorKey' => "Vacant Land",
                'floorNo' => "Vacant Land",
                'alv' => $alv,
                'maintancePerc' => $this->_maintancePerc,
                'maintantance10Perc' => $maintance10Perc,
                'valueAfterMaintance' => $valueAfterMaintanance,
                'agingPerc' => $this->_agingPerc,
                'agingAmt' => $aging,
                'taxValue' => $taxValue,
                'generalTax' => $generalTax,
                'roadTax' => $roadTax,
                'firefightingTax' => $firefightingTax,
                'educationTax' => $educationTax,
                'waterTax' => $waterTax,
                'cleanlinessTax' => $cleanlinessTax,
                'sewerageTax' => $sewerageTax,
                'treeTax' => $treeTax,
                'isCommercial' => $isCommercial,
                'stateEducationTaxPerc' => $stateTaxes['educationTaxPerc'],
                'stateEducationTax' => $stateTaxes['educationTax'],
                'professionalTaxPerc' => $stateTaxes['professionalTaxPerc'],
                'professionalTax' => $stateTaxes['professionalTax'],
            ];
        }

        $this->_GRID['floorsTaxes'] = $this->_floorsTaxes;
    }

    /**
     * | read State Taxes
     */

    public function readStateTaxes($alv, $isCommercial)
    {
        // State Taxes
        if (is_between($alv, 0, 151)) {
            $stateEducationTaxPerc = $isCommercial ? 4 : 2;
            $stateEducationTax = roundFigure(($alv * $stateEducationTaxPerc) / 100);
            $professionalTaxPerc = $isCommercial ? 1 : 0;
            $professionalTax = roundFigure(($alv * $professionalTaxPerc) / 100);
        }

        if (is_between($alv, 150, 301)) {
            $stateEducationTaxPerc = $isCommercial ? 6 : 3;
            $stateEducationTax = roundFigure(($alv * $stateEducationTaxPerc) / 100);
            $professionalTaxPerc = $isCommercial ? 1.5 : 0;
            $professionalTax = roundFigure(($alv * $professionalTaxPerc) / 100);
        }

        if (is_between($alv, 300, 3001)) {
            $stateEducationTaxPerc = $isCommercial ? 8 : 4;
            $stateEducationTax = roundFigure(($alv * $stateEducationTaxPerc) / 100);
            $professionalTaxPerc = $isCommercial ? 2 : 0;
            $professionalTax = roundFigure(($alv * $professionalTaxPerc) / 100);
        }

        if (is_between($alv, 3000, 6001)) {
            $stateEducationTaxPerc = $isCommercial ? 10 : 5;
            $stateEducationTax = roundFigure(($alv * $stateEducationTaxPerc) / 100);
            $professionalTaxPerc = $isCommercial ? 2.5 : 0;
            $professionalTax = roundFigure(($alv * $professionalTaxPerc) / 100);
        }

        if ($alv > 6000) {
            $stateEducationTaxPerc = $isCommercial ? 12 : 6;
            $stateEducationTax = roundFigure(($alv * $stateEducationTaxPerc) / 100);
            $professionalTaxPerc = $isCommercial ? 3 : 0;
            $professionalTax = roundFigure(($alv * $professionalTaxPerc) / 100);
        }

        return [
            'educationTaxPerc' => $stateEducationTaxPerc,
            'educationTax' => $stateEducationTax,
            'professionalTaxPerc' => $professionalTaxPerc,
            'professionalTax' => $professionalTax,
        ];
    }

    /**
     * | Generation of Total Taxes
     */
    public function generateAnnuallyTaxes()
    {
        $floorTaxes = collect($this->_floorsTaxes);
        $this->_GRID['annualTaxes'] = $this->sumTaxes($floorTaxes);
    }

    /**
     * | Summation of Taxes
     */
    public function sumTaxes($floorTaxes)
    {
        $annualTaxes = $floorTaxes->pipe(function (Collection $taxes) {
            return [
                "alv" => (float)roundFigure($taxes->sum('alv')),
                "maintancePerc" => $taxes->sum('maintancePerc'),
                "maintantance10Perc" => roundFigure($taxes->sum('maintantance10Perc')),
                "valueAfterMaintance" => roundFigure($taxes->sum('valueAfterMaintance')),
                "agingPerc" => roundFigure($taxes->sum('agingPerc')),
                "agingAmt" => roundFigure($taxes->sum('agingAmt')),
                "taxValue" => roundFigure($taxes->sum('taxValue')),
                "generalTax" => roundFigure($taxes->sum('generalTax')),
                "roadTax" => roundFigure($taxes->sum('roadTax')),
                "firefightingTax" => roundFigure($taxes->sum('firefightingTax')),
                "educationTax" => roundFigure($taxes->sum('educationTax')),
                "waterTax" => roundFigure($taxes->sum('waterTax')),
                "cleanlinessTax" => roundFigure($taxes->sum('cleanlinessTax')),
                "sewerageTax" => roundFigure($taxes->sum('sewerageTax')),
                "treeTax" => roundFigure($taxes->sum('treeTax')),
                "stateEducationTaxPerc" => roundFigure($taxes->sum('stateEducationTaxPerc')),
                "stateEducationTax" => roundFigure($taxes->sum('stateEducationTax')),
                "professionalTaxPerc" => roundFigure($taxes->sum('professionalTaxPerc')),
                "professionalTax" => roundFigure($taxes->sum('professionalTax')),
            ];
        });
        $annualTaxes['totalTax'] = roundFigure($annualTaxes['generalTax'] + $annualTaxes['roadTax'] + $annualTaxes['firefightingTax'] + $annualTaxes['educationTax']
            + $annualTaxes['waterTax'] + $annualTaxes['cleanlinessTax'] + $annualTaxes['sewerageTax']
            + $annualTaxes['treeTax'] + $annualTaxes['stateEducationTax'] + $annualTaxes['professionalTax']);
        return $annualTaxes;
    }


    /**
     * | Grand Taxes
     */
    public function generateFyearWiseTaxes()
    {
        $fyearWiseTaxes = collect();
        for ($i = 0; $i < $this->_pendingYrs; $i++) {
            $fyear = ($this->_propFyearFrom + $i) . '-' . ($this->_propFyearFrom + $i + 1);
            $fyearWiseTaxes->put($fyear, $this->_GRID['annualTaxes']);
        }
        $this->_GRID['fyearWiseTaxes'] = $fyearWiseTaxes->toArray();
        $this->_GRID['grandTaxes'] = $this->sumTaxes($fyearWiseTaxes);
    }
}
