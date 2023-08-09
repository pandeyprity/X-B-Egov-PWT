<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            line-height: 0.1em;
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
        .text-center {
            text-align: center!important;
        }
    </style>
</head>

<body class="container bg-light border border-dark" id="container" style="font-size:small">
    <div class="water-mark">
        <img style ="max-height:400px;" src="{{public_path('image/logo/jharkhand_log.png')}}" alt="">
    </div>
    <div >
        <div class="row">
            <div class="col-md-12 text-center">
                <img src="{{public_path('image/logo/jharkhand_log.png')}}" alt="logo" id="logo">
            </div>
        </div>
        <div class="row">
            <div class="col-md-12 text-center">
                <p style="font-family: Hindi-Bold;">{{$noticeData->ulb_name}} <br/>राजस्व शाखा <br/>नोटिस</p>
            </div>
        </div>

        <table style="width: 100%; padding:0;margin:0; font-family: Hindi-Bold;">
                <tr>
                    <td style="width: 60%;">प्रेषित,</td>
                    <td style="width: 5%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 35%;">पत्रांक : <strong>{{$noticeData->notice_no}}</strong> 
                    </td>
                </tr>  
                <tr>
                    <td >व्यवसाय का नाम : <strong class="upperCase"> {{$noticeData->firm_name}} </strong></td>
                    <td >&nbsp;&nbsp;&nbsp;</td>
                    <td >
                        दिनांक : <strong>{{date('d-m-Y',strtotime($reminder_notice_date))}}</strong>
                    </td>
                </tr>  
                <tr>
                    <td >नाम : <strong class="upperCase"> {{$noticeData->owner_name}} </strong></td>
                    <td >&nbsp;&nbsp;&nbsp;</td>
                    <td > </td>
                </tr>     
                <tr>
                    <td >पता : <strong class="upperCase"> {{$noticeData->address}} </strong></td>
                    <td >&nbsp;&nbsp;&nbsp;</td>
                    <td > </td>
                </tr> 
                <tr>
                    <td >मो० न० : <strong> {{$noticeData->mobile_no}} </strong></td>
                    <td >&nbsp;&nbsp;&nbsp;</td>
                    <td > </td>
                </tr>          
        </table>
        
        <div class="row">
            <div class="col-md-12">
                <p>
                    बजरिये नोटिस आपको सूचित किया जाता है कि राँची नगर निगम क्षेत्र में किसी भी भवन का गैर
                    आवासीय उपयोग करने के लिए झारखण्ड नगरपालिका अधिनियम, 2011 की धारा 455 के तहत
                    म्यूनिसिपल अनुज्ञप्ति प्राप्त करना अनिवार्य है।
                </p>
                <p>
                    अधोहस्ताक्षरी के संज्ञान में यह लाया गया है कि आपके द्वारा उपर्युक्त भवन का गैर आवासीय उपयोग
                    बिना म्यूनिसिपल अनुज्ञप्ति प्राप्त किये जा रहा है, जो कि झारखण्ड नगरपालिका अधिनियम, 2011 की धारा
                    455 का उल्लंघन है। यदि आपके पास भवन के गैरआवासीय उपयोग हेतु म्यूनिसिपल अनुज्ञप्ति प्राप्त है तो
                    जन सुविधा केन्द्र या निगम समर्थित 
                    <span class="upperCase">{{$agency_name}}</span>
                     के प्रतिनिधि
                    को उपलब्ध करायें।
                    <span style="font-family: Hindi-Bold;">(कृपया नोटिस की छायाप्रति भी साथ में संलग्न करें) </span>
                </p>
                <p>अतएव आपको निदेशित किया जाता है कि नोटिस प्रप्ती के तीन दिनों के अन्दर उपर्युक्त भवन के
                    लिए म्यूनिसिपल अनुज्ञप्ति प्राप्त कर लें अथवा भवन का गैर आवासीय उपयोग बंद कर लें तथा
                    अधोहस्ताक्षरी को सूचित करें , , अन्यथा झारखण्ड नगरपालिका अधिनियम की धारा 187 एवं 600 के तहत
                    कार्यवाई प्रारम्भ की जायेगी एवं झारखण्ड नगरपालिका व्यापार अनुज्ञप्ति नियमावली 2017 के नियम 19
                    तथा 20 के तहत कार्यवाई की जायेगी। 
                </p>
                <p class="text-center" style="font-family: Hindi-Bold;">नोट : इसे अति आवश्यक समझें।</p>
            </div>
        </div>
        <table style="width: 100%; padding:0;margin:0; font-family: Hindi-Bold;">
                <tr>
                    <td style="width: 50%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 5%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 45%;"><img src="{{public_path('image/signatur/sign.png')}}" alt="signatur"></td>
                </tr> 
                <tr>
                    <td style="width: 50%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 5%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 45%;"> {{$noticeData->ulb_name}} राजस्व शाखा नोटिस</td>
                </tr>
                <tr>
                    <td style="width: 50%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 5%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 45%;">-सह</td>
                </tr>
                <tr>
                    <td style="width: 50%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 5%;">&nbsp;&nbsp;&nbsp;</td>
                    <td style="width: 45%;">{{$noticeData->ulb_name}}</td>
                </tr>
        </table>
        
    </div>
</body>

</html>