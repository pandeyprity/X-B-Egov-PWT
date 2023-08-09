<?php

namespace App\Pipelines\CareTakers;

use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use Carbon\Carbon;
use Closure;
use Exception;

/**
 * | Created On-21-04-2023 
 * | Created By-Anshu Kumar
 * | Status - Closed
 * | ------------------------------
 * | PipeLine Class to Tag Property 
 */
class TagProperty
{
    private $_mActiveCitizenUnderCares;
    private $_mPropProperty;
    private $_currentDate;
    private $_mPropOwner;
    private $_propertyId;

    /**
     * | Initializing Master Values
     */
    public function __construct()
    {
        $this->_mActiveCitizenUnderCares = new ActiveCitizenUndercare();
        $this->_mPropProperty = new PropProperty();
        $this->_currentDate = Carbon::now();
        $this->_mPropOwner = new PropOwner();
    }

    /**
     * | Handle Class(1)
     */

    public function handle($request, Closure $next)
    {

        if (request()->input('moduleId') != 1) {
            return $next($request);
        }

        $referenceNo = request()->input('referenceNo');
        $property = $this->_mPropProperty->getPropByPtnOrHolding($referenceNo);
        $this->_propertyId = $property->id;
        $this->isPropertyAlreadyTagged();           // function (1.1)
        $propOwner = $this->_mPropOwner->getfirstOwner($property->id);
        $underCareReq = [
            'property_id' => $property->id,
            'date_of_attachment' => $this->_currentDate,
            'mobile_no' => $propOwner->mobile_no,
            'citizen_id' => auth()->user()->id
        ];
        $this->_mActiveCitizenUnderCares->store($underCareReq);
        return "Property Successfully Tagged";
    }

    /**
     * | Is The Property Already Tagged
     */
    public function isPropertyAlreadyTagged()
    {
        $taggedPropertyList = $this->_mActiveCitizenUnderCares->getTaggedProperties($this->_propertyId);
        $totalProperties = $taggedPropertyList->count('property_id');
        if ($totalProperties > 3)                                               // Check if the Property is already tagged 3 times of not
            throw new Exception("Property has already tagged 3 Times");

        $citizens = $taggedPropertyList->pluck('citizen_id');

        if ($citizens->contains(auth()->user()->id))                                // Check Is the Property already tagged by the citizen 
            throw new Exception("Property Already Tagged");
    }
}
