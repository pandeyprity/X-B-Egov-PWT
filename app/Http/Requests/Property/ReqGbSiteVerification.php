<?php

namespace App\Http\Requests\Property;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class ReqGbSiteVerification extends FormRequest
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
        $mNowDate = Carbon::now()->format('Y-m-d');
        return [
            'safId' => 'required|integer',
            'gbUsageTypes' => 'required|integer',
            'gbPropUsageTypes' => 'required|integer',
            'roadWidth' => 'required|numeric',
            'areaOfPlot' => 'required|numeric',
            'wardId' => 'required|integer',
            'isMobileTower' => 'required|bool',
            'mobileTower.area' => 'required_if:isMobileTower,1',
            'mobileTower.dateFrom' => 'required_if:isMobileTower,1',
            'isHoardingBoard' => 'required|bool',
            'hoardingBoard.area' => 'required_if:isHoardingBoard,1',
            'hoardingBoard.dateFrom' => 'required_if:isHoardingBoard,1',
            'isPetrolPump' => 'required|bool',
            'petrolPump.area' => 'required_if:isPetrolPump,1',
            'petrolPump.dateFrom' => 'required_if:isPetrolPump,1',
            'isWaterHarvesting' => 'required|bool',
            'floor' => 'required|array',
            'floor.*.floorId' => 'numeric',
            'floor.*.floorNo' => 'required|integer',
            'floor.*.useType' => 'required|integer',
            'floor.*.constructionType' => 'required|integer',
            'floor.*.occupancyType' => 'required|integer',
            'floor.*.buildupArea' => 'required|numeric',
            'floor.*.dateFrom' => 'required|date|date_format:Y-m-d|before_or_equal:' . $mNowDate,
            'floor.*.dateUpto' => 'nullable|date|date_format:Y-m-d|before_or_equal:' . $mNowDate,
        ];
    }
}
