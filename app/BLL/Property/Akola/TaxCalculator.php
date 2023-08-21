<?php

namespace App\BLL\Property\Akola;

use App\Models\Property\RefPropConstructionType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

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
    private $_maintancePerc;
    private $_refPropConstTypes;
    private $_mRefPropConsTypes;
    private $_calculationDateFrom;
    private $_agingPercs;
    /**
     * | Initialization
     */
    public function __construct(Request $req)
    {
        $this->_REQUEST = $req;
        $this->_carbonToday = Carbon::now();
        $this->_mRefPropConsTypes = new RefPropConstructionType();
        $this->_agingPercs = Config::get('PropertyConstaint.AGING_PERC');
    }

    /**
     * | Calculate Tax
     */
    public function calculateTax()
    {
        $this->readCalculatorParams();

        $this->generateFloorWiseTax();

        $this->generateVacantWiseTax();

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

        if ($propMonth > 3) {                                                           // Adjustment of Pending Years by Financial Years
            $this->_GRID['pendingYrs'] = $this->_pendingYrs;
        }

        if ($propMonth < 4) {
            $this->_propFyearFrom = $this->_propFyearFrom - 1;
            $this->_pendingYrs = ($currentFYear - $this->_propFyearFrom) + 1;
            $this->_GRID['pendingYrs'] =  $this->_pendingYrs;                               // Calculate Total Fyears
        }

        $this->_calculatorParams = [
            'areaOfPlot' => $this->_REQUEST->areaOfPlot * 0.092903,
            'category' => $this->_REQUEST->category,
            'dateOfPurchase' => $this->_REQUEST->dateOfPurchase,
            'floors' => $this->_REQUEST->floor
        ];

        $this->_maintancePerc = 10;

        if ($this->_REQUEST->propertyType != 4)                                            // i.e for building case
            $this->_calculationDateFrom = collect($this->_REQUEST->floor)->sortBy('dateFrom')->first()['dateFrom'];
        else
            $this->_calculationDateFrom = $this->_REQUEST->dateOfPurchase;
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
                $agingPerc = $this->readAgingByFloor($item);

                $floorBuildupArea = roundFigure($item->buildupArea * 0.092903);
                $alv = roundFigure($floorBuildupArea * $rate);
                $maintance10Perc = roundFigure(($alv * $this->_maintancePerc) / 100);
                $valueAfterMaintanance = roundFigure($alv - $maintance10Perc);
                $aging = roundFigure(($valueAfterMaintanance * $agingPerc) / 100);
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

                $isCommercial = ($item->usageType == 11) ? false : true;                    // Residential usage type id

                $stateTaxes = $this->readStateTaxes($alv, $isCommercial);                   // Read State Taxes

                $this->_floorsTaxes[$key] = [
                    'dateFrom' => $item->dateFrom,
                    'appliedFrom' => getFY($item->dateFrom),
                    'rate' => $rate,
                    'floorKey' => $key,
                    'floorNo' => $item->floorNo,
                    'buildupAreaInSqmt' => $floorBuildupArea,
                    'alv' => $alv,
                    'maintancePerc' => $this->_maintancePerc,
                    'maintantance10Perc' => $maintance10Perc,
                    'valueAfterMaintance' => $valueAfterMaintanance,
                    'agingPerc' => $agingPerc,
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
     * | Read Rate to Calculate ALV of the floor
     */
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
     * 
     * | Read aging of the floor
     */
    public function readAgingByFloor($item)
    {
        $agings = $this->_agingPercs;
        $constYear = Carbon::parse($item->dateFrom)->diffInYears(Carbon::now());
        $perc = 0;
        if ($constYear > 10) {
            $perc = collect($agings)->where('const_id', $item->constructionType)
                ->where('range_from', '<=', $constYear)
                ->sortByDesc('range_from')
                ->first();
            $perc = $perc['aging_perc'];
        }
        return $perc;
    }

    /**
     * | Calculate Vacant wise Tax
     */
    public function generateVacantWiseTax()
    {
        if ($this->_REQUEST->propertyType == 4) {
            $agingPerc = 0;                         // No Aging Percent for Vacant Land
            if ($this->_REQUEST->category == 1)
                $rate = 11;
            elseif ($this->_REQUEST->category == 1)
                $rate = 10;
            else
                $rate = 8;

            $alv = roundFigure($this->_calculatorParams['areaOfPlot'] * $rate);
            $maintance10Perc = roundFigure(($alv * $this->_maintancePerc) / 100);
            $valueAfterMaintanance = roundFigure($alv - $maintance10Perc);
            $aging = roundFigure(($valueAfterMaintanance * $agingPerc) / 100);
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
                'agingPerc' => $agingPerc,
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
        $today = Carbon::now()->format('Y-m-d');
        $fyearWiseTaxes = collect();
        // Act Of limitation
        $yearDiffs = Carbon::parse($this->_calculationDateFrom)->diffInYears(Carbon::now());                // year differences
        $this->_GRID['demandPendingYrs'] = $yearDiffs;

        if ($yearDiffs >= 5) {
            $this->_GRID['demandPendingYrs'] = 5;
            $this->_calculationDateFrom = Carbon::now()->addYears(-4)->format('Y-m-d');
        }
        // Act Of Limitations end
        while ($this->_calculationDateFrom <= $today) {
            $annualTaxes = collect($this->_floorsTaxes)->where('dateFrom', '<=', $this->_calculationDateFrom);
            $fyear = getFY($this->_calculationDateFrom);
            $yearTax = $this->sumTaxes($annualTaxes);

            $fyearWiseTaxes->put($fyear, array_merge($yearTax, ['fyear' => $fyear]));
            $this->_calculationDateFrom = Carbon::parse($this->_calculationDateFrom)->addYear()->format('Y-m-d');
        }
        $this->_GRID['fyearWiseTaxes'] = $fyearWiseTaxes;
        $this->_GRID['grandTaxes'] = $this->sumTaxes($fyearWiseTaxes);
    }
}
