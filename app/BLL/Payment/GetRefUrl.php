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
    private static $returnUrl = "http://203.129.217.244/property";
    private static $ciphering = "aes-128-ecb";                 // Store the cipher method for encryption

    /**
     * | Generate Referal Url
     */
    public function generateRefUrl()
    {
        $todayDate = Carbon::now()->format('d/M/Y');
        $refNo = time() . rand();
        $mandatoryField = "$refNo|" . self::$subMerchantId . "|10|" . $todayDate . "|0123456789|xy|xy";               // 10 is transactional amount
        $eMandatoryField = $this->encryptAes($mandatoryField);
        $optionalField = $this->encryptAes("X|X|X");
        $returnUrl = $this->encryptAes(self::$returnUrl);
        $eRefNo = $this->encryptAes($refNo);
        $subMerchantId = $this->encryptAes(self::$subMerchantId);
        $tranAmt = $this->encryptAes(10);
        $paymentMode = $this->encryptAes(self::$paymentMode);

        $plainUrl = self::$baseUrl . '/EazyPG?merchantid=' . self::$icid . '&mandatory fields=' . $mandatoryField . "&optional fields=X|X|X" . '&returnurl=' . self::$returnUrl . '&Reference No=' . $refNo
            . '&submerchantid=' . self::$subMerchantId . '&transaction amount=' . "10" . '&paymode=' . self::$paymentMode;

        $encryptUrl = self::$baseUrl . '/EazyPG?merchantid=' . self::$icid . '&mandatory fields=' . $eMandatoryField . "&optional fields=$optionalField" . '&returnurl=' . $returnUrl . '&Reference No=' . $eRefNo
            . '&submerchantid=' . $subMerchantId . '&transaction amount=' . $tranAmt . '&paymode=' . $paymentMode;
        return [
            'plainUrl' => $plainUrl,
            'encryptUrl' => $encryptUrl
        ];
    }

    /**
     * | Encrypt AES
     */
    public function encryptAes($string)
    {
        // Encrption AES
        $cipher = self::$ciphering;
        $key = self::$aesKey;
        in_array($cipher, openssl_get_cipher_methods(true));
        $ivlen = openssl_cipher_iv_length($cipher);
        //echo "ivlen [". $ivlen . "]";
        $iv = openssl_random_pseudo_bytes(1);
        // echo "iv [". $iv . "]";
        $ciphertext = openssl_encrypt($string, $cipher, $key, $options = 0, "");
        return $ciphertext;
    }
}
