<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

if (!function_exists('WHATSAPPJHGOVT')) {
    function WHATSAPPJHGOVT($mobileno, $templateid, array $message = [],)
    {
        $bearerToken = Config::get("NoticeConstaint.WHATSAPP_TOKEN");
        $numberId = Config::get("NoticeConstaint.WHATSAPP_NUMBER_ID");
        $url = Config::get("NoticeConstaint.WHATSAPP_URL");


        $url = Config::get("NoticeConstaint.WHATSAPP_URL");  
        $data = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => "+91$mobileno",                             //<--------------------- here
            "type" => "template",
            "template" => [
                "name" => "$templateid",
                "language" => [
                    "code" => "en_US"                           //<-------------------- en:English/ en_US:EngPDf
                ],
                "components" => [
                    ($message
                        ?
                    "code" => "en_US"
                ],
                "components" => [
                    ($message
                    ?
                    (
                        ($message['content_type']??"")=="pdf"?
                        (
                            ($message['content_type'] ?? "") == "pdf"
                            ?
                            ([
                                "type" => "header",
                                "parameters" => [
                                    [
                                        "type" => "document",
                                        "document" => $message[0]
                                        // [
<<<<<<< HEAD
                                        //     "link" => "http://www.xmlpdf.com/manualfiles/hello-world.pdf",
                                        //     "filename" => "Payment Receipt.pdf"
                                        // ]
                                    ]
                                ]
                            ])
                            : (($message['content_type'] ?? "") == "text" ?
                                ([
=======
                                        //     "link"=> "http://www.xmlpdf.com/manualfiles/hello-world.pdf",
                                        //     "filename"=> "Payment Receipt.pdf"
                                        // //     // $message[0]
                                        //     ]
                                    ]
                                ]
                            ]
                        )
                        :
                        (
                            ($message['content_type']??"")=="text"?
                            (
                                [
>>>>>>> 87f2b272b9e1aa17d22d42dd943feb7ac2f899d7
                                    "type" => "body",
                                    "parameters" => array_map(function ($val) {
                                        return ["type" => "text", "text" => $val];
                                    }, $message[0] ?? [])
                                ]
                                )
                                :
                                "")
                        )
                        : ""),
                ]
            ]
<<<<<<< HEAD
        ];
        $result = Http::withHeaders([

            "Authorization" => "Bearer $bearerToken",
            "contentType" => "application/json"

        ])->post($url . $numberId . "/messages", $data);
        $responseBody = json_decode($result->getBody(), true);
        if (isset($responseBody["error"])) {
            $response = ['response' => false, 'status' => 'failure', 'msg' => $responseBody];
        } else {
            $response = ['response' => true, 'status' => 'success', 'msg' => $responseBody];
=======
        ];       
        $result = Http::withHeaders([
    
            "Authorization" => "Bearer $bearerToken",
            "contentType" => "application/json"

        ])->post($url.$numberId."/messages", $data);
        $responseBody = json_decode($result->getBody(),true);
        if (isset($responseBody["error"]))
        {
            $response = ['response'=>false, 'status'=> 'failure', 'msg'=>$responseBody];
>>>>>>> 87f2b272b9e1aa17d22d42dd943feb7ac2f899d7
        }

        return $response;
    }
}

if (!function_exists('Whatsapp_Send')) {
    function Whatsapp_Send($mobileno, $templateid, array $message = [], $lang = "en")
    {
        // $res=WHATSAPPJHGOVT("9708846652", $templateid,$message);
        $res = WHATSAPPJHGOVT($mobileno, $templateid, $message, $lang);
        return $res;
    }
}
