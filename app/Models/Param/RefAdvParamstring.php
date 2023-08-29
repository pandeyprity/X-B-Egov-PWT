<?php

namespace App\Models\Param;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RefAdvParamstring extends Model
{
    use HasFactory;
    protected $guarded = [];

    // Get all Masters 
    public function masters()
    {
        return DB::table('ref_adv_paramstrings')
            ->select(
                "ref_adv_paramstrings.id",
                DB::raw('CONCAT(UPPER(SUBSTRING(ref_adv_paramstrings.string_parameter,1,1)),LOWER(SUBSTRING(ref_adv_paramstrings.string_parameter,2))) as string_parameter'),
                // "ref_adv_paramstrings.string_parameter",
                "c.param_category",
                "ref_adv_paramstrings.param_category_id"
            )
            ->leftJoin('ref_adv_paramcategories as c', 'c.id', '=', 'ref_adv_paramstrings.param_category_id')
            ->where('ref_adv_paramstrings.status','1')
            ->orderBy('ref_adv_paramstrings.id','Asc')
            ->get();
    }
}
