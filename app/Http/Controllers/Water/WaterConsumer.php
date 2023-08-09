<?php

namespace App\Http\Controllers\Water;

use App\Http\Controllers\Controller;
use App\Http\Requests\Water\reqDeactivate;
use App\Http\Requests\Water\reqMeterEntry;
use App\MicroServices\DocUpload;
use App\MicroServices\IdGeneration;
use App\MicroServices\IdGenerator\PrefixIdGenerator;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Payment\TempTransaction;
use App\Models\Water\WaterAdvance;
use App\Models\Water\WaterApplication;
use App\Models\Water\WaterApprovalApplicationDetail;
use App\Models\Water\WaterChequeDtl;
use App\Models\Water\WaterConnectionCharge;
use App\Models\Water\WaterConnectionTypeMstr;
use App\Models\Water\WaterConsumer as WaterWaterConsumer;
use App\Models\Water\WaterConsumerActiveRequest;
use App\Models\Water\WaterConsumerCharge;
use App\Models\Water\WaterConsumerChargeCategory;
use App\Models\Water\WaterConsumerDemand;
use App\Models\Water\WaterConsumerDisconnection;
use App\Models\Water\WaterConsumerInitialMeter;
use App\Models\Water\WaterConsumerMeter;
use App\Models\Water\WaterConsumerTax;
use App\Models\Water\WaterDisconnection;
use App\Models\Water\WaterMeterReadingDoc;
use App\Models\Water\WaterPenaltyInstallment;
use App\Models\Water\WaterSiteInspection;
use App\Models\Water\WaterTran;
use App\Models\Water\WaterTranDetail;
use App\Models\Workflows\WfRoleusermap;
use App\Models\Workflows\WfWorkflow;
use App\Models\Workflows\WfWorkflowrolemap;
use App\Repository\Water\Concrete\WaterNewConnection;
use App\Repository\Water\Interfaces\IConsumer;
use App\Traits\Workflow\Workflow;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\CssSelector\Node\FunctionNode;

class WaterConsumer extends Controller
{
    use Workflow;

    private $Repository;
    public function __construct(IConsumer $Repository)
    {
        $this->Repository = $Repository;
    }


    /**
     * | Calcullate the Consumer demand 
     * | @param request
     * | @return Repository
        | Serial No : 01
        | Working
     */
    public function calConsumerDemand(Request $request)
    {
        return $this->Repository->calConsumerDemand($request);
    }


    /**
     * | List Consumer Active Demand
     * | Show the Demand With payed-status false
     * | @param request consumerId
     * | @var WaterConsumerDemand  model
     * | @var consumerDemand  
     * | @var refConsumerId
     * | @var refMeterData
     * | @var connectionName
     * | @return consumerDemand  Consumer Demand List
        | Serial no : 02
        | Working
     */
    public function listConsumerDemand(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'ConsumerId' => 'required|',
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterConsumerDemand   = new WaterConsumerDemand();
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $refConnectionName      = Config::get('waterConstaint.METER_CONN_TYPE');
            $refConsumerId          = $request->ConsumerId;

            $consumerDemand['consumerDemands'] = $mWaterConsumerDemand->getConsumerDemand($refConsumerId);
            $checkParam = collect($consumerDemand['consumerDemands'])->first();
            if (isset($checkParam)) {
                $sumDemandAmount = collect($consumerDemand['consumerDemands'])->sum('balance_amount');
                $totalPenalty = collect($consumerDemand['consumerDemands'])->sum('penalty');
                $consumerDemand['totalSumDemand'] = round($sumDemandAmount, 2);
                $consumerDemand['totalPenalty'] = round($totalPenalty, 2);

                # meter Details 
                $refMeterData = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
                switch ($refMeterData['connection_type']) {
                    case (1):
                        if ($refMeterData['meter_status'] == 1) {
                            $connectionName = $refConnectionName['1'];
                            break;
                        }
                        $connectionName = $refConnectionName['4'];
                        break;
                    case (2):
                        $connectionName = $refConnectionName['2'];
                        break;
                    case (3):
                        $connectionName = $refConnectionName['3'];
                        break;
                }
                $refMeterData['connectionName'] = $connectionName;
                $consumerDemand['meterDetails'] = $refMeterData;

                return responseMsgs(true, "List of Consumer Demand!", $consumerDemand, "", "01", "ms", "POST", "");
            }
            throw new Exception("There is no demand!");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Save the consumer demand 
     * | Also generate demand 
     * | @param request
     * | @var mWaterConsumerInitialMeter
     * | @var mWaterConsumerMeter
     * | @var refMeterConnectionType
     * | @var consumerDetails
     * | @var calculatedDemand
     * | @var demandDetails
     * | @var meterId
     * | @return 
        | Serial No : 03
        | Not Tested
        | Work on the valuidation and the saving of the meter details document
     */
    public function saveGenerateConsumerDemand(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();
            $mWaterConsumerMeter        = new WaterConsumerMeter();
            $mWaterMeterReadingDoc      = new WaterMeterReadingDoc();
            $refMeterConnectionType     = Config::get('waterConstaint.METER_CONN_TYPE');
            $meterRefImageName          = config::get('waterConstaint.WATER_METER_CODE');
            $demandIds = array();

            # Check and calculate Demand                    
            $consumerDetails = WaterWaterConsumer::findOrFail($request->consumerId);
            $this->checkDemandGeneration($request, $consumerDetails);                                       // unfinished function
            $calculatedDemand = collect($this->Repository->calConsumerDemand($request));
            if ($calculatedDemand['status'] == false) {
                throw new Exception($calculatedDemand['errors']);
            }

            # Save demand details 
            DB::beginTransaction();
            $userDetails = $this->checkUserType($request);
            if (isset($calculatedDemand)) {
                $demandDetails = collect($calculatedDemand['consumer_tax']['0']);
                switch ($demandDetails['charge_type']) {
                    case ($refMeterConnectionType['1']):
                        $validated = Validator::make(
                            $request->all(),
                            [
                                'document' => "required|mimes:pdf,jpeg,png,jpg",
                            ]
                        );
                        if ($validated->fails())
                            return validationError($validated);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails, $userDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;
                    case ($refMeterConnectionType['5']):
                        $validated = Validator::make(
                            $request->all(),
                            [
                                'document' => "required|mimes:pdf,jpeg,png,jpg",
                            ]
                        );
                        if ($validated->fails())
                            return validationError($validated);
                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails, $userDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;

                    case ($refMeterConnectionType['2']):
                        $validated = Validator::make(
                            $request->all(),
                            [
                                'document' => "required|mimes:pdf,jpeg,png,jpg",
                            ]
                        );
                        if ($validated->fails())
                            return validationError($validated);

                        $meterDetails = $mWaterConsumerMeter->saveMeterReading($request);
                        $mWaterConsumerInitialMeter->saveConsumerReading($request, $meterDetails, $userDetails);
                        $demandIds = $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);

                        # save the chages doc
                        $documentPath = $this->saveDocument($request, $meterRefImageName);
                        collect($demandIds)->map(function ($value)
                        use ($mWaterMeterReadingDoc, $meterDetails, $documentPath) {
                            $mWaterMeterReadingDoc->saveDemandDocs($meterDetails, $documentPath, $value);
                        });
                        break;

                    case ($refMeterConnectionType['3']):
                        $this->savingDemand($calculatedDemand, $request, $consumerDetails, $demandDetails['charge_type'], $refMeterConnectionType, $userDetails);
                        break;
                }
                DB::commit();
                return responseMsgs(true, "Demand Generated! for" . " " . $request->consumerId, "", "", "02", ".ms", "POST", "");
            }
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", "ms", "POST", "");
        }
    }

    /**
     * | Save the Details for the Connection Type Meter 
     * | In Case Of Connection Type is meter OR Gallon 
     * | @param Request  
     * | @var mWaterConsumerDemand
     * | @var mWaterConsumerTax
     * | @var generatedDemand
     * | @var taxId
     * | @var meterDetails
     * | @var refDemands
        | Serial No : 03.01
        | Not Tested
     */
    public function savingDemand($calculatedDemand, $request, $consumerDetails, $demandType, $refMeterConnectionType, $userDetails)
    {
        $mWaterConsumerTax      = new WaterConsumerTax();
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $generatedDemand        = $calculatedDemand['consumer_tax'];

        $returnDemandIds = collect($generatedDemand)->map(function ($firstValue)
        use ($mWaterConsumerDemand, $consumerDetails, $request, $mWaterConsumerTax, $demandType, $refMeterConnectionType, $userDetails) {
            $taxId = $mWaterConsumerTax->saveConsumerTax($firstValue, $consumerDetails, $userDetails);
            $refDemandIds = array();
            # User for meter details entry
            $meterDetails = [
                "charge_type"       => $firstValue['charge_type'],
                "amount"            => $firstValue['charge_type'],
                "effective_from"    => $firstValue['effective_from'],
                "initial_reading"   => $firstValue['initial_reading'],
                "final_reading"     => $firstValue['final_reading'],
                "rate_id"           => $firstValue['rate_id'],
            ];
            switch ($demandType) {
                case ($refMeterConnectionType['1']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $consumerDetails, $request, $taxId, $userDetails);
                    break;
                case ($refMeterConnectionType['5']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands,  $consumerDetails, $request, $taxId, $userDetails);
                    break;
                case ($refMeterConnectionType['2']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue, $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands,  $consumerDetails, $request, $taxId, $userDetails);
                    break;
                case ($refMeterConnectionType['3']):
                    $refDemands = $firstValue['consumer_demand'];
                    $check = collect($refDemands)->first();
                    if (is_array($check)) {
                        $refDemandIds = collect($refDemands)->map(function ($secondValue)
                        use ($mWaterConsumerDemand, $consumerDetails, $request, $taxId, $userDetails) {
                            $refDemandId = $mWaterConsumerDemand->saveConsumerDemand($secondValue,  $consumerDetails, $request, $taxId, $userDetails);
                            return $refDemandId;
                        });
                        break;
                    }
                    $refDemandIds = $mWaterConsumerDemand->saveConsumerDemand($refDemands, $consumerDetails, $request, $taxId, $userDetails);
                    break;
            }
            return $refDemandIds;
        });
        return $returnDemandIds;
    }

    /**
     * | Validate the user and other criteria for the Genereating demand
     * | @param request
        | Serial No : 03.02
        | Not Used 
     */
    public function checkDemandGeneration($request, $consumerDetails)
    {
        $user                   = authUser($request);
        $today                  = Carbon::now();
        $refConsumerId          = $request->consumerId;
        $mWaterConsumerDemand   = new WaterConsumerDemand();

        $lastDemand = $mWaterConsumerDemand->getRefConsumerDemand($refConsumerId)->first();
        if ($lastDemand) {
            $refDemandUpto = Carbon::parse($lastDemand->demand_upto);
            if ($refDemandUpto > $today) {
                throw new Exception("the demand is generated till" . "" . $lastDemand->demand_upto);
            }
            $startDate  = Carbon::parse($refDemandUpto);
            $uptoMonth  = $startDate;
            $todayMonth = $today;
            if ($uptoMonth->greaterThan($todayMonth)) {
                throw new Exception("demand should be generated generate in next month!");
            }
            $diffMonth = $startDate->diffInMonths($today);
            if ($diffMonth < 1) {
                throw new Exception("there should be a difference of month!");
            }
        }
    }



    /**
     * | Save the Meter details 
     * | @param request
        | Serial No : 04
        | Working  
        | Check the parameter for the autherised person
        | Chack the Demand for the fixed rate 
        | Re discuss
     */
    public function saveUpdateMeterDetails(reqMeterEntry $request)
    {
        try {
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $meterRefImageName      = config::get('waterConstaint.WATER_METER_CODE');
            $param                  = $this->checkParamForMeterEntry($request);

            DB::beginTransaction();
            $metaRequest = new Request([
                "consumerId"    => $request->consumerId,
                "finalRading"   => $request->oldMeterFinalReading,
                "demandUpto"    => $request->connectionDate,
                "document"      => $request->document,
            ]);
            if ($param['meterStatus'] != false) {
                $this->saveGenerateConsumerDemand($metaRequest);
            }
            $documentPath = $this->saveDocument($request, $meterRefImageName);
            $mWaterConsumerMeter->saveMeterDetails($request, $documentPath, $fixedRate = null);
            DB::commit();
            return responseMsgs(true, "Meter Detail Entry Success !", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Chech the parameter before Meter entry
     * | Validate the Admin For entring the meter details
     * | @param request
        | Serial No : 04.01
        | Working
        | Look for the meter status true condition while returning data
        | Recheck the process for meter and non meter 
        | validation for the respective meter conversion and verify the new consumer.
     */
    public function checkParamForMeterEntry($request)
    {
        $refConsumerId  = $request->consumerId;
        $todayDate      = Carbon::now();

        $mWaterWaterConsumer    = new WaterWaterConsumer();
        $mWaterConsumerMeter    = new WaterConsumerMeter();
        $mWaterConsumerDemand   = new WaterConsumerDemand();
        $mWaterConsumerCharge   = new WaterConsumerCharge();
        $refMeterConnType       = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
        $refConsumerChrages     = Config::get('waterConstaint.CONSUMER_CHARGE_CATAGORY');

        $refConsumerDetails     = $mWaterWaterConsumer->getConsumerDetailById($refConsumerId);
        $consumerMeterDetails   = $mWaterConsumerMeter->getMeterDetailsByConsumerId($refConsumerId)->first();
        $consumerDemand         = $mWaterConsumerDemand->getFirstConsumerDemand($refConsumerId)->first();

        # Check the meter/fixed case 
        $this->checkForMeterFixedCase($request, $consumerMeterDetails, $refMeterConnType);

        switch ($request) {
            case (strtotime($request->connectionDate) > strtotime($todayDate)):
                throw new Exception("Connection Date can not be greater than Current Date!");
                break;
            case ($request->connectionType != $refMeterConnType['Meter/Fixed']):
                if (!is_null($consumerMeterDetails)) {
                    if ($consumerMeterDetails->final_meter_reading >= $request->oldMeterFinalReading) {
                        throw new Exception("Rading Should be Greater Than last Reading!");
                    }
                }
                break;
            case ($request->connectionType != $refMeterConnType['Meter']):
                if (!is_null($consumerMeterDetails)) {
                    if ($consumerMeterDetails->connection_type == $request->connectionType) {
                        throw new Exception("You can not update same connection type as before!");
                    }
                }
                break;
        }

        # If Previous meter details exist
        if ($consumerMeterDetails) {
            # If fixed meter connection is changing to meter connection as per rule every connection should be in meter
            if ($request->connectionType != $refMeterConnType['Fixed'] && $consumerMeterDetails->connection_type == $refMeterConnType['Fixed']) {
                if ($consumerDemand) {
                    throw new Exception("Please pay the old Demand Amount! as per rule to change fixed connection to meter!");
                }
                throw new Exception("Please apply for regularization as per rule 16 your connection shoul be in meter!");
            }

            # If there is previous meter detail exist
            $reqConnectionDate = $request->connectionDate;
            if (strtotime($consumerMeterDetails->connection_date) > strtotime($reqConnectionDate)) {
                throw new Exception("Connection Date should be grater than previous Connection date!");
            }
        }

        # If the consumer demand exist
        if (isset($consumerDemand)) {
            $reqConnectionDate = $request->connectionDate;
            $reqConnectionDate = Carbon::parse($reqConnectionDate)->format('m');
            $consumerDmandDate = Carbon::parse($consumerDemand->demand_upto)->format('m');
            switch ($consumerDemand) {
                case ($consumerDmandDate >= $reqConnectionDate):
                    throw new Exception("Can not update Connection Date, Demand already generated upto that month!");
                    break;
            }
        }
        # If the meter detail do not exist 
        if (is_null($consumerMeterDetails)) {
            $returnData['meterStatus'] = false;
        }
        return $returnData;
    }

    /**
     * | Check for the Meter/Fixed 
     * | @param request
     * | @param consumerMeterDetails
        | Serial No : 04.01.01
        | Not Working
     */
    public function checkForMeterFixedCase($request, $consumerMeterDetails, $refMeterConnType)
    {
        if ($request->connectionType == $refMeterConnType['Meter/Fixed']) {
            $refConnectionType = 1;
            if ($consumerMeterDetails->connection_type == $refConnectionType && $consumerMeterDetails->meter_status == 0) {
                throw new Exception("You can not update same connection type as before!");
            }
            if ($request->meterNo != $consumerMeterDetails->meter_no) {
                throw new Exception("You Can Meter/Fixed The Connection On Priviuse Meter");
            }
        }
    }

    /**
     * | Save the Document for the Meter Entry 
     * | Return the Document Path
     * | @param request
        | Serial No : 04.02 / 06.02
        | Working
        | Common function
     */
    public function saveDocument($request, $refImageName)
    {
        $document       = $request->document;
        $docUpload      = new DocUpload;
        $relativePath   = Config::get('waterConstaint.WATER_RELATIVE_PATH');

        $imageName = $docUpload->upload($refImageName, $document, $relativePath);
        $doc = [
            "document"      => $imageName,
            "relaivePath"   => $relativePath
        ];
        return $doc;
    }


    /**
     * | Get all the meter details According to the consumer Id
     * | @param request
     * | @var 
     * | @return 
        | Serial No : 05
        | Not Working
     */
    public function getMeterList(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId' => "required|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $meterConnectionType    = null;
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $refWaterNewConnection  = new WaterNewConnection();
            $refMeterConnType       = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

            $meterList = $mWaterConsumerMeter->getMeterDetailsByConsumerId($request->consumerId)->get();
            $returnData = collect($meterList)->map(function ($value)
            use ($refMeterConnType, $meterConnectionType, $refWaterNewConnection) {
                switch ($value['connection_type']) {
                    case ($refMeterConnType['Meter']):
                        if ($value['meter_status'] == 0) {
                            $meterConnectionType = "Metre/Fixed";                               // Static
                        }
                        $meterConnectionType = "Meter";                                         // Static
                        break;

                    case ($refMeterConnType['Gallon']):
                        $meterConnectionType = "Gallon";                                        // Static
                        break;
                    case ($refMeterConnType['Fixed']):
                        $meterConnectionType = "Fixed";                                         // Static
                        break;
                }
                $value['meter_connection_type'] = $meterConnectionType;
                $path = $refWaterNewConnection->readDocumentPath($value['doc_path']);
                $value['doc_path'] = !empty(trim($value['doc_path'])) ? $path : null;
                return $value;
            });
            return responseMsgs(true, "Meter List!", remove_null($returnData), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Apply For Deactivation
     * | Save the details for Deactivation
     * | @param request
     * | @var 
        | Not Working
        | Serial No : 06
        | Differenciate btw citizen and user 
        | check if the ulb is same as the consumer details 
     */
    public function applyDeactivation(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'ulbId'         => "required",
                'reason'        => "required",
                'remarks'       => "required"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $user                           = authUser($request);
            $refRequest                     = array();
            $ulbWorkflowObj                 = new WfWorkflow();
            $mWaterWaterConsumer            = new WaterWaterConsumer();
            $mWaterConsumerCharge           = new WaterConsumerCharge();
            $mWaterConsumerChargeCategory   = new WaterConsumerChargeCategory();
            $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
            $refUserType                    = Config::get('waterConstaint.REF_USER_TYPE');
            $refConsumerCharges             = Config::get('waterConstaint.CONSUMER_CHARGE_CATAGORY');
            $refApplyFrom                   = Config::get('waterConstaint.APP_APPLY_FROM');
            $refWorkflow                    = Config::get('workflow-constants.WATER_DISCONNECTION');
            $refConParamId                  = Config::get('waterConstaint.PARAM_IDS');

            # Check the condition for deactivation
            $refDetails = $this->PreConsumerDeactivationCheck($request, $user);
            $ulbId      = $request->ulbId ?? $refDetails['consumerDetails']['ulb_id'];

            # Get initiater and finisher
            $ulbWorkflowId = $ulbWorkflowObj->getulbWorkflowId($refWorkflow, $ulbId);
            if (!$ulbWorkflowId) {
                throw new Exception("Respective Ulb is not maped to Water Workflow!");
            }
            $refInitiatorRoleId = $this->getInitiatorId($ulbWorkflowId->id);
            $refFinisherRoleId  = $this->getFinisherId($ulbWorkflowId->id);
            $finisherRoleId     = DB::select($refFinisherRoleId);
            $initiatorRoleId    = DB::select($refInitiatorRoleId);
            if (!$finisherRoleId || !$initiatorRoleId) {
                throw new Exception("initiatorRoleId or finisherRoleId not found for respective Workflow!");
            }

            # If the user is not citizen
            if ($user->user_type != $refUserType['1']) {
                $request->request->add(['workflowId' => $refWorkflow]);
                $roleDetails = $this->getRole($request);
                $roleId = $roleDetails['wf_role_id'];
                $refRequest = [
                    "applyFrom" => $user->user_type,
                    "empId"     => $user->id
                ];
            } else {
                $refRequest = [
                    "applyFrom" => $refApplyFrom['1'],
                    "citizenId" => $user->id
                ];
            }

            # Get chrages for deactivation
            $chargeAmount = $mWaterConsumerChargeCategory->getChargesByid($refConsumerCharges['WATER_DISCONNECTION']);
            $refChargeList = collect($refConsumerCharges)->flip();

            $refRequest["initiatorRoleId"]   = collect($initiatorRoleId)->first()->role_id;
            $refRequest["finisherRoleId"]    = collect($finisherRoleId)->first()->role_id;
            $refRequest["roleId"]            = $roleId ?? null;
            $refRequest["ulbWorkflowId"]     = $ulbWorkflowId->id;
            $refRequest["chargeCatagoryId"]  = $refConsumerCharges['WATER_DISCONNECTION'];
            $refRequest["amount"]            = $chargeAmount->amount;
            $refRequest['userType']          = $user->user_type;

            DB::beginTransaction();
            $idGeneration       = new PrefixIdGenerator($refConParamId['WCD'], $ulbId);
            $applicationNo      = $idGeneration->generate();
            $applicationNo      = str_replace('/', '-', $applicationNo);
            $deactivatedDetails = $mWaterConsumerActiveRequest->saveRequestDetails($request, $refDetails['consumerDetails'], $refRequest, $applicationNo);
            $metaRequest = [
                'chargeAmount'      => $chargeAmount->amount,
                'amount'            => $chargeAmount->amount,
                'ruleSet'           => null,
                'chargeCategoryId'  => $refConsumerCharges['WATER_DISCONNECTION'],
                'relatedId'         => $deactivatedDetails['id'],
                'status'            => 2                                                // Static
            ];
            $mWaterConsumerCharge->saveConsumerCharges($metaRequest, $request->consumerId, $refChargeList['2']);
            $mWaterWaterConsumer->dissconnetConsumer($request->consumerId, $metaRequest['status']);
            DB::commit();
            return responseMsgs(true, "Respective Consumer Deactivated!", "", "", "02", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", "");
        }
    }

    /**
     * | Check the condition before appling for deactivation
     * | @param
     * | @var 
        | Not Working
        | Serial No : 06.01
        | Recheck the amount and the order from weaver committee 
        | Check if the consumer applied for other requests
     */
    public function PreConsumerDeactivationCheck($request, $user)
    {
        $consumerId                     = $request->consumerId;
        $mWaterWaterConsumer            = new WaterWaterConsumer();
        $mWaterConsumerDemand           = new WaterConsumerDemand();
        $mWaterConsumerActiveRequest    = new WaterConsumerActiveRequest();
        $refUserType                    = Config::get('waterConstaint.REF_USER_TYPE');

        $refConsumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        $pendingDemand      = $mWaterConsumerDemand->getConsumerDemand($consumerId);
        $firstPendingDemand = collect($pendingDemand)->first();

        if (isset($firstPendingDemand)) {
            throw new Exception("There are unpaid pending demand!");
        }
        if (isset($request->ulbId) && $request->ulbId != $refConsumerDetails->ulb_id) {
            throw new Exception("ulb not matched according to consumer connection!");
        }
        if ($refConsumerDetails->user_type == $refUserType['1'] && $user->id != $refConsumerDetails->user_id) {
            throw new Exception("You are not the autherised user who filled before the connection!");
        }
        $activeReq = $mWaterConsumerActiveRequest->getRequestByConId($consumerId)->first();
        if ($activeReq) {
            throw new Exception("There are other request applied for respective consumer connection!");
        }
        return [
            "consumerDetails" => $refConsumerDetails
        ];
    }



    /**
     * | Post Other Payment Modes for Cheque,DD,Neft
     * | @param req
        | Serial No : 06.03.01
        | Not Working
     */
    public function postOtherPaymentModes($req)
    {
        $cash = Config::get('payment-constants.PAYMENT_MODE.3');
        $moduleId = Config::get('module-constants.WATER_MODULE_ID');
        $mTempTransaction = new TempTransaction();

        if ($req['paymentMode'] != $cash) {
            $mPropChequeDtl = new WaterChequeDtl();
            $chequeReqs = [
                'user_id'           => $req['userId'],
                'consumer_id'       => $req['id'],
                'transaction_id'    => $req['tranId'],
                'cheque_date'       => $req['chequeDate'],
                'bank_name'         => $req['bankName'],
                'branch_name'       => $req['branchName'],
                'cheque_no'         => $req['chequeNo']
            ];

            $mPropChequeDtl->postChequeDtl($chequeReqs);
        }

        $tranReqs = [
            'transaction_id'    => $req['tranId'],
            'application_id'    => $req['id'],
            'module_id'         => $moduleId,
            'workflow_id'       => $req['workflowId'] ?? 0,
            'transaction_no'    => $req['tranNo'],
            'application_no'    => $req['applicationNo'],
            'amount'            => $req['amount'],
            'payment_mode'      => strtoupper($req['paymentMode']),
            'cheque_dd_no'      => $req['chequeNo'],
            'bank_name'         => $req['bankName'],
            'tran_date'         => $req['todayDate'],
            'user_id'           => $req['userId'],
            'ulb_id'            => $req['ulbId'],
            'ward_no'           => $req['ward_no']
        ];
        $mTempTransaction->tempTransaction($tranReqs);
    }


    #---------------------------------------------------------------------------------------------------------#

    /**
     * | Demand deactivation process
     * | @param 
     * | @var 
     * | @return 
        | Not Working
        | Serial No :
        | Not Build
     */
    public function consumerDemandDeactivation(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'demandId'      => "required|array|unique:water_consumer_demands,id'",
                'paymentMode'   => "required|in:Cash,Cheque,DD",
                'amount'        => "required",
                'reason'        => "required"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $mWaterWaterConsumer = new WaterWaterConsumer();
            $mWaterConsumerDemand = new WaterConsumerDemand();

            $this->checkDeactivationDemand($request);
            $this->checkForPayment($request);
        } catch (Exception $e) {
            return responseMsgs(true, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | check if the following conditon if fullfilled for demand deactivation
     * | check for valid user
     * | @param request
     * | @var 
     * | @return 
        | Not Working
        | Serial No: 
        | Not Build
        | Get Concept for deactivation demand
     */
    public function checkDeactivationDemand($request)
    {
        return true;
    }

    /**
     * | Check the concept for payment and amount
     * | @param request
     * | @var 
     * | @return 
        | Not Working
        | Serial No:
        | Get Concept Notes for demand deactivation 
     */
    public function checkForPayment($request)
    {
        $mWaterTran = new WaterTran();
    }

    #---------------------------------------------------------------------------------------------------------#


    /**
     * | View details of the caretaken water connection
     * | using user id
     * | @param request
        | Working
        | Serial No : 07
     */
    public function viewCaretakenConnection(Request $request)
    {
        try {
            $mWaterWaterConsumer        = new WaterWaterConsumer();
            $mActiveCitizenUndercare    = new ActiveCitizenUndercare();

            $connectionDetails = $mActiveCitizenUndercare->getDetailsByCitizenId();
            $checkDemand = collect($connectionDetails)->first();
            if (is_null($checkDemand))
                throw new Exception("Under taken data not found!");

            $consumerIds = collect($connectionDetails)->pluck('consumer_id');
            $consumerDetails = $mWaterWaterConsumer->getConsumerByIds($consumerIds)->get();
            $checkConsumer = collect($consumerDetails)->first();
            if (is_null($checkConsumer)) {
                throw new Exception("Consuemr Details Not Found!");
            }
            return responseMsgs(true, 'list of undertaken water connections!', remove_null($consumerDetails), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Add Fixed Rate for the Meter connection is under Fixed
     * | Admin Entered Data
        | Serial No : 08
        | Use It
        | Recheck 
     */
    public function addFixedRate(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'    => "required|digits_between:1,9223372036854775807",
                'document'      => "required|mimes:pdf,jpg,jpeg,png",
                'ratePerMonth'  => "required|numeric"
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $consumerId             = $request->consumerId;
            $mWaterConsumerMeter    = new WaterConsumerMeter();
            $fixedMeterCode         = Config::get("waterConstaint.WATER_FIXED_CODE");

            $relatedDetails = $this->checkParamForFixedEntry($consumerId);
            $metaRequest = new Request([
                'consumerId'                => $consumerId,
                'connectionDate'            => $relatedDetails['meterDetails']['connection_date'],
                'connectionType'            => $relatedDetails['meterDetails']['connection_type'],
                'newMeterInitialReading'    => $relatedDetails['meterDetails']['initial_reading']
            ]);

            DB::beginTransaction();
            $refDocument = $this->saveDocument($request, $fixedMeterCode);
            $document = [
                'relaivePath'   => $refDocument['relaivePath'],
                'document'      => $refDocument['document']
            ];
            $mWaterConsumerMeter->saveMeterDetails($metaRequest, $document, $request->ratePerMonth);
            DB::commit();
            return responseMsgs(true, "Fixed rate entered successfully!", "", "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [""], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Check the parameter for Fixed meter entry
     * | @param consumerId
        | Seriel No : 08.01
        | Not used
     */
    public function checkParamForFixedEntry($consumerId)
    {
        $mWaterConsumerMeter    = new WaterConsumerMeter();
        $mWaterWaterConsumer    = new WaterWaterConsumer();
        $refPropertyType        = Config::get('waterConstaint.PROPERTY_TYPE');
        $refConnectionType      = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');

        // $consumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        // if ($consumerDetails->property_type_id != $refPropertyType['Government'])
        // throw new Exception("Consumer's property type is not under Government!");

        $meterConnectionDetails = $mWaterConsumerMeter->getMeterDetailsByConsumerId($consumerId)->first();
        if (!$meterConnectionDetails)
            throw new Exception("Consumer meter details not found maybe meter is not installed!");

        if ($meterConnectionDetails->connection_type != $refConnectionType['Fixed'])
            throw new Exception("Consumer meter's connection type is not fixed!");

        return [
            "meterDetails" => $meterConnectionDetails
        ];
    }


    /**
     * | Calculate Final meter reading according to demand upto date and previous upto data 
     * | @param request
        | Serial No : 09
        | Working
     */
    public function calculateMeterFixedReading(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerId'  => "required|",
                'uptoData'    => "required|date",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $todayDate                  = Carbon::now();
            $refConsumerId              = $request->consumerId;
            $mWaterConsumerDemand       = new WaterConsumerDemand();
            $mWaterConsumerInitialMeter = new WaterConsumerInitialMeter();

            if ($request->uptoData > $todayDate) {
                throw new Exception("uptoDate should not be grater than" . " " . $todayDate);
            }
            $refConsumerDemand = $mWaterConsumerDemand->consumerDemandByConsumerId($refConsumerId);
            if (is_null($refConsumerDemand)) {
                throw new Exception("There should be last data regarding meter!");
            }

            $refOldDemandUpto   = $refConsumerDemand->demand_upto;
            $privdayDiff        = Carbon::parse($refConsumerDemand->demand_upto)->diffInDays(Carbon::parse($refConsumerDemand->demand_from));
            $endDate            = Carbon::parse($request->uptoData);
            $startDate          = Carbon::parse($refOldDemandUpto);

            $difference = $endDate->diffInMonths($startDate);
            if ($difference < 1 || $startDate > $endDate) {
                throw new Exception("current uptoData should be grater than the previous uptoDate! and should have a month difference!");
            }
            $diffInDays = $endDate->diffInDays($startDate);
            $finalMeterReading = $mWaterConsumerInitialMeter->getmeterReadingAndDetails($refConsumerId)
                ->orderByDesc('id')
                ->first();
            $finalSecondLastReading = $mWaterConsumerInitialMeter->getSecondLastReading($refConsumerId, $finalMeterReading->id);
            if (is_null($refConsumerDemand)) {
                throw new Exception("There should be demand for the previous meter entry!");
            }

            $refTaxUnitConsumed = ($finalMeterReading['initial_reading'] ?? 0) - ($finalSecondLastReading['initial_reading'] ?? 0);
            $avgReading         = $privdayDiff > 0 ? $refTaxUnitConsumed / $privdayDiff : 1;
            $lastMeterReading   = $finalMeterReading->initial_reading;
            $ActualReading      = ($diffInDays * $avgReading) + $lastMeterReading;

            $returnData['finalMeterReading']    = round($ActualReading, 2);
            $returnData['diffInDays']           = $diffInDays;
            $returnData['previousConsumed']     = $refTaxUnitConsumed;

            return responseMsgs(true, "calculated date difference!", $returnData, "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }


    /**
     * | Get Details for memo
     * | Get all details for the consumer application and consumer both details 
     * | @param request
        | Serial No 
        | Use
        | Not Finished
        | Get the card details 
     */
    public function generateMemo(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'consumerNo'  => "required",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $refConsumerNo          = $request->consumerNo;
            $mWaterWaterConsumer    = new WaterWaterConsumer();
            $mWaterTran             = new WaterTran();

            $dbKey = "consumer_no";
            $consumerDetails = $mWaterWaterConsumer->getRefDetailByConsumerNo($dbKey, $refConsumerNo)->first();
            if (is_null($consumerDetails)) {
                throw new Exception("consumer Details not found!");
            }
            $applicationDetails = $this->Repository->getconsumerRelatedData($consumerDetails->id);
            if (is_null($applicationDetails)) {
                throw new Exception("Application Details not found!");
            }
            $transactionDetails = $mWaterTran->getTransNo($consumerDetails->apply_connection_id, null)->get();
            $checkTransaction = collect($transactionDetails)->first();
            if ($checkTransaction) {
                throw new Exception("transactions not found!");
            }

            $consumerDetails;           // consumer related details 
            $applicationDetails;        // application / owners / siteinspection related details 
            $transactionDetails;        // all transactions details 
            $var = null;

            $returnValues = [
                "consumerNo"            => $var,
                "applicationNo"         => $var,
                "year"                  => $var,
                "receivingDate"         => $var,
                "ApprovalDate"          => $var,
                "receiptNo"             => $var,
                "paymentDate"           => $var,
                "wardNo"                => $var,
                "applicantName"         => $var,
                "guardianName"          => $var,
                "correspondingAddress"  => $var,
                "mobileNo"              => $var,
                "email"                 => $var,
                "holdingNo"             => $var,
                "safNo"                 => $var,
                "builUpArea"            => $var,
                "connectionThrough"     => $var,
                "AppliedFrom"           => $var,
                "ownersDetails"         => $var,
                "siteInspectionDetails" => $var,


            ];
            return responseMsgs(true, "successfully fetched memo details!", remove_null($returnValues), "", "01", ".ms", "POST", $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    //Start///////////////////////////////////////////////////////////////////////
    /**
     * | Search the governmental prop water commention 
     * | Search only the Gov water connections 
        | Serial No :
        | use
        | Not finished
     */
    public function searchFixedConsumers(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'required',
                'parameter' => 'required'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {

            return $waterReturnDetails = $this->getDetailByConsumerNo($request, 'consumer_no', '2016000500');
            return false;

            $mWaterConsumer = new WaterWaterConsumer();
            $key            = $request->filterBy;
            $paramenter     = $request->parameter;
            $string         = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring      = strtolower($string);

            switch ($key) {
                case ("consumerNo"):                                                                        // Static
                    $waterReturnDetails = $this->getDetailByConsumerNo($request, $refstring, $paramenter);
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("holdingNo"):                                                                         // Static
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($request, $refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("safNo"):                                                                             // Static
                    $waterReturnDetails = $mWaterConsumer->getDetailByConsumerNo($request, $refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ("applicantName"):                                                                     // Static
                    $paramenter = strtoupper($paramenter);
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ('mobileNo'):                                                                          // Static
                    $paramenter = strtoupper($paramenter);
                    $waterReturnDetails = $mWaterConsumer->getDetailByOwnerDetails($refstring, $paramenter)->get();
                    $checkVal = collect($waterReturnDetails)->first();
                    if (!$checkVal)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                default:
                    throw new Exception("Data provided in filterBy is not valid!");
            }
            return responseMsgs(true, "Water Consumer Data According To Parameter!", remove_null($waterReturnDetails), "", "01", "652 ms", "POST", "");
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }


    // Calling function
    public function getDetailByConsumerNo($req, $key, $refNo)
    {
        $refConnectionType = Config::get('waterConstaint.WATER_MASTER_DATA.METER_CONNECTION_TYPE');
        return WaterWaterConsumer::select(
            'water_consumers.id',
            'water_consumers.consumer_no',
            'water_consumers.ward_mstr_id',
            'water_consumers.address',
            'water_consumers.holding_no',
            'water_consumers.saf_no',
            'water_consumers.ulb_id',
            'ulb_ward_masters.ward_name',
            DB::raw("string_agg(water_consumer_owners.applicant_name,',') as applicant_name"),
            DB::raw("string_agg(water_consumer_owners.mobile_no::VARCHAR,',') as mobile_no"),
            DB::raw("string_agg(water_consumer_owners.guardian_name,',') as guardian_name"),
        )
            ->join('water_consumer_owners', 'water_consumer_owners.consumer_id', '=', 'water_consumers.id')
            ->leftJoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'water_consumers.ward_mstr_id')
            ->leftjoin('water_consumer_meters', 'water_consumer_meters.consumer_id', 'water_consumers.id')
            ->where('water_consumers.' . $key, 'LIKE', '%' . $refNo . '%')
            ->where('water_consumers.status', 1)
            ->where('water_consumers.ulb_id', authUser($req)->ulb_id)
            ->where('water_consumer_meters.connection_type', $refConnectionType['Fixed'])
            ->groupBy(
                'water_consumers.saf_no',
                'water_consumers.holding_no',
                'water_consumers.address',
                'water_consumers.id',
                'water_consumers.ulb_id',
                'water_consumer_owners.consumer_id',
                'water_consumers.consumer_no',
                'water_consumers.ward_mstr_id',
                'ulb_ward_masters.ward_name'
            )->first();
    }
    ///////////////////////////////////////////////////////////////////////End//

    /**
     * | Citizen self generation of demand 
     * | generate demand only the last day of the month
        | Serial No :
        | Working
     */
    public function selfGenerateDemand(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'id'            => 'required',
                'finalReading'  => 'required',
                'document'      => 'required|file|'
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        try {
            $today                  = Carbon::now();
            $consumerId             = $req->id;
            $mWaterWaterConsumer    = new WaterWaterConsumer();
            $refConsumerDetails     = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
            $refDetails             = $this->checkUser($req, $refConsumerDetails);
            $metaReq = new Request([
                "consumerId" => $consumerId
            ]);

            $this->checkDemandGeneration($metaReq, $refConsumerDetails);
            $metaRequest = new Request([
                "consumerId"    => $consumerId,
                "finalRading"   => $req->finalReading,                          // if the demand is generated for the first time
                "demandUpto"    => $today->format('Y-m-d'),
                "document"      => $req->document,
            ]);
            $returnDetails = $this->saveGenerateConsumerDemand($metaRequest);
            if ($returnDetails->original['status'] == false) {
                throw new Exception($returnDetails->original['message']);
            }
            return responseMsgs(true, "Self Demand Generated!", [], "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Check the user details for self demand generation
     * | check the consumer details with user details
        | Serial No :
     */
    public function checkUser($req, $refConsumerDetails)
    {
        $user                       = authUser($req);
        $todayDate                  = Carbon::now();
        $endDate                    = Carbon::now()->endOfMonth();
        $formatEndDate              = $endDate->format('d-m-Y');
        $refUserType                = Config::get("waterConstaint.REF_USER_TYPE");
        $mActiveCitizenUndercare    = new ActiveCitizenUndercare();

        if ($endDate > $todayDate) {
            throw new Exception("please generate the demand on $formatEndDate or after it!");
        }
        $careTakerDetails   = $mActiveCitizenUndercare->getWaterUnderCare($user->id)->get();
        $consumerIds        = collect($careTakerDetails)->pluck('consumer_id');
        if (!in_array($req->id, ($consumerIds->toArray()))) {
            if ($refConsumerDetails->user_type != $refUserType['1']) {
                throw new Exception("you are not the citizen whose consumer is assigned!");
            }
            if ($refConsumerDetails->user_id != $user->id) {
                throw new Exception("you are not the authorized user!");
            }
        }
    }

    /**
     * | Check the user type and return its id
        | Serial No :
        | Working
     */
    public function checkUserType($req)
    {
        $user = authUser($req);
        $confUserType = Config::get("waterConstaint.REF_USER_TYPE");
        $userType = $user->user_type;

        if ($userType == $confUserType['1']) {
            return [
                "citizen_id"    => $user->id,
                "user_type"     => $userType
            ];
        } else {
            return [
                "emp_id"    => $user->id,
                "user_type" => $userType
            ];
        }
    }


    /**
     * | Add the advance amount for consumer 
     * | If advance amount is present it should be added by a certain official
        | Serial No :
        | Under Con
     */
    public function addAdvance(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'consumerId'    => 'required|int',
                'amount'        => 'required|int',
                'document'      => 'required|file|',
                'remarks'       => 'required',
                'reason'        => 'nullable'
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $user           = authUser($req);
            $docAdvanceCode = Config::get('waterConstaint.WATER_ADVANCE_CODE');
            $refAdvanceFor  = Config::get('waterConstaint.ADVANCE_FOR');
            $refWorkflow    = Config::get('workflow-constants.WATER_MASTER_ID');
            $mWaterAdvance  = new WaterAdvance();

            $refDetails = $this->checkParamForAdvanceEntry($req, $user);
            $req->request->add(['workflowId' => $refWorkflow]);
            $roleDetails = $this->getRole($req);
            $roleId = $roleDetails['wf_role_id'];
            $req->request->add(['roleId' => $roleId]);

            DB::beginTransaction();
            $docDetails = $this->saveDocument($req, $docAdvanceCode);
            $req->merge([
                "relatedId" => $req->consumerId,
                "userId"    => $user->id,
                "userType"  => $user->user_type,
            ]);
            $mWaterAdvance->saveAdvanceDetails($req, $refAdvanceFor['1'], $docDetails);
            DB::commit();
            return responseMsgs(true, "Advance Details saved successfully!", [], "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsgs(false, $e->getMessage(), [], "", "01", ".ms", "POST", $req->deviceId);
        }
    }


    /**
     * | Chech the params for adding advance 
        | Serial No :
        | Under Con
        | Check the autherised user is entring the advance amount
     */
    public function checkParamForAdvanceEntry($req, $user)
    {
        $consumerId = $req->consumerId;
        $refUserType = Config::get("waterConstaint.REF_USER_TYPE");
        $mWaterWaterConsumer = new WaterWaterConsumer();

        $consumerDetails = $mWaterWaterConsumer->getConsumerDetailById($consumerId);
        if ($user->user_type == $refUserType['1']) {
            throw new Exception("You are not a verified Use!");
        }
    }















    /**
     * | Doc upload through document upload service 
        | Type test
     */
    public function checkDoc(Request $request)
    {
        try {
            // $contentType = (collect(($request->headers->all())['content-type'] ?? "")->first());
            $file = $request->document;
            $filePath = $file->getPathname();
            $hashedFile = hash_file('sha256', $filePath);
            $filename = ($request->document)->getClientOriginalExtension();
            $api = "http://192.168.0.106:8001/myDoc/upload";
            $transfer = [
                "file" => $request->document,
                "tags" => "good",
                "token" => 425
            ];
            $returnData = Http::withHeaders([
                "x-digest" => "$hashedFile"
            ])->attach([
                [
                    'file',
                    file_get_contents($request->file('document')->getRealPath()),
                    $filename
                ]
            ])->post("$api", $transfer);

            if ($returnData->successful()) {
                $statusCode = $returnData->status();
                $responseBody = $returnData->body();
                return $returnData;
            } else {
                $statusCode = $returnData->status();
                $responseBody = $returnData->body();
                return $responseBody;
            }
            return false;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
