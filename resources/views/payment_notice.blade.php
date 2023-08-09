<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <link rel="stylesheet" href="style.css" /> -->
    <title>Document</title>

    <style>
        @font-face {
            font-family: Hindi-Light;
            src: url("{{storage_path('Fonts/Noto_Sans/NotoSans-Light.ttf')}}");
        }
        @font-face {
            font-family: Hindi-Bold;
            src: url("{{storage_path('Fonts/Noto_Sans/NotoSans-Bold.ttf')}}");
        }
        #logo {
            height: 80px;
            width: 80px;
        }

        #perfectSpacing {
            line-height: 0.1rem;
        }

        #container {            
            padding: 20px;
            margin-top: 0.5px;
            font-family: Hindi-Light;
            position: relative;
        }
        .water-mark{
            position: absolute;
            top:30%;
            display: inline-block;
            opacity: 0.2;
            text-align: center;
            width: 100%;
        }
        .border-dark {
            border-color: #343a40!important;
        }
        .border {
            border: 0.5px solid #dee2e6!important;
        }
        .bg-light {
            background-color: #f8f9fa!important;
        }
        .upperCase{
            text-transform: uppercase;
        }
        .row {
            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -ms-flex-wrap: wrap;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }
        .col-md-12 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 100%;
            flex: 0 0 100%;
            max-width: 100%;
        } 
        .col-md-8 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 66.666667%;
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
        }
        .col-md-6 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 50%;
            flex: 0 0 50%;
            max-width: 50%;
        }
        .col-md-4 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 33.333333%;
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }
        .col-md-3 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 25%;
            flex: 0 0 25%;
            max-width: 25%;
        }  
        .col-md-2 {
            -webkit-box-flex: 0;
            -ms-flex: 0 0 15%;
            flex: 0 0 15%;
            max-width: 15%;
        }      
        .text-center {
            text-align: center!important;
        }
        .text-right {
            text-align: right!important;
        }
    </style>
</head>

<body class="container bg-light border border-dark" id="container" style="font-size:small">
    <div class="water-mark">
        <img style ="max-height:400px;" src="{{public_path('image/logo/jharkhand_log.png')}}" alt="">
    </div>
    <div >
        <div class="row">
            <table class="text-center" style="width: 100%; padding:0;margin:0; font-family: Hindi-Bold;">
                <tr>
                    <td style="width: 2%;"><img class="text-left" src="{{public_path('image/logo/jharkhand_log.png')}}" alt="logo" id="logo"></td>
                    <td style="width: 50%;">कार्यालय {{$noticeData->ulb_name}} <br/>(राजस्व शाखा)</td>                    
                </tr> 
            </table> 
            <p class="text-center">झारखण्ड नगरपालिका अधिनियम 2011 की धारा 182 (3) के अधीन कर भुगतान हेतु डिमांड नोटिस का प्रेषण।</p>  
            <table style="width: 100%; padding:0;margin:0;">
                <tr>
                    <td style="width: 65%;">पत्रांक : <strong><u>{{$noticeData->notice_no}}</u></strong></td>
                    <td style="width: 5%;">&nbsp;&nbsp;&nbsp;</td>
                    <td>नोटिस दिनांक : <strong><u>{{date('d-m-Y',strtotime($reminder_notice_date))}}</u></strong></td>
                </tr>
                <tr>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>प्रिंट दिनांक : <strong><u>{{date('d-m-Y')}}</u></strong></td>
                </tr>
                <tr>
                    <td>प्रेषित</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                </tr>
                <tr>
                    <td>श्री/श्रीमती/मेसर्स : <strong><u>{{$noticeData->owner_name}}</u></strong></td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                </tr>
                <tr>
                    <td>पिता/पति का नाम : <strong><u>{{$noticeData->fathe_name??"N/A"}}</u></strong></td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                </tr>
                <tr>
                    <td>होलिडंग नं० : <strong><u>{{$noticeData->holding_no??"N/A"}}</u></strong> वार्ड नं०:- <strong><u>{{$noticeData->ward_no??"N/A"}}</u></strong></td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                </tr>
                <tr>
                    <td>पता : <strong><u>{{$noticeData->address??"N/A"}}</u></strong></td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                    <td>&nbsp;&nbsp;&nbsp;</td>
                </tr>
            </table>  
            <div class="col-md-12">
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                आपके भवन (होल्डिंग नं० - {{$noticeData->holding_no??"N/A"}}) का धृतिकार - बकाया। 
                आपके द्वारा {{date('d-m-Y',strtotime($noticeData->due_date??""))}} तक की अवधि का टैक्स जमा नहीं किया गया है। 
                अतएव झारखंड नगरपालिका कर भुगतान (समय,प्रक्रिया तथा वसूली ) 
                विनियम,- 2017 के नियम 3. 1 . 2 तहत आपको उक्त अवधी का होल्डिंग टैक्स भुगतान हेतु मांग पत्र निम्न गणना के 
                अनुसार दी जा रही है।
            </div> 
            <table style="width: 100%; padding:0;margin:0;">
                <tr>
                    <td style="width: 10%;"></td>
                    <td style="width: 70%;">
                        <table  class="text-right" style="width: 100%; padding:0;margin:0; border:1px solid; border-collapse: collapse">
                            <tr>
                                <td style="width: 35.2%; border:1px solid; font-weight: bold;">Till-{{$noticeData->from_quater??""}}/{{$noticeData->from_fyear??""}}</td>
                                <td style="width: 35.2%;  border:1px solid; font-weight: bold;">{{$noticeData->upto_quater??""}}/{{$noticeData->upto_fyear??""}}</td>
                                <td style="width: 35.2%;  border:1px solid; font-weight: bold;">Total Amount</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid;">&nbsp;&nbsp;&nbsp;</td>
                                <td style="border:1px solid;">Demand Amount</td>
                                <td style="border:1px solid; font-weight: bold;">{{round(($noticeData->demand_amont??0.00),2)}}</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid;">&nbsp;&nbsp;&nbsp;</td>
                                <td style="border:1px solid;">Penalty</td>
                                <td style="border:1px solid; font-weight: bold;">{{round(($noticeData->penalty??0.00),2)}}</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="border:1px solid;">Total Amount</td>
                                <td style="border:1px solid; font-weight: bold;">{{round(($noticeData->penalty??0.00),2)}}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 10%;"></td>
                </tr>
            </table>  
            
            <div class="col-md-12">
                अतएव झारखण्ड नगरपालिका कर भुगतान 
                ( समय , प्रक्रिया तथा वसूली ) विनियम,-2017 के विहित प्रावधान के अनुसार 
                आपको उक्त अवधी का होल्डिंग टैक्स का भुगतान करना है।
                <br/>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;इस राशि का भुगतान नोटिस प्राप्त होने के 01(एक) माह के अंदर करना सुनिश्चित करेंगे।
                अन्यथा उक्त नियमावली की कंडिका 3.1.4 के विहित प्रावधान के अनुसार निम्न प्रकार से उपरोक्त के 
                अतिरिक्त के अतिरिक्त दण्ड की राशि निम्न प्रकार से अधिरोपित की जायेगीः-
            </div>
            <table style="width: 100%; padding:0;margin:0; font-size:x-small;">
                <tr>
                    <td style="width: 5%;"></td>
                    <td style="width: 90%;">
                        <table style="width: 100%; padding:0;margin:0; border:1px solid; border-collapse: collapse">
                            <tr>
                                <td style="width: 5%; border:1px solid;">क्रमांक</td>
                                <td style="width: 50%;  border:1px solid; ">विलम्बित अवधी</td>
                                <td style="width: 45%;  border:1px solid; ">दण्ड की राशि</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid;">01.</td>
                                <td style="border:1px solid;">निर्धारित अवधी से एक सप्ताह की अवधी तक</td>
                                <td style="border:1px solid; ">भुगतेय राशि का 1 प्रतिशत</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid;">02.</td>
                                <td style="border:1px solid;">निर्धारित अवधी से दो सप्ताह की अवधी तक</td>
                                <td style="border:1px solid;">भुगतेय राशि का 2 प्रतिशत</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid;">03.</td>
                                <td style="border:1px solid;">निर्धारित अवधी से एक माह की अवधी तक</td>
                                <td style="border:1px solid;">भुगतेय राशि का 3 प्रतिशत</td>
                            </tr>
                            <tr>
                                <td style="border:1px solid;">04.</td>
                                <td style="border:1px solid;">निर्धारित अवधी से दो माह की अवधी तक</td>
                                <td style="border:1px solid;">भुगतेय राशि का 5 प्रतिशत</td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 5%;"></td>
                </tr>
            </table>
            <div class="col-md-12">
                उसके पश्चात प्रत्येक माह 2 प्रतिशत अतिरिक्त दण्ड की राशि भुगतेय होगी।
                <br/>
                अतएव आप कर का भुगतान ससमय करना सुनिश्चित करें।
                <br/>
                <span style="font-family: Hindi-Bold;">*दण्ड राशि का गणना भुगतान के समय निर्धारित किया जाएगा।</span>
            </div>  
            <p class="text-center">सख्त ताकीद समझा जाए।</p>  
            <table style="width: 100%;">
                <tr>
                    <td width="60%">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
                    <td width="40%"  class="text-center">
                        <img style="bottom: 80px;max-height: 50px;" src="{{public_path('image/signatur/notice_signature.png')}}" alt="signatur">
                        उपनगर आयुक्त
                        <br/>
                        राँची नगर निगम, राँची
                    </td>
                </tr>        
            </table>
        </div>
    </div>
</body>

</html>