<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ThirdPartyController;
use App\Models\ActiveCitizen;
use App\Models\Citizen\ActiveCitizenUndercare;
use App\Models\Property\PropActiveConcession;
use App\Models\Property\PropActiveHarvesting;
use App\Models\Property\PropActiveObjection;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropDemand;
use App\Models\Property\PropOwner;
use App\Models\Property\PropProperty;
use App\Models\Property\PropSaf;
use App\Models\Property\PropSafsDemand;
use App\Models\Property\PropTransaction;
use App\Models\Workflows\WfActiveDocument;
use App\Pipelines\SearchHolding;
use App\Pipelines\SearchPtn;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

/**
 * | Created On - 11-03-2023
 * | Created By - Mrinal Kumar
 * | Status - Open
 */

class PropertyController extends Controller
{
    /**
     * | Send otp for caretaker property
     */
    public function caretakerOtp(Request $req)
    {
        try {
            $mPropOwner = new PropOwner();
            $ThirdPartyController = new ThirdPartyController();
            $propertyModuleId = Config::get('module-constants.PROPERTY_MODULE_ID');
            $waterModuleId = Config::get('module-constants.WATER_MODULE_ID');
            $tradeModuleId = Config::get('module-constants.TRADE_MODULE_ID');
            if ($req->moduleId == $propertyModuleId) {
            }
            $propDtl = app(Pipeline::class)
                ->send(PropProperty::query()->where('status', 1))
                ->through([
                    SearchHolding::class,
                    SearchPtn::class
                ])
                ->thenReturn()
                ->first();

            if (!isset($propDtl))
                throw new Exception('Property Not Found');
            $propOwners = $mPropOwner->getOwnerByPropId($propDtl->id);
            $firstOwner = collect($propOwners)->first();
            if (!$firstOwner)
                throw new Exception('Owner Not Found');
            $ownerMobile = $firstOwner->mobileNo;

            $myRequest = new \Illuminate\Http\Request();
            $myRequest->setMethod('POST');
            $myRequest->request->add(['mobileNo' => $ownerMobile]);
            $response = $ThirdPartyController->sendOtp($myRequest);

            $response = collect($response)->toArray();
            $data['otp'] = $response['original']['data'];
            $data['mobileNo'] = $ownerMobile;

            return responseMsgs(true, "OTP send successfully", $data, '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Care taker property tag
     */
    public function caretakerPropertyTag(Request $req)
    {
        $req->validate([
            'holdingNo' => 'required_without:ptNo|max:255',
            'ptNo' => 'required_without:holdingNo|numeric',
        ]);
        try {
            $userId = authUser()->id;
            $activeCitizen = ActiveCitizen::findOrFail($userId);

            $propDtl = app(Pipeline::class)
                ->send(PropProperty::query()->where('status', 1))
                ->through([
                    SearchHolding::class,
                    SearchPtn::class
                ])
                ->thenReturn()
                ->first();

            if (!isset($propDtl))
                throw new Exception('Property Not Found');

            $allPropIds = $this->ifPropertyExists($propDtl->id, $activeCitizen);
            $activeCitizen->caretaker = $allPropIds;
            $activeCitizen->save();

            return responseMsgs(true, "Property Tagged!", '', '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Function if Property Exists
     */
    public function ifPropertyExists($propId, $activeCitizen)
    {
        $propIds = collect(explode(',', $activeCitizen->caretaker));
        $propIds->push($propId);
        return $propIds->implode(',');
    }

    /**
     * | Logged in citizen Holding & Saf
     */
    public function citizenHoldingSaf(Request $req)
    {
        $req->validate([
            'type' => 'required|In:holding,saf,ptn',
            'ulbId' => 'required|numeric'
        ]);
        try {
            $citizenId = authUser()->id;
            $ulbId = $req->ulbId;
            $type = $req->type;
            $mPropSafs = new PropSaf();
            $mPropActiveSafs = new PropActiveSaf();
            $mPropProperty = new PropProperty();
            $mActiveCitizenUndercare = new ActiveCitizenUndercare();
            $caretakerProperty =  $mActiveCitizenUndercare->getTaggedPropsByCitizenId($citizenId);

            if ($type == 'saf') {
                $data = $mPropActiveSafs->getCitizenSafs($citizenId, $ulbId);
                $msg = 'Citizen Safs';
            }

            if ($type == 'holding') {
                $data = $mPropProperty->getCitizenHoldings($citizenId, $ulbId);
                if ($caretakerProperty->isNotEmpty()) {
                    $propertyId = collect($caretakerProperty)->pluck('property_id');
                    $data2 = $mPropProperty->getNewholding($propertyId);
                    $data = $data->merge($data2);
                }
                $data = collect($data)->map(function ($value) {
                    if (isset($value['new_holding_no'])) {
                        return $value;
                    }
                })->filter()->values();
                $msg = 'Citizen Holdings';
            }

            if ($type == 'ptn') {
                $data = $mPropProperty->getCitizenPtn($citizenId, $ulbId);
                $msg = 'Citizen Ptn';

                if ($caretakerProperty->isNotEmpty()) {
                    $propertyId = collect($caretakerProperty)->pluck('property_id');
                    $data2 = $mPropProperty->getPtn($propertyId);
                    $data = $data->merge($data2);
                }
                $data = collect($data)->map(function ($value) {
                    if (isset($value['pt_no'])) {
                        return $value;
                    }
                })->filter()->values();
            }

            if ($data->isEmpty())
                throw new Exception('No Data Found');

            return responseMsgs(true, $msg, remove_null($data), '010801', '01', '623ms', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Property Basic Edit
     */
    public function basicPropertyEdit(Request $req)
    {
        try {
            $mPropProperty = new PropProperty();
            $mPropOwners = new PropOwner();
            $propId = $req->propertyId;
            $mOwners = $req->owner;

            $mreq = new Request(
                [
                    "new_ward_mstr_id" => $req->newWardMstrId,
                    "khata_no" => $req->khataNo,
                    "plot_no" => $req->plotNo,
                    "village_mauja_name" => $req->villageMauja,
                    "prop_pin_code" => $req->pinCode,
                    "building_name" => $req->buildingName,
                    "street_name" => $req->streetName,
                    "location" => $req->location,
                    "landmark" => $req->landmark,
                    "prop_address" => $req->address,
                    "corr_pin_code" => $req->corrPin,
                    "corr_address" => $req->corrAddress
                ]
            );
            $mPropProperty->editProp($propId, $mreq);

            collect($mOwners)->map(function ($owner) use ($mPropOwners) {            // Updation of Owner Basic Details
                if (isset($owner['ownerId'])) {

                    $req = new Request([
                        'id' =>  $owner['ownerId'],
                        'owner_name' => $owner['ownerName'],
                        'guardian_name' => $owner['guardianName'],
                        'relation_type' => $owner['relation'],
                        'mobile_no' => $owner['mobileNo'],
                        'aadhar_no' => $owner['aadhar'],
                        'pan_no' => $owner['pan'],
                        'email' => $owner['email'],
                    ]);
                    $mPropOwners->editPropOwner($req);
                }
            });

            return responseMsgs(true, 'Data Updated', '', '010801', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Check if the property id exist in the workflow
     */
    public function CheckProperty(Request $req)
    {
        $validated = Validator::make(
            $req->all(),
            [
                'type' => 'required|in:Reassesment,Mutation,Concession,Objection,Harvesting,Bifurcation',
                'propertyId' => 'required|numeric',
            ]
        );
        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $type = $req->type;
            $propertyId = $req->propertyId;

            switch ($type) {
                case 'Reassesment':
                    $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                        ->where('previous_holding_id', $propertyId)
                        ->where('prop_active_safs.status', 1)
                        ->first();
                    break;
                case 'Mutation':
                    $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                        ->where('previous_holding_id', $propertyId)
                        ->where('prop_active_safs.status', 1)
                        ->first();
                    break;
                case 'Bifurcation':
                    $data = PropActiveSaf::select('prop_active_safs.id', 'role_name', 'saf_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_safs.current_role')
                        ->where('previous_holding_id', $propertyId)
                        ->where('prop_active_safs.status', 1)
                        ->first();
                    break;
                case 'Concession':
                    $data = PropActiveConcession::select('prop_active_concessions.id', 'role_name', 'application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_concessions.current_role')
                        ->where('property_id', $propertyId)
                        ->where('prop_active_concessions.status', 1)
                        ->first();
                    break;
                case 'Objection':
                    $data = PropActiveObjection::select('prop_active_objections.id', 'role_name', 'objection_no as application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_objections.current_role')
                        ->where('property_id', $propertyId)
                        ->where('prop_active_objections.status', 1)
                        ->first();
                    break;
                case 'Harvesting':
                    $data = PropActiveHarvesting::select('prop_active_harvestings.id', 'role_name', 'application_no')
                        ->join('wf_roles', 'wf_roles.id', 'prop_active_harvestings.current_role')
                        ->where('property_id', $propertyId)
                        ->where('prop_active_harvestings.status', 1)
                        ->first();
                    break;
            }
            if ($data) {
                $msg['id'] = $data->id;
                $msg['inWorkflow'] = true;
                $msg['currentRole'] = $data->role_name;
                $msg['message'] = "The application is still in workflow and pending at " . $data->role_name . ". Please Track your application with " . $data->application_no;
            } else
                $msg['inWorkflow'] = false;

            return responseMsgs(true, 'Data Updated', $msg, '010801', '01', '', 'Post', '');
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    /**
     * | Get the Property LatLong for Heat map
     * | Using wardId used in dashboard data 
     * | @param req
        | For MVP testing
     */
    public function getpropLatLong(Request $req)
    {
        $req->validate([
            'wardId' => 'required|integer',
        ]);
        try {
            $mPropProperty = new PropProperty();
            $propDetails = $mPropProperty->getPropLatlong($req->wardId);
            $propDetails = collect($propDetails)->map(function ($value) {

                $currentDate = Carbon::now()->format('Y-04-01');
                $refCurrentDate = Carbon::createFromFormat('Y-m-d', $currentDate);
                $mPropDemand = new PropDemand();

                $geoDate = strtotime($value['created_at']);
                $geoDate = date('Y-m-d', $geoDate);
                $ref2023 = Carbon::createFromFormat('Y-m-d', "2023-01-01")->toDateString();

                $path = $this->readDocumentPath($value['doc_path']);
                # arrrer,current,paid
                $refUnpaidPropDemands = $mPropDemand->getDueDemandByPropId($value['property_id']);
                $checkPropDemand = collect($refUnpaidPropDemands)->last();
                if (is_null($checkPropDemand)) {
                    $currentStatus = 3;                                                             // Static
                    $statusName = "No Dues";                                                         // Static
                }
                if ($checkPropDemand) {
                    $lastDemand = collect($refUnpaidPropDemands)->last();
                    if (is_null($lastDemand->due_date)) {
                        $currentStatus = 3;                                                         // Static
                        $statusName = "No Dues";                                                     // Static
                    }
                    $refDate = Carbon::createFromFormat('Y-m-d', $lastDemand->due_date);
                    if ($refDate < $refCurrentDate) {
                        $currentStatus = 1;                                                         // Static
                        $statusName = "Arrear";                                                    // Static
                    } else {
                        $currentStatus = 2;                                                         // Static
                        $statusName = "Current Dues";                                               // Static
                    }
                }
                $value['statusName'] = $statusName;
                $value['currentStatus'] = $currentStatus;
                if ($geoDate < $ref2023) {
                    $path = $this->readRefDocumentPath($value['doc_path']);
                    $value['full_doc'] = !empty(trim($value['doc_path'])) ? $path : null;
                    return $value;
                }
                $value['full_doc'] = !empty(trim($value['doc_path'])) ? $path : null;
                return $value;
            })->filter(function ($refValues) {
                return $refValues['new_holding_no'] != null;
            });
            return responseMsgs(true, "latLong Details", remove_null($propDetails), "", "01", ".ms", "POST", $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", ".ms", "POST", $req->deviceId);
        }
    }
    public function readRefDocumentPath($path)
    {
        $path = ("https://smartulb.co.in/RMCDMC/getImageLink.php?path=" . "/" . $path);                      // Static
        return $path;
    }
    public function readDocumentPath($path)
    {
        $path = (config('app.url') . "/" . $path);
        return $path;
    }
}
