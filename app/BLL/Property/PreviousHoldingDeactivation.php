<?php

namespace App\BLL\Property;

use App\Models\Property\PropDemand;
use App\Models\Property\PropProperty;
use Illuminate\Support\Facades\Config;

/**
 * | Created On-03-04-2023 
 * | Created By-Anshu Kumar
 * | Created for = The BLL for the Previous holding Deactivation application
 */
class PreviousHoldingDeactivation
{
    private $_mPropProperty;
    private $_reassessmentTypes;

    public function __construct()
    {
        $this->_mPropProperty = new PropProperty();
        $this->_reassessmentTypes = Config::get('PropertyConstaint.REASSESSMENT_TYPES');
    }
    /**
     * | @param safDetails Active Saf Details
     */
    public function deactivatePreviousHoldings($safDetails)
    {
        $assessmentType = $safDetails->assessment_type;
        // Deactivate for the kind of properties reassessment,mutation,amalgamation,bifurcation
        if (in_array($assessmentType, ['Mutation', 'Amalgamation'])) {
            $explodedPreviousHoldingIds = explode(',', $safDetails->previous_holding_id);
            $this->_mPropProperty->deactivateHoldingByIds($explodedPreviousHoldingIds);     // Deactivation of Holding
        }
    }

    /**
     * | Deactivate Previous Holding Demand
     * | @param safDetails Active Saf Details
     */
    public function deactivateHoldingDemands($safDetails)
    {
        $mPropDemand = new PropDemand();
        $assessmentType = $safDetails->assessment_type;
        if (in_array($assessmentType, $this->_reassessmentTypes)) {
            $explodedPreviousHoldingIds = explode(',', $safDetails->previous_holding_id);

            foreach ($explodedPreviousHoldingIds as $propId) {
                $unpaidDemand = $mPropDemand->getDemandByPropId($propId);
                if ($unpaidDemand->isNotEmpty())
                    $mPropDemand->deactivateDemand($propId);
            }
        }
    }
}
