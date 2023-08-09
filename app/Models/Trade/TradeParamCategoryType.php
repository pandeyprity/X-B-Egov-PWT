<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamCategoryType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public function activeApplication()
    {
        return $this->hasMany(ActiveTradeLicence::class,'category_type_id',"id")->where("is_active",true);
    }
    public function rejectedApplication()
    {
        return $this->hasMany(RejectedTradeLicence::class,'category_type_id',"id")->where("is_active",true);
    }
    public function approvedApplication()
    {
        return $this->hasMany(TradeLicence::class,'category_type_id',"id");
    }
    public function renewalApplication()
    {
        return $this->hasMany(TradeRenewal::class,'category_type_id',"id");
    }

    public static function List()
    {
        return self::select("id","category_type")
                ->where("status",1)
                ->get();
                
    }
}
