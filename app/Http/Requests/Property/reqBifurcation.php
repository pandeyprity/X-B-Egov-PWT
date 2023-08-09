<?php

namespace App\Http\Requests\Property;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class reqBifurcation extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        $mNowDate     = Carbon::now()->format("Y-m-d");
        $mNowDateYm   = Carbon::now()->format("Y-m");
        if($this->getMethod()=="GET")
        {
            return[];
        }
        $rules['assessmentType'] = "required|int|in:3";
        $rules['container']      = "required|array";
        // if(isset($this->assessmentType) && $this->assessmentType !=1)
        // {
        //     $rules['container.'.$key.'.previousHoldingId'] = "required|digits_between:1,9223372036854775807";
        //     $rules['container.'.$key.'.holdingNo']         = "required|string";
        // }
        if(isset($this->assessmentType) && $this->assessmentType !=1)
        { 
            $rules['oldHoldingId'] = "required|digits_between:1,9223372036854775807";
            $rules['oldHoldingNo']         = "required|string";
        }
        $req = $this->all();
        $isAcquired = false;
        $count = 0;
        if(isset($req['container']) && is_array($req['container']) )
        {
            foreach($req['container'] as $key =>$val)
            { 
                if(isset($this->assessmentType) && $this->assessmentType ==3)
                {
                    $rules["container.".$key.".transferModeId"] = "required";
                    // $rules["container.".$key.".dateOfPurchase"] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                    $rules["container.".$key.".isOwnerChanged"] = "required|bool";
                } 
                $rules['container.'.$key.'.isAcquired']          = "required|bool";
                if(isset($val["isAcquired"]) && $val["isAcquired"]) 
                {
                    $isAcquired=true;
                } 
                if($isAcquired && ($count++))  
                {
                    $rules['container.'.$key.'.isAcquired'] = "required|bool|in:0";
                }           
                $rules['container.'.$key.'.ward']          = "required|digits_between:1,9223372036854775807";
                $rules['container.'.$key.'.propertyType']  = "required|int";
                $rules['container.'.$key.'.ownershipType'] = "required|int";
                $rules['container.'.$key.'.roadType']      = "required|numeric";
                $rules['container.'.$key.'.areaOfPlot']    = "required|numeric";
                $rules['container.'.$key.'.isMobileTower'] = "required|bool";
                if(isset($val["isMobileTower"]) && $val["isMobileTower"])
                {
                    $rules['container.'.$key.'.mobileTower.area'] = "required|numeric";
                    $rules['container.'.$key.'.mobileTower.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                }
                $rules['container.'.$key.'.isHoardingBoard'] = "required|bool";
                if(isset($val["isHoardingBoard"]) && $val["isHoardingBoard"])
                {
                    $rules['container.'.$key.'.hoardingBoard.area'] = "required|numeric";
                    $rules['container.'.$key.'.hoardingBoard.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                }
                $rules['container.'.$key.'.isPetrolPump'] = "required|bool";
                if(isset($val["isPetrolPump"]) && $val["isPetrolPump"])
                {
                    $rules['container.'.$key.'.petrolPump.area'] = "required|numeric";
                    $rules['container.'.$key.'.petrolPump.dateFrom'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                }
                
                if(isset($val["propertyType"]) && $val["propertyType"]==4)
                {
                    $rules['container.'.$key.'.landOccupationDate'] = "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                }
                else
                {
                    $rules['container.'.$key.'.floor']        = "required|array";
                    //dd($val["floor"]);
                    if(isset($val["floor"]) && $val["floor"])
                    {
                        $rules["container.".$key.".floor.*.floorNo"]           =   "required|int";
                        $rules["container.".$key.".floor.*.useType"]           =   "required|int";
                        $rules["container.".$key.".floor.*.constructionType"]  =   "required|int|in:1,2,3";
                        $rules["container.".$key.".floor.*.occupancyType"]     =   "required|int";
    
                        $rules["container.".$key.".floor.*.buildupArea"]       =   "required|numeric";
                        $rules["container.".$key.".floor.*.dateFrom"]          =   "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                        $rules["container.".$key.".floor.*.dateUpto"]          =   "nullable|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                    }
                }
                $rules['container.'.$key.'.isWaterHarvesting'] = "required|bool";
                
                $rules['container.'.$key.'.zone']           = "required|int|in:1,2";
                if(isset($this->assessmentType) && ($this->assessmentType ==1 || ($this->assessmentType ==3 && isset($val['isOwnerChanged']) && $val['isOwnerChanged'])))
                { 
                    $rules['container.'.$key.'.owner']        = "required|array";
                    if(isset($val['owner']) && $val['owner'])
                    { 
                        $rules["container.".$key.".owner.*.ownerName"]           =   "required|regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/";
                        $rules["container.".$key.".owner.*.gender"]              =   "required|int|in:1,2,3";
                        $rules["container.".$key.".owner.*.dob"]                 =   "required|date|date_format:Y-m-d|before_or_equal:$mNowDate";
                        $rules["container.".$key.".owner.*.guardianName"]        =   "regex:/^([a-zA-Z]+)(\s[a-zA-Z0-9]+)*$/|nullable";
                        $rules["container.".$key.".owner.*.relation"]            =   "nullable|string|in:S/O,W/O,D/O,C/O";
                        $rules["container.".$key.".owner.*.mobileNo"]            =   "required|digits:10|regex:/[0-9]{10}/";
                        $rules["container.".$key.".owner.*.email"]               =   "email|nullable";
                        $rules["container.".$key.".owner.*.pan"]                 =   "string|nullable";
                        $rules["container.".$key.".owner.*.aadhar"]              =   "digits:12|regex:/[0-9]{12}/|nullable";
                        $rules["container.".$key.".owner.*.isArmedForce"]        =   "required|bool";
                        $rules["container.".$key.".owner.*.isSpeciallyAbled"]    =   "required|bool";
                    }
                }
            }
            if(!$isAcquired)
            {
                $rules['container.0.isAcquired'] = "required|bool|in:1";
            }

        } 
        return $rules;
    }

    public function validateBooleanFalse($value)
    {
        $acceptable = [false, 0, '0', ];

        return in_array($this->toBooleanFalse($value), $acceptable, true);
    }
    private function toBooleanFalse($booleable)
    {
        return filter_var($booleable, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}
