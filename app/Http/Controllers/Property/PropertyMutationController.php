<?php

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Property\reqApplySaf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Property\PropActiveMutation;
use App\Models\Property\PropActiveApp;
use App\Models\Property\PropActiveSaf;
use App\Models\Property\PropProperty;
use App\Traits\Property\Property;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Instanceof_;

class PropertyMutationController extends Controller
{
    use Property;
    public function addMutationApplication(Request $request)
    { 
        
        try{
            $todayDate = Carbon::now()->format('Y-m-d');
            $validator = Validator::make($request->all(), [
                "propertyId" => "required|digits_between:1,9223372036854775807",
                "citizenId" => "required|digits_between:1,9223372036854775807",
                //"applicationType" => 'required|regex:/^[A-Za-z.\s]+$/i',
                'applicationDate' => 'required|date',
                "areaOfPlot"    => "required|numeric|not_in:0",
                "owner" => "nullable|array",
                'owner.*.ownerName' => 'required|regex:/^[A-Za-z.\s]+$/i',
                'owner.*.ward' => 'required|digits_between:1,9223372036854775807',
                'owner.*.Zone' => 'required|digits_between:1,9223372036854775807',
                'owner.*.address' => 'required|string|max:255',
                "owner.*.gender" => "nullable|In:Male,Female,Transgender",
                "owner.*.dob" => "nullable|date|date_format:Y-m-d|before_or_equal:$todayDate",
                "owner.*.guardianName" => "nullable|string",
                "owner.*.relation" => "nullable|string|in:S/O,W/O,D/O,C/O",
                "owner.*.mobileNo" => "nullable|digits:10|regex:/[0-9]{10}/",
                "owner.*.aadhar" => "digits:12|regex:/[0-9]{12}/|nullable",
                "owner.*.pan" => "string|nullable",
                "owner.*.email" => "email|nullable",
                "owner.*.isArmedForce" => "nullable|bool",
                "owner.*.isSpeciallyAbled" => "nullable|bool"

            ]);
            $request->merge(["assessmentType"=>"6",
                            "previousHoldingId"=>$request->propertyId,
            ]);
            //dd($request);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validator->errors()
                ], 422);
            }
            $propProperty = PropProperty::find($request->propertyId);         
            if(!$propProperty)
            {
                throw new Exception("Data Not found");
            }
            $request->merge($this->generatePropUpdateRequest($request,$propProperty,true));
            
            if(!$propProperty->status)
            {
                throw new Exception("Property Is Deacatived");
            }
            $dueDemands = $propProperty->PropDueDemands()->get();
            if(collect($dueDemands)->sum("due_total_tax")>0)
            {
                // throw new Exception("Please clear Due Demand First");
            }
            $owners = $propProperty->Owneres()->get();
            if(!$request->owner)
            {   $newOwners = [];
                foreach($owners as $val)
                {
                    $newOwners[]=$this->generatePropOwnerUpdateRequest([],$val,true);
                }
                $request->merge(["owner"=>$newOwners]);
            }
            $propFloars = $propProperty->floars()->get();
            $newFloars["floor"] =[];
            foreach($propFloars as $key=>$val)
            {
                $newFloars["floor"][]=($this->generatePropFloar($request,$val));

            }
            $request->merge($newFloars);
            //dd($request->all());

            $ApplySafContoller = new ApplySafController();
            $applySafRequet = new reqApplySaf();
            $applySafRequet->merge($request->all());
            // dd($applySafRequet->all());
            DB::beginTransaction();
            $newSafRes = ($ApplySafContoller->applySaf($applySafRequet));
            if(!$newSafRes->original["status"])
            {
                return $newSafRes;
            }
            $safData = $newSafRes->original["data"];
            $safId = $safData["safId"];
            dd($safData);
            
            $app = PropActiveApp::create([
                'citizen_id' => $request->citizenId,
                'application_type' => $request->applicationType,
                'application_date' => $request->applicationDate
            ]);            
            $appId = $app->id;
            $newSafData = PropActiveSaf::find($safId);
            $newSafData->app_id = $appId; 
            $newSafData->update();
            // $mutation = PropActiveMutation::create([
            //     'app_id' => $appId,
            //     'ownerName' => $request->applicantName, 
            //     'address' => $request->address,
            //     'mobileNo' => $request->mobileNo,
            //     'areaOfPlot' => $request->areaOfPlot,
            //     'email' => $request->email,
            //     'ward' => $request->ward,
            //     'propertyId' => $request->propertyId,
            //     'applicationType' => $request->applicationType,
            //     'applicationDate' => $request->applicationDate,
            //     'Zone' => $request->Zone,
            // ]);
          
          DB::commit();
  
          return responseMsgs(true, "mutation applied successfully", $safData, '010801', '01', '623ms', 'Post', '');
        
        } catch (Exception $e) {
            DB::rollBack();
            return responseMsg(false, [$e->getMessage(),$e->getFile(),$e->getLine()], "");

        }
    }

    function calculateTransferFee($saleValue, $propertyType, $isFamilyTransfer, $isGovernmentLand) {
        $transferFee = 0;
    
        if ($saleValue <= 1500000) {
            $transferFee = $saleValue * 0.01;
        } else {
            $transferFee = 15000;
        }
    
        if ($propertyType == 'Open Land') {
            $transferFee += 2000;
        }
    
        if ($isFamilyTransfer) {
            $transferFee = 500;
        }
    
        if ($propertyType == 'Private Property' && $isGovernmentLand) {
            $transferFee += 2000;
        }
    
        return $transferFee;
    }
    
    
    
}
  

