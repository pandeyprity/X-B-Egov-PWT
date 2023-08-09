<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Facades\DB;

class RefPropUsageType extends Model
{
    use HasFactory;

    public function propUsageType()
    {
        return RefPropUsageType::select(
            'id',
            DB::raw('INITCAP(usage_type) as usage_type'),
            'usage_code'
        )
            ->where('status', 1)
            ->get();
    }


    public function propAllUsageType()
    {
        return RefPropUsageType::select(
            'id',
            DB::raw('INITCAP(usage_type) as usage_type'),
            'usage_code'
        )
            ->get();
    }
}
