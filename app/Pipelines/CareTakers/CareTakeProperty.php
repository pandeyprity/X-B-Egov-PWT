<?php

namespace App\Pipelines\CareTakers;

use App\Http\Controllers\ThirdPartyController;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use Closure;
use Exception;

/**
 * | Created On-21-04-2023 
 * | Created By-Sam Kerketta
 * | Status - open
 * | ------------------------------
 * | PipeLine Class to Tag Trade 
 */
class CareTakeProperty
{
    private $_mActiveCitizenUnderCares;
    private $_mPropProperty;
    private $_mPropOwner;
    private $_propertyId;
    private $_ThirdPartyController;

    /**
     * | Initializing Master Values
     */
    public function __construct()
    {
        $this->_mActiveCitizenUnderCares = new ActiveCitizenUndercare();
        $this->_mPropProperty = new PropProperty();
        $this->_mPropOwner = new PropOwner();
        $this->_ThirdPartyController = new ThirdPartyController();
    }

    public function handle($request, Closure $next)
    {
        if (request()->input('moduleId') != 1) {
            return $next($request);
        }

        $referenceNo = request()->input('referenceNo');
        $property = $this->_mPropProperty->getPropByPtnOrHolding($referenceNo);
        $this->_propertyId = $property->id;
        $this->isPropertyAlreadyTagged();                                           // function (1.1)
        $propOwner = $this->_mPropOwner->getfirstOwner($property->id);

        $myRequest = new \Illuminate\Http\Request();
        $myRequest->setMethod('POST');
        $myRequest->request->add(['mobileNo' => $propOwner->mobile_no]);
        $otpResponse = $this->_ThirdPartyController->sendOtp($myRequest);
        $verificationStatus = collect($otpResponse)['original']['status'];
        if ($verificationStatus == false)
            throw new Exception(collect($otpResponse)['original']['message']);

        $response = collect($otpResponse)->toArray();
        $data = [
            'otp' => $response['original']['data'],
            'mobileNo' => $propOwner->mobile_no
        ];
        return $data;
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

        if ($citizens->contains(authUser()->id))                                // Check Is the Property already tagged by the citizen 
            throw new Exception("Property Already Tagged");
    }
}
