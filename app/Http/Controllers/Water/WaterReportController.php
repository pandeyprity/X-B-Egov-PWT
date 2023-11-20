<?php

namespace App\Http\Controllers\Water;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Water\WaterTran;
use App\Traits\Water\WaterTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\UlbWardMaster;
use App\Models\Water\WaterConsumer;
use Illuminate\Support\Facades\Config;
use App\Models\Water\WaterConsumerDemand;
use Illuminate\Support\Facades\Validator;
use App\Models\Water\WaterSecondConsumer;
use  App\Http\Requests\Water\colllectionReport;


/**
 * | ----------------------------------------------------------------------------------
 * | Water Module |
 * |-----------------------------------------------------------------------------------
 * | Created On-14-04-2023
 * | Created By-Sam Kumar 
 * | Created For-Water Related Reports
 */

class WaterReportController extends Controller
{
    use WaterTrait;
    /**
     * | Water count of online payment
        | Serial No : 01
        | Not Tested
     */
    public function onlinePaymentCount(Request $req)
    {
        try {
            $mWaterTran = new WaterTran();
            $year = Carbon::now()->year;

            if (isset($req->fyear))
                $year = substr($req->fyear, 0, 4);

            $financialYearStart = $year;
            if (Carbon::now()->month < 4) {
                $financialYearStart--;
            }

            $fromDate =  $financialYearStart . '-04-01';
            $toDate   =  $financialYearStart + 1 . '-03-31';

            if ($req->financialYear) {
                $fy = explode('-', $req->financialYear);
                $strtYr = collect($fy)->first();
                $endYr = collect($fy)->last();
                $fromDate =  $strtYr . '-04-01';
                $toDate   =  $endYr . '-03-31';;
            }

            $waterTran = $mWaterTran->getOnlineTrans($fromDate, $toDate)->get();
            $returnData = [
                'waterCount' => $waterTran->count(),
                'totaAmount' => collect($waterTran)->sum('amount')
            ];

            return responseMsgs(true, "Online Payment Count", remove_null($returnData), "", '', '01', '.ms', 'Post', $req->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $req->deviceId);
        }
    }

    /**
     * | Water DCB
        | Serial No : 02
        | Not Working
     */
    public function waterDcb(Request $request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = authUser($request);
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $fiYear = getFY();
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear . "-04-01";
            $uptoDate = $toYear . "-03-31";
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $limit = $perPage;
            $offset =  $request->page && $request->page > 0 ? ($request->page * $perPage) : 0;


            $from = "
                FROM (
                    SELECT *
                    FROM water_consumers
                    WHERE water_consumers.ulb_id = $ulbId
                    ORDER BY id
                    limit $limit offset $offset
                  )water_consumers
                LEFT JOIN (
                    SELECT STRING_AGG(applicant_name, ', ') AS owner_name,
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        water_consumers.id AS water_id
                    FROM water_approval_applicants 
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.apply_connection_id = water_approval_applicants.application_id
                        AND water_consumers.ulb_id = $ulbId
                    WHERE water_approval_applicants.status = true
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                    GROUP BY water_consumers.id
                )water_owner_detail ON water_owner_detail.application_id = water_consumers.apply_connection_id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from <'$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(water_consumer_demands.amount) AS total_demand
                    FROM water_consumer_demands
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_consumer_demands.demand_upto <= '$uptoDate'
                    GROUP BY water_consumer_demands.consumer_id    
                )demands ON demands.consumer_id = water_consumers.id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            CASE when water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(water_consumer_demands.amount) AS total_collection
                    FROM water_consumer_demands
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    JOIN water_tran_details ON water_tran_details.related_id = water_consumer_demands.id 
                        AND water_tran_details.related_id is not null 
                    JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                        AND water_trans.status in (1,2) 
                        AND water_trans.related_id is not null
                        AND water_trans.tran_type = 'Demand Collection'
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_trans.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND water_consumer_demands.demand_upto <='$uptoDate'
                    GROUP BY water_consumer_demands.consumer_id
                )collection ON collection.consumer_id = water_consumers.id
                LEFT JOIN ( 
                    SELECT water_consumer_demands.consumer_id,
                    SUM(water_consumer_demands.amount) AS total_prev_collection
                    FROM water_consumer_demands
                    JOIN (
                        SELECT * 
                        FROM water_consumers
                        WHERE water_consumers.ulb_id = $ulbId
                        ORDER BY id
                        limit $limit offset $offset
                      )water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    JOIN water_tran_details ON water_tran_details.application_id = water_consumer_demands.id 
                        AND water_tran_details.application_id is not null 
                    JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                        AND water_trans.status in (1,2) 
                        AND water_trans.related_id is not null
                        AND water_trans.tran_type = 'Demand Collection'
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_trans.tran_date < '$fromDate'
                    GROUP BY water_consumer_demands.consumer_id
                )prev_collection ON prev_collection.consumer_id = water_consumers.id 
                JOIN ulb_ward_masters ON ulb_ward_masters.id = water_consumers.ward_mstr_id
                WHERE  water_consumers.ulb_id = $ulbId  
                    " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "           
            ";
            $footerfrom = "
                FROM water_consumers
                LEFT JOIN (
                    SELECT STRING_AGG(applicant_name, ', ') AS owner_name,
                        STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                        water_consumers.id AS water_id
                    FROM water_approval_applicants 
                    JOIN water_consumers ON water_consumers.apply_connection_id = water_approval_applicants.application_id
                        AND water_consumers.ulb_id = $ulbId
                    WHERE water_approval_applicants.status = true
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                    GROUP BY water_consumers.id
                )water_owner_detail ON water_owner_detail.application_id = water_consumers.apply_connection_id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(water_consumer_demands.amount) AS total_demand
                    FROM water_consumer_demands
                    JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        AND water_consumer_demands.demand_upto <= '$uptoDate'
                    GROUP BY water_consumer_demands.consumer_id    
                )demands ON demands.consumer_id = water_consumers.id
                LEFT JOIN (
                    SELECT water_consumer_demands.consumer_id,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <='$uptoDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                        ) AS current_collection,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(water_consumer_demands.amount) AS total_collection
                    FROM water_consumer_demands
                    JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id

                                #####################------------#################

                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN water_trans ON water_trans.id = prop_tran_dtls.tran_id 
                        AND water_trans.status in (1,2) AND water_trans.related_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND water_trans.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY prop_demands.related_id
                )collection ON collection.related_id = prop_properties.id
                LEFT JOIN ( 
                    SELECT prop_demands.related_id,
                    SUM(prop_demands.amount) AS total_prev_collection
                    FROM prop_demands
                    JOIN prop_properties ON prop_properties.id = prop_demands.related_id
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN water_trans ON water_trans.id = prop_tran_dtls.tran_id 
                        AND water_trans.status in (1,2) AND water_trans.related_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "
                        AND water_trans.tran_date<'$fromDate'
                    GROUP BY prop_demands.related_id
                )prev_collection ON prev_collection.related_id = prop_properties.id 
                JOIN ulb_ward_masters ON ulb_ward_masters.id = prop_properties.ward_mstr_id
                WHERE  prop_properties.ulb_id = $ulbId  
                    " . ($wardId ? " AND prop_properties.ward_mstr_id = $wardId" : "") . "           
            ";
            $select = "SELECT  prop_properties.id,
                            ulb_ward_masters.ward_name AS ward_no,
                            CONCAT('', prop_properties.holding_no, '') AS holding_no,
                            (
                                CASE WHEN prop_properties.new_holding_no='' OR prop_properties.new_holding_no IS NULL THEN 'N/A' 
                                ELSE prop_properties.new_holding_no END
                            ) AS new_holding_no,
                            prop_owner_detail.owner_name,
                            prop_owner_detail.mobile_no,
                    
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ) AS arrear_demand,
                            COALESCE(demands.current_demand, 0::numeric) AS current_demand,   
                            COALESCE(prev_collection.total_prev_collection, 0::numeric) AS previous_collection,
                            
                            COALESCE(collection.arrear_collection, 0::numeric) AS arrear_collection,
                            COALESCE(collection.current_collection, 0::numeric) AS current_collection,
                    
                            (COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) )AS old_due,
                    
                            (COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)) AS current_due,
                    
                            (
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.current_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            ) AS outstanding                                 
            ";
            $footerselect = "SELECT
                        COUNT(prop_properties.id)AS total_prop,
                        COUNT(DISTINCT(ulb_ward_masters.ward_name)) AS total_ward,
                        SUM(
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            )
                        ) AS outstanding_at_begin,
                        SUM(COALESCE(demands.currend_demand, 0::numeric)) AS current_demand,
                
                        SUM(COALESCE(prev_collection.total_prev_collection, 0::numeric)) AS prev_coll,
                        SUM(COALESCE(collection.arrear_collection, 0::numeric)) AS arrear_ollection,
                        SUM(COALESCE(collection.current_collection, 0::numeric)) AS current_collection,
                
                        SUM(
                            (
                                COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) 
                            )
                        )AS old_due,
                
                        SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric))) AS current_due,
                
                        SUM(
                            (
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.currend_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            )
                        ) AS outstanding               
            ";
            $data = DB::TABLE(DB::RAW("($select $from)AS prop"))->get();
            // $footer = DB::TABLE(DB::RAW("($footerselect $footerfrom)AS prop"))->get();
            $items = $data;
            $total = (collect(DB::SELECT("SELECT COUNT(*) AS total $footerfrom"))->first())->total ?? 0;
            $lastPage = ceil($total / $perPage);
            $list = [
                // "perPage" => $perPage,
                // "page" => $page,
                // "items" => $items,
                // "footer" => $footer,
                // "total" => $total,
                // "numberOfPages" => $numberOfPages,
                "current_page" => $page,
                "data" => $data,
                "total" => $total,
                "per_page" => $perPage,
                "last_page" => $lastPage - 1
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | Ward Wise Dcb 
        | Serial No : 03
        | Working
        | Not Verified
     */
    public function wardWiseDCB(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "connectionType" => "nullable|in:1,0",
                "propType" => "nullable|in:1,2,3"
                // "page" => "nullable|digits_between:1,9223372036854775807",
                // "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        $request->request->add(["metaData" => ["", 1.1, "", $request->getMethod(), $request->deviceId,]]);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        try {
            $refUser        = authUser($request);
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $connectionType = null;
            $propType = null;
            $refPropType = Config::get('waterConstaint.PROPERTY_TYPE');

            $fiYear = getFY();
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear . "-04-01";
            $uptoDate = $toYear . "-03-31";
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->connectionType != '') {
                $connectionType = $request->connectionType;
            }
            if ($request->propType) {
                switch ($request->propType) {
                    case ('1'):
                        $propType = $refPropType['Residential'];
                        break;
                    case ('2'):
                        $propType = $refPropType['Commercial'];
                        break;
                    case ('3'):
                        $propType = $refPropType['Government'];
                        break;
                }
            }

            # From Querry
            //" . ($connectionType ? " AND water_consumer_demands.connection_type IN (" . implode(',', $connectionType) . ")" : "") . "
            $from = "
                FROM ulb_ward_masters 
                LEFT JOIN(
                        SELECT water_consumers.ward_mstr_id,
                        COUNT
                        (DISTINCT (
                            CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.consumer_id
                            END)
                        ) as current_demand_consumer,
                        SUM(
                                CASE WHEN  water_consumer_demands.demand_from >= '$fromDate' 
                                        AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.amount
                                ELSE 0
                                    END
                        ) AS current_demand,
                        COUNT
                        (DISTINCT (
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate'  then water_consumer_demands.consumer_id
                            END)
                        ) as arrear_demand_consumer,
                        SUM(
                            CASE WHEN water_consumer_demands.demand_from < '$fromDate'  then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                        SUM(water_consumer_demands.amount) AS total_demand
                FROM water_consumer_demands
                JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                JOIN (
                    SELECT water_consumer_meters.* 
                    FROM water_consumer_meters
                        JOIN(
                            select max(id)as max_id
                            from water_consumer_meters
                            where status = 1
                            group by consumer_id
                        )maxdata on maxdata.max_id = water_consumer_meters.id
                    )water_consumer_meters on water_consumer_meters.consumer_id = water_consumers.id

                WHERE water_consumer_demands.status = true
                    AND water_consumer_demands.ulb_id = $ulbId
                    " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                    " . ($connectionType ? " AND water_consumer_meters.meter_status = $connectionType " : "") . "
                    " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                    AND water_consumer_demands.demand_upto <='$uptoDate'
                GROUP BY water_consumers.ward_mstr_id
                )demands ON demands.ward_mstr_id = ulb_ward_masters.id
                LEFT JOIN (
                    SELECT water_consumers.ward_mstr_id,
                    COUNT
                        (DISTINCT (
                            CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.consumer_id
                            END)
                        ) as current_collection_consumer,

                        COUNT(DISTINCT(water_consumers.id)) AS collection_from_no_of_consumer,
                        SUM(
                                CASE WHEN water_consumer_demands.demand_from >= '$fromDate' 
                                AND water_consumer_demands.demand_upto <= '$uptoDate'  then water_consumer_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,

                        COUNT
                            (DISTINCT (
                                CASE WHEN water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.consumer_id
                                END)
                            ) as arrear_collection_consumer,

                        SUM(
                            CASE when water_consumer_demands.demand_from < '$fromDate' then water_consumer_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                            
                    SUM(water_consumer_demands.amount) AS total_collection
                    FROM water_consumer_demands
                    JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                    JOIN water_tran_details ON water_tran_details.demand_id = water_consumer_demands.id 
                        AND water_tran_details.demand_id is not null 
                    JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                        AND water_trans.status in (1,2) 
								AND water_trans.related_id is not null
								AND water_trans.tran_type = 'Demand Collection'
                    JOIN (
                        SELECT water_consumer_meters.* from water_consumer_meters
                            join(
                                select max(id)as max_id
                                from water_consumer_meters
                                where status = 1
                                group by consumer_id
                            )maxdata on maxdata.max_id = water_consumer_meters.id
                        )water_consumer_meters on water_consumer_meters.consumer_id = water_consumers.id            
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND water_consumers.ward_mstr_id = $wardId" : "") . "
                        " . ($connectionType ? " AND water_consumer_meters.meter_status = $connectionType " : "") . "
                        " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                        AND water_trans.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND water_consumer_demands.demand_from <='$fromDate'
                    GROUP BY (water_consumers.ward_mstr_id)
                )collection ON collection.ward_mstr_id = ulb_ward_masters.id
                LEFT JOIN ( 
                    SELECT water_consumers.ward_mstr_id, 
                        SUM(water_consumer_demands.amount) 
                                AS total_prev_collection
                                FROM water_consumer_demands
                        JOIN water_consumers ON water_consumers.id = water_consumer_demands.consumer_id
                        JOIN water_tran_details ON water_tran_details.demand_id = water_consumer_demands.id
                            AND water_tran_details.demand_id IS NOT NULL
                        JOIN water_trans ON water_trans.id = water_tran_details.tran_id 
                            AND water_trans.status in (1,2) 
                            AND water_trans.related_id IS NOT NULL
                            AND water_trans.tran_type = 'Demand Collection'
                        JOIN (
                            SELECT water_consumer_meters.* from water_consumer_meters
                                join(
                                    select max(id)as max_id
                                    from water_consumer_meters
                                    where status = 1
                                    group by consumer_id
                                )maxdata on maxdata.max_id = water_consumer_meters.id
                            )water_consumer_meters on water_consumer_meters.consumer_id = water_consumers.id    
                    WHERE water_consumer_demands.status = true 
                        AND water_consumer_demands.ulb_id = $ulbId
                        " . ($wardId ? " AND ulb_ward_masters.id = $wardId" : "") . "
                        " . ($connectionType ? " AND water_consumer_meters.meter_status = $connectionType " : "") . "
                        " . ($propType ? " AND water_consumers.property_type_id = $propType" : "") . "
                       AND water_trans.tran_date <'$fromDate'
                    GROUP BY water_consumers.ward_mstr_id  
                )prev_collection ON prev_collection.ward_mstr_id = ulb_ward_masters.id                 
                WHERE  ulb_ward_masters.ulb_id = $ulbId  
                    " . ($wardId ? " AND ulb_ward_masters.id = $wardId" : "") . "
                GROUP BY ulb_ward_masters.ward_name           
            ";

            # Select Querry
            $select = "SELECT ulb_ward_masters.ward_name AS ward_no, 
                            SUM(COALESCE(demands.current_demand_consumer, 0::numeric)) AS current_demand_consumer,   
                            SUM(COALESCE(demands.arrear_demand_consumer, 0::numeric)) AS arrear_demand_consumer,
                            SUM(COALESCE(collection.current_collection_consumer, 0::numeric)) AS current_collection_consumer,   
                            SUM(COALESCE(collection.arrear_collection_consumer, 0::numeric)) AS arrear_collection_consumer,
                            SUM(COALESCE(collection.collection_from_no_of_consumer, 0::numeric)) AS collection_from_consumer,
                            
                            round(SUM(((collection.arrear_collection_consumer ::numeric) / (case when demands.arrear_demand_consumer > 0 then demands.arrear_demand_consumer else 1 end))*100)) AS arrear_consumer_eff,
                            round(SUM(((collection.current_collection_consumer ::numeric) / (case when demands.current_demand_consumer > 0 then demands.current_demand_consumer else 1 end))*100)) AS current_consumer_eff,

                            round(SUM(COALESCE(
                                COALESCE(demands.current_demand_consumer, 0::numeric) 
                                - COALESCE(collection.collection_from_no_of_consumer, 0::numeric), 0::numeric
                            ))) AS balance_consumer,                       
                            round(SUM(COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ))) AS arrear_demand,
                    
                            round(SUM(COALESCE(prev_collection.total_prev_collection, 0::numeric))) AS previous_collection,
                            round(SUM(COALESCE(demands.current_demand, 0::numeric))) AS current_demand,
                            round(SUM(COALESCE(collection.arrear_collection, 0::numeric))) AS arrear_collection,
                            round(SUM(COALESCE(collection.current_collection, 0::numeric))) AS current_collection,
                    
                            round(SUM((COALESCE(
                                    COALESCE(demands.arrear_demand, 0::numeric) 
                                    - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                                ) 
                                - COALESCE(collection.arrear_collection, 0::numeric) 
                                )))AS old_due,
                    
                            round(SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(collection.current_collection, 0::numeric)))) AS current_due,

                            round(SUM((COALESCE(demands.current_demand_consumer, 0::numeric) - COALESCE(collection.current_collection_consumer, 0::numeric)))) AS current_balance_consumer,
                            round(SUM((COALESCE(demands.arrear_demand_consumer, 0::numeric) - COALESCE(collection.arrear_collection_consumer, 0::numeric)))) AS arrear_balance_consumer,

                            round(SUM(((collection.arrear_collection ::numeric) / (case when demands.arrear_demand > 0 then demands.arrear_demand else 1 end))*100)) AS arrear_eff,
                            round(SUM(((collection.current_collection ::numeric) / (case when demands.current_demand > 0 then demands.current_demand else 1 end))*100)) AS current_eff,

                            round(SUM((
                                COALESCE(
                                    COALESCE(demands.current_demand, 0::numeric) 
                                    + (
                                        COALESCE(demands.arrear_demand, 0::numeric) 
                                        - COALESCE(prev_collection.total_prev_collection, 0::numeric)
                                    ), 0::numeric
                                ) 
                                - COALESCE(
                                    COALESCE(collection.current_collection, 0::numeric) 
                                    + COALESCE(collection.arrear_collection, 0::numeric), 0::numeric
                                )
                            ))) AS outstanding                                 
            ";
            // dd($connectionType);
            # Data Structuring
            $dcb = DB::select($select . $from);
            $data['total_arrear_demand']                = round(collect($dcb)->sum('arrear_demand'), 0);
            $data['total_current_demand']               = round(collect($dcb)->sum('current_demand'), 0);
            $data['total_arrear_collection']            = round(collect($dcb)->sum('arrear_collection'), 0);
            $data['total_current_collection']           = round(collect($dcb)->sum('current_collection'), 0);
            $data['total_old_due']                      = round(collect($dcb)->sum('old_due'), 0);
            $data['total_current_due']                  = round(collect($dcb)->sum('current_due'), 0);
            $data['total_arrear_demand_consumer']       = round(collect($dcb)->sum('arrear_demand_consumer'), 0);
            $data['total_current_demand_consumer']      = round(collect($dcb)->sum('current_demand_consumer'), 0);
            $data['total_arrear_collection_consumer']   = round(collect($dcb)->sum('arrear_collection_consumer'), 0);
            $data['total_current_collection_consumer']  = round(collect($dcb)->sum('current_collection_consumer'), 0);
            $data['total_arrear_balance_consumer']      = round(collect($dcb)->sum('arrear_balance_consumer'));
            $data['total_current_balance_consumer']     = round(collect($dcb)->sum('current_balance_consumer'));
            $data['total_current_eff']                  = ($data['total_current_collection_consumer'] == 0) ? 0 : round(($data['total_current_collection_consumer'] / $data['total_current_demand']) * 100);
            $data['total_arrear_consumer_eff']          = ($data['total_arrear_demand_consumer'] == 0) ? 0 : round(($data['total_arrear_collection_consumer'] /  $data['total_arrear_demand_consumer']) * 100);
            $data['total_current_consumer_eff']         = ($data['total_current_demand_consumer'] == 0) ? 0 : round(($data['total_current_collection_consumer']) / ($data['total_current_demand_consumer']) * 100);
            $data['total_arrear_eff']                   = ($data['total_arrear_collection'] == 0) ? 0 : round(($data['total_arrear_collection']) / ($data['total_arrear_demand']) * 100);
            $data['total_eff']                          = round((($data['total_arrear_collection'] + $data['total_current_collection']) / ($data['total_arrear_demand'] + $data['total_current_demand'])) * 100);
            $data['dcb']                                = $dcb;

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }


    /**
     * | DCB Pie Chart
        | Serial No : 04
        | Working
     */
    public function dcbPieChart(Request $request)
    {
        try {
            $ulbId = $request->ulbId ?? authUser($request)->ulb_id;
            $currentDate = Carbon::now()->format('Y-m-d');
            $currentYear = Carbon::now()->year;
            $currentFyear = getFinancialYear($currentDate);
            $startOfCurrentYear = Carbon::createFromDate($currentYear, 4, 1);   // Start date of current financial year
            $startOfPreviousYear = $startOfCurrentYear->copy()->subYear();      // Start date of previous financial year
            $previousFinancialYear = getFinancialYear($startOfPreviousYear);
            $startOfprePreviousYear = $startOfCurrentYear->copy()->subYear()->subYear();
            $prePreviousFinancialYear = getFinancialYear($startOfprePreviousYear);


            # common function
            $refDate = $this->getFyearDate($currentFyear);
            $fromDate = $refDate['fromDate'];
            $uptoDate = $refDate['uptoDate'];

            # common function
            $refDate = $this->getFyearDate($previousFinancialYear);
            $previousFromDate = $refDate['fromDate'];
            $previousUptoDate = $refDate['uptoDate'];

            # common function
            $refDate = $this->getFyearDate($prePreviousFinancialYear);
            $prePreviousFromDate = $refDate['fromDate'];
            $prePreviousUptoDate = $refDate['uptoDate'];


            return $sql1 = $this->demandByFyear($currentFyear, $fromDate, $uptoDate, $ulbId);
            $sql2 = $this->demandByFyear($previousFinancialYear, $previousFromDate, $previousUptoDate, $ulbId);
            $sql3 = $this->demandByFyear($prePreviousFinancialYear, $prePreviousFromDate, $prePreviousUptoDate, $ulbId);

            $currentYearDcb     = DB::select($sql1);
            $previousYearDcb    = DB::select($sql2);
            $prePreviousYearDcb = DB::select($sql3);

            $data = [
                collect($currentYearDcb)->first(),
                collect($previousYearDcb)->first(),
                collect($prePreviousYearDcb)->first()
            ];
            return responseMsgs(true, "", remove_null($data), "", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", "01", ".ms", "POST", $request->deviceId);
        }
    }

    /**
     * | for collecting finantial year's starting date and end date
     * | common functon
     * | @param fyear
        | Serial No : 04.01
        | Working
     */
    public function getFyearDate($fyear)
    {
        list($fromYear, $toYear) = explode("-", $fyear);
        if ($toYear - $fromYear != 1) {
            throw new Exception("Enter Valide Financial Year");
        }
        $fromDate = $fromYear . "-04-01";
        $uptoDate = $toYear . "-03-31";
        return [
            "fromDate" => $fromDate,
            "uptoDate" => $uptoDate
        ];
    }



    /**
     * | Water collection Report for Consumer and Connections
     */
    public function WaterCollection(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fromDate"      => "required|date|date_format:Y-m-d",
                "uptoDate"      => "required|date|date_format:Y-m-d",
                "wardId"        => "nullable|digits_between:1,9223372036854775807",
                "userId"        => "nullable|digits_between:1,9223372036854775807",
                "paymentMode"   => "nullable",
                "page"          => "nullable|digits_between:1,9223372036854775807",
                "perPage"       => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        $consumerCollection = null;
        $applicationCollection = null;
        $consumerData = 0;
        $consumerTotal = 0;
        $applicationTotal = 0;
        $applicationData = 0;
        $collectionTypes = $request->collectionType;
        $perPage = $request->perPage ?? 5;

        if ($request->user == 'tc') {
            $userId = authUser($request)->id;
            $request->merge(["userId" => $userId]);
        }

        foreach ($collectionTypes as $collectionType) {
            if ($collectionType == 'consumer') {
                $consumerCollection = $this->consumerReport($request);
                $consumerTotal = $consumerCollection->original['data']['totalAmount'];
                $consumerData = $consumerCollection->original['data']['total'];
                $consumerCollection = $consumerCollection->original['data']['data'];
            }

            if ($collectionType == 'connection') {
                $applicationCollection = $this->applicationCollection($request);
                $applicationTotal = $applicationCollection->original['data']['totalAmount'];
                $applicationData = $applicationCollection->original['data']['total'];
                $applicationCollection = $applicationCollection->original['data']['data'];
            }
        }
        $currentPage = $request->page ?? 1;
        $details = collect($consumerCollection)->merge($applicationCollection);

        $a = round($consumerData / $perPage);
        $b = round($applicationData / $perPage);
        $data['current_page'] = $currentPage;
        $data['total'] = $consumerData + $applicationData;
        $data['totalAmt'] = round($consumerTotal + $applicationTotal);
        $data['last_page'] = max($a, $b);
        $data['data'] = $details;

        return responseMsgs(true, "", $data, "", "", "", "post", $request->deviceId);
    }

    /**
     * | Consumer Collection Report 
     */
    public function consumerReport(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fromDate"      => "required|date|date_format:Y-m-d",
                "uptoDate"      => "required|date|date_format:Y-m-d",
                "wardId"        => "nullable|digits_between:1,9223372036854775807",
                "userId"        => "nullable|digits_between:1,9223372036854775807",
                "paymentMode"   => "nullable",
                "page"          => "nullable|digits_between:1,9223372036854775807",
                "perPage"       => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        $metaData = collect($request->metaData)->all();
        $request->request->add(["metaData" => ["pr1.1", 1.1, null, $request->getMethod(), null,]]);
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        try {
            $refUser        = authUser($request);
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");

            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }

            // DB::enableQueryLog();
            $data = WaterTran::SELECT(
                DB::raw("
                            water_trans.id AS tran_id,
                            water_consumers.id AS ref_consumer_id,
                            ulb_ward_masters.ward_name AS ward_no,
                             'consumer' as type,
                            water_consumers.consumer_no,
                            CONCAT('', water_consumers.holding_no, '') AS holding_no,
                            water_owner_detail.owner_name,
                            water_owner_detail.mobile_no,
                            water_trans.tran_date,
                            water_trans.payment_mode AS transaction_mode,
                            water_trans.amount,users.user_name as emp_name,users.id as user_id,
                            water_trans.tran_no,
                            water_cheque_dtls.cheque_no,
                            water_cheque_dtls.bank_name,
                            water_cheque_dtls.branch_name
                "),
            )
                ->JOIN("water_consumers", "water_consumers.id", "water_trans.related_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(applicant_name, ', ') AS owner_name, 
                                STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                                water_consumer_owners.consumer_id 
                        FROM water_consumer_owners 
                        JOIN water_trans on water_trans.related_id = water_consumer_owners.consumer_id 
                        WHERE water_trans.related_id IS NOT NULL AND water_trans.status in (1, 2) 
                        AND water_trans.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " . ($userId ? " AND water_trans.emp_dtl_id = $userId " : "")
                        . ($paymentMode ? " AND upper(water_trans.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND water_trans.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY water_consumer_owners.consumer_id
                        ) AS water_owner_detail
                        "),
                    function ($join) {
                        $join->on("water_owner_detail.consumer_id", "=", "water_trans.related_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "water_consumers.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "water_trans.emp_dtl_id")
                ->LEFTJOIN("water_cheque_dtls", "water_cheque_dtls.transaction_id", "water_trans.id")
                ->WHERE("water_trans.tran_type", "Demand Collection")
                ->WHERENOTNULL("water_trans.related_id")
                ->WHEREIN("water_trans.status", [1, 2])
                ->WHEREBETWEEN("water_trans.tran_date", [$fromDate, $uptoDate]);
            if ($wardId) {
                $data = $data->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $data = $data->where("water_trans.emp_dtl_id", $userId);
            }
            if ($paymentMode) {
                $data = $data->where(DB::raw("upper(water_trans.payment_mode)"), $paymentMode);
            }
            if ($ulbId) {
                $data = $data->where("water_trans.ulb_id", $ulbId);
            }
            $paginator = collect();

            $data2 = $data;
            $totalHolding = $data2->count("water_consumers.id");
            $totalAmount = $data2->sum("water_trans.amount");
            $perPage = $request->perPage ? $request->perPage : 5;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $paginator = $data->paginate($perPage);

            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                "current_page"  => $paginator->currentPage(),
                "last_page"     => $paginator->lastPage(),
                "totalHolding"  => $totalHolding,
                "totalAmount"   => $totalAmount,
                "data"          => $paginator->items(),
                "total"         => $paginator->total(),
                // "numberOfPages" => $numberOfPages
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all());
        }
    }


    /**
     * | Connection Collection Report
     */
    public function connectionCollection(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fromDate"      => "required|date|date_format:Y-m-d",
                "uptoDate"      => "required|date|date_format:Y-m-d",
                "wardId"        => "nullable|digits_between:1,9223372036854775807",
                "userId"        => "nullable|digits_between:1,9223372036854775807",
                "paymentMode"   => "nullable",
                "page"          => "nullable|digits_between:1,9223372036854775807",
                "perPage"       => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails())
            return validationError($validated);

        $request->request->add(["metaData" => ["pr2.1", 1.1, null, $request->getMethod(), null,]]);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        try {
            $refUser        = authUser($request);
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $refTransType = Config::get("waterConstaint.PAYMENT_FOR");

            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }

            DB::enableQueryLog();
            $worflowIds = DB::table('water_applications')->select(DB::raw("trim(concat(workflow_id::text,','),',') workflow_id"))
                ->groupBy("workflow_id")
                ->first();
            $activConnections = WaterTran::select(
                DB::raw("
                            water_trans.id AS tran_id,
                            ulb_ward_masters.ward_name AS ward_no,
                            water_applications.id,
                            water_trans.tran_date,                             
                            owner_detail.owner_name,
                            owner_detail.mobile_no,
                            water_trans.payment_mode AS transaction_mode,
                            water_trans.amount,
                            users.user_name as emp_name,
                            users.id as user_id,
                            water_trans.tran_no,
                            water_cheque_dtls.cheque_no,
                            water_cheque_dtls.bank_name,
                            water_cheque_dtls.branch_name
                "),
            )
                ->JOIN("water_applications", "water_applications.id", "water_trans.related_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(applicant_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            water_applicants.application_id 
                        FROM water_applicants 
                        JOIN water_trans on water_trans.related_id = water_applicants.application_id 
                        WHERE water_trans.related_id IS NOT NULL 
                        AND water_trans.status in (1, 2) 
                        AND water_trans.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND water_trans.emp_dtl_id = $userId " : "")
                        . ($paymentMode ? " AND upper(water_trans.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND water_trans.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY water_applicants.application_id 
                        ) AS owner_detail
                        "),
                    function ($join) {
                        $join->on("owner_detail.application_id", "=", "water_trans.related_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "water_applications.ward_id")
                ->LEFTJOIN("users", "users.id", "water_trans.emp_dtl_id")
                ->LEFTJOIN("water_cheque_dtls", "water_cheque_dtls.transaction_id", "water_trans.id")

                ->JOIN("wf_roleusermaps", "wf_roleusermaps.user_id", "users.id")
                ->JOIN("wf_workflowrolemaps", "wf_workflowrolemaps.wf_role_id", "wf_roleusermaps.wf_role_id")
                ->WHEREIN("wf_workflowrolemaps.workflow_id", explode(",", collect($worflowIds)->implode("workflow_id", ",")))
                ->WHERENULL("water_trans.citizen_id")

                ->WHERENOTNULL("water_trans.related_id")
                ->WHEREIN("water_trans.status", [1, 2])
                ->WHERE("water_trans.tran_type", "<>", $refTransType['1'])
                ->WHEREBETWEEN("water_trans.tran_date", [$fromDate, $uptoDate]);

            $rejectedConnections = WaterTran::select(
                DB::raw("
                            water_trans.id AS tran_id,
                            ulb_ward_masters.ward_name AS ward_no,
                            water_rejection_application_details.id,
                            water_trans.tran_date,
                            owner_detail.owner_name,
                            owner_detail.mobile_no,
                            water_trans.payment_mode AS transaction_mode,
                            water_trans.amount,
                            users.user_name as emp_name,
                            users.id as user_id,
                            water_trans.tran_no,
                            water_cheque_dtls.cheque_no,
                            water_cheque_dtls.bank_name,
                            water_cheque_dtls.branch_name
                "),
            )
                ->JOIN("water_rejection_application_details", "water_rejection_application_details.id", "water_trans.related_id")
                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(applicant_name, ', ') AS owner_name, 
                            STRING_AGG(mobile_no::TEXT, ', ') AS mobile_no, 
                            water_rejection_applicants.application_id 
                        FROM water_rejection_applicants 
                        JOIN water_trans on water_trans.related_id = water_rejection_applicants.application_id 
                        WHERE water_trans.related_id IS NOT NULL 
                        AND water_trans.status in (1, 2) 
                        AND water_trans.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND water_trans.emp_dtl_id = $userId " : "")
                        . ($paymentMode ? " AND upper(water_trans.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND water_trans.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY water_rejection_applicants.application_id 
                        ) AS owner_detail
                        "),
                    function ($join) {
                        $join->on("owner_detail.application_id", "=", "water_trans.related_id");
                    }
                )
                ->JOIN("ulb_ward_masters", "ulb_ward_masters.id", "water_rejection_application_details.ward_id")
                ->LEFTJOIN("users", "users.id", "water_trans.emp_dtl_id")
                ->LEFTJOIN("water_cheque_dtls", "water_cheque_dtls.transaction_id", "water_trans.id")

                ->JOIN("wf_roleusermaps", "wf_roleusermaps.user_id", "users.id")
                ->JOIN("wf_workflowrolemaps", "wf_workflowrolemaps.wf_role_id", "wf_roleusermaps.wf_role_id")
                ->WHEREIN("wf_workflowrolemaps.workflow_id", explode(",", collect($worflowIds)->implode("workflow_id", ",")))
                ->WHERENULL("water_trans.citizen_id")

                ->WHERENOTNULL("water_trans.related_id")
                ->WHEREIN("water_trans.status", [1, 2])
                ->WHERE("water_trans.tran_type", "<>", $refTransType['1'])
                ->WHEREBETWEEN("water_trans.tran_date", [$fromDate, $uptoDate]);


            if ($wardId) {
                $activConnections = $activConnections->where("ulb_ward_masters.id", $wardId);
                $rejectedConnections = $rejectedConnections->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $activConnections = $activConnections->where("water_trans.emp_dtl_id", $userId);
                $rejectedConnections = $rejectedConnections->where("water_trans.emp_dtl_id", $userId);
            }
            if ($paymentMode) {
                $activConnections = $activConnections->where(DB::raw("water_trans.payment_mode"), $paymentMode);
                $rejectedConnections = $rejectedConnections->where(DB::raw("water_trans.payment_mode"), $paymentMode);
            }
            if ($ulbId) {
                $activConnections = $activConnections->where("water_trans.ulb_id", $ulbId);
                $rejectedConnections = $rejectedConnections->where("water_trans.ulb_id", $ulbId);
            }

            $data = $activConnections->union($rejectedConnections);
            // dd($data->ORDERBY("tran_id")->get()->implode("tran_id",","));
            $data2 = $data;
            // $totalApplications = $data2->count("id");
            $totalAmount = $data2->sum("amount");
            $perPage = $request->perPage ? $request->perPage : 5;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $paginator = $data->paginate($perPage);
            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                // "perPage" => $perPage,
                // "page" => $page,
                // "totalApplications" => $totalSaf,
                // "totalAmount" => $totalAmount,
                // "items" => $items,
                // "total" => $total,
                // "numberOfPages" => $numberOfPages

                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * dcb report
      |working
     *
     */
    public function WaterdcbReport(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "wardId" => "nullable|int",
                "zoneId" => "nullable|int"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $now                        = Carbon::now();
            $mWaterConsumerDemand       = new WaterConsumerDemand();
            $currentDate                = $now->format('Y-m-d');
            $wardId = null;
            $userId = null;
            $zoneId = null;
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            if ($request->zoneId) {
                $zoneId = $request->zoneId;
            }

            $currentYear                = collect(explode('-', $request->fiYear))->first() ?? $now->year;
            $currentFyear               = $request->fiYear ?? getFinancialYear($currentDate);
            $startOfCurrentYear         = Carbon::createFromDate($currentYear, 4, 1);           // Start date of current financial year
            $startOfPreviousYear        = $startOfCurrentYear->copy()->subYear();               // Start date of previous financial year
            $previousFinancialYear      = getFinancialYear($startOfPreviousYear);

            #get financial  year 
            $refDate = $this->getFyearDate($currentFyear);
            $fromDate = $refDate['fromDate'];
            $uptoDate = $refDate['uptoDate'];

            #common function 
            $refDate = $this->getFyearDate($previousFinancialYear);
            $previousFromDate = $refDate['fromDate'];
            $previousUptoDate = $refDate['uptoDate'];
            $dataraw =  "SELECT *,
            (arrear_balance + current_balance) AS total_balance
        FROM (
            SELECT 
                SUM(CASE WHEN demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END) AS current_demands,
                SUM(CASE WHEN paid_status = 0 AND demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END) AS current_year_balance_amount,
                SUM(CASE WHEN paid_status = 1 AND demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END) AS current_year_collection_amount,
                SUM(CASE WHEN paid_status = 1 AND demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END) AS previous_year_collection_amount,
                SUM(CASE WHEN demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END) AS previous_year_demands,
                SUM(CASE WHEN paid_status = 0 AND demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END) AS previous_year_balance_amount,
                (SUM(CASE WHEN demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END) - SUM(CASE WHEN paid_status = 1 AND demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END)) AS arrear_balance,
                (SUM(CASE WHEN demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END) - SUM(CASE WHEN paid_status = 1 AND demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END)) AS current_balance,
                (SUM(CASE WHEN demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END) + SUM(CASE WHEN water_consumer_demands.STATUS = TRUE AND demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END)) AS total_demand,
                (SUM(CASE WHEN paid_status = 1 AND demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date THEN amount ELSE 0 END) + SUM(CASE WHEN paid_status = 1 AND demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date THEN amount ELSE 0 END)) AS total_collection,
                ulb_ward_masters.ward_name
            FROM water_consumer_demands  
            LEFT JOIN water_second_consumers ON water_consumer_demands.consumer_id = water_second_consumers.id
            LEFT JOIN ulb_ward_masters ON water_second_consumers.ward_mstr_id = ulb_ward_masters.id
            WHERE 
          
                (demand_from >= '$fromDate'::date AND demand_upto <= '$uptoDate'::date AND water_consumer_demands.status = TRUE)
                OR (demand_from >= '$previousFromDate'::date AND demand_upto <= '$previousUptoDate'::date AND water_consumer_demands.STATUS = TRUE)
                
                "
                . ($wardId ? " AND water_second_consumers.ward_mstr_id = $wardId " : "")
                . ($zoneId ? " AND water_second_consumers.zone_mstr_id = $zoneId " : "")
                . "
            "

                . "
                GROUP BY ulb_ward_masters.ward_name
        ) AS subquery
        ";


            $results = DB::connection('pgsql_water')->select($dataraw);
            $resultObject = (object) $results[0];
            return responseMsgs(true, "water demand report", remove_null($resultObject), "", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }


    /**
     * | Get details of water according to applicationNo , consumerNo , etc
     * | maping of water with property  
        | Serial No : 0
        | Under con
     */
    public function getWaterDetailsByParams(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'parmeter' => 'required|',
                'filterBy' => 'required',
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterConsumer = new WaterConsumer();
            $parameter = $request->parmeter;
            $filterBy = $request->filterBy;

            switch ($filterBy) {
                case 'consumerNo':                                      // Static
                    $this->getConsumerRelatedDetails();
                    break;
                case 'applicationNo':                                   // Static
                    $this->getApplicationRelatedDetails();
                    break;
            }
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), [], "", "01", responseTime(), $request->getMethod(), $request->deviceId);
        }
    }

    /**
     * | Get consumer details by consumer id and related property details
        | Serial No :
        | Under Con
     */
    public function getConsumerRelatedDetails()
    {
    }

    /**
     * |get transaction lis by year 
       |under wo
     */
    public function getTransactionDetail(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fiYear"    => "nullable|regex:/^\d{4}-\d{4}$/",
                "wardId"    => "nullable|int",
                "zoneId"    => "nullable|int",
                "dateFrom"  => "nullable|date",
                "dateUpto"  => "nullable|date"

            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterTrans            = new WaterTran();
            $dateFrom =  $dateUpto  = Carbon::now()->format('Y-m-d');
            $fiYear = $wardId = $zoneId = null;
            if ($request->dateFrom) {
                $dateFrom = $request->dateFrom;
            }
            if ($request->dateUpto) {
                $dateUpto = $request->dateUpto;
            }
            if ($request->fiYear) {
                $fiYear = $request->fiYear;
                $refDate        = $this->getFyearDate($fiYear);
                $dateFrom       = $refDate['fromDate'];
                $dateUpto       = $refDate['uptoDate'];
            }
            if ($request->wardId) {
                $wardId =  $request->wardId;
            }
            if ($request->zoneId) {
                $zoneId =  $request->zoneId;
            }

            $dataraw = "SELECT
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Cash'  THEN water_trans.id ELSE NULL END
         ) AS waterCash,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Cheque'  THEN water_trans.id ELSE NULL END
         ) AS waterCheque,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Online'  THEN water_trans.id ELSE NULL END
         ) AS waterOnline,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'DD'  THEN water_trans.id ELSE NULL END
         ) AS waterDd,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Neft'  THEN water_trans.id ELSE NULL END
         ) AS waterNeft,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'RTGS'  THEN water_trans.id ELSE NULL END
         ) AS waterRtgs,
         COUNT(
            CASE WHEN water_trans.status=1 THEN water_trans.id ELSE NULL END
         ) AS NetTotalTransaction,
         
         
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Cash' AND  water_trans.user_type = 'TC'  THEN water_trans.id ELSE NULL END
         ) AS TcCashCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Cheque'AND  water_trans.user_type = 'TC'  THEN water_trans.id ELSE NULL END
         ) AS TcChequeCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Online' AND  water_trans.user_type = 'TC'  THEN water_trans.id ELSE NULL END
         ) AS TcOnlineCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'DD' AND  water_trans.user_type = 'TC'  THEN water_trans.id ELSE NULL END
         ) AS TcDdCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Neft' AND  water_trans.user_type = 'TC'  THEN water_trans.id ELSE NULL END
         ) AS TcNeftCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'RTGS' AND  water_trans.user_type = 'TC'  THEN water_trans.id ELSE NULL END
         ) AS TcRtgsCount,
         
         
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Cash' AND  water_trans.user_type = 'JSK'  THEN water_trans.id ELSE NULL END
         ) AS JskCashCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Cheque'AND  water_trans.user_type = 'JSK'  THEN water_trans.id ELSE NULL END
         ) AS JskChequeCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Online' AND  water_trans.user_type = 'JSK'  THEN water_trans.id ELSE NULL END
         ) AS JskOnlineCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'DD' AND  water_trans.user_type = 'JSK'  THEN water_trans.id ELSE NULL END
         ) AS JskDdCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'Neft' AND  water_trans.user_type = 'JSK'  THEN water_trans.id ELSE NULL END
         ) AS JskNeftCount,
            COUNT(
             CASE WHEN water_trans.payment_mode = 'RTGS' AND  water_trans.user_type = 'JSK'  THEN water_trans.id ELSE NULL END
         ) AS JskRtgsCount,
         
         -- Sum of amount for diff payment mode 
         SUM(CASE WHEN water_trans.payment_mode = 'Cash' THEN COALESCE(amount,0) ELSE 0 END) AS CashTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Cheque' THEN COALESCE(amount,0) ELSE 0 END) AS ChequeTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'DD' THEN COALESCE(amount,0) ELSE 0 END) AS DdTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Online' THEN COALESCE(amount,0) ELSE 0 END ) AS OnlineTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Neft' THEN COALESCE(amount,0) ELSE 0 END ) AS NeftTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'RTGS' THEN COALESCE(amount,0) ELSE 0 END ) AS RtgsTotalAmount,
         SUM(amount) AS TotalPaymentModeAmount,
         -- Sum of amount of TC for diff payament mode 
         SUM(CASE WHEN water_trans.payment_mode = 'Cash' AND   water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END) AS TcCasTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Cheque' AND  water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END) AS TcChequeTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'DD' AND  water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END) AS TcDdTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Online' AND  water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END ) AS TcOnlineTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Neft' AND  water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END ) AS TcNeftTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'RTGS' AND  water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END ) AS TcRtgsTotalAmount,
         SUM(CASE WHEN  water_trans.user_type = 'TC' THEN COALESCE(amount,0) ELSE 0 END) AS tc_total_amount,
           -- Sum of amount of JSK for diff payament mode 
         SUM(CASE WHEN water_trans.payment_mode = 'Cash' AND  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END) AS JskCashTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Cheque' AND  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END) AS JskChequeTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'DD' AND  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END) AS JskDdTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Online' AND  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END ) AS JskOnlineTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'Neft' AND  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END ) AS JskNeftTotalAmount,
         SUM(CASE WHEN water_trans.payment_mode = 'RTGS' AND  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END ) AS JskRtgsTotalAmount,
         SUM(CASE WHEN  water_trans.user_type = 'JSK' THEN COALESCE(amount,0) ELSE 0 END) AS JskTotalAmount
        
        FROM water_trans
        LEFT JOIN water_second_consumers on water_trans.related_id = water_second_consumers.id
        LEFT JOIN zone_masters ON zone_masters.id = water_second_consumers.zone_mstr_id
        WHERE water_trans.payment_mode IN ('Cash', 'Cheque', 'DD', 'Neft', 'RTGS', 'Online')
            AND water_trans.status = 1
            AND water_trans.tran_date BETWEEN '$dateFrom' AND '$dateUpto'
            AND water_trans.tran_type = 'Demand Collection'
            "
                . ($wardId ? " AND water_second_consumers.ward_mstr_id = $wardId " : "")
                . ($zoneId ? " AND water_second_consumers.zone_mstr_id = $zoneId " : "")
                . "
         ";
            $results = collect(collect(DB::connection('pgsql_water')->select($dataraw))->first())->map(function ($val) {
                return $val ? $val : 0;
            });
            return responseMsgs(true, "water Dcb report", remove_null($results), "", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }
    /**
     * tc visit report 
     * dateWise
     * 
     */
    public function tCvisitReport(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'filterBy'  => 'required',
                'parameter' => 'required',
                'pages'     => 'nullable',
            ]
        );

        if ($validated->fails()) {
            return validationError($validated);
        }

        try {
            $mWaterConsumerDemand    = new WaterConsumerDemand();
            $key            = $request->filterBy;
            $paramenter     = $request->parameter;
            $pages          = $request->pages ?? 10;
            $string         = preg_replace("/([A-Z])/", "_$1", $key);
            $refstring      = strtolower($string);
            switch ($key) {
                case ("generationDate"):                                                                        // Static
                    $waterReturnDetails = $mWaterConsumerDemand->getDetailsOfTc($refstring, $paramenter)->paginate($pages);
                    $checkVal = collect($waterReturnDetails)->last();
                    if (!$checkVal || $checkVal == 0)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
                case ('userName'):
                    $waterReturnDetails = $mWaterConsumerDemand->getTcDetails($refstring, $paramenter)->paginate($pages);
                    return $waterReturnDetails;
                    $checkVal = collect($waterReturnDetails)->last();
                    if (!$checkVal || $checkVal == 0)
                        throw new Exception("Data according to " . $key . " not Found!");
                    break;
            }

            $returnData["netcollectionSummary"] = [];

            return responseMsgs(true, "tc visit report", remove_null($waterReturnDetails), "", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }
    /**
     * total consumer type report
     Meter/Non-Meter
     */
    public function totalConsumerType(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                'zoneId'   => 'nullable',
                'wardId'   => 'nullable',
                'pages'    => 'nullable',
            ]
        );

        if ($validated->fails()) {
            return validationError($validated);
        }
        try {
            $mWaterSecondConsumer = new WaterSecondConsumer();
            $wardId  = $request->wardId;
            $zoneId    = $request->zone;
            return $getConsumer = $mWaterSecondConsumer->totalConsumerType($wardId, $zoneId)->get();



            // return responseMsgs(true, "total consumer type", remove_null($waterReturnDetails), "", "", "", 'POST', "");
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $e->getFile(), "", "01", "ms", "POST", "");
        }
    }

    public function WardList(Request $request)
    {
        $request->request->add(["metaData" => ["tr13.1", 1.1, null, $request->getMethod(), null,]]);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            if ($request->ulbId) {
                $ulbId  =   $request->ulbId;
            }
            $wardList = UlbWardMaster::select(DB::raw("min(id) as id ,ward_name,ward_name as ward_no"))
                ->WHERE("ulb_id", $ulbId)
                ->GROUPBY("ward_name")
                ->ORDERBY("ward_name")
                ->GET();

            return responseMsgs(true, "", $wardList, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    /**
     * | water Collection
     */
    public function WaterCollectionReport(colllectionReport $request)
    {
        $request->merge(["metaData" => ["pr1.1", 1.1, null, $request->getMethod(), null,]]);
        $metaData = collect($request->metaData)->all();

        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        // return $request->all();
        try {

            $refUser        = authUser($request);
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $zoneId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            if ($request->userId) {
                $userId = $request->userId;
            }

            # In Case of any logged in TC User
            if ($refUser->user_type == "TC") {
                $userId = $refUser->id;
            }

            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->zoneId) {
                $zoneId = $request->zoneId;
            }

            // DB::enableQueryLog();
            $data = waterTran::SELECT(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            water_second_consumers.id,
                            'water' as type,
                            water_second_consumers.saf_no,
                            water_second_consumers.user_type,
                            water_trans.id AS tran_id,
                            water_second_consumers.property_no,
                            water_second_consumers.address,
                            water_consumer_owners.applicant_name,
                            water_consumer_owners.mobile_no,
                            water_trans.payment_mode AS transaction_mode,
                            water_trans.amount,
                            water_trans.tran_date,
                            users.name as name,
                            users.user_name as emp_name,
                            users.id as user_id,
                            users.mobile as tc_mobile,
                            water_trans.tran_no,
                            water_cheque_dtls.cheque_no,
                            water_cheque_dtls.bank_name,
                            water_cheque_dtls.branch_name,
                            zone_masters.zone_name
                            
                "),
            )
                ->leftJOIN("water_second_consumers", "water_second_consumers.id", "water_trans.related_id")
                ->leftJoin("water_consumer_owners", "water_consumer_owners.consumer_id", "=", "water_second_consumers.id")
                ->leftJoin('zone_masters', 'zone_masters.id', '=', 'water_second_consumers.zone_mstr_id')

                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(applicant_name, ', ') AS owner_name, STRING_AGG(water_consumer_owners.mobile_no::TEXT, ', ') AS mobile_no, water_consumer_owners.consumer_id 
                            FROM water_second_consumers 
                        JOIN water_trans  on water_trans.related_id = water_second_consumers.id
                        JOIN water_consumer_owners on water_consumer_owners.consumer_id = water_second_consumers.id
                        WHERE water_trans.related_id IS NOT NULL AND water_trans.status in (1, 2) 
                     
                        AND water_trans.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " .
                        ($userId ? " AND water_trans.emp_dtl_id = $userId " : "")
                        . ($paymentMode ? " AND upper(water_trans.payment_mode) = upper('$paymentMode') " : "")
                        . ($ulbId ? " AND water_trans.ulb_id = $ulbId" : "")
                        . "
                        GROUP BY water_consumer_owners.consumer_id
                        ) AS water_owner_details
                        "),
                    function ($join) {
                        $join->on("water_owner_details.consumer_id", "=", "water_trans.related_id");
                    }

                )
                ->LEFTJOIN("ulb_ward_masters", "ulb_ward_masters.id", "water_second_consumers.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "water_trans.emp_dtl_id")
                ->LEFTJOIN("water_cheque_dtls", "water_cheque_dtls.transaction_id", "water_trans.id")
                ->WHERENOTNULL("water_trans.related_id")
                ->WHEREIN("water_trans.status", [1, 2])
                ->WHERE('tran_type', "=", "Demand Collection")

                ->WHEREBETWEEN("water_trans.tran_date", [$fromDate, $uptoDate]);
            if ($wardId) {
                $data = $data->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $data = $data->where("water_trans.emp_dtl_id", $userId);
            }
            if ($paymentMode) {
                $data = $data->where(DB::raw("upper(water_trans.payment_mode)"), $paymentMode);
            }
            if ($ulbId) {
                $data = $data->where("water_trans.ulb_id", $ulbId);
            }
            if ($zoneId) {
                $data = $data->where("water_second_consumers.zone_mstr_id", $zoneId);
            }
            $paginator = collect();

            $data2 = $data;
            $totalConsumers = $data2->count("water_second_consumers.id");
            $totalAmount = $data2->sum("water_trans.amount");
            $perPage = $request->perPage ? $request->perPage : 5;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            if ($request->all) {
                $data = $data->get();
                $mode = collect($data)->unique("transaction_mode")->pluck("transaction_mode");
                $totalFAmount = collect($data)->unique("tran_id")->sum("amount");
                $totalFCount = collect($data)->unique("tran_id")->count("tran_id");
                $footer = $mode->map(function ($val) use ($data) {
                    $count = $data->where("transaction_mode", $val)->unique("tran_id")->count("tran_id");
                    $amount = $data->where("transaction_mode", $val)->unique("tran_id")->sum("amount");
                    return ['mode' => $val, "count" => $count, "amount" => $amount];
                });
                $list = [
                    "data" => $data,

                ];
                $tcName = collect($data)->first()->emp_name ?? "";
                $tcMobile = collect($data)->first()->tc_mobile ?? "";
                if ($request->footer) {
                    $list["tcName"] = $tcName;
                    $list["tcMobile"] = $tcMobile;
                    $list["footer"] = $footer;
                    $list["totalCount"] = $totalFCount;
                    $list["totalAmount"] = $totalFAmount;
                }
                return responseMsgs(true, "", remove_null($list), $apiId, $version, $queryRunTime, $action, $deviceId);
            }

            $paginator = $data->paginate($perPage);

            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalHolding" => $totalConsumers,
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
                // "numberOfPages" => $numberOfPages
            ];
            $queryRunTime = (collect(DB::connection('pgsql_water'))->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }

    # over all tc collection report 
    public function userWiseCollectionSummary(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fromDate" => "nullable|date|date_format:Y-m-d",
                "uptoDate" => "nullable|date|date_format:Y-m-d|after_or_equal:" . $request->fromDate,
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "zoneId" => "nullable|digits_between:1,9223372036854775807",
                "paymentMode" => "nullable",
                "userId" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        if ($validated->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validated->errors()
            ]);
        }
        try {
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            $user = Auth()->user();
            $ulbId = $user->ulb_id ?? 2;
            $perPage = $request->perPage ? $request->perPage : 10;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $limit = $perPage;
            $offset =  $request->page && $request->page > 0 ? (($request->page - 1) * $perPage) : 0;
            $wardId = $zoneId = $paymentMode = $userId = null;
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->zoneId) {
                $zoneId = $request->zoneId;
            }
            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->userId) {
                $userId = $request->userId;
            }
            $sql = "
                SELECT  water_trans_sub.*,
                    users.id as user_id,
                    users.name,
                    users.mobile,
                    users.photo,
                    users.photo_relative_path
                FROM(
                    SELECT SUM(amount) as total_amount,
                        count(wt.id) as total_tran,
                        count(distinct wt.related_id) as total_water, 
                        wt.emp_dtl_id                   
                    FROM water_trans as wt  
                    JOIN water_second_consumers wsc on wsc.id = wt.related_id                 
                    WHERE wt.status IN (1,2)
                    AND wt.tran_type = 'Demand Collection'
                   
                        AND wt.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        " . ($wardId ? " AND water_second_consumers.ward_mstr_id = $wardId" : "") . "
                        " . ($zoneId ? " AND water_second_consumers.zone_mstr_id	 = $zoneId" : "") . "
                        " . ($userId ? " AND wt.emp_dtl_id = $userId" : "") . "
                    GROUP BY wt.emp_dtl_id
                    ORDER BY wt.emp_dtl_id
                ) water_trans_sub
                JOIN users ON users.id = water_trans_sub.emp_dtl_id
            ";
            $data = DB::connection('pgsql_water')->select($sql . " limit $limit offset $offset");
            $count = (collect(DB::connection('pgsql_water')->SELECT("SELECT COUNT(*)AS total, SUM(total_amount) AS total_amount FROM ($sql) total"))->first());
            $tran = (collect(DB::connection('pgsql_water')->SELECT("SELECT COUNT(*)AS total, SUM(total_tran) AS total_tran FROM ($sql) total"))->first());
            $total = ($count)->total ?? 0;
            $sum = ($count)->total_amount ?? 0;
            $lastPage = ceil($total / $perPage);
            $total = ($tran)->total ?? 0;
            $tran_sum = ($tran)->total_tran ?? 0;
            $list = [
                "current_page" => $page,
                "data" => $data,
                "total" => $total,
                "total_sum" => $sum,
                "per_page" => $perPage,
                "last_page" => $lastPage,
                "total_tran_sum" => $tran_sum
            ];
            return responseMsgs(true, "", $list, "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), "", "", 01, responseTime(), $request->getMethod(), $request->deviceId);
        }
    }
    /**
     * water ward wise dcb
     */
    public function WaterWardWiseDCB(Request $request)
    {
        $request->validate(
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "ulbId" => "nullable|digits_between:1,9223372036854775807",
                "wardId" => "nullable|digits_between:1,9223372036854775807",
                "zoneId" => "nullable|digits_between:1,9223372036854775807",
                // "page" => "nullable|digits_between:1,9223372036854775807",
                // "perPage" => "nullable|digits_between:1,9223372036854775807",
            ]
        );
        $request->merge(["metaData" => ["pr8.1", 1.1, null, $request->getMethod(), null,]]);
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        try {
            $refUser        = authUser($request);
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $now                        = Carbon::now();
            $mWaterConsumerDemand       = new WaterConsumerDemand();
            $currentDate                = $now->format('Y-m-d');
            $zoneId = $wardId = null;
            $currentYear                = collect(explode('-', $request->fiYear))->first() ?? $now->year;
            $currentFyear               = $request->fiYear ?? getFinancialYear($currentDate);
            $startOfCurrentYear         = Carbon::createFromDate($currentYear, 4, 1);           // Start date of current financial year
            $startOfPreviousYear        = $startOfCurrentYear->copy()->subYear();               // Start date of previous financial year
            $previousFinancialYear      = getFinancialYear($startOfPreviousYear);

            #get financial  year 
            $refDate = $this->getFyearDate($currentFyear);
            $fromDate = $refDate['fromDate'];
            $uptoDate = $refDate['uptoDate'];

            #common function 
            $refDate = $this->getFyearDate($previousFinancialYear);
            $previousFromDate = $refDate['fromDate'];
            $previousUptoDate = $refDate['uptoDate'];
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }
            if ($request->zoneId || $request->zone) {
                $zoneId = $request->zoneId ?? $request->zone;
            }
            $from = "
            FROM ulb_ward_masters 
            LEFT JOIN(
                SELECT water_second_consumers.ward_mstr_id, 
                COUNT(DISTINCT water_second_consumers.id)AS ref_consumer_count,       
                    COUNT(DISTINCT (CASE WHEN water_consumer_demands.demand_from >= '$fromDate' AND water_consumer_demands.demand_upto <= '$uptoDate'  THEN water_consumer_demands.consumer_id               
                                    END)                        
                        ) as current_demand_hh,    
                    SUM(              
                        CASE WHEN water_consumer_demands.demand_from >= '$fromDate' AND water_consumer_demands.demand_upto <= '$uptoDate' THEN water_consumer_demands.balance_amount          
                        ELSE 0                                   
                        END                         
                    ) AS current_demand,       
                    COUNT(DISTINCT ( CASE WHEN water_consumer_demands.demand_upto <= '$previousUptoDate'  THEN water_consumer_demands.consumer_id    
                                    END)                            
                        ) as arrear_demand_hh,                       
                    SUM(CASE WHEN water_consumer_demands.demand_upto <= '$previousUptoDate' THEN (water_consumer_demands.balance_amount) ELSE 0 END ) AS arrear_demand,     
                    SUM(amount) AS total_demand,
                    COUNT(DISTINCT (CASE WHEN  water_consumer_demands.demand_from >= '$fromDate' AND water_consumer_demands.demand_upto <= '$uptoDate'   AND water_consumer_demands.paid_status =1 THEN water_consumer_demands.consumer_id               
                                        END)                        
                            ) as current_collection_hh,  
                    SUM(              
                            CASE WHEN water_consumer_demands.demand_from >= '$fromDate' AND water_consumer_demands.demand_upto <= '$uptoDate'  AND water_consumer_demands.paid_status =1 THEN water_consumer_demands.balance_amount          
                            ELSE 0                                   
                            END                        
                        ) AS current_collection,
                    COUNT(DISTINCT ( CASE WHEN water_consumer_demands.demand_upto <= '$previousUptoDate' AND water_consumer_demands.paid_status =1 THEN water_consumer_demands.consumer_id
                                        END)                            
                            ) as arrear_collection_hh, 
                    SUM(CASE WHEN water_consumer_demands.demand_upto <= '$previousUptoDate'  AND water_consumer_demands.paid_status =1  THEN water_consumer_demands.balance_amount ELSE 0 END ) AS arrear_collection,
                    SUM(CASE WHEN water_consumer_demands.paid_status =1  then water_consumer_demands.balance_amount ELSE 0 END) AS total_collection,
                    COUNT(DISTINCT(CASE WHEN water_consumer_demands.paid_status =1  then water_second_consumers.id end)) AS collection_from_no_of_hh 
                FROM water_consumer_demands                    
                JOIN water_second_consumers ON water_second_consumers.id = water_consumer_demands.consumer_id
                WHERE water_consumer_demands.status =true
                    AND water_consumer_demands.ulb_id =$ulbId   
                    " . ($wardId ? " AND water_second_consumers.ward_mstr_id = $wardId" : "") . "
                    AND water_consumer_demands.demand_upto <= '$uptoDate'
                GROUP BY water_second_consumers.ward_mstr_id
            )demands ON demands.ward_mstr_id = ulb_ward_masters.id   
            left join(
                SELECT water_second_consumers.ward_mstr_id, SUM(0)AS balance
                FROM water_second_consumers
                where water_second_consumers.status = 1 
                    AND water_second_consumers.ulb_id =$ulbId
                " . ($wardId ? " AND water_second_consumers.ward_mstr_id = $wardId" : "") . "
                GROUP BY water_second_consumers.ward_mstr_id
            ) AS arrear  on arrear.ward_mstr_id = ulb_ward_masters.id                            
            WHERE  ulb_ward_masters.ulb_id = $ulbId  
                " . ($wardId ? " AND ulb_ward_masters.id = $wardId" : "") . "
                " . ($zoneId ? " AND ulb_ward_masters.zone = $zoneId" : "") . "
            GROUP BY ulb_ward_masters.ward_name,
            demands.ref_consumer_count         
        ";

            $select = "SELECT ulb_ward_masters.ward_name AS ward_no,ulb_ward_masters.ward_name,ref_consumer_count,
                            SUM(COALESCE(demands.current_demand_hh, 0::numeric)) AS current_demand_hh,   
                            SUM(COALESCE(demands.arrear_demand_hh, 0::numeric)) AS arrear_demand_hh,      
                            SUM(COALESCE(demands.current_collection_hh, 0::numeric)) AS current_collection_hh,  
                            SUM(COALESCE(demands.arrear_collection_hh, 0::numeric)) AS arrear_collection_hh,      
                            SUM(COALESCE(demands.collection_from_no_of_hh, 0::numeric)) AS collection_from_hh,   
                            round(
                                SUM(
                                    (
                                            COALESCE(demands.arrear_collection_hh, 0::numeric) 
                                            / (case when COALESCE(demands.arrear_demand_hh, 0::numeric) > 0  then demands.arrear_demand_hh else 1 end)
                                    )
                                    *100
                                )
                            ) AS arrear_hh_eff,                            
                            round(
                                SUM(
                                    (
                                        COALESCE(demands.current_collection_hh, 0::numeric) 
                                        / (case when COALESCE(demands.current_demand_hh, 0::numeric) > 0 then demands.current_demand_hh else 1 end)
                                    )
                                    *100
                                )
                            ) AS current_hh_eff,                            
                            round(
                                SUM(
                                    COALESCE(                                
                                        COALESCE(demands.current_demand_hh, 0::numeric)  
                                        +COALESCE(demands.arrear_demand_hh, 0::numeric)
                                        - COALESCE(demands.collection_from_no_of_hh, 0::numeric), 0::numeric     
                                    )
                                )
                            ) AS balance_hh,                                                   
                            round(
                                SUM(
                                    COALESCE(                                
                                        COALESCE(demands.arrear_demand, 0::numeric) + COALESCE(arrear.balance, 0::numeric)   
                                    )
                                )
                            ) AS arrear_demand,    
                            round(SUM(COALESCE(demands.current_demand, 0::numeric))) AS current_demand,   
                            round(SUM(COALESCE(demands.arrear_collection, 0::numeric))) AS arrear_collection,   
                            round(SUM(COALESCE(demands.current_collection, 0::numeric))) AS current_collection, 
                            round(
                                SUM(
                                    (                                    
                                        COALESCE(demands.arrear_demand, 0::numeric) + COALESCE(arrear.balance, 0::numeric)                              
                                        - COALESCE(demands.arrear_collection, 0::numeric)           
                                    )
                                )
                            )AS old_due,                          
                            round(SUM((COALESCE(demands.current_demand, 0::numeric) - COALESCE(demands.current_collection, 0::numeric)))) AS current_due,    
                            round(SUM((COALESCE(demands.current_demand_hh, 0::numeric) - COALESCE(demands.current_collection_hh, 0::numeric)))) AS current_balance_hh, 
                            round(SUM((COALESCE(demands.arrear_demand_hh, 0::numeric) - COALESCE(demands.arrear_collection_hh, 0::numeric)))) AS arrear_balance_hh,   
                            round(
                                SUM(
                                    (
                                        COALESCE(demands.arrear_collection ::numeric , 0::numeric)
                                        / (case when (COALESCE(demands.arrear_demand, 0::numeric) + COALESCE(arrear.balance, 0::numeric)) > 0 then demands.arrear_demand else 1 end)
                                        
                                    )
                                    *100
                                )
                            ) AS arrear_eff,                            
                            round(
                                SUM(
                                    (
                                        COALESCE(demands.current_collection, 0::numeric)
                                            / (case when COALESCE(demands.current_demand, 0::numeric) > 0  then demands.current_demand else 1 end)
                                        
                                    )
                                    *100
                                )
                            ) AS current_eff,
                            round(
                                SUM(
                                    (                                
                                        COALESCE(                                   
                                            COALESCE(demands.current_demand, 0::numeric)         
                                            + (                                       
                                                COALESCE(demands.arrear_demand, 0::numeric) + COALESCE(arrear.balance, 0::numeric)  
                                            ), 0::numeric                                
                                        )                                 
                                        - COALESCE(                              
                                            COALESCE(demands.current_collection, 0::numeric)   
                                            + COALESCE(demands.arrear_collection, 0::numeric), 0::numeric     
                                        )                            
                                    )
                                )
                            ) AS outstanding                                  
            ";
            $dcb = DB::connection('pgsql_water')->select($select . $from);

            $data['total_consumer_count'] = round(collect($dcb)->sum('ref_consumer_count'), 0);
            $data['total_arrear_demand'] = round(collect($dcb)->sum('arrear_demand'), 0);
            $data['total_current_demand'] = round(collect($dcb)->sum('current_demand'), 0);
            $data['total_arrear_collection'] = round(collect($dcb)->sum('arrear_collection'), 0);
            $data['total_current_collection'] = round(collect($dcb)->sum('current_collection'), 0);
            $data['total_old_due'] = round(collect($dcb)->sum('old_due'), 0);
            $data['total_current_due'] = round(collect($dcb)->sum('current_due'), 0);
            $data['total_arrear_demand_hh'] = round(collect($dcb)->sum('arrear_demand_hh'), 0);
            $data['total_current_demand_hh'] = round(collect($dcb)->sum('current_demand_hh'), 0);
            $data['total_arrear_collection_hh'] = round(collect($dcb)->sum('arrear_collection_hh'), 0);
            $data['total_current_collection_hh'] = round(collect($dcb)->sum('current_collection_hh'), 0);
            $data['total_arrear_balance_hh'] = round(collect($dcb)->sum('arrear_balance_hh'));
            $data['total_current_balance_hh'] = round(collect($dcb)->sum('current_balance_hh'));
            // $data['total_current_eff'] = round(($data['total_current_collection_hh'] / $data['total_current_demand']) * 100);
            // $data['total_arrear_hh_eff'] = round(($data['total_arrear_collection_hh'] /  $data['total_arrear_demand_hh']) * 100);
            // $data['total_current_hh_eff'] = round(($data['total_current_collection_hh']) / ($data['total_current_demand_hh']) * 100);
            // $data['total_arrear_eff'] = round(($data['total_arrear_collection']) / ($data['total_arrear_demand']) * 100);
            $refCollection = $data['total_arrear_collection'] + $data['total_current_collection'];
            $refDemand = $data['total_arrear_demand'] + $data['total_current_demand'];
            $data['total_eff'] = round((($refCollection) / ($refDemand == 0 ? 1 : $refDemand)) * 100);
            $data['dcb'] = collect($dcb)->sortBy(function ($item) {
                // Extract the numeric part from the "ward_name"
                preg_match('/\d+/', $item->ward_name, $matches);
                return (int) ($matches[0] ?? "");
            })->values();

            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, [$e->getMessage(), $e->getFile(), $e->getLine()], $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
    /**
     * | water demands
     */
    public function WaterDemandsReport(colllectionReport $request)
    {
        $request->merge(["metaData" => ["pr1.1", 1.1, null, $request->getMethod(), null,]]);
        $metaData = collect($request->metaData)->all();

        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        // return $request->all();
        try {
            $metertype    = null;
            $refUser        = authUser($request);
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $zoneId = null;
            $paymentMode = null;
            $perPage = $request->perPage ? $request->perPage : 5;
            $page = $request->page && $request->page > 0 ? $request->page : 1;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            if ($request->userId)
                $userId = $request->userId;
            else
                $userId = auth()->user()->id;                   // In Case of any logged in TC User

            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->zoneId) {
                $zoneId = $request->zoneId;
            }
            if ($request->metertype == 1) {
                $metertype = 'Meter';
            }
            if ($request->metertype == 2) {
                $metertype = 'Fixed';
            }

            // DB::connection('pgsql_water')->enableQueryLog();

            $rawData = ("SELECT 
            water_consumer_demands.*,
            ulb_ward_masters.ward_name AS ward_no,
            water_second_consumers.id,
            'water' as type,
            water_second_consumers.consumer_no,
            water_second_consumers.user_type,
            water_second_consumers.property_no,
            water_second_consumers.address,
            water_consumer_owners.applicant_name,
            water_consumer_owners.guardian_name,
            water_consumer_owners.mobile_no,
            water_second_consumers.ward_mstr_id,
            zone_masters.zone_name
        FROM (
            SELECT 
                COUNT(water_consumer_demands.id)as demand_count,
                SUM(balance_amount) as sum_balance_amount,
                water_consumer_demands.consumer_id,
                water_consumer_demands.connection_type,
                  water_consumer_demands.status
            FROM water_consumer_demands
            WHERE  
                demand_from >= '$fromDate'
                AND demand_upto <= '$uptoDate'
                AND water_consumer_demands.status = TRUE
                AND water_consumer_demands.consumer_id IS NOT NULL
                AND water_consumer_demands.paid_status= 0
            GROUP BY water_consumer_demands.consumer_id, 
                             water_consumer_demands.connection_type,
                             water_consumer_demands.status
        ) water_consumer_demands
        JOIN water_second_consumers ON water_second_consumers.id = water_consumer_demands.consumer_id
        LEFT JOIN water_consumer_owners ON water_consumer_owners.consumer_id = water_second_consumers.id
        LEFT JOIN zone_masters ON zone_masters.id = water_second_consumers.zone_mstr_id
        LEFT JOIN ulb_ward_masters ON ulb_ward_masters.id = water_second_consumers.ward_mstr_id
        JOIN (
            SELECT 
                STRING_AGG(applicant_name, ', ') AS owner_name, 
                STRING_AGG(water_consumer_owners.mobile_no::TEXT, ', ') AS mobile_no, 
                water_consumer_owners.consumer_id 
            FROM water_second_consumers 
            JOIN water_consumer_demands ON water_consumer_demands.consumer_id = water_second_consumers.id
            JOIN water_consumer_owners ON water_consumer_owners.consumer_id = water_second_consumers.id
            GROUP BY water_consumer_owners.consumer_id
        ) owners ON owners.consumer_id = water_second_consumers.id
        WHERE water_consumer_demands.status = true
        ");

            // return ["details" => $data->get()];
            if ($wardId) {
                $rawData = $rawData . "and ulb_ward_masters.id = $wardId";
            }
            if ($zoneId) {
                $rawData = $rawData . " and water_second_consumers.zone_mstr_id = $zoneId";
            }
            if ($metertype) {
                $rawData = $rawData . "and water_consumer_demands.connection_type = '$metertype'";
            }

            $data = DB::connection('pgsql_water')->select(DB::raw($rawData . " OFFSET 0
                    LIMIT $perPage"));

            $count = (collect(DB::connection('pgsql_water')->SELECT("SELECT COUNT(*) AS total
                                FROM ($rawData) total"))->first());
            $total = ($count)->total ?? 0;
            $lastPage = ceil($total / $perPage);
            $list = [
                "current_page" => $page,
                "data" => $data,
                "total" => $total,
                "per_page" => $perPage,
                "last_page" => $lastPage
            ];
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime = NULL, $action, $deviceId);


            // return ["kjsfd" => $data];
            $paginator = collect();

            $page = $request->page && $request->page > 0 ? $request->page : 1;

            $data2 = DB::connection('pgsql_water')->select(DB::raw($rawData));
            $totalConsumers = collect($data2)->unique("id")->count("water_second_consumers.id");
            $totalAmount = collect($data2)->sum("demandamount");

            // if ($request->all) {
            //     $data = $data->get();
            //     $mode = collect($data)->unique("consumer_id")->pluck("transaction_mode");
            //     $totalFAmount = collect($data)->unique("consumer_id")->sum("amount");
            //     $totalFCount = collect($data)->unique("tran_id")->count("tran_id");
            //     $footer = $mode->map(function ($val) use ($data) {
            //         $count = $data->where("transaction_mode", $val)->unique("tran_id")->count("tran_id");
            //         $amount = $data->where("transaction_mode", $val)->unique("tran_id")->sum("amount");
            //         return ['mode' => $val, "count" => $count, "amount" => $amount];
            //     });
            //     $list = [
            //         "data" => $data,

            //     ];
            //     $tcName = collect($data)->first()->emp_name ?? "";
            //     $tcMobile = collect($data)->first()->tc_mobile ?? "";
            //     if ($request->footer) {
            //         $list["tcName"] = $tcName;
            //         $list["tcMobile"] = $tcMobile;
            //         $list["footer"] = $footer;
            //         $list["totalCount"] = $totalFCount;
            //         $list["totalAmount"] = $totalFAmount;
            //     }
            //     return responseMsgs(true, "", remove_null($list), $apiId, $version, $queryRunTime, $action, $deviceId);
            // }



            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalConsumers" => $totalConsumers,
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
            ];
            // $queryRunTime = (collect(DB::connection('pgsql_water'))->sum("time"));
            return responseMsgs(true, "", $data, $apiId, $version, $queryRunTime = NULL, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
    /**
     * | water Collection
     */
    public function WaterCollectionConsumerReport(colllectionReport $request)
    {
        $request->merge(["metaData" => ["pr1.1", 1.1, null, $request->getMethod(), null,]]);
        $metaData = collect($request->metaData)->all();

        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;
        // return $request->all();
        try {

            $refUser        = authUser($request);
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $userId = null;
            $zoneId = null;
            $paymentMode = null;
            $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
            if ($request->fromDate) {
                $fromDate = $request->fromDate;
            }
            if ($request->uptoDate) {
                $uptoDate = $request->uptoDate;
            }
            if ($request->wardId) {
                $wardId = $request->wardId;
            }

            if ($request->userId)
                $userId = $request->userId;
            else
                $userId = auth()->user()->id;                   // In Case of any logged in TC User

            if ($request->paymentMode) {
                $paymentMode = $request->paymentMode;
            }
            if ($request->ulbId) {
                $ulbId = $request->ulbId;
            }
            if ($request->zoneId) {
                $zoneId = $request->zoneId;
            }

            // DB::enableQueryLog();
            $data = waterTran::SELECT(
                DB::raw("
                            ulb_ward_masters.ward_name AS ward_no,
                            water_second_consumers.id,
                            'water' as type,
                            water_second_consumers.saf_no,
                            water_second_consumers.user_type,
                            water_second_consumers.property_no,
                            water_second_consumers.consumer_no,
                            water_second_consumers.address,
                            water_consumer_owners.applicant_name,
                            water_consumer_owners.mobile_no,
                            water_consumer_owners.guardian_name,
                            users.name as name,
                            users.user_name as emp_name,
                            users.id as user_id,
                            users.mobile as tc_mobile,
                            water_cheque_dtls.cheque_no,
                            water_cheque_dtls.bank_name,
                            water_cheque_dtls.branch_name,
                            zone_masters.zone_name,
                            water_consumer_demands.connection_type,
                            water_second_consumers.meter_no,
                            water_consumer_initial_meters.initial_reading,
                            water_consumer_demands.demand_upto,
                            water_consumer_demands.demand_from,
                            water_consumer_demands.amount

                            
                "),
            )
                ->leftJOIN("water_second_consumers", "water_second_consumers.id", "water_trans.related_id")
                ->leftJoin("water_consumer_owners", "water_consumer_owners.consumer_id", "=", "water_second_consumers.id")
                ->leftJoin('zone_masters', 'zone_masters.id', '=', 'water_second_consumers.zone_mstr_id')
                ->join('water_consumer_demands', 'water_consumer_demands.consumer_id', 'water_second_consumers.id')
                ->join('water_consumer_initial_meters', 'water_consumer_initial_meters.consumer_id', 'water_second_consumers.id')
                ->orderByDesc('water_consumer_initial_meters.id')

                ->JOIN(
                    DB::RAW("(
                        SELECT STRING_AGG(applicant_name, ', ') AS owner_name, STRING_AGG(water_consumer_owners.mobile_no::TEXT, ', ') AS mobile_no, water_consumer_owners.consumer_id 
                            FROM water_second_consumers 
                        JOIN water_consumer_demands  on water_consumer_demands.consumer_id = water_second_consumers.id
                        JOIN water_consumer_owners on water_consumer_owners.consumer_id = water_second_consumers.id
                        WHERE water_consumer_demands.consumer_id IS NOT NULL AND water_consume_demands.status in (1, 2) 
                     
                        AND water_consumer_demands.tran_date BETWEEN '$fromDate' AND '$uptoDate'
                        GROUP BY water_consumer_owners.consumer_id
                        ) AS water_owner_details
                        "),
                    function ($join) {
                        $join->on("water_owner_details.consumer_id", "=", "water_trans.related_id");
                    }

                )
                ->LEFTJOIN("ulb_ward_masters", "ulb_ward_masters.id", "water_second_consumers.ward_mstr_id")
                ->LEFTJOIN("users", "users.id", "water_trans.emp_dtl_id")
                ->where('water_consumer_demands.demand_from', $fromDate)
                ->where('water_consumer_demands.demand_upto', $uptoDate);
            if ($wardId) {
                $data = $data->where("ulb_ward_masters.id", $wardId);
            }
            if ($userId) {
                $data = $data->where("water_trans.emp_dtl_id", $userId);
            }
            if ($paymentMode) {
                $data = $data->where(DB::raw("upper(water_trans.payment_mode)"), $paymentMode);
            }
            if ($ulbId) {
                $data = $data->where("water_trans.ulb_id", $ulbId);
            }
            if ($zoneId) {
                $data = $data->where("water_second_consumers.zone_mstr_id", $zoneId);
            }
            $paginator = collect();

            $data2 = $data;
            $totalConsumers = $data2->count("water_consumer_demands.id");
            $totalAmount = $data2->sum("water_consumer_demands.amount");
            $perPage = $request->perPage ? $request->perPage : 5;
            $page = $request->page && $request->page > 0 ? $request->page : 1;

            // if ($request->all) {
            //     $data = $data->get();
            //     $mode = collect($data)->unique("transaction_mode")->pluck("transaction_mode");
            //     $totalFAmount = collect($data)->unique("tran_id")->sum("amount");
            //     $totalFCount = collect($data)->unique("tran_id")->count("tran_id");
            //     $footer = $mode->map(function ($val) use ($data) {
            //         $count = $data->where("transaction_mode", $val)->unique("tran_id")->count("tran_id");
            //         $amount = $data->where("transaction_mode", $val)->unique("tran_id")->sum("amount");
            //         return ['mode' => $val, "count" => $count, "amount" => $amount];
            //     });
            //     $list = [
            //         "data" => $data,

            //     ];
            //     $tcName = collect($data)->first()->emp_name ?? "";
            //     $tcMobile = collect($data)->first()->tc_mobile ?? "";
            //     if ($request->footer) {
            //         $list["tcName"] = $tcName;
            //         $list["tcMobile"] = $tcMobile;
            //         $list["footer"] = $footer;
            //         $list["totalCount"] = $totalFCount;
            //         $list["totalAmount"] = $totalFAmount;
            //     }
            //     return responseMsgs(true, "", remove_null($list), $apiId, $version, $queryRunTime, $action, $deviceId);
            // }

            $paginator = $data->paginate($perPage);

            // $items = $paginator->items();
            // $total = $paginator->total();
            // $numberOfPages = ceil($total / $perPage);
            $list = [
                "current_page" => $paginator->currentPage(),
                "last_page" => $paginator->lastPage(),
                "totalHolding" => $totalConsumers,
                "totalAmount" => $totalAmount,
                "data" => $paginator->items(),
                "total" => $paginator->total(),
                // "numberOfPages" => $numberOfPages
            ];
            $queryRunTime = (collect(DB::connection('pgsql_water'))->sum("time"));
            return responseMsgs(true, "", $list, $apiId, $version, $queryRunTime, $action, $deviceId);
        } catch (Exception $e) {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }
    }
    /**
     * |
     */
    /**
     * | Ward wise demand report
     */
    public function wardWiseConsumerReport(Request $request)
    {
        $mconsumerDemand = new waterConsumerDemand();
        $fromDate = $uptoDate = Carbon::now()->format("Y-m-d");
        $ulbId = null;
        $wardId = null;
        $perPage = 5;
        if ($request->fromDate) {
            $fromDate = $request->fromDate;
        }
        if ($request->uptoDate) {
            $uptoDate = $request->uptoDate;
        }
        if ($request->wardId) {
            $wardId = $request->wardId;
        }

        if ($request->ulbId) {
            $ulbId = $request->ulbId;
        }
        if ($request->zoneId) {
            $zoneId = $request->zoneId;
        }
        if ($request->perPage) {
            $perPage = $request->perPage ?? 1;
        }
        $data = $mconsumerDemand->wardWiseConsumer($fromDate, $uptoDate, $wardId, $ulbId, $perPage);
        if (!$data) {
            throw new Exception('no demand found!');
        }

        $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
        return responseMsgs(true, "Ward Wise Demand Data!", remove_null($data), 'pr6.1', '1.1', $queryRunTime, 'Post', '');
    }
}
