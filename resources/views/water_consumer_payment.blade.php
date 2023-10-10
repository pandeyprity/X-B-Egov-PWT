<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Water Demand Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .main {
            border: 2px dashed #718096;
            background-color: #ffffff;
            padding: 24px;
            width: 21cm;
            /* A4 size width */
            height: 29.7cm;
            /* A4 size height */
            margin: 0 auto;
        }



        .logo1 {
            margin-left: 38%;
        }

        .heading {
            font-weight: bold;
            font-size: 2.25rem;
            text-align: center;
            text-transform: uppercase;
        }

        .logo2 {
            width: 22rem;
            height: 22rem;
            position: absolute;
            z-index: 10;
            background-color: transparent;
            opacity: 0.2;
            margin-top: 16rem;
            margin-left: 17rem;
        }

        .mainheading {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }

        .box {
            margin-left: auto;
            margin-right: auto;
        }

        .waterheading {
            font-weight: 600;
            font-size: 1.5rem;
            text-align: center;
            color: #2d3748;
            border: 1px solid #4a5568;
            width: 24rem;
            text-transform: uppercase;
        }



        .tableinfo {
            display: flex;
            padding: 0.4em;
            font-size: 0.6rem;
        }



        .info {
            width: 100%;
            padding: 0.5rem;
            margin-top: 1rem;
            font-size: 1rem;
            /* Adjust the font size as needed */
            font-weight: bold;
        }

        .table1 {
            width: 100%;
            padding: 0.10rem;
            margin-top: 0.25rem;
            font-size: 1rem;
            /* Adjust the font size as needed */
            font-weight: bold;
        }

        .tabledetails {
            flex: 1;
            color: #333;
            font-weight: bold;
            font-family: sans-serif;
            margin: 0;
            font-size: 1rem;
            /* Adjust the font size as needed */
        }

        .table2 {
            width: 100%;
        }

        .maintable2 {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem;
        }

        .tableinfo2 {
            display: flex;
            padding: 0.25rem;
            font-size: 1rem;
        }

        .tabledetails2 {
            flex: 1;
            color: #333;
            font-weight: bold;
            font-family: sans-serif;
            margin: 0;
            font-size: 1.2rem;
        }

        .vide {
            font-family: sans-serif;
            font-weight: normal;
            margin-left: 0.125rem;
        }

        .paymentmode {
            display: grid;
            grid-template-columns: 1fr;
            padding: 1rem;
            margin-top: 0.125rem;
        }

        .paymentheading1 {
            font-weight: bold;
            font-size: 1.2rem;
            text-align: left;
            margin: 0;
        }

        .paymentheading2 {
            font-size: 1rem;
        }

        .table3 {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.625rem;
        }

        .width {
            width: 100%;
        }

        .des {
            border: 1px solid #888;
            text-align: center;
            font-weight: bold;
        }

        .td {
            border-right: 1px solid #888;
            width: 33%;
        }

        .desrtext {
            margin: 2px;
        }

        .total {
            border: 1px solid #888;
            width: 33%;
        }

        .amount {
            margin: 2px;
        }

        .tr {
            border: 1px solid #888;
            text-align: center;
        }

        .td1 {
            border-right: 1px solid #888;
            width: 33%;
            text-align: left;
            padding-left: 2px;
        }

        .period {
            margin: 2px;
        }

        .from {
            font-weight: bold;
        }

        .to {
            font-weight: bold;
        }

        .td2 {
            border: 1px solid #888;
            width: 33%;
            text-align: right;
            padding-right: 2px;
        }

        .margin {
            margin: 2px;
        }

        .table4 {
            width: 100%;
            margin-top: 0.5rem;
            font-size: 0.5rem;
        }

        .qr {
            margin-bottom: 0.25rem;
        }

        .code {
            width: 64px;
            height: 64px;
        }

        .visit {
            display: flex;
        }

        .detailsheading {
            flex: 1;
            color: #333;
            font-family: sans-serif;
            margin: 0;
        }

        .with {
            text-align: right;
            margin-top: 1rem;
        }

        .computer {
            width: 100%;
            margin-top: 0.5rem;
            text-align: center;
            font-size: 0.6rem;
            padding: 1rem;
        }

        .generate {
            font-weight: bold;
        }

        .logo5 {
            width: 100%;
            text-align: center;
        }

        .logo6 {
            width: 4rem;
            margin: 1rem auto;
        }
    </style>
</head>

<body>
    <div class="main">
        <div class="display">
            <div>
                <img class="logo1" src='' />
                <h1 class="heading">Akola Municipal Corporation</h1>
                <div>
                    <img class="logo2" src='' alt="" />
                </div>
                <div class="mainheading">
                    <div class="box">
                        <h1 class="waterheading">Water User Charge Payment Receipt</h1>
                    </div>
                </div>
                <div class="info">
                    <table class="table1">
                        <tr>
                            <td>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Department/Section:</h1>
                                    <h1 class="tabledetails">N/A</h1>
                                </div>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Account Description:</h1>
                                    <h1 class="tabledetails">Water User Charge</h1>
                                </div>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Holding No.:</h1>
                                    <h1 class="tabledetails">N/A</h1>
                                </div>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Holding No.:</h1>
                                    <h1 class="tabledetails">N/A</h1>
                                </div>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Holding No.:</h1>
                                    <h1 class="tabledetails">{{ $returnValues['propertyNo']??"NA" }}</h1>
                                </div>
                            </td>
                            <td>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Date:</h1>
                                    <h1 class="tabledetails">{{ $returnValues['transactionDate']??"NA" }}</h1>
                                </div>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Ward No.:</h1>
                                    <h1 class="tabledetails">N/A</h1>
                                </div>
                                <div class="tableinfo">
                                    <h1 class="tabledetails">Consumer No.:</h1>
                                    <h1 class="tabledetails">{{ $returnValues['consumerNo']??"NA" }}</h1>
                                </div></h1>
                                </div>
                                <div class="tableinfo"></div>
                                <div class="tableinfo"></div>
                                <div class="tableinfo"></div>
                                <div class="tableinfo"></div>
                                <div class="tableinfo"></div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="table2">
                    <table class="maintable2">
                        <tr>
                            <td>
                                <div class="tableinfo2">
                                    <h1 class="tabledetails2">Received From Mr/Mrs/Miss:</h1>
                                    <h1 class="tabledetails2"></h1>
                                </div>
                                <div class="tableinfo2">
                                    <h1 class="tabledetails2">Mobile No.:</h1>
                                    <h1 class="tabledetails2">
                                <div class="tableinfo2">
                                    <h1 class="tabledetails2">Address:</h1>
                                    <h1 class="tabledetails2"></h1>
                                </div>
                                <div class="tableinfo2">
                                    <h1 class="tabledetails2">A Sum of Rs. (in words): </h1>
                                    <h1 class="tabledetails2"></h1>
                                </div>
                                <div class="tableinfo2">
                                    <h1 class="tabledetails2">Towards: <span class="vide">hh</span> <span>vide:</span></h1>
                                </div>
                                <div class="tableinfo2">
                                    <h1 class="tabledetails2">Water Consumed (in K.L.):</h1>
                                    <h1 class="tabledetails2"></h1>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="paymentmode">
                    <div>
                        <h1 class="paymentheading1">N.B. Online Payment/Cheque/Draft/Banker's Cheque are Subject to Realization</h1>
                        <h1 class="paymentheading2">Water Demand Fee Details:</h1>
                    </div>
                    <table class="table3">
                        <thead class="width">
                            <tr class="des">
                                <td colspan="2" class="td">
                                    <h1 class="desrtext">Description</h1>
                                </td>
                                <td colspan="1" class="total">
                                    <h1 class="amount">Total Amount</h1>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="tr">
                                <td colspan="2" class="td1">
                                    <h1 class="period">Period: From <span class="from"></span> To <span class="to"></span></h1>
                                </td>
                                <td colspan="1" class="td2">
                                    <h1 class="margin"></h1>
                                </td>
                            </tr>
                            <tr class="tr">
                                <td colspan="2" class="td1">
                                    <h1 class="period">Penalty</h1>
                                </td>
                                <td colspan="1" class="td2">
                                    <h1 class="margin"></h1>
                                </td>
                            </tr>
                            <tr class="tr">
                                <td colspan="2" class="td1">
                                    <h1 class="period">Paid Amount</h1>
                                </td>
                                <td colspan="1" class="td2">
                                    <h1 class="margin"></h1>
                                </td>
                            </tr>
                            <tr class="tr">
                                <td colspan="2" class="td1">
                                    <h1 class="period">Due Amount</h1>
                                </td>
                                <td colspan="1" class="td2">
                                    <h1 class="margin"></h1>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="table4">
                        <tr>
                            <td>
                                <div class="qr">
                                    <img src="{ulb_data()?.state_logo}" alt="QR Code" class="code">
                                </div>
                                <div class="visit">
                                    <h1 class="detailsheading">For Details Please Visit:</h1>
                                </div>
                                <div class="visit">
                                    <h1 class="detailsheading">Or Call us at 9118008907909</h1>
                                </div>
                            </td>
                            <td class="with">
                                <div class="visit">
                                    <h1 class="detailsheading">In Collaboration with</h1>
                                </div>
                                <div class="visit">
                                    <h1 class="detailsheading"></h1>
                                </div>
                                <div class="visit">
                                    <h1 class="detailsheading">M/S Swati Industries</h1>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="computer">
                    <div>
                        <h1 class="generate">**This is a computer-generated receipt and it does not require a physical signature.**</h1>
                    </div>
                </div>
                <div class="logo5">
                    <div>
                        <img src="https://zeevector.com/wp-content/uploads/LOGO/Swachh-Bharat-Logo-PNG.png" class="logo6" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>

