<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;
use Illuminate\Support\Facades\DB;

class RefPropConstructionType extends Model
{
    use HasFactory;

    public function propConstructionType()
    {
        return RefPropConstructionType::select(
            'id',
            DB::raw('INITCAP(construction_type) as construction_type')
        )
            ->where('status', 1)
            ->get();
    }
}
