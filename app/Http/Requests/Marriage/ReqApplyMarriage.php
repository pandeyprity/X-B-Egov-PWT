<?php

namespace App\Http\Requests\Marriage;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReqApplyMarriage extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $userType = auth()->user()->user_type ?? 'Citizen';
        $mNowDate     = Carbon::now()->format("Y-m-d");
        $mNowDateYm   = Carbon::now()->format("Y-m");

        if ($userType == 'Citizen')
            $rules['ulbId'] = "required|int";

        if (isset($this->assessmentType) && $this->assessmentType == 3) {
            $rules['transferModeId'] = "required";
            $rules['dateOfPurchase'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
            $rules["isOwnerChanged"] = "required|bool";
        }
        if (isset($this->assessmentType) && $this->assessmentType == 5) {
            $rules['holdingNoLists'] = "required|array";
            $rules['holdingNoLists.*'] = "required";
        }
        if (isset($this->isGBSaf))
            $rules['isGBSaf'] = "required|bool";

        if ($this->propertyType == 3)
            $rules['apartmentId'] = "required|integer";

        $rules['ward']          = "required|digits_between:1,9223372036854775807";
        $rules['propertyType']  = "required|int";
        $rules['ownershipType'] = "required|int";
        $rules['roadType']      = "required|numeric";
        $rules['areaOfPlot']    = "required|numeric|not_in:0";
        $rules['isMobileTower'] = "required|bool";
        $rules['owner'] = "required|array";
        if (isset($this->owner) && $this->owner) {
            $rules["owner.*.ownerName"] = "required";
            $rules["owner.*.gender"] = "required";
            $rules["owner.*.dob"] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
            $rules["owner.*.isArmedForce"] = "required|in:true,false,0,1";
            $rules["owner.*.isSpeciallyAbled"] = "required|in:true,false,0,1";
        }
        if (isset($this->isMobileTower) && $this->isMobileTower) {
            $rules['mobileTower.area'] = "required|numeric";
            $rules['mobileTower.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        $rules['isHoardingBoard'] = "required|bool";
        if (isset($this->isHoardingBoard) && $this->isHoardingBoard) {
            $rules['hoardingBoard.area'] = "required|numeric";
            $rules['hoardingBoard.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        $rules['isPetrolPump'] = "required|bool";
        if (isset($this->isPetrolPump) && $this->isPetrolPump) {
            $rules['petrolPump.area'] = "required|numeric";
            $rules['petrolPump.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        }
        if (isset($this->propertyType) && $this->propertyType == 4) {
            $rules['landOccupationDate'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
        } else {
            $rules['floor']        = "required|array";
            if (isset($this->floor) && $this->floor) {
                $rules["floor.*.propFloorDetailId"] =   "nullable|numeric";
                $rules["floor.*.floorNo"]           =   "required|int";
                $rules["floor.*.useType"]           =   "required|int";
                $rules["floor.*.constructionType"]  =   "required|int|in:1,2,3";
                $rules["floor.*.occupancyType"]     =   "required|int";

                $rules["floor.*.buildupArea"]       =   "required|numeric|not_in:0";
                $rules["floor.*.dateFrom"]          =   "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                $rules["floor.*.dateUpto"]          =   "nullable|date|date_format:Y-m-d|before_or_equal:$mNowDate";
            }
        }


        $rules['isWaterHarvesting'] = "required|bool";

        if ($this->isWaterHarvesting == 1)
            $rules['rwhDateFrom'] = 'required|date|date_format:Y-m-d|before_or_equal:$mNowDate';

        if (isset($this->assessmentType) && $this->assessmentType != 1 && $this->assessmentType != 5) {           // Holding No Required for Reassess,Mutation,Bifurcation,Amalgamation
            $rules['previousHoldingId'] = "required|numeric";
        }
        $rules['zone']           = "required|int|in:1,2";

        return $rules;
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(
            [
                'status' => false,
                'message' => 'The given data was invalid',
                'errors' => $validator->errors()
            ],
            422
        ));
    }
}
