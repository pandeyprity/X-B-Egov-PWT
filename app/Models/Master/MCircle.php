<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MCircle extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'm_circle';

    public function getCircleNameByUlbId($circleName, $ulbId)
    {
        return MCircle::select('*')
            ->where('ulb_id', $ulbId)
            ->where('circle_name', $circleName)
            ->get();
    }

    public function getCircleByUlbId($ulbId)
    {
        return MCircle::select('*')
            ->where('ulb_id', $ulbId)
            ->get();
    }

    public function getAllActive()
    {
        return MCircle::select('*')
            ->where('is_active', 1)
            ->get();
    }
}
