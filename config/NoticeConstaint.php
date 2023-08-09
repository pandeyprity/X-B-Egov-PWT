<?php

/**
 * | Created On-28-03-2023 
 * | Created By-Sandeep Bara
 * | For Notice Module
 */

 return [
    "WHATSAPP_TOKEN"        =>env("WHATSAPP_TOKEN","xxx"),
    "WHATSAPP_NUMBER_ID"    =>env("WHATSAPP_NUMBER_ID","xxx"),
    "WHATSAPP_URL"          =>env("WHATSAPP_URL","xxx"),
    
    "CITIZEN"       => "Citizen",
    "NOTICE_REF_TABLE" => "notice_applications",
    "NOTICE_RELATIVE_PATH" => "Uploads/Notice",
    "APPLICATION_NO_GENERATOR_ID" => "22",
    "NOTICE_NO_GENERATOR_ID" => "23",

    "NOTICE-TYPE" =>
    [
        "GENERAL NOTICE"    =>  "1",
        "DENIAL NOTICE"       =>  "2",
        "PAYMENT RELATED NOTICE"     =>  "3",
        "ILLEGAL OCCUPATION NOTICE"     =>  "4"
    ],
    "NOTICE-TYPE-BY-ID" =>
    [
        "1" =>  "GENERAL NOTICE",
        "2" =>  "DENIAL NOTICE",
        "3" =>  "PAYMENT RELATED NOTICE",
        "4" =>  "ILLEGAL OCCUPATION NOTICE"
    ],
    "NOTICE-MODULE"=>
    [
        "PROPERTY"      => "1",
        "WATER"         => "2",
        "TRADE"         => "3",
        "SWM"           => "4",
        "SOLID WASTE USER CHARGE" => "4",
        "ADVERTISEMENT" => "5",
    ],
    "MODULE-TYPE"=> ["PROPERTY","WATER","TRADE","SOLID WASTE USER CHARGE","ADVERTISEMENT"],
];