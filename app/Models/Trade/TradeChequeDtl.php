<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradeChequeDtl extends Model
{
    use HasFactory;
    public $timestamps = false;

    public function chequeDtlById($request)
    {
        return TradeChequeDtl::select('*')
            ->where('id', $request->chequeId)
            ->where('status', 2)
            ->first();
    }
}
