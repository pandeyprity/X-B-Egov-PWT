<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevDailycollectiondetail extends Model
{
    use HasFactory;

    protected $fillable = [
        "collection_id", "module_id", "demand", "deposit_amount", "cheq_dd_no",
        "bank_name", "deposit_mode", "application_no", "transaction_id"
    ];

    public function store($req)
    {
        $req = $req->toarray();
        $revDailycollectiondetail =  RevDailycollectiondetail::create($req);
        return $revDailycollectiondetail->id;
    }
}
