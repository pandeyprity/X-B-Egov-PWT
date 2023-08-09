<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReqGBSaf extends FormRequest
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
        $todayDate = Carbon::now()->format("Y-m-d");
        $assessmentTypes = "1,2";
        $validation = [
            "assessmentType" => "required|integer|In:$assessmentTypes",
            "ulbId" => "required|integer",
            "buildingName" => "required",
            "nameOfOffice" => "required",
            "wardId" => "required",
            "buildingAddress" => "required",
            "gbUsageTypes" => "required|integer",
            "gbPropUsageTypes" => "required|integer",
            "zone" => "required",
            "roadWidth" => "required",
            "designation" => "required",
            "address" => "required",
            "floors" => "required",
            "floors.*.floorNo" => "required",
            "floors.*.useType" => "required",
            "floors.*.constructionType" => "required",
            "floors.*.occupancyType" => "required",
            "floors.*.buildupArea" => "required",
            "floors.*.dateFrom" => "required",
            "floors.*.dateFrom" => "nullable",
            "isMobileTower" => "required|bool",
            "isHoardingBoard" => "required|bool",
            "isPetrolPump" => "required|bool",
            "isWaterHarvesting" => "required|bool",
            "areaOfPlot" => "required|numeric",
            "officerMobile" => "nullable|numeric|digits:10",
            "officerEmail" => "nullable|email"
        ];

        if ($this->isMobileTower == true) {
            $validation = array_merge($validation, [
                "mobileTower.area" => "required|numeric",
                "mobileTower.dateFrom" => "required|date|date_format:Y-m-d|before_or_equal:$todayDate"
            ]);
        }

        if ($this->isHoardingBoard == true) {
            $validation = array_merge($validation, [
                "hoardingBoard.area" => "required|numeric",
                "hoardingBoard.dateFrom" => "required|date_format:Y-m-d|before_or_equal:$todayDate"
            ]);
        }

        if ($this->isPetrolPump == true) {
            $validation = array_merge($validation, [
                "petrolPump.area" => "required|numeric",
                "petrolPump.dateFrom" => "required|date_format:Y-m-d|before_or_equal:$todayDate"
            ]);
        }

        if ($this->assessmentType == 2) {
            $validation = array_merge($validation, [
                "holdingNo" => "required"
            ]);
        }

        return $validation;
    }
}
