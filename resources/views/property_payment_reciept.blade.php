<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>


    <style>
        @font-face {
            font-family: Hindi-Light;
            src: url("{{storage_path('Fonts/Noto_Sans/NotoSans-Light.ttf')}}");
        }

        @font-face {
            font-family: Hindi-Bold;
            src: url("{{storage_path('Fonts/Noto_Sans/NotoSans-Bold.ttf')}}");
        }

        * {
            background-color: #fff;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            /* background-color: #d1d5db; */
            text-align: center;
        }

        .container {
            /* height: auto; */
            width: 800px;
            margin: 0 auto;
            /* background-color: #fff;
                padding: 12px 12px 12px 12px; */
            text-align: center;
        }

        .grid {
            /* display: grid;
                grid-template-columns: auto auto auto;
                column-gap: 30px;             */
            width: 100%;
            float: left;

        }

        #grid2 {
            display: grid;
            grid-template-columns: auto auto auto auto auto;
            column-gap: 50px;
            padding: 4px;
        }

        #table {
            border: 1px solid green;
            border-collapse: collapse;
            width: 100%;
            /* padding: 5px; */
            /* margin: 0px 50px; */
        }

        #tableRow {
            border: 1px solid green;
            border-collapse: collapse;
        }

        .th {
            border: 1px solid black;
        }

        .fh {
            background-color: #e5e7eb;
        }

        .headers {
            width: 12%;
            text-align: left !important;
            margin-left: 1px;
        }

        .seperator {
            width: 1%;
            text-align: left !important;
        }

        .values {
            width: 17%;
            text-align: left !important;
        }

        .sml {
            font-size: 11px;
        }

        .logo {
            width: 10%;
            display: inline-block;
            vertical-align: top;
            padding: 10px;
            box-sizing: border-box;
            /* height: 100px; */
            /* width: 100px; */
        }

        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>

<body>
    <div style="width: 1000px; margin:0 auto;">
        <table class="sml">
            <tr>
                <td style="width: 14%; padding:10px">
                    <img src="https://mahasarkar.co.in/wp-content/uploads/2018/04/Akola-Municipal-Corporation.png" alt="logo">
                </td>
                <td style="width: 64%;">
                    <table style="text-align: center; font-size: 11px; font-family: Hindi-Light; width: 100%;">
                        <tbody style='width: 100%;'>
                            <tr>
                                <td colspan="9">
                                    <h1 style="width: 100%;background: #374151; color: white; text-align: center; font-size: 18px; padding: 6px; border-radius: 5px; ">
                                        {{($data['receiptDtls']['ulbDetails']['ulb_name'])??"Akola Municipal corporation"}}
                                    </h1>
                                </td>
                            </tr>
                            <tr>
                                <td class="headers" style="text-align: left; font-weight: 700;">Owner Name</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap; font-family: Hindi-Light;"> {{($data['receiptDtls']['customerName'])??"NA"}} </td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Holding No</td>
                                <td class="seperator">:</td>
                                <td class="headers" style="white-space: pre-wrap;font-family: Hindi-Light;"> {{($data['receiptDtls']['applicationNo'])??"NA"}} </td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Bill No</td>
                                <td class="seperator">:</td>
                                <td class="headers" style="white-space: pre-wrap;font-family: Hindi-Light;"> {{($data['receiptDtls']['transactionNo'])??"NA"}} </td>
                            </tr>
                            <tr>
                                <td class="headers" style="text-align: left; font-weight: 700;">Address</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;font-family: Hindi-Light;">{{($data['receiptDtls']['address'])??"NA"}}</td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Mobile No</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;font-family: Hindi-Light;">{{($data['receiptDtls']['mobileNo'])??"NA"}}</td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Property Type</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;font-family: Hindi-Light;">{{($data['receiptDtls']['property_type'])??"NA"}}</td>
                            </tr>
                            <tr>
                                <td class="headers" style="text-align: left; font-weight: 700;">Zone</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;"> {{($data['receiptDtls']['zone_name'])??"NA"}} </td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Ward No</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;"> {{($data['receiptDtls']['wardNo'])??"NA"}} </td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Verify Status</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;"> {{($data['receiptDtls']['verifyStatus']==1 ?"Verified":"Not Verified")??"NA"}} </td>
                            </tr>
                            <tr>
                                <td class="headers" style="text-align: left; font-weight: 700;">Payment Mode</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;">{{($data['receiptDtls']['paymentMode'])??"NA"}}</td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Paid From</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;"> {{($data['receiptDtls']['paidFrom'])??"NA"}} </td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Paid Upto</td>
                                <td class="seperator">:</td>
                                <td class="values" style="white-space: pre-wrap;"> {{($data['receiptDtls']['paidUpto'])??"NA"}} </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 14%; padding:10px">
                    <img src="https://mahasarkar.co.in/wp-content/uploads/2018/04/Akola-Municipal-Corporation.png" alt="logo">
                </td>
            </tr>
        </table>

        <table id="table" class="sml">
            <thead>
                <tr id="tableRow">
                    <th style="border: 1px solid gray; border-collapse: collapse; font-weight: 600;">#.</th>
                    <th style="border: 1px solid gray; border-collapse: collapse; font-weight: 600;">Tax Statement </th>
                    <th style="border: 1px solid gray; border-collapse: collapse; font-weight: 600;"><b>(A)</b> Overdue Demand </th>
                    <th style="border: 1px solid gray; border-collapse: collapse; font-weight: 600;"><b>(B)</b> Current Demand (2023 -
                        24) </th>
                    <th style="border: 1px solid gray; border-collapse: collapse; font-weight: 600;"> <b>(A+B)</b> Grand Total
                        (2023-24) </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="th"> 1 </td>
                    <td class="th"> &nbsp; General Tax </td>
                    <td class="th">{{($data['overdueDemand']['general_tax'])}} </td>
                    <td class="th"> {{($data['currentDemand']['general_tax'])}} </td>
                    <td class="th"> {{($data['aggregateDemand']['general_tax'])}} </td>
                </tr>
                <tr>
                    <td class="th"> 2 </td>
                    <td class="th"> &nbsp; Road Tax </td>
                    <td class="th">{{($data['overdueDemand']['road_tax'])}} </td>
                    <td class="th"> {{($data['currentDemand']['road_tax'])}} </td>
                    <td class="th"> {{($data['aggregateDemand']['road_tax'])}} </td>
                </tr>
                <tr>
                    <td class="th"> 3 </td>
                    <td class="th"> &nbsp; Fire Fighting Tax </td>
                    <td class="th">{{($data['overdueDemand']['firefighting_tax'])}} </td>
                    <td class="th"> {{($data['currentDemand']['firefighting_tax'])}} </td>
                    <td class="th"> {{($data['aggregateDemand']['firefighting_tax'])}} </td>
                </tr>
                <tr>
                    <td class="th"> 4 </td>
                    <td class="th"> &nbsp; Education Tax </td>
                    <td class="th">{{($data['overdueDemand']['education_tax'])}} </td>
                    <td class="th"> {{($data['currentDemand']['education_tax'])}} </td>
                    <td class="th"> {{($data['aggregateDemand']['education_tax'])}} </td>
                </tr>
                <tr>
                    <td class="th"> 5 </td>
                    <td class="th"> &nbsp; Water Tax </td>
                    <td class="th">{{($data['overdueDemand']['water_tax'])}} </td>
                    <td class="th"> {{($data['currentDemand']['water_tax'])}} </td>
                    <td class="th"> {{($data['aggregateDemand']['water_tax'])}} </td>
                </tr>
                <tr>
                    <td class="th"> 6 </td>
                    <td class="th"> &nbsp; Cleanliness Tax </td>
                    <td class="th">{{($data['overdueDemand']['water_tax'])}} </td>
                    <td class="th"> {{($data['currentDemand']['water_tax'])}} </td>
                    <td class="th"> {{($data['aggregateDemand']['water_tax'])}} </td>
                </tr>
                <tr>
                    <td class="th"> 7 </td>
                    <td class="th"> &nbsp; Sewage Tax </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 8 </td>
                    <td class="th"> &nbsp; Tree Tax </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 9 </td>
                    <td class="th">&nbsp; Professional Tax</td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 10 </td>
                    <td class="th"> &nbsp; Adjust Amt </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>

                <tr>
                    <td class="th"> 11 </td>
                    <td class="th"> &nbsp; Tax 1 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>

                <tr>
                    <td class="th"> 12 </td>
                    <td class="th"> &nbsp; State Education Tax </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 13 </td>
                    <td class="th"> &nbsp; Water Benefit </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 14 </td>
                    <td class="th"> &nbsp; Water Bill </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 15 </td>
                    <td class="th"> &nbsp; Sp Water Cess </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 16 </td>
                    <td class="th"> &nbsp; Drain Cess</td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 17 </td>
                    <td class="th"> &nbsp; Light Cess</td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>

                <tr>
                    <td class="th"> 18 </td>
                    <td class="th"> &nbsp; Big Building Tax </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr>
                    <td class="th"> 19 </td>
                    <td class="th"> &nbsp; Total Tax </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                    <td class="th"> 0 </td>
                </tr>
                <tr style="font-weight: bold;">
                    <td class="th fh" colSpan="4" style="padding: 4px; text-align:left"> Monthly Penalty</td>
                    <td class="th fh" colSpan="2" style="padding: 4px; "> 0 </td>

                </tr>
                <tr style="font-weight: bold;">
                    <td class="th fh" colSpan="4" style="padding: 4px;  text-align:left"> Paid Arrear Amount</td>
                    <td class="th fh" colSpan="2" style="padding: 4px; "> {{($data['receiptDtls']['arrearSettled']??0.000)}} </td>

                </tr>
                <tr style="font-weight: bold;">
                    <td class="th fh" colSpan="4" style="padding: 4px;   text-align:left"> Paid demand Amount</td>
                    <td class="th fh" colSpan="2" style="padding: 4px;  "> {{($data['receiptDtls']['demandAmount']??0.000)}} </td>
                </tr>
                <tr style="font-weight: bold;">
                    <td class="th fh" colSpan="4" style="padding: 4px;   text-align:left">Paid Total Amount</td>
                    <td class="th fh" colSpan="2"> {{($data['receiptDtls']['totalPaidAmount']??0.000)}} </td>
                </tr>
                <tr style="font-weight: bold;">
                    <td class="th fh" colSpan="4" style="padding: 4px;   text-align:left">Paid Amount In Words</td>
                    <td class="th fh" colSpan="2"> {{($data['receiptDtls']['paidAmtInWords']??"NA")}}</td>

                </tr>

            </tbody>
        </table>

        <table class="sml">
            <tr>
                <td>Connect With Us:-<br />
                    Recovery Clerk : {{($data['receiptDtls']['tcName']??"")}}<br />
                    Mobile No. {{($data['receiptDtls']['tcMobile']??"")}}
                </td>
                <td>
                    <button style="background-color: #374151;border: 1px solid gray; border-radius: 5px; padding: 12px; color: white; margin: 4px;" class="sml">
                        Pay Property Tax Online https://akolamc.org
                    </button>
                </td>
                <td>Tax Clerk<br />
                    Akola Municipal Corporation,<br />
                    Akola
                </td>
                <td>Tax Superintendent<br />
                    Akola Municipal Corporation,<br />
                    Akola
                </td>
                <td>Deputy Commissionar<br />
                    Akola Municipal Corporation,<br />
                    Akola
                </td>
            </tr>

        </table>
    </div>
</body>

</html>