<?php

namespace App\Models\Cluster;

use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cluster extends Model
{
    use HasFactory;

    /**
     * | ----------------- saving new data in the cluster/master ------------------------------- |
     * | @param request
     * | @var userId
     * | @var ulbId
     * | @var newCluster
     * | Operation : saving the data of cluster   
     * | rating - 1
     * | time - 477ms
     */
    public function saveClusterDetails($request)
    {
        $userId = auth()->user()->id;
        $ulbId = auth()->user()->ulb_id;

        $newCluster = new Cluster();
        $newCluster->ulb_id                 = $ulbId;
        $newCluster->user_id                = $userId;
        $newCluster->cluster_name           = $request->clusterName;
        $newCluster->cluster_type           = $request->clusterType;
        $newCluster->address                = $request->clusterAddress;
        $newCluster->mobile_no              = $request->clusterMobileNo;
        $newCluster->authorized_person_name = $request->clusterAuthPersonName;
        $newCluster->ward_mstr_id           = $request->wardNo;
        $newCluster->new_ward_mstr_id       = $request->newWardNo;
        $newCluster->filled_ulb_id          = $request->ulbId;
        $newCluster->save();
    }


    /**
     * | ------------------------- updating the cluster data according to cluster id/master ------------------------------- |
     * | @param request
     * | @var userId
     * | @var ulbId
     * | Operation : updating the cluster data whith new data
     * | rating - 1
     * | time - 428 ms
     */
    public function editClusterDetails($request)
    {
        $userId = auth()->user()->id;
        $ulbId = auth()->user()->ulb_id;

        if (is_null($request->status)) {
            Cluster::where('id', $request->id)
                ->update([
                    'ulb_id'                    => $ulbId,
                    'user_id'                   => $userId,
                    'cluster_name'              => $request->clusterName,
                    'cluster_type'              => $request->clusterType,
                    'address'                   => $request->clusterAddress,
                    'mobile_no'                 => $request->clusterMobileNo,
                    'authorized_person_name'    => $request->clusterAuthPersonName,
                    'ward_mstr_id'              => $request->wardNo,
                    'new_ward_mstr_id'          => $request->newWardNo,
                    'filled_ulb_id'             => $request->ulbId
                ]);
            return responseMsg(true, "Cluster Saved without status!", "");
        }
        Cluster::where('id', $request->id)
            ->update([
                'status'                    => $request->status,
                'ulb_id'                    => $ulbId,
                'user_id'                   => $userId,
                'cluster_name'              => $request->clusterName,
                'cluster_type'              => $request->clusterType,
                'address'                   => $request->clusterAddress,
                'mobile_no'                 => $request->clusterMobileNo,
                'authorized_person_name'    => $request->clusterAuthPersonName,
                'ward_mstr_id'              => $request->wardNo,
                'new_ward_mstr_id'          => $request->newWardNo,
                'filled_ulb_id'             => $request->ulbId
            ]);
        return responseMsg(true, "Cluster Saved with status!", "");
    }


    /**
     * | -------------------------Get All the Cluster Detils from the Cluster Table------------------------------- |
     * | Operation : fetch all the cluster data from the Table
     * | rating - 1
     */
    public function allClusters()
    {
        return Cluster::select(
            'clusters.id',
            'cluster_name AS name',
            'cluster_type AS type',
            'address',
            'mobile_no AS mobileNo',
            'authorized_person_name AS authPersonName',
            'clusters.status',
            'ward_mstr_id as oldWard',
            'new_ward_mstr_id as newWard',
            'ulb_ward_masters.ward_name as oldWardName',
            'u.ward_name as newWardName',
        )
            ->leftjoin('ulb_ward_masters', 'ulb_ward_masters.id', '=', 'clusters.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as u', 'u.id', '=', 'clusters.new_ward_mstr_id')
            ->where('clusters.status', 1)
            ->where('clusters.ulb_id', auth()->user()->ulb_id ?? 2)
            ->orderBy('cluster_name')
            ->get();
    }


    /**
     * | ----------------- deleting the data of the cluster/master ------------------------------- |
     * | @param request : request clusterId AS id
     * | Operation : soft delete of the respective detail 
     * | rating - 1
     * | time - 320ms
     */
    public function deleteClusterData($request)
    {
        Cluster::where('id', $request->id)
            ->update(['status' => "0"]);
    }


    /**
     * | Get Active cluster 
     */
    public function checkActiveCluster($clusterId)
    {
        return Cluster::where('id', $clusterId)
            ->where('status', 1)
            ->first();
    }

    /**
     * | Get cluster Details by id
     */
    public function getClusterDtlsById($id)
    {
        return DB::table('clusters as c')
            ->select('c.*', 'ow.ward_name as old_ward', 'nw.ward_name as new_ward')
            ->leftJoin('ulb_ward_masters as ow', 'ow.id', 'c.ward_mstr_id')
            ->leftJoin('ulb_ward_masters as nw', 'ow.id', 'c.new_ward_mstr_id')
            ->where('c.id', $id)
            ->first();
    }
}
