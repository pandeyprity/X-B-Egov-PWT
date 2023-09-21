<?php

namespace App\Models\Trade;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TradePinelabPayResponse extends Model
{
    use HasFactory;
    public $timestamps=false;
    protected $connection;
    protected $guarded = [];
    public function __construct($DB=null)
    {
       $this->connection = $DB ? $DB:"pgsql_trade";
    }

    public function insert(array $data)
    {
        $reqs = [
            "request_id"      => $data["requestId"]??null,
            'temp_id'         => $data["applicationId"]??null,
            'module_id'       => $data["moduleId"]??null,
            'merchant_id'     => $data["merchantId"]??null,
            'amount'          => $data["amount"]??null,
            'order_id'        => $data["orderId"]??null,
            'payment_id'      => $data["paymentId"]??null,
            'error_code'      => $data["errorCode"]??null,
            'error_desc'      => $data["errorDesc"]??null, 
            "error_source"    => $data["errorSource"]??null, 
            "error_stap"      => $data["errorStap"]??null, 
            "error_reason"    => $data["errorReason"]??null,       
            'ip_address'      => $data["ipAddress"]??null,
            'citizen_id'      => $data["citizenId"]??null,
            'response_data'   => $data["responseData"]??null,
        ];
        if(isset($data["status"]))
        {            
            $reqs['status']          = $data["status"];
        }
        return TradePinelabPayResponse::create($reqs)->id;  
    }

    // public function edit(array $data)
    // {
    //     $requestData = self::find($data["id"]);
    //     $reqs = [
    //         'payment_id'      => $data["paymentId"]??null,
    //         'signature'       => $data["signature"]??null,
    //         'error_reason'    => $data["errorReason"]??null,
    //     ];
    //     if(isset($data['status']))
    //     {
    //         $reqs["status"]= $data['status']?1:3;
    //     }
    //     return $requestData->update($reqs);
    // }
}
