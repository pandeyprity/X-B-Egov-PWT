<?php

namespace App\Models\Water;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaterParamPipelineType extends Model
{
    use HasFactory;
    protected $connection = 'pgsql_water';

    /**
     * | Get Lsit Of peline type 
     */
    public function getWaterParamPipelineType()
    {
        return WaterParamPipelineType::select(
            'id',
            'pipeline_type'
        )
            ->where('status', true)
            ->get();
    }
}
