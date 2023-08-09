<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RejectedTradeOwner extends Model
{
    use HasFactory;
    public $timestamps=false;

    public function application()
    {
        return $this->belongsTo(RejectedTradeLicence::class,'temp_id',"id");
    }

    public function docDtl()
    {
        return $this->hasManyThrough(WfActiveDocument::class,RejectedTradeLicence::class,'id',"active_id","temp_id","id")
                ->whereColumn("wf_active_documents.workflow_id","rejected_trade_licences.workflow_id")
                ->where("wf_active_documents.owner_dtl_id",$this->id)
                ->where("wf_active_documents.status",1);
    }
}
