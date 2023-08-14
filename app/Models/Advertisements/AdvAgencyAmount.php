<?php

namespace App\Models\Advertisements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvAgencyAmount extends Model
{
    use HasFactory;

    /** 
     * | Get Agency Price for Registration & Renewal For application
     */
    public function getAgencyPrice($ulb_id,$application_type){
       return AdvAgencyAmount::select('amount')->where('ulb_id',$ulb_id)->where('application_type',$application_type)->first();
    }
}
