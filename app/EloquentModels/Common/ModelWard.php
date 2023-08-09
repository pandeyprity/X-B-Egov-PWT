<?php
namespace App\EloquentModels\Common;

use App\Models\UlbWardMaster;
use Exception;
use Illuminate\Support\Facades\DB;

class ModelWard
{
    private $obj;
    public function __construct()
    {
        $this->obj = new UlbWardMaster();
    }
    public function getAllWard(int $ulb_id)
    {
        try{            
            return $this->obj->select("id",
                            DB::RAW("CASE WHEN old_ward_name IS NULL THEN CAST(ward_name AS TEXT) 
                                        ELSE old_ward_name 
                                        END AS ward_name")
                            )
            ->where("ulb_id",$ulb_id)
            ->where("ulb_ward_masters.status",1)
            ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }

    public function getOldWard(int $ulb_id)
    {
        try{
            return $this->obj->select(DB::raw("min(id) as id,ward_name"))
            ->where("ulb_id",$ulb_id)
            ->where("ulb_ward_masters.status",1)
            ->groupBy("ward_name")
            ->orderBy("ward_name")
            ->get();
        }
        catch (Exception $e)
        {
            echo $e->getMessage();
        }
    }
}