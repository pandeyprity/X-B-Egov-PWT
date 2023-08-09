<?php

namespace App\BLL\Property;

use App\Models\Property\PropRazorpayPenalrebate;
use Exception;
use Illuminate\Support\Facades\Config;

/**
 * | Post Razor pay penalty Rabate
 */

class PostRazorPayPenaltyRebate
{
    public $_calculateRebates;
    public $_rebatePenalList;
    public $_dueList;
    public $_headNames;
    public $_ipAddress;
    public $_safId = null;
    public $_propId = null;
    public $_razorPayRequestId;

    public function __construct()
    {
        $this->_rebatePenalList = collect(Config::get('PropertyConstaint.REBATE_PENAL_MASTERS'));
        $this->_ipAddress = getClientIpAddress();
    }
    /**
     * | Post Razor Pay Request Penalty Rebates
     */
    public function postRazorPayPenaltyRebates($dueList)
    {
        $mPropRazorpayPenalrebates = new PropRazorpayPenalrebate();
        $this->_calculateRebates = $dueList['rebates'];
        $this->_dueList = $dueList;

        $this->generateHeadName();

        if ($this->_safId == null && $this->_propId == null)
            throw new Exception("Application Id Not Available");

        foreach ($this->_headNames as $headName) {
            if ($headName['value'] > 0) {
                $reqs = [
                    'razorpay_request_id' => $this->_razorPayRequestId,
                    'saf_id' => $this->_safId,
                    'prop_id' => $this->_propId,
                    'head_name' => $headName['keyString'],
                    'amount' => $headName['value'],
                    'is_rebate' => $headName['isRebate'],
                    'ip_address' => $this->_ipAddress
                ];
                $mPropRazorpayPenalrebates->store($reqs);
            }
        }
    }

    /**
     * | Generate Head Name
     */
    public function generateHeadName()
    {
        $rebateList = array();
        $calculatedRebates = $this->_calculateRebates;
        foreach ($calculatedRebates as $item) {
            $rebate = [
                'keyString' => $item['keyString'],
                'value' => $item['rebateAmount'],
                'isRebate' => true
            ];
            array_push($rebateList, $rebate);
        }

        $headNames = [
            [
                'keyString' => $this->_rebatePenalList->where('id', 1)->first()['value'],
                'value' => $this->_dueList['onePercPenalty'] ?? $this->_dueList['totalOnePercPenalty'],
                'isRebate' => false
            ],
            [
                'keyString' => $this->_rebatePenalList->where('id', 5)->first()['value'],
                'value' => $this->_dueList['lateAssessmentPenalty'] ?? 0,
                'isRebate' => false
            ]
        ];
        $this->_headNames = array_merge($headNames, $rebateList);
    }
}
