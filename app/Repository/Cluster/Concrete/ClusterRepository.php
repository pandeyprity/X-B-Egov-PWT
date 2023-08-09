<?php

namespace App\Repository\Cluster\Concrete;

use App\Models\Cluster\Cluster;
use App\Models\Property\PropProperty;
use App\Repository\Cluster\Interfaces\iCluster;

/**
 * | Property Cluster
 * | Created By - Sam kerketta
 * | Created On- 23-11-2022 
 * | Cluster Related All functions Are Listed Below.
 */

class ClusterRepository implements iCluster
{
    /**
     * | ----------------- Collecting all data of the cluster according to cluster id /returning/master ------------------------------- |
     * | @param request
     * | @var mdetails
     * | @var detailsById
     * | @var obj : object for the modlel (Cluster)
     * | @return detailsById : List of cluster by Id
     * | Operation : read table cluster and returning the data according to id  
     * | rating - 1
     * | time - 385 ms
        | Serial No : 
     */
    public function getClusterById($request)
    {
        $obj = new Cluster();
        $detailsById = $obj->allClusters()
            ->where('id', $request->id)
            ->first();

        if (!empty($detailsById)) {
            return $this->success($detailsById);
        }
        return  $this->noData();
    }


    /**
     * | ----------------- details of the respective holding NO ------------------------------- |
     * | @param request
     * | @var holdingCheck
     * | @return holdingCheck : property details by holding
     * | Operation : returning details according to the holdin no 
     * | Rating : 2
     * | Time :
        | Serial No :  
        | Removal 
     */
    public function detailsByHolding($holdingNo)
    {
        $holdingCheck = PropProperty::join('prop_owners', 'prop_owners.saf_id', '=', 'prop_properties.saf_id')
            ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->select(
                'prop_properties.new_ward_mstr_id AS wardId',
                'prop_owners.owner_name AS ownerName',
                'prop_properties.prop_address AS address',
                'ref_prop_types.property_type AS propertyType',
                'prop_owners.mobile_no AS mobileNo',
                'prop_properties.holding_no'
            )
            ->where('prop_properties.holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->orWhere('prop_properties.new_holding_no', 'LIKE', '%' . $holdingNo . '%')
            ->where('prop_properties.status', '1')
            ->where('ulb_id', auth()->user()->ulb_id)
            ->get();
        return $this->success($holdingCheck);
    }

    /**
     * | ----------------- respective holding according to cluster ID ------------------------------- |
     * | @param request
     * | @var clusterDetails
     * | @return clusterDetails
     * | Operation : returning the details according to the cluster Id
     * | Time: 385ms
     * | Rating - 2
        | Serial No : 
        | Removal
     */
    public function holdingByCluster($request)
    {
        $clusterDetails = PropProperty::join('prop_owners', 'prop_owners.saf_id', '=', 'prop_properties.saf_id')
            ->join('ref_prop_types', 'ref_prop_types.id', '=', 'prop_properties.prop_type_mstr_id')
            ->select(
                'prop_properties.new_ward_mstr_id AS wardId',
                'prop_owners.owner_name AS ownerName',
                'prop_properties.prop_address AS address',
                'ref_prop_types.property_type AS propertyType',
                'prop_owners.mobile_no AS mobileNo'
            )
            ->where('prop_properties.cluster_id', $request->clusterId)
            ->where('prop_properties.status', '1')
            ->get();

        if (empty($clusterDetails['0'])) {
            return $this->noData();
        }
        return $this->success($clusterDetails);
    }

    /**
     * | ----------------- saving the respective holding to the cluster ------------------------------- |
     * | @param request
     * | @var checkActiveCluster
     * | @var notActive 
     * | Operation : Saving the Cluster to respective holdings / checking the Active cluster 
     * | Time : 385ms
     * | rating - 2
     */
    public function saveHoldingInCluster($request)
    {
        $notActive = "Not a valid cluter ID!";
        $checkActiveCluster =  $this->checkActiveCluster($request->clusterId);

        if ($checkActiveCluster == "1") {
            PropProperty::where('new_holding_no', $request->holdingNo)
                ->update([
                    'cluster_id' => $request->clusterId
                ]);
            return $this->success($request->holdingId);
        }
        return $this->failure($notActive);
    }


    /**
     * | ----------------- calling function for the cheking of active cluster ------------------------------- |
     * | @param clusterID
     * | @var checkCluster
     * | Operation : finding cluster is Active
     * | rating - 1
     */
    public function checkActiveCluster($clusterID)
    {
        $checkCluster = Cluster::select('id')
            ->where('id', $clusterID)
            ->where('status', 1)
            ->get();
        if (empty($checkCluster['0'])) {
            return ("0");
        }
        return ("1");
    }

















    #----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------#

    /**
     * | ----------------- Common funtion for the return components in success ------------------------------- |
     * | @param req
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function success($req)
    {
        $mreturn = responseMsg(true, "Operation Success!", $req, "");
        return $mreturn;
    }

    /**
     * | ----------------- Common funtion for the return component in failer ------------------------------- |
     * | @param req
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function failure($req)
    {
        $mreturn = responseMsg(false, "Operation Failer!", $req);
        return (object)$mreturn;
    }

    /**
     * | ----------------- Common funtion for No data found in database ------------------------------- |
     * | @var mreturn
     * | Operation : returning the messge using (responseMsg)
     */
    public function noData()
    {
        $mreq = "Data Not Found!";
        $mreturn = responseMsg(false, "Operation Failer!", $mreq);
        return (object)$mreturn;
    }
}
