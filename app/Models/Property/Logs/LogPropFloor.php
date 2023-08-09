<?php

namespace App\Models\Property\Logs;

use App\Models\Property\PropFloor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPropFloor extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * | Log Of Prop floor before edition or Deletition
     */
    public function replicateFloorByPropFloors($propFloorId): void
    {
        $floor = PropFloor::findOrFail($propFloorId);
        $loggingFloors = $floor->replicate();
        $loggingFloors->setTable('log_prop_floors');
    }
}
