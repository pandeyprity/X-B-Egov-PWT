<?php

namespace App\BLL\Property\Akola;

use App\Models\Property\PropDemand;
use App\Models\Property\PropPenaltyrebate;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Property\PropTranDtl;
use App\Models\Property\PropTransaction;
use App\Models\UlbMaster;
use App\Models\UlbWardMaster;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

/**
 * | Author -Anshu Kumar
 * | Created On-09-09-2023 
 * | Created for - Payment Receipt for SAF Payment and Property Payment
 */

class GeneratePaymentReceipt
{
    private $_mPropProperty;
    private $_mPropTransaction;
    private $_mPropTranDtl;
    private $_mPropDemands;
    private $_tranNo;
    private $_tranType;
    private $_currentDemand;
    private $_overDueDemand;
    private $_mPropPenaltyRebates;
    public array $_GRID;
    private $_trans;
    private $_mTowards;
    private $_mAccDescription;
    private $_mDepartmentSection;
    private $_propertyDtls;
    private $_ulbDetails;
    private $_mUlbMasters;
    private $_mPropSaf;

    /**
     * | Initializations of Variables
     */
    public function __construct()
    {
        $this->_mUlbMasters = new UlbMaster();
        $this->_mPropProperty = new PropProperty();
        $this->_mPropTransaction = new PropTransaction();
        $this->_mPropTranDtl = new PropTranDtl();
        $this->_mPropDemands = new PropDemand();
        $this->_mPropPenaltyRebates = new PropPenaltyrebate();
        $this->_mPropSaf = new PropSaf();
    }

    /**
     * | Generate Payment Receipt
     */
    public function generateReceipt($tranNo)
    {
        $this->_tranNo = $tranNo;
        $this->readParams();
        $this->addPropDtls();
    }

    /**
     * | Read parameters
     */
    public function readParams()
    {
        $this->_mTowards = Config::get('PropertyConstaint.SAF_TOWARDS');
        $this->_mAccDescription = Config::get('PropertyConstaint.ACCOUNT_DESCRIPTION');
        $this->_mDepartmentSection = Config::get('PropertyConstaint.DEPARTMENT_SECTION');

        $currentFyear = getFY();

        $trans = $this->_mPropTransaction->getPropByTranPropId($this->_tranNo);
        $this->_trans = $trans;
        if (collect($trans)->isEmpty())
            throw new Exception("Transaction Not Available for this Transaction No");

        $this->_GRID['transactionNo'] = $trans->tran_no;
        $this->_tranType = $trans->tran_type;                // Property or SAF 

        $tranDtls = $this->_mPropTranDtl->getTranDemandsByTranId($trans->id);

        if (collect($tranDtls)->isEmpty())
            throw new Exception("Demands against transaction not exist");

        if ($this->_tranType == 'Property') {                                   // Get Property Demands by demand ids
            $demandIds = collect($tranDtls)->pluck('prop_demand_id')->toArray();
            $demandsList = $this->_mPropDemands->getDemandsListByIds($demandIds);
            $this->_GRID['penaltyRebates'] = $this->_mPropPenaltyRebates->getPenaltyRebatesHeads($trans->id, "Property");
            // Fetch Application Details
            $this->_propertyDtls = $this->_mPropProperty->getBasicDetails($trans->property_id);             // Get details from property table
            if (collect($this->_propertyDtls)->isEmpty())
                throw new Exception("Property Details not available");
        }

        if ($this->_tranType == 'Saf') {                                   // Get Saf Demands by demand ids
            $demandIds = collect($tranDtls)->pluck('saf_demand_id')->toArray();
            $demandsList = $this->_mPropDemands->getDemandsListByIds($demandIds);
            $this->_GRID['penaltyRebates'] = $this->_mPropPenaltyRebates->getPenaltyRebatesHeads($trans->id, "Saf");
            $this->_propertyDtls = $this->_mPropSaf->getBasicDetails($trans->saf_id);                       // Get Details from saf table
            if (collect($this->_propertyDtls)->isEmpty())
                throw new Exception("Saf Details not available");
        }

        $this->_ulbDetails = $this->_mUlbMasters->getUlbDetails($this->_propertyDtls->ulb_id);

        $currentDemand = $demandsList->where('fyear', $currentFyear);
        $this->_currentDemand = $this->aggregateDemand($currentDemand);

        $overdueDemand = $demandsList->where('fyear', '<>', $currentFyear);
        $this->_overDueDemand = $this->aggregateDemand($overdueDemand);

        $this->_GRID['overdueDemand'] = $this->_overDueDemand;
        $this->_GRID['currentDemand'] = $this->_currentDemand;

        $aggregateDemandList = new Collection([$this->_currentDemand, $this->_overDueDemand]);
        $this->_GRID['aggregateDemand'] = $this->aggregateDemand($aggregateDemandList);
    }

    /**
     * | Aggregate Demand
     */
    public function aggregateDemand($demandList)
    {
        $aggregate = $demandList->pipe(function ($item) {
            return [
                "general_tax" => $item->sum('general_tax'),
                "road_tax" => $item->sum('road_tax'),
                "firefighting_tax" => $item->sum('firefighting_tax'),
                "education_tax" => $item->sum('education_tax'),
                "water_tax" => $item->sum('water_tax'),
                "cleanliness_tax" => $item->sum('cleanliness_tax'),
                "sewarage_tax" => $item->sum('sewarage_tax'),
                "tree_tax" => $item->sum('tree_tax'),
                "professional_tax" => $item->sum('professional_tax'),
                "adjust_amt" => $item->sum('adjust_amt'),
                "tax1" => $item->sum('tax1'),
                "tax2" => $item->sum('tax2'),
                "tax3" => $item->sum('tax3'),
                "sp_education_tax" => $item->sum('sp_education_tax'),
                "water_benefit" => $item->sum('water_benefit'),
                "water_bill" => $item->sum('water_bill'),
                "sp_water_cess" => $item->sum('sp_water_cess'),
                "drain_cess" => $item->sum('drain_cess'),
                "light_cess" => $item->sum('light_cess'),
                "major_building" => $item->sum('major_building'),
                "total_tax" => $item->sum('total_tax'),
            ];
        });

        return collect($aggregate);
    }

    /**
     * | Property Details
     */
    public function addPropDtls()
    {
        $receiptDtls = [
            "departmentSection" => $this->_mDepartmentSection,
            "accountDescription" => $this->_mAccDescription,
            "transactionDate" => Carbon::parse($this->_trans->tran_date)->format('d-m-Y'),
            "transactionNo" => $this->_trans->tran_no,
            "transactionTime" => $this->_trans->created_at->format('H:i:s'),
            "applicationNo" => $this->_propertyDtls->application_no,
            "customerName" => $this->_propertyDtls->owner_name,
            "mobileNo" => $this->_propertyDtls->mobile_no,
            "address" => $this->_propertyDtls->prop_address,
            "paidFrom" => $this->_trans->from_fyear,
            "paidUpto" => $this->_trans->to_fyear,
            "paymentMode" => $this->_trans->payment_mode,
            "bankName" => $this->_trans->bank_name,
            "branchName" => $this->_trans->branch_name,
            "chequeNo" => $this->_trans->cheque_no,
            "chequeDate" => ymdToDmyDate($this->_trans->cheque_date),
            "demandAmount" => $this->_trans->demand_amt,
            "arrearSettled" => $this->_trans->arrear_settled_amt,
            "ulbId" => $this->_propertyDtls->ulb_id,
            "wardNo" => $this->_propertyDtls->ward_no,
            "towards" => $this->_mTowards,
            "description" => [
                "keyString" => "Holding Tax"
            ],
            "totalPaidAmount" => $this->_trans->amount,
            "paidAmtInWords" => getIndianCurrency($this->_trans->amount),
            "tcName" => $this->_trans->tc_name,
            "tcMobile" => $this->_trans->tc_mobile,
            "ulbDetails" => $this->_ulbDetails
        ];

        $this->_GRID['receiptDtls'] = $receiptDtls;
    }
}
