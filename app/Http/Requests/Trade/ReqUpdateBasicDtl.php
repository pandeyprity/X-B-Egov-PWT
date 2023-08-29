<?php

namespace App\Http\Requests\Trade;

use App\Models\Trade\ActiveTradeLicence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ReqUpdateBasicDtl extends TradeRequest
{
    public function __construct()
    {
        parent::__construct();

    }
    /**
     * Get the validation rules that apply to the request. 
     *
     * @return array
     */
    #jflkdj
    public function rules()
    {
        $refWorkflowId      = $this->_WF_MASTER_Id;
        $mUserType          = $this->_COMMON_FUNCTION->userType($refWorkflowId);        
        $mNowdate           = $this->_CURRENT_DATE;
        $mTimstamp          = $this->_CURRENT_DATE_TIME;
        $mRegex             = $this->_REX_ALPHA_NUM_DOT_SPACE;
        $mFramNameRegex     = $this->_REX_ALPHA_NUM_OPS_DOT_MIN_COM_AND_SPACE_SL; 
        $mOwnerName         = $this->_REX_OWNER_NAME;
        $mMobileNo          = $this->_REX_MOBILE_NO;

        $refOldLicece = ActiveTradeLicence::find($this->initialBusinessDetails["id"]??0);
        
        $rules["initialBusinessDetails.id"] = "required|digits_between:1,9223372036854775807";

        if ($refOldLicece && $refOldLicece->payment_status == 0) 
        {
            $rules["firmDetails.areaSqft"] = "required|numeric";
            $rules["firmDetails.firmEstdDate"] = "required|date";
            $rules["firmDetails.natureOfBusiness"] = "required|array";
            $rules["firmDetails.natureOfBusiness.*.id"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.tocStatus"] = "required|bool";
        }
        $rules["firmDetails.businessAddress"] = "required|regex:$mFramNameRegex";
        $rules["firmDetails.businessDescription"] = "required|regex:$mFramNameRegex";
        $rules["firmDetails.firmName"] = "required|regex:$mFramNameRegex";
        $rules["firmDetails.premisesOwner"] = "required|regex:$mFramNameRegex";
        $rules["firmDetails.newWardNo"] = "required|digits_between:1,9223372036854775807";
        $rules["firmDetails.wardNo"] = "required|digits_between:1,9223372036854775807";
        $rules["firmDetails.pincode"] = "required|digits:6|regex:/[0-9]{6}/|nullable";

        $rules["firmDetails.landmark"] = "regex:$mFramNameRegex|nullable";
        $rules["firmDetails.kNo"] = "digits|regex:/[0-9]{10}/";
        $rules["firmDetails.bindBookNo"] = "regex:$mRegex";
        $rules["firmDetails.accountNo"] = "regex:$mRegex";

        $rules["initialBusinessDetails.firmType"] = "required|digits_between:1,9223372036854775807";
        $rules["initialBusinessDetails.categoryTypeId"] = "required|digits_between:1,9223372036854775807";
        if (isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType'] == 5) 
        {
            $rules["initialBusinessDetails.otherFirmType"] = "required|regex:$mRegex";
        }
        $rules["initialBusinessDetails.ownershipType"] = "required|digits_between:1,9223372036854775807";

        $rules["ownerDetails"] = "required|array";
        $rules["ownerDetails.*.id"] = "nullable|digits_between:1,9223372036854775807";
        $rules["ownerDetails.*.businessOwnerName"] = "required|regex:$mOwnerName";
        $rules["ownerDetails.*.guardianName"] = "nullable|regex:$mOwnerName";
        $rules["ownerDetails.*.mobileNo"] = "required|digits:10|regex:$mMobileNo";
        $rules["ownerDetails.*.email"] = "email|nullable";

        return $rules;
    }   
}