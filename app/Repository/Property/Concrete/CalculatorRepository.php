<?php

namespace App\Repository\Property\Concrete;

use App\Repository\Property\Interfaces\iCalculatorRepository;
use Illuminate\Http\Request;
use App\Models\UlbWardMaster;

use App\Traits\Auth;
use App\Traits\Property\WardPermission;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\EloquentClass\Property\InsertTax;
use App\EloquentClass\Property\SafCalculation;
use App\Models\Property\PropActiveSaf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

/**
 * | Created On-
 * | Created By-
 * -----------------------------------------------------------------------------------------
 * | Calculate property tax
 */
class CalculatorRepository implements iCalculatorRepository
{
    use Auth;

    /**
     * | Citizens check property tax
     * | Proper Validation will be applied after 
     * | @param Illuminate\Http\Request
     * | @param Request $request
     * | @param response
     */
    protected $calculation;
    protected $user_id;
    public function __construct()
    {
        $this->calculation = new SafCalculation();
    }


    public function safCalculator($request)
    {
        try {
            $calculateArr = array();
            $mobileTower = array();
            $hordingBoard = array();
            $petrolPump = array();

            $mobileTower['area'] = $request->mobileTowerArea;
            $mobileTower['dateFrom'] = $request->mobileTowerDate;

            $hordingBoard['area'] = $request->hoardingArea;
            $hordingBoard['dateFrom'] = $request->hoardingDate;

            $petrolPump['area'] = $request->petrolPumpArea;
            $petrolPump['dateFrom'] = $request->petrolPumpDate;

            $calculateArr['ulbId'] = $request->ulbId;
            $calculateArr['isMobileTower'] = ($request->mobileTowerArea) ? 1 : 0;
            $calculateArr['mobileTower'] = $mobileTower;
            $calculateArr['isHoardingBoard'] = ($request->hoardingArea) ? 1 : 0;
            $calculateArr['hoardingBoard'] = $hordingBoard;
            $calculateArr['isPetrolPump'] = ($request->petrolPumpArea) ? 1 : 0;
            $calculateArr['petrolPump'] = $petrolPump;


            $request->request->add($calculateArr);

            $response = $this->calculation->calculateTax($request);
            $fetchDetails = collect($response->original['data']['details'])->groupBy('ruleSet');
            $finalResponse['demand'] = $response->original['data']['demand'];
            $finalResponse['details']['review'] = $response->original['data']['demand'];
            $finalResponse['details']['description'] = $fetchDetails;
            return responseMsg(true, "", $finalResponse);
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }

    public function getDashboardData($request)
    {
        try {
            if (isset($request->fromDate) && isset($request->toDate)) {
                $user_id = authUser($request)->id;
                $ulb_id = authUser($request)->ulb_id;
                $fromDate = Carbon::create($request->fromDate)->format('Y-m-d');
                $toDate = Carbon::create($request->toDate)->format('Y-m-d');
                $response = array();
                $applicationList = array();

                $pendingApplication = PropActiveSaf::where('ulb_id', $ulb_id)->whereBetween('application_date', [$fromDate, $toDate])->count();
                $approvedApplication = PropActiveSaf::where('ulb_id', $ulb_id)->whereBetween('application_date', [$fromDate, $toDate])->count();
                $rejectedApplication = PropActiveSaf::where('ulb_id', $ulb_id)->whereBetween('application_date', [$fromDate, $toDate])->count();


                $val['type'] = 'New Assessment';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Re-Assessment';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Mutation';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Bifercation';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Amalgamation';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Objection';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Concession';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $val['type'] = 'Water-Harvesting';
                $val['applied'] = 400;
                $val['approved'] = 200;
                $val['rejected'] = 200;
                $val['pending'] = 200;
                $applicationList[] = $val;

                $response['totalAppliedApplication'] = 5000;
                $response['totalApprovedApplication'] = 2000;
                $response['totalRejectedApplication'] = 500;
                $response['totalPendingApplication'] = 10;
                $response['applicationList'] = $applicationList;

                return responseMsg(true, "", $response);
            } else {
                return responseMsg(true, "Undefined parameter supply", "");
            }
        } catch (Exception $e) {
            return responseMsg(false, $e->getMessage(), "");
        }
    }
}
