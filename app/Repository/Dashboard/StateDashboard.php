<?php

namespace App\Repository\Dashboard;

use App\EloquentModels\Common\ModelWard;
use App\Repository\Common\CommonFunction;
use App\Repository\Property\Concrete\SafRepository;
use App\Traits\Property\SAF;
use App\Traits\Workflow\Workflow;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StateDashboard
{
    use SAF;
    use Workflow;

    protected $_common;
    protected $_modelWard;
    protected $_Saf;

    public function __construct()
    {
        $this->_common = new CommonFunction();
        $this->_modelWard = new ModelWard();
        $this->_Saf = new SafRepository();
    }
    public function stateDashboardDCB($request)
    {
        $metaData = collect($request->metaData)->all();
        list($apiId, $version, $queryRunTime, $action, $deviceId) = $metaData;

        try {
            $refUser        = Auth()->user();
            $refUserId      = $refUser->id;
            $ulbId          = $refUser->ulb_id;
            $wardId = null;
            $fiYear = getFY();
            if ($request->fiYear) 
            {
                $fiYear = $request->fiYear;
            }
            list($fromYear, $toYear) = explode("-", $fiYear);
            if ($toYear - $fromYear != 1) 
            {
                throw new Exception("Enter Valide Financial Year");
            }
            $fromDate = $fromYear . "-04-01";
            $uptoDate = $toYear . "-03-31";
            if ($request->ulbId) 
            {
                $ulbId = $request->ulbId;
            }
            if ($request->wardId) 
            {
                $wardId = $request->wardId;
            }
            $from = "
                FROM (
                    SELECT CASE WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 4 AND 6 THEN 1
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 7 AND 9 THEN 2
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 10 AND 12 THEN 3
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 1 AND 3 THEN 4
                                ELSE 0 END AS quater,
                        COUNT(DISTINCT(prop_demands.property_id)) AS current_hh,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_demand,
                        SUM(
                            CASE WHEN prop_demands.due_date<'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_demand,
                    SUM(prop_demands.amount) AS total_demand
                    FROM prop_demands
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_demands.ward_mstr_id = $wardId" : "") . "
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY TO_CHAR(prop_demands.due_date::DATE,'mm')   
                )demands 
                LEFT JOIN (
                    SELECT CASE WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 4 AND 6 THEN 1
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 7 AND 9 THEN 2
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 10 AND 12 THEN 3
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 1 AND 3 THEN 4
                                ELSE 0 END AS quater,
                        COUNT(DISTINCT(prop_demands.property_id)) AS collection_from_no_of_hh,
                        SUM(
                                CASE WHEN prop_demands.due_date BETWEEN '$fromDate' AND '$uptoDate' then prop_demands.amount
                                    ELSE 0
                                    END
                        ) AS current_collection,
                        SUM(
                            cASe when prop_demands.due_date <'$fromDate' then prop_demands.amount
                                ELSE 0
                                END
                            ) AS arrear_collection,
                    SUM(prop_demands.amount) AS total_collection
                    FROM prop_demands                    
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_demands.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date  BETWEEN '$fromDate' AND '$uptoDate'
                        AND prop_demands.due_date<='$uptoDate'
                    GROUP BY TO_CHAR(prop_demands.due_date::DATE,'mm')
                )collection ON collection.quater =  demands.quater
                LEFT JOIN ( 
                    SELECT CASE WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 4 AND 6 THEN 1
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 7 AND 9 THEN 2
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 10 AND 12 THEN 3
                                WHEN TO_CHAR(prop_demands.due_date::DATE,'mm')::INT BETWEEN 1 AND 3 THEN 4
                                ELSE 0 END AS quater,
                        SUM(prop_demands.amount) AS total_prev_collection
                    FROM prop_demands
                    JOIN prop_tran_dtls ON prop_tran_dtls.prop_demand_id = prop_demands.id 
                        AND prop_tran_dtls.prop_demand_id is not null 
                    JOIN prop_transactions ON prop_transactions.id = prop_tran_dtls.tran_id 
                        AND prop_transactions.status in (1,2) AND prop_transactions.property_id is not null
                    WHERE prop_demands.status =1 
                        AND prop_demands.ulb_id =$ulbId
                        " . ($wardId ? " AND prop_demands.ward_mstr_id = $wardId" : "") . "
                        AND prop_transactions.tran_date<'$fromDate'
                    GROUP BY TO_CHAR(prop_demands.due_date::DATE,'mm')
                )prev_collection ON prev_collection.quater =  demands.quater                 
                ORDER BY demands.quater          
            ";
            $select = "SELECT demands.quater,
                            COALESCE(demands.current_hh, 0::numeric) AS current_hh,   
                            COALESCE(collection.collection_from_no_of_hh, 0::numeric) AS collection_from_hh,
                            COALESCE(
                                COALESCE(demands.current_hh, 0::numeric) 
                                - COALESCE(collection.collection_from_no_of_hh, 0::numeric), 0::numeric
                            ) AS balance_hh,                       
                            COALESCE(
                                COALESCE(demands.arrear_demand, 0::numeric) 
                                - COALESCE(prev_collection.total_prev_collection, 0::numeric), 0::numeric
                            ) AS arrear_demand,
                    
                            COALESCE(prev_collection.total_prev_collection, 0::numeric) AS previous_collection,
                            COALESCE(demands.current_demand, 0::numeric) AS current_demand,
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
            $data = DB::select($select . $from);
            $data = collect($data);            
           
            $monthWise["demand"]["quater"] = $data->map(function($val)
            {
                return($val->quater);
            });
            $monthWise["demand"]["propertyCount"] = $data->map(function($val)
            {
                return($val->current_hh);
            });
            $monthWise["demand"]["arrearDemand"] = $data->map(function($val)
            {
                return round(($val->arrear_demand),2);
            });
            $monthWise["demand"]["currentDemand"] = $data->map(function($val)
            {
                return round(($val->current_demand),2);
            });
            $monthWise["demand"]["totalDemand"] = $data->map(function($val)
            {
                return round(($val->current_demand + $val->arrear_demand),2);
            });

            #-----------------------------------------
            $monthWise["collection"]["quater"] = $data->map(function($val)
            {
                return($val->quater);
            });
            $monthWise["collection"]["propertyCount"] = $data->map(function($val)
            {
                return($val->collection_from_hh);
            });
            $monthWise["collection"]["arrearCollection"] = $data->map(function($val)
            {
                return round(($val->arrear_collection),2);
            });
            $monthWise["collection"]["currentCollection"] = $data->map(function($val)
            {
                return round(($val->current_collection),2);
            });
            $monthWise["collection"]["totalCollection"] = $data->map(function($val)
            {
                return round(($val->arrear_collection + $val->current_collection),2);
            });

            #-------------------------------------------
            $monthWise["balance"]["quater"] = $data->map(function($val)
            {
                return($val->quater);
            });
            $monthWise["balance"]["propertyCount"] = $data->map(function($val)
            {
                return(($val->current_hh - $val->collection_from_hh));
            });
            $monthWise["balance"]["arrearBalance"] = $data->map(function($val)
            {
                return round(($val->arrear_demand - $val->arrear_collection),2);
            });
            $monthWise["balance"]["currentBalance"] = $data->map(function($val)
            {
                return round(($val->current_demand - $val->current_collection),2);
            });
            $monthWise["balance"]["totalBalance"] = $data->map(function($val)
            {
                return round((($val->current_demand + $val->arrear_demand)- ($val->arrear_collection + $val->current_collection)),2);
            });
            $queryRunTime = (collect(DB::getQueryLog())->sum("time"));
            return responseMsgs(true, "", $monthWise, $apiId, $version, $queryRunTime, $action, $deviceId);
        } 
        catch (Exception $e) 
        {
            return responseMsgs(false, $e->getMessage(), $request->all(), $apiId, $version, $queryRunTime, $action, $deviceId);
        }

    }

}
