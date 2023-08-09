<?php

namespace App\Traits\Property;

use App\Models\Masters\RefRequiredDocument;
use App\Models\Workflows\WfActiveDocument;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Config;

/**
 * | Trait Used for Gettting the Document Lists By Property Types and Owner Details
 * | Created On-02-02-2022 
 * | Created By-Anshu Kumar
 */
trait SafDoc
{
    private $_refSafs;
    private $_documentLists;
    private $_mRefReqDocs;
    private $_moduleId;
    private $_propLists;
    private $_propDocList;

    public function __construct()
    {
        $this->_moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $this->_mRefReqDocs = new RefRequiredDocument();
        $this->_propDocList = $this->_mRefReqDocs->getDocsByModuleId($this->_moduleId);
    }

    public function getPropTypeDocList($refSafs)
    {
        $propTypes = Config::get('PropertyConstaint.PROPERTY-TYPE');
        $propType = $refSafs->prop_type_mstr_id;
        $flip = flipConstants($propTypes);
        $this->_refSafs = $refSafs;
        switch ($propType) {
            case $flip['FLATS / UNIT IN MULTI STORIED BUILDING']:
                $this->_documentLists = collect($this->_propDocList)->where('code', 'PROP_FLATS')->first()->requirements;
                break;
            case $flip['INDEPENDENT BUILDING']:
                $this->_documentLists = collect($this->_propDocList)->where('code', 'PROP_INDEPENDENT_BUILDING')->first()->requirements;
                break;
            case $flip['SUPER STRUCTURE']:
                $this->_documentLists = collect($this->_propDocList)->where('code', 'PROP_SUPER_STRUCTURE')->first()->requirements;
                break;
            case $flip['VACANT LAND']:
                $this->_documentLists = collect($this->_propDocList)->where('code', 'PROP_VACANT_LAND')->first()->requirements;
                break;
            case $flip['OCCUPIED PROPERTY']:
                $this->_documentLists = collect($this->_propDocList)->where('code', 'PROP_OCCUPIED_PROPERTY')->first()->requirements;
                break;
        }
        if ($refSafs->assessment_type == 'Mutation')
            $this->mutationReqDocuments();

        if ($refSafs->is_trust == true)
            $this->_documentLists .= collect($this->_propDocList)->where('code', 'PROP_TRUST')->first()->requirements;

        return $this->_documentLists;
    }

    /**
     * | Mutation Required Documents
     */
    public function mutationReqDocuments()
    {
        $transferModes = $this->_refSafs->transfer_mode_mstr_id;
        switch ($transferModes) {
            case 1:
                $this->_documentLists .=  collect($this->_propDocList)->where('code', 'PROP_MUTATION_SALE_TRANSFER')->first()->requirements;                     // Sale Document Add
                break;
            case 2:
                $this->_documentLists .= collect($this->_propDocList)->where('code', 'PROP_MUTATION_GIFT_TRANSFER')->first()->requirements;                     // Gift Document Add
                break;
            case 3:
                $this->_documentLists .= collect($this->_propDocList)->where('code', 'PROP_MUTATION_WILL_TRANSFER')->first()->requirements;                    // Will Document Add
                break;
            case 4:
                $this->_documentLists .=  collect($this->_propDocList)->where('code', 'PROP_MUTATION_LEASE_TRANSFER')->first()->requirements;                     // Lease Document Add
                break;
            case 5:
                $this->_documentLists .= collect($this->_propDocList)->where('code', 'PROP_MUTATION_PARTITION_TRANSFER')->first()->requirements;                    // Partition Document Add
                break;
            case 6:
                $this->_documentLists .=  collect($this->_propDocList)->where('code', 'PROP_MUTATION_SUCCESSION_TRANSFER')->first()->requirements;                     // Succession Document Add
                break;
        }
    }

    /**
     * | Get Owner Document Lists
     */
    public function getOwnerDocs($refOwners)
    {
        $mRefReqDocs = new RefRequiredDocument();
        $moduleId = Config::get('module-constants.PROPERTY_MODULE_ID');
        $isSpeciallyAbled = $refOwners->is_specially_abled;
        $isArmedForce = $refOwners->is_armed_force;

        if ($isSpeciallyAbled == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_IS_SPECIALLY_ABLED")->requirements;

        if ($isArmedForce == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_IS_ARMED_FORCE")->requirements;

        if ($isSpeciallyAbled == true && $isArmedForce == true)
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_SPECIALLY_ARMED")->requirements;

        if ($isSpeciallyAbled == false && $isArmedForce == false)                                           // Condition for the Extra Documents
            $documentList = $mRefReqDocs->getDocsByDocCode($moduleId, "OWNER_EXTRA_DOCUMENT")->requirements;

        return $documentList;
    }
}
