<?php

namespace App\BLL\Property\Akola;

use App\Models\Property\RefPropConstructionType;
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
    private $_refPropConstTypes;
    private $_mRefPropConsTypes;
    /**
     * | Initialization
     */
    public function __construct(Request $req)
    {
        $this->_REQUEST = $req;
        $this->_carbonToday = Carbon::now();
        $this->_mRefPropConsTypes = new RefPropConstructionType();
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
    }

    /**
     * | Read Params
     */
    public function readCalculatorParams()
    {
        $this->_refPropConstTypes = $this->_mRefPropConsTypes->propConstructionType();
        $this->_propFyearFrom = Carbon::parse($this->_REQUEST->dateOfPurchase)->format('Y');
        $currentFYear = $this->_carbonToday->format('Y');
        $this->_pendingYrs = ($currentFYear - $this->_propFyearFrom) + 1;                      // Read Total FYears
        $propMonth = Carbon::parse($this->_REQUEST->dateOfPurchase)->format('m');

        if ($propMonth > 3) {
            $this->_GRID['pendingYrs'] = $this->_pendingYrs;
            $this->_GRID['demandPendingYrs'] = $this->_pendingYrs;
        }

        if ($propMonth < 4) {
            $this->_propFyearFrom = $this->_propFyearFrom - 1;
            $this->_pendingYrs = ($currentFYear - $this->_propFyearFrom) + 1;
            $this->_GRID['pendingYrs'] =  $this->_pendingYrs;                               // Calculate Total Fyears
            $this->_GRID['demandPendingYrs'] = $this->_pendingYrs;
        }

        $this->_calculatorParams = [
            'areaOfPlot' => $this->_REQUEST->areaOfPlot * 0.092903,
            'category' => $this->_REQUEST->category,
            'dateOfPurchase' => $this->_REQUEST->dateOfPurchase,
            'floors' => $this->_REQUEST->floor
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
            foreach ($this->_REQUEST->floor as $key => $item) {
                $item = (object)$item;

                $rate = $this->readRateByFloor($item);

                $floorBuildupArea = roundFigure($item->buildupArea * 0.092903);
                $alv = roundFigure($floorBuildupArea * $rate);
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
                    'rate' => $rate,
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

    public function readRateByFloor($item)
    {
        $constType = $this->_refPropConstTypes->where('id', $item->constructionType);
        $category = $this->_REQUEST->category;
        if ($category == 1)
            $rate = $constType->first()->category1_rate;
        elseif ($category == 2)
            $rate = $constType->first()->category2_rate;
        else
            $rate = $constType->first()->category3_rate;

        return $rate;
    }

    /**
     * | Calculate Vacant wise Tax
     */
    public function generateVacantWiseTax()
    {
        if ($this->_REQUEST->propertyType == 4) {
            if ($this->_REQUEST->category == 1)
                $rate = 11;
            elseif ($this->_REQUEST->category == 1)
                $rate = 10;
            else
                $rate = 8;

            $alv = roundFigure($this->_calculatorParams['areaOfPlot'] * $rate);
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
                'rate' => $rate,
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
        $isFyearBack = false;
        $pendingYrs = $this->_pendingYrs;
        $pendingYrsFrom = $this->_propFyearFrom;
        // Checking Act Of Limitation
        if ($this->_pendingYrs > 5) {
            $pendingYrsFrom = $this->_carbonToday->format('Y') - 4;
            $pendingYrs = 5;
            $this->_GRID['demandPendingYrs'] = $pendingYrs;                     // After Appling Act of limitation
        }

        if ($this->_carbonToday->format('m') < 4) {                             // Check if financial year fulfilled
            $isFyearBack = true;                                                // Checks if the Fyear Started or Not means fyear month < 4
            $pendingYrsFrom = $pendingYrsFrom - 1;
        }

        for ($i = 0; $i < $pendingYrs; $i++) {
            $fyear = ($pendingYrsFrom + $i) . '-' . ($pendingYrsFrom + $i + 1);
            if ($i == 0)                                                  // Pending From year
                $this->_GRID['pendingFromFyear'] = $fyear;

            $isLastIndex = $i == $pendingYrs - 1;
            if ($isLastIndex)                                   // Pending Upto Year
                $this->_GRID['pendingUptoFyear'] = $fyear;

            $fyearWiseTaxes->put($fyear, array_merge($this->_GRID['annualTaxes'], ['fyear' => $fyear]));
            if ($isFyearBack && $isLastIndex)
                break;
        }

        $this->_GRID['fyearWiseTaxes'] = $fyearWiseTaxes->toArray();
        $this->_GRID['grandTaxes'] = $this->sumTaxes($fyearWiseTaxes);
    }
}
