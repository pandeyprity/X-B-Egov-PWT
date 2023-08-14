<?php

namespace App\Models\Markets;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MarRejectedDharamshala extends Model
{
    use HasFactory;
            
     /**
     * | Get Application Reject List by Role Ids
     */
    public function listRejected($citizenId)
    {
        return MarRejectedDharamshala::where('mar_rejected_dharamshalas.citizen_id', $citizenId)
            ->select(
                'mar_rejected_dharamshalas.id',
                'mar_rejected_dharamshalas.application_no',
                'mar_rejected_dharamshalas.application_date',
                'mar_rejected_dharamshalas.entity_address',
                'mar_rejected_dharamshalas.rejected_date',
                'mar_rejected_dharamshalas.citizen_id',
                'um.ulb_name as ulb_name',
            )
            ->join('ulb_masters as um', 'um.id', '=', 'mar_rejected_dharamshalas.ulb_id')
            ->orderByDesc('mar_rejected_dharamshalas.id')
            ->get();
    }    
    
    /**
    * | Get All Application Reject List
    */
   public function rejectedApplication()
   {
       return MarRejectedDharamshala::select(
               'id',
               'application_no',
               'application_date',
               'entity_address',
               'rejected_date',
               'citizen_id',
               'ulb_id',
           )
           ->orderByDesc('id')
           ->get();
   }

   /**
    * | Reject List For Report
    */
   public function rejectListForReport(){
    return MarRejectedDharamshala::select('id', 'application_no', 'applicant', 'application_date', 'application_type', 'entity_ward_id', 'rule', 'organization_type','ulb_id','license_year',DB::raw("'Reject' as application_status"));
   }
}
