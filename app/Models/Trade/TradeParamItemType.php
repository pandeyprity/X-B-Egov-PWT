<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeParamItemType extends Model
{
    use HasFactory;
    public $timestamps=false;

    public Static function List($all=false)
    {
        if($all)
        {
            return self::select("id","trade_item","trade_code")
                ->where("status",1)
                ->where("id","<>",187)
                ->get();
        }
        else
        {
            return self::select("id","trade_item","trade_code")
                ->where("status",1)
                ->get();

        }
    }
    public Static function itemsById($id)
    {        
        if(!$id)
        {
            $id="0";
        }
        $id = explode(",",$id);
        $items = self::select("*")
            ->whereIn("id",$id)
            ->get();
        return $items;
               
    }
}
