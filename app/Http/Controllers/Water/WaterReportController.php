<?php

namespace App\Http\Controllers\Water;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Water\WaterTran;
use App\Traits\Water\WaterTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Water\WaterConsumer;
use Illuminate\Support\Facades\Config;
use App\Models\Water\WaterConsumerDemand;
use Illuminate\Support\Facades\Validator;
use App\Models\Water\WaterSecondConsumer;


/**
 * | ----------------------------------------------------------------------------------
 * | Water Module |
 * |-----------------------------------------------------------------------------------
 * | Created On-14-04-2023
 * | Created By-Mrinal Kumar 
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
     * date-25/09/2023
     */
    public function WaterdcbReport(Request $request)
    {
        $validated = Validator::make(
            $request->all(),
            [
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "wardId" => "nullable|int"
            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $now                        = Carbon::now();
            $mWaterConsumerDemand       = new WaterConsumerDemand();
            $currentDate                = $now->format('Y-m-d');
            $wardId                     = $request->wardId;
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

            #curent year demands 
            $demand                 = $mWaterConsumerDemand->getAllDemand($fromDate, $uptoDate, $wardId)->get();
            $previousDemand         = $mWaterConsumerDemand->previousDemand($previousFromDate, $previousUptoDate, $wardId)->get();

            $totalCurrentDemands    = round($demand->sum('amount'), 2);  // Format the sum to two decimal places
            $balanceAmount          = round($demand->where('paid_status', 0)->sum('amount'), 2);  // Format to two decimal places
            $totalCollection        = round($demand->where('paid_status', 1)->sum('amount'), 2);  // Format to two decimal places 
            // sum of collection amount 
            $financialYear = [
                'balanceAmount'  => $balanceAmount ?? 0,
                'collections'    => $totalCollection ?? 0,
                'totalDemand'    => $totalCurrentDemands ?? 0,
            ];

            #previous year demands 
            $totalPreviousDemands   = round($previousDemand->sum('amount'), 2);  // Format the sum to two decimal places
            $previousCollection     = round($previousDemand->where('paid_status', 1)->sum('amount'), 2);  // Format to two decimal places
            $totalPreviousBalance   = round($previousDemand->where('paid_status', 0)->sum('amount'), 2);  // Format to two decimal place

            $previousYear = [
                'balanceAmountPrevious' => $totalPreviousBalance ?? 0,
                'collectionsPrevious'   => $previousCollection ?? 0,
                "totalDemandPrevious"   => $totalPreviousDemands ?? 0
            ];

            $arrearBalance = $totalPreviousDemands - $previousCollection;
            $currentBalance = round($totalCurrentDemands - $totalCollection, 2);
            $totalDcb = [
                'totalDemands'      => $totalCurrentDemands + $totalPreviousDemands,
                'totalCollections'  => $totalCollection + $previousCollection,
                "arrearBalance"     => $arrearBalance,
                "currentBalance"    => $currentBalance
            ];
            $totalDcb["totalBalance"] = $arrearBalance + $currentBalance;

            $returnValues = collect($financialYear)->merge($previousYear)->merge($totalDcb);
            return responseMsgs(true, "water demand report", remove_null($returnValues), "", "", "", 'POST', "");
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
                "fiYear" => "nullable|regex:/^\d{4}-\d{4}$/",
                "wardId" => "nullable|int"

            ]
        );
        if ($validated->fails())
            return validationError($validated);
        try {
            $mWaterTrans    = new WaterTran();
            $currentDate    = Carbon::now()->format('Y-m-d');
            $wardId         = $request->wardId;
            $currentFyear   = $request->fiYear ?? getFinancialYear($currentDate);

            #get financial  year 
            $refDate        = $this->getFyearDate($currentFyear);
            $fromDate       = $refDate['fromDate'];
            $uptoDate       = $refDate['uptoDate'];
            $transaction    = $mWaterTrans->getWaterReport($fromDate, $uptoDate, $wardId)->get();

            # For Payment Mode 
            $cash   = $transaction->where('payment_mode', 'Cash');
            $cheque = $transaction->where('payment_mode', 'Cheque');
            $online = $transaction->where('payment_mode', 'Online');
            $DD     = $transaction->where('payment_mode', 'DD');
            $Neft   = $transaction->where('payment_mode', 'Neft');
            $returnData["collectionSummery"] = [
                'waterCash'         => $cash->count(),
                'waterCheque'       => $cheque->count(),
                'waterOnline'       => $online->count(),
                'waterDd'           => $DD->count(),
                'waterNeft'         => $Neft->count(),

                'totaAmountCash'    => $cash->sum('amount'),
                'totalAmountCheque' => $cheque->sum('amount'),
                'waterAmountOnline' => $online->sum('amount'),
                'totalAmountDd'     => $DD->sum('amount'),
                'totalAmuntNeft'    => $Neft->sum('amount'),
            ];
            $returnData["collectionSummery"]["toatalCollection"] = $returnData["collectionSummery"]["totaAmountCash"]
                + $returnData["collectionSummery"]["totalAmountCheque"]
                + $returnData["collectionSummery"]["waterAmountOnline"]
                + $returnData["collectionSummery"]["totalAmountDd"]
                + $returnData["collectionSummery"]["totalAmuntNeft"];


            # For JSK collection
            $jskCash    = $cash->where('user_type', 'JSK');
            $jskCheque  = $cheque->where('user_type', 'JSK');
            $jskOnline  = $online->where('user_type', 'JSK');
            $jskDd      = $DD->where('user_type', 'JSK');
            $jskNeft    = $Neft->where('user_type', 'JSK');
            $returnData["jskCollectionSummery"] = [
                'waterCash'         => $jskCash->count(),
                'waterCheque'       => $jskCheque->count(),
                'waterOnline'       => $jskOnline->count(),
                'waterDd'           => $jskDd->count(),
                "waterNeft"         => $jskNeft->count(),

                'totaAmountCash'    => $jskCash->sum('amount'),
                'totalAmountCheque' => $jskCheque->sum('amount'),
                'waterAmountOnline' => $jskOnline->sum('amount'),
                'totaAmountDd'      => $jskDd->sum('amount'),
                'totalAmountNeft'   => $jskNeft->sum('amount'),
            ];
            $returnData["jskCollectionSummery"]["toatalCollection"] = $returnData["jskCollectionSummery"]["totaAmountCash"]
                + $returnData["jskCollectionSummery"]["totaAmountCash"]
                + $returnData["jskCollectionSummery"]["totaAmountCash"]
                + $returnData["jskCollectionSummery"]["totaAmountCash"]
                + $returnData["jskCollectionSummery"]["totaAmountCash"];


            # May use where in for tc and tl
            $TcCash     = $cash->where('user_type', 'TC');
            $TcCheque   = $cheque->where('user_type', 'TC');
            $TcOnline   = $online->where('user_type', 'TC');
            $TcDd       = $DD->where('user_type', 'TC');
            $TcNeft     = $Neft->where('user_type', 'TC');
            $returnData["dtdCollectionSummery"] = [
                'waterCash'         => $TcCash->count(),
                'waterCheque'       => $TcCheque->count(),
                'waterOnline'       => $TcOnline->count(),
                'waterDd'           => $TcDd->count(),
                'waterNeft'         => $TcNeft->count(),

                'totaAmountCash'    => $TcCash->sum('amount'),
                'totalAmountCheque' => $TcCheque->sum('amount'),
                'waterAmountOnline' => $TcOnline->sum('amount'),
                'totalAmountDd'     => $TcDd->sum('amount'),
                'totalAmountNeft'   => $TcNeft->sum('amount'),

                // 'toatalCollection'  => $TcCash->sum('amount') + $TcCheque->sum('amount') + $TcOnline->sum('amount') + $TcDd->sum('amount') + $TcNeft->sum('amount')

            ];
            $returnData["dtdCollectionSummery"]["toatalCollection"] =  $returnData["dtdCollectionSummery"]["totaAmountCash"]
                + $returnData["dtdCollectionSummery"]["totalAmountCheque"]
                + $returnData["dtdCollectionSummery"]["waterAmountOnline"]
                + $returnData["dtdCollectionSummery"]["totalAmountDd"]
                + $returnData["dtdCollectionSummery"]["totalAmountNeft"];

            # Net collection summary 
            $returnData["netcollectionSummary"] = [
                'netTransaction' => $transaction->count(),
                'netTotalAmount' => $returnData["collectionSummery"]["toatalCollection"]
            ];

            return responseMsgs(true, "water transaction report", remove_null($returnData), "", "", "", 'POST', "");
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
}
