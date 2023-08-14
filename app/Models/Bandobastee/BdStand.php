<?php

namespace App\Models\Bandobastee;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BdStand extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $_applicationDate;

    public function __construct()
    {
        $this->_applicationDate = Carbon::now()->format('Y-m-d H:i:s');
    }

    /**
     * | Get standlist by category and ULB wise
     */
    public function listStands($categoryId, $ulb)
    {
        return BdStand::select('id', 'stand_name')
            ->where(['status' => '1', 'stand_category_id' => $categoryId, 'ulb_id' => $ulb])
            ->orderBy('id')
            ->get();
    }

    /**
     * | Get Master data for bandobastee
     */
    public function masters($ulbId)
    {
        return DB::table('bd_stands')
            ->select(
                "bd_stands.id",
                // DB::raw('CONCAT(UPPER(SUBSTRING(ref_adv_paramstrings.string_parameter,1,1)),LOWER(SUBSTRING(ref_adv_paramstrings.string_parameter,2))) as string_parameter'),
                "bd_stands.stand_name",
                "c.stand_category",
                "bd_stands.stand_category_id"
            )
            ->leftJoin('bd_stand_categories as c', 'c.id', '=', 'bd_stands.stand_category_id')
            ->where('bd_stands.status', '1')
            ->where('bd_stands.ulb_id', $ulbId)
            ->orderBy('bd_stands.id', 'Asc')
            ->get();
    }
}
