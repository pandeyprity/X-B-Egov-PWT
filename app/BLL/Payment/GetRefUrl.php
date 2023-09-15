<?php

namespace App\BLL\Payment;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * | Created On-02-09-2023 
 * | Author-Anshu Kumar
 * | Status - Closed
 * | Final Url-https://eazypayuat.icicibank.com/EazyPGVerify?ezpaytranid=2309111661222&amount=&paymentmode=&merchantid=136082&trandate=&pgreferenceno=  // tranid is ref no
 */
class GetRefUrl
{
    // private static $icid = 600587;
    private static $icid = 136082;                                                      // Merchant Id
    // private static $aesKey = 6000010105805020;
    private static $aesKey = 1300011160805020;
    private static $subMerchantId = 45;
    private static $paymentMode = 9;
    private static $baseUrl = "https://eazypayuat.icicibank.com";
    private static $returnUrl = "http://203.129.217.244/property";
    private static $ciphering = "aes-128-ecb";                                          // Store the cipher method for encryption
    public $_refNo;
    public $_refUrl;

    /**
     * | Generate Referal Url
     */
    public function generateRefUrl()
    {
        $todayDate = Carbon::now()->format('d/M/Y');
        $refNo = time() . rand();
        $this->_refNo = $refNo;
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
        $this->_refUrl = $encryptUrl;
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


    
    /**
     * | Get the Payment Status and data 
     */
    public function getPaymentStatusByUrl()
    {
        # calling the http request for Payment request
        // https://eazypayuat.icicibank.com/EazyPGVerify?ezpaytranid=2309111661222
        // // &amount=
        // // &paymentmode=
        // // &merchantid=136082
        // // &trandate=
        // // &pgreferenceno=16945076411108222585

        // // Http::->post("$petApi->end_point", $transfer);
    }

}
