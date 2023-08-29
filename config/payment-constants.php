<?php

/**
 * | Created On-14-02-2022 
 * | Created By-Anshu Kumar
 * | Created for- Payment Constants Masters
 */
return [
    'PAYMENT_MODE' => [
        '1' => 'ONLINE',
        '2' => 'NETBANKING',
        '3' => 'CASH',
        '4' => 'CHEQUE',
        '5' => 'DD',
        '6' => 'NEFT'
    ],

    'PAYMENT_MODE_OFFLINE' => [
        'CASH',
        'CHEQUE',
        'DD',
        'NEFT'
    ],

    "VERIFICATION_PAYMENT_MODES" => [           // The Verification payment modes which needs the verification
        "CHEQUE",
        "DD",
        "NEFT"
    ],


    'PAYMENT_OFFLINE_MODE_WATER' => [
        'Cash',
        'Cheque',
        'DD',
        'Neft'
    ],

    "VERIFICATION_PAYMENT_MODE" => [
        'Cheque',
        'DD',
    ],

    'ONLINE' => "Online",
    "PAYMENT_OFFLINE_MODE" => [
        "1" => "Cash",
        "2" => "Cheque",
        "3" => "DD",
        "4" => "Neft",
        "5" => "Online"
    ],
    "REF_PAY_MODE" => [
        "CASH"      => "Cash",
        "CHEQUE"    => "Cheque",
        "DD"        => "DD",
        "NEFT"      => "Neft",
        "ONLINE"    => "Online"
    ],
    "TRAN_PARAM_ID" => 37
];
