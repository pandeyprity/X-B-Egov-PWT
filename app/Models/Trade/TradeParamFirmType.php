<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamFirmType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public function activeApplication()
    {
        return $this->hasMany(ActiveTradeLicence::class,'firm_type_id',"id")->where("is_active",true);
    }
    public function rejectedApplication()
    {
        return $this->hasMany(RejectedTradeLicence::class,'firm_type_id',"id")->where("is_active",true);
    }
    public function approvedApplication()
    {
        return $this->hasMany(TradeLicence::class,'firm_type_id',"id");
    }
    public function renewalApplication()
    {
        return $this->hasMany(TradeRenewal::class,'firm_type_id',"id");
    }

    public Static function List()
    {
        return self::select("id","firm_type")
                ->where("status",1)
                ->get();
    }
}
