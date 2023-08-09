<?php

namespace App\Http\Requests\Trade;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;

class ReqAddRecorde extends TradeRequest
{
    public function __construct()
    {
        parent::__construct();

    }
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
        // $mAlphaSpace = '/^[a-zA-Z ]+$/i';
        // $mAlphaNumhyphen = '/^[a-zA-Z0-9- ]+$/i';
        // $mNumDot = '/^\d+(?:\.\d+)+$/i';
        // $mDateFormatYYYMMDD = '/^([12]\d{3}-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01]))+$/i';
        // $mDateFormatYYYMM = '/^([12]\d{3}-(0[1-9]|1[0-2]))+$/i';

        $rules["applicationType"] = $this->_REX_APPLICATION_TYPE;

        $mApplicationTypeId = $this->_TRADE_CONSTAINT["APPLICATION-TYPE"][$this->applicationType]??0;
        
        if (!in_array($mApplicationTypeId, [1]))
        {
            $rules["licenseId"] = "required|digits_between:1,9223372036854775807";
        }
        
        if (in_array($mApplicationTypeId, [1])) 
        {
            $rules["firmDetails.areaSqft"] = "required|numeric";
            $rules["firmDetails.businessAddress"] = "required|regex:$mFramNameRegex";
            $rules["firmDetails.businessDescription"] = "required|regex:$mFramNameRegex";
            $rules["firmDetails.firmEstdDate"] = "required|date";
            $rules["firmDetails.firmName"] = "required|regex:$mFramNameRegex";
            $rules["firmDetails.premisesOwner"] = "required|regex:/^[a-zA-Z1-9][a-zA-Z1-9\., \s]+$/";
            $rules["firmDetails.natureOfBusiness"] = "required|array";
            $rules["firmDetails.natureOfBusiness.*.id"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.newWardNo"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.wardNo"] = "required|digits_between:1,9223372036854775807";
            $rules["firmDetails.tocStatus"] = "required|bool";
            $rules["firmDetails.landmark"] = "regex:$mFramNameRegex";
            $rules["firmDetails.k_no"] = "digits|regex:/[0-9]{10}/";
            $rules["firmDetails.bind_book_no"] = "regex:$mRegex";
            $rules["firmDetails.account_no"] = "regex:$mRegex";
            if (strtoupper($mUserType) == "ONLINE") {
                $rules["firmDetails.pincode"] = "digits:6|regex:/[0-9]{6}/";
            }

            $rules["initialBusinessDetails.applyWith"] = "required|digits_between:1,9223372036854775807";
            $rules["initialBusinessDetails.firmType"] = "required|digits_between:1,9223372036854775807";
            $rules["initialBusinessDetails.categoryTypeId"] = "digits_between:1,9223372036854775807";
            if (isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType'] == 5) {
                $rules["initialBusinessDetails.otherFirmType"] = "required|regex:$mRegex";
            }
            $rules["initialBusinessDetails.ownershipType"] = "required|digits_between:1,9223372036854775807";
            if (isset($this->initialBusinessDetails['applyWith']) && $this->initialBusinessDetails['applyWith'] == 1) {
                $rules["initialBusinessDetails.noticeNo"] = "required";
                $rules["initialBusinessDetails.noticeDate"] = "required|date";
            }
            $rules["licenseDetails.licenseFor"] = "required|int";
            if ($mApplicationTypeId != 4 && strtoupper($mUserType) != "ONLINE") {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if (isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"]) {
                $rules["licenseDetails.licenseFor"] = "required|int|max:1";
            }
            if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) {
                $rules["licenseDetails.paymentMode"] = "required|alpha";
                if (isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode'] != "CASH") {
                    $rules["licenseDetails.chequeNo"] = "required";
                    $rules["licenseDetails.chequeDate"] = "required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] = "required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] = "required|regex:$mRegex";
                }
            }

            $rules["ownerDetails"] = "required|array";
            $rules["ownerDetails.*.businessOwnerName"] = "required|regex:$mOwnerName";
            $rules["ownerDetails.*.guardianName"] = "nullable|regex:$mOwnerName";
            $rules["ownerDetails.*.mobileNo"] = "required|digits:10|regex:$mMobileNo";
            $rules["ownerDetails.*.email"] = "email|nullable";
        } 
        elseif (in_array($mApplicationTypeId, [2, 4])) # 2- Renewal,4- Surender
        {
            $rules["firmDetails.holdingNo"] = "required";
            
            if ($mApplicationTypeId == 2) {
                $rules["licenseDetails.licenseFor"] = "required|int";
                if (isset($this->firmDetails["tocStatus"]) && $this->firmDetails["tocStatus"]) {
                    $rules["licenseDetails.licenseFor"] = "required|int|max:1";
                }
            }
            if ($mApplicationTypeId != 4 && strtoupper($mUserType) != "ONLINE") {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"]) && $mApplicationTypeId == 2) {
                $rules["licenseDetails.paymentMode"] = "required|alpha";
                if (isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode'] != "CASH") {
                    $rules["licenseDetails.chequeNo"] = "required";
                    $rules["licenseDetails.chequeDate"] = "required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] = "required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] = "required|regex:$mRegex";
                }
            }
        } 
        elseif (in_array($mApplicationTypeId, [3])) # 3- Amendment
        {
            $rules["firmDetails.areaSqft"] = "required|numeric";
            $rules["firmDetails.businessDescription"] = "required|regex:$mFramNameRegex";
            $rules["firmDetails.holdingNo"] = "required";
            $rules["initialBusinessDetails.ownershipType"] = "required|digits_between:1,9223372036854775807";
            $rules["licenseDetails.licenseFor"] = "required|int";
            $rules["initialBusinessDetails.firmType"] = "required|digits_between:1,9223372036854775807";
            if (isset($this->initialBusinessDetails['firmType']) && $this->initialBusinessDetails['firmType'] == 5) {
                $rules["initialBusinessDetails.otherFirmType"] = "required|regex:$mRegex";
            }
            if ($mApplicationTypeId != 4 && strtoupper($mUserType) != "ONLINE") {
                $rules["licenseDetails.totalCharge"] = "required|numeric";
            }
            if (in_array(strtoupper($mUserType), ["JSK", "UTC", "TC", "SUPER ADMIN", "TL"])) {
                $rules["licenseDetails.paymentMode"] = "required|alpha";
                if (isset($this->licenseDetails['paymentMode']) && $this->licenseDetails['paymentMode'] != "CASH") {
                    $rules["licenseDetails.chequeNo"] = "required";
                    $rules["licenseDetails.chequeDate"] = "required|date|date_format:Y-m-d|after_or_equal:$mNowdate";
                    $rules["licenseDetails.bankName"] = "required|regex:$mRegex";
                    $rules["licenseDetails.branchName"] = "required|regex:$mRegex";
                }
            }
            $rules["ownerDetails"] = "array";
            if ($this->ownerDetails) {
                $rules["ownerDetails.*.businessOwnerName"] = "required|regex:$mOwnerName";
                $rules["ownerDetails.*.guardianName"] = "nullable|regex:$mOwnerName";
                $rules["ownerDetails.*.mobileNo"] = "required|digits:10|regex:$mMobileNo";
                $rules["ownerDetails.*.email"] = "email|nullable";
            }
        }
        return $rules;
    }
}
