<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterSiteInspectionsScheduling extends Model
{
    use HasFactory;

    /**
     * | Save the Sheduled Date and Time of the Site Inspection
     * | Create a record for further Edit in site inspection
     * | @param request
     */
    public function saveSiteDateTime($request)
    {
        $inspectionDate = date('Y-m-d', strtotime($request->inspectionDate));
        $mWaterSiteInspection = new WaterSiteInspectionsScheduling();
        $mWaterSiteInspection->apply_connection_id    =   $request->applicationId;
        $mWaterSiteInspection->inspection_date        =   $inspectionDate;
        $mWaterSiteInspection->inspection_time        =   $request->inspectionTime;
        $mWaterSiteInspection->save();
    }

    /**
     * | Get Site inspection Details by ApplicationId
     * | According to verification status false
     * | @param applicationId
     */
    public function getInspectionById($applicationId)
    {
        return WaterSiteInspectionsScheduling::where('apply_connection_id', $applicationId)
            ->where('status', true)
            ->where('site_verify_status', 0)
            ->orderByDesc('id');
    }


    /**
     * | Cancell the Sheduled Date And time
     * | @param applicationId
     */
    public function cancelInspectionDateTime($applicationId)
    {
        $refData = WaterSiteInspectionsScheduling::where('apply_connection_id', $applicationId)
            ->orderByDesc('id')
            ->first();
        $refData->status = false;
        $refData->save();
    }


    /**
     * | Save the Inspection Status after the site Inspection
     * | Make the Site Verification Status true
     * | @param request
     */
    public function saveInspectionStatus($request)
    {
        $refData = WaterSiteInspectionsScheduling::where('apply_connection_id', $request->applicationId)
            ->where('site_verify_status', 0)
            ->orderByDesc('id')
            ->first();
        $refData->site_verify_status = true;
        $refData->save();
    }

    /**
     * | Get Site inspection Details by ApplicationId
     * | @param applicationId
     */
    public function getInspectionData($applicationId)
    {
        return WaterSiteInspectionsScheduling::where('apply_connection_id', $applicationId)
            ->where('status', 1)
            ->orderByDesc('id');
    }
}
