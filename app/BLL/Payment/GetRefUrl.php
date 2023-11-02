<?php

namespace App\BLL\Payment;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

/**
 * | Created On-02-09-2023 
 * | Author-Anshu Kumar
 * | Status - Closed
 * | Final Url-https://eazypayuat.icicibank.com/EazyPGVerify?ezpaytranid=2309111661222&amount=&paymentmode=&merchantid=136082&trandate=&pgreferenceno=16945076411108222585  // tranid is ref no
 */
class GetRefUrl
{
    // private static $icid = 600587;
    // private static $icid = 136082;                                                       // Merchant Id uat
    private static $icid = 378278;                                                          // live
    // private static $aesKey = 6000010105805020;
    // private static $aesKey = 1300011160805020;                                           // Uat
    private static $aesKey = 3705200682705002;                                              // Live
    private static $subMerchantId = 45;
    private static $paymentMode = 9;
    private static $baseUrl = "https://eazypay.icicibank.com";                       // https://eazypayuat.icicibank.com
    private static $returnUrl = "https://modernulb.com/property/payment-success/87878787";                   // http://203.129.217.62:82/api/payment/v1/collect-callback-data
    private static $ciphering = "aes-128-ecb";                                                                  // Store the cipher method for encryption
    public $_tranAmt;
    public $_refNo;
    public $_refUrl;

    /**
     * | Generate Referal Url
     */
    public function generateRefUrl($req)
    {
        $todayDate          = Carbon::now()->format('d/M/Y');
        $refNo              = time() . rand();
        $this->_refNo       = $refNo;
        $tranAmt            = $req->amount;                                                                            // Remove the static amount
        // $mandatoryField     = "$refNo|" . self::$subMerchantId . "|$tranAmt|" . $todayDate . "|0123456789|xy|xy";               // 10 is transactional amount
        $mandatoryField     = "$refNo|" . self::$subMerchantId . "|$tranAmt|" . "1";               // 10 is transactional amount
        $eMandatoryField    = $this->encryptAes($mandatoryField);
        $optionalField      = $this->encryptAes("X|X|X");
        $returnUrl          = $this->encryptAes(self::$returnUrl);
        $eRefNo             = $this->encryptAes($refNo);
        $subMerchantId      = $this->encryptAes(self::$subMerchantId);
        $eTranAmt           = $this->encryptAes($tranAmt);
        $paymentMode        = $this->encryptAes(self::$paymentMode);

        $plainUrl = self::$baseUrl . '/EazyPG?merchantid=' . self::$icid . '&mandatory fields=' . $mandatoryField . "&optional fields=X|X|X" . '&returnurl=' . self::$returnUrl . '&Reference No=' . $refNo
            . '&submerchantid=' . self::$subMerchantId . '&transaction amount=' . "$tranAmt" . '&paymode=' . self::$paymentMode;

        $encryptUrl = self::$baseUrl . '/EazyPG?merchantid=' . self::$icid . '&mandatory fields=' . $eMandatoryField . "&optional fields=$optionalField" . '&returnurl=' . $returnUrl . '&Reference No=' . $eRefNo
            . '&submerchantid=' . $subMerchantId . '&transaction amount=' . $eTranAmt . '&paymode=' . $paymentMode;
        $this->_refUrl = $encryptUrl;
        return [
            'plainUrl'      => $plainUrl,
            'encryptUrl'    => $encryptUrl
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
        // &amount=
        // &paymentmode=
        // &merchantid=136082
        // &trandate=
        // &pgreferenceno=16945076411108222585

        // Http::->post("$petApi->end_point", $transfer);

        // https://eazypayuat.icicibank.com/EazyPG?merchantid=136082&mandatory fields=8+zD9Frb3bx+M8s1/1y//ymDXvTCLhON9Sxi1KftfI/6jiy1PPavcxwnhOLwTYlRgeyF3rsKUcias1KbX4wJiQ==&optional fields=h4UMk/cXKHxuF078YudPmA==&returnurl=p8HR4AwfAUB/HLkeYYSwhK/JM3Y1K/NyWYDWX8UwKpphWIcOauBRZo13tlLA1KAu&Reference No=8+zD9Frb3bx+M8s1/1y///L6yuC9oo312vy8Fu8HkMI=&submerchantid=EJpmy96shfiIc7fg4quxtQ==&transaction amount=TspCx9wbUIG3AHm40YYwjA==&paymode=FsAVZXp0rTj81r6v2bzn1w==


        # Sms Process
        // http://nimbusit.biz/api/SmsApi/SendSingleApi?
        // UserID=SwatiIndbiz
        // &Password=txif7813TX
        // &SenderID=TECSSP
        // &Phno=7319867430
        // &Msg=Dear Student, Get confirmed admission in ABC in TOP medical colleges/Deemed Universities under management quota at low cost. Call-9999999999 TECSSP
        // &EntityID=1201159409941345107
        // &TemplateID=1707169477672412036



    }
}
