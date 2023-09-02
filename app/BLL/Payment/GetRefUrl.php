<?php

namespace App\BLL\Payment;

use Carbon\Carbon;
use Exception;

/**
 * | Created On-02-09-2023 
 * | Author-Anshu Kumar
 * | Status - Closed
 */
class GetRefUrl
{
    private static $icid = 600587;
    private static $aesKey = 6000010105805020;
    private static $subMerchantId = 45;
    private static $paymentMode = 9;
    private static $baseUrl = "https://eazypayuat.icicibank.com";
    private static $returnUrl = "http://203.129.217.244/citizen";
    private static $ciphering = "AES-128-CTR";                 // Store the cipher method for encryption

    /**
     * | Generate Referal Url
     */
    public function generateRefUrl()
    {
        $todayDate = Carbon::now()->format('d/M/Y');
        $refNo = rand(3, 3);

        $mandatoryField = "$refNo|" . self::$subMerchantId . "10" . $todayDate . "|6201675668|xy|xy";
        $eMandatoryField = $this->encryptAes($mandatoryField);
        $optionalField = $this->encryptAes("X|X|X");
        $returnUrl = $this->encryptAes(self::$returnUrl);
        $eRefNo = $this->encryptAes($refNo);
        $subMerchantId = $this->encryptAes(self::$subMerchantId);
        $tranAmt = $this->encryptAes(10);
        $paymentMode = $this->encryptAes(self::$paymentMode);

        $plainUrl = self::$baseUrl . '/EazyPG?merchantid=' . self::$icid . '&mandatoryfields=' . $eMandatoryField . "&optionalfields=$optionalField" . '&returnurl=' . $returnUrl . '&ReferenceNo=' . $eRefNo
            . '&submerchantid=' . $subMerchantId . '&transactionamount=' . $tranAmt . '&paymentMode=' . $paymentMode;
        return $plainUrl;
    }

    /**
     * | Encrypt AES
     */
    public function encryptAes($string)
    {
        // Encrption AES
        // Use OpenSSl Encryption method
        $options = 0;

        // Non-NULL Initialization Vector for encryption
        $encryption_iv = '1234567891011121';

        // Use openssl_encrypt() function to encrypt the data
        $encryption = openssl_encrypt(
            $string,
            self::$ciphering,
            self::$aesKey,
            $options,
            $encryption_iv
        );

        return $encryption;
    }
}
