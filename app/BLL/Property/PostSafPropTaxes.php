<?php

namespace App\BLL\Property;

use App\Models\Property\PropSafTax;
use App\Models\Property\PropTax;

/**
 * | Created On-22-05-2023 
 * | Author-Anshu Kumar
 * | Created for the Group by entry for saf and prop taxes
 */
class PostSafPropTaxes
{
    private $_mPropSafTaxes;
    private $_mPropTaxes;
    public function __construct()
    {
        $this->_mPropSafTaxes = new PropSafTax();
        $this->_mPropTaxes = new PropTax();
    }

    /**
     * | Post Saf Taxes
     */
    public function postSafTaxes($safId, array $demands, $ulbId = null)
    {
        $groupByQuaterlyTax = collect($demands)->groupBy('amount');
        $isTaxesExists = $this->_mPropSafTaxes->getSafTaxesBySafId($safId);
        if ($isTaxesExists->isNotEmpty())
            $this->_mPropSafTaxes->deactivateTaxes($safId);            // Deactivate Already Existing Saf Details
        foreach ($groupByQuaterlyTax as $item) {
            $firstTax = $item->first();
            if (is_array($firstTax))
                $firstTax = (object) $firstTax;
            $reqPost = [
                'saf_id' => $safId,
                'arv' => $firstTax->arv,
                'holding_tax' => $firstTax->holding_tax,
                'water_tax' => $firstTax->water_tax,
                'education_cess' => $firstTax->education_cess,
                'health_cess' => $firstTax->health_cess,
                'latrine_tax' => $firstTax->latrine_tax,
                'additional_tax' => $firstTax->additional_tax,
                'qtr' => $firstTax->qtr,
                'fyear' => $firstTax->fyear,
                'quarterly_tax' => $firstTax->amount,
                'ulb_id' => $ulbId
            ];
            $this->_mPropSafTaxes->postTaxes($reqPost);
        }
    }


    /**
     * | Post Prop Taxes
     */
    public function postPropTaxes($propId, array $demands)
    {
        $groupByQuaterlyTax = collect($demands)->groupBy('totalTax');
        $ifTaxesExists = $this->_mPropTaxes->getPropTaxesByPropId($propId);
        if ($ifTaxesExists)
            $this->_mPropTaxes->deactivatePropTax($propId);

        foreach ($groupByQuaterlyTax as $item) {
            $firstTax = $item->first();
            $reqPost = [
                'prop_id' => $propId,
                'arv' => $firstTax['arv'],
                'holding_tax' => $firstTax['holdingTax'],
                'water_tax' => $firstTax['waterTax'],
                'education_cess' => $firstTax['educationTax'],
                'health_cess' => $firstTax['healthCess'],
                'latrine_tax' => $firstTax['latrineTax'],
                'additional_tax' => $firstTax['additionTax'],
                'qtr' => $firstTax['qtr'],
                'fyear' => $firstTax['quarterYear'],
                'quarterly_tax' => $firstTax['totalTax']
            ];
            $this->_mPropTaxes->postTaxes($reqPost);
        }
    }
}
