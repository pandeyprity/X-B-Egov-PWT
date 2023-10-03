<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
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
            *{
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
            .fh{
                background-color: #e5e7eb;
            }
            .headers{
                width: 8%;
                text-align: left !important;
                margin-left: 1px;
            }
            .seperator{
                width: 1%;
                text-align: left !important;
            }
            .values{
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
            img{
                max-width: 100%;
                height: auto;
            }
            .water-mark{
            position: absolute;
            top:30%;
            display: inline-block;
            opacity: 0.2;
            text-align: center;
            width: 100%;
        }
        </style>
    </head>

    <body> 
        <div style="width: 1000px; margin:0 auto;">
            <div class="water-mark">
                <img src="https://mahasarkar.co.in/wp-content/uploads/2018/04/Akola-Municipal-Corporation.png" alt="" >
            </div>
            <table class="sml">
                <tr>
                    <td style="width: 14%;">
                        <img src="https://mahasarkar.co.in/wp-content/uploads/2018/04/Akola-Municipal-Corporation.png" alt="logo" >
                    </td>
                </tr>
                <tr>
                    <td>
                        <h1 style="width: 100%; text-align: center; font-size: 25px; ">
                            {{"Akola Municipal corporation"}}
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td style="width: 100%; text-align: center; padding:0 30%">
                        <h1 style=" font-size: 25px; border: 1px solid #4a5568;  text-transform: uppercase; color: #2d3748; font-weight: 600;">
                                    water user charge 
                                <br /> payment receipt
                        </h1>
                    </td>
                </tr>
                <tr>
                    <td>
                        <table style="width: 1000px; margin:0 auto;">
                            <tr>
                                <td class="headers" style="text-align: left; font-weight: 700;">Receipt No. :</td>
                                <td class="values">N/A</td>
                                <td class="headers" style="text-align: left; font-weight: 700;">Date :</td>
                                <td class="values">N/A</td>
                            </tr>
                        </table>
                    </td>
                    
                </tr>
            </table>



            
            <div style="display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); position: relative;">
                <div>
                    
                    <div style="width:100%;padding:1rem;margin-top:1rem">
                        <table style="width:100%;padding:0.25rem;margin-top:0.25rem">
                            <tr>
                                <td>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Receipt No. :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            
                                        </h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Department/Section :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            N/A</h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Account Description :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            Water User Charge</h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Holding No. :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            N/A</h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Holding No. :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            N/A</h1>
                                    </div>
                                </td>
                                <td>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Date :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            N/A</h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Ward No. :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            N/A</h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem">
                                            Consumer No. :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem">
                                            N/A</h1>
                                    </div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem"></div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem"></div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem"></div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem"></div>
                                    <div style="display:flex;padding:0.125rem;font-size:1rem"></div>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div style="width:100%;">
                        <table style="width:100%;padding:0.5rem;margin-top:0.5rem;">
                            <tr>
                                <td>
                                    <div style="display:flex;padding:0.25rem;font-size:1rem;">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem;">
                                            Received From Mr/Mrs/Miss :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem;">
                                        </h1>
                                    </div>
                                    <div style="display:flex;padding:0.25rem;font-size:1rem;">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem;">
                                            Mobile No. :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem;">
                                        </h1>
                                    </div>
                                    <div style="display:flex;padding:0.25rem;font-size:1rem;">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem;">
                                            Address :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem;">
                                        </h1>
                                    </div>
                                    <div style="display:flex;padding:0.25rem;font-size:1rem;">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem;">
                                            A Sum of Rs. (in words) : </h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem;">
                                        </h1>
                                    </div>
                                    <div style="display:flex;padding:0.25rem;font-size:1rem;">
                                        <h1
                                            style="flex:1;color:#333;font-family:sans-serif;font-weight:bold;margin:0;font-size:1.2rem;">
                                            Towards : <span
                                                style="font-family:sans-serif;font-weight:normal;margin-left:0.125rem;">hh</span>
                                            <span>vide:</span>
                                        </h1>

                                    </div>

                                    <div style="display:flex;padding:0.25rem;font-size:1rem;">
                                        <h1
                                            style="flex:1;color:#333;font-weight:bold;font-family:sans-serif;margin:0;font-size:1.2rem;">
                                            Water Consumed (in K.L.) :</h1>
                                        <h1
                                            style="flex:1;font-family:sans-serif;margin:0;padding-left:0.125rem;font-size:1.2rem;">
                                        </h1>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div
                        style="display: grid; grid-template-columns: 1fr; grid-template-columns: 1fr; grid-template-columns: 1fr; padding: 1rem; margin-top: 0.125rem;">
                        <div>
                            <h1 style="font-weight: bold; font-size: 1.2rem; text-align: left; margin: 0;">N.B. Online
                                Payment/Cheque/Draft/Bankers Cheque are Subject to Realisation</h1>
                            <h1 style="font-size:1.2rem;">Water Demand Fee Details:</h1>
                        </div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.625rem;">
                            <thead style="width: 100%;">
                                <tr style="border: 1px solid #888; text-align: center; font-weight: bold;">
                                    <td colspan="2" style="border-right: 1px solid #888; width: 33%;">
                                        <h1 style="margin: 2px;">Description</h1>
                                    </td>
                                    <td colspan="1" style="border: 1px solid #888; width: 33%;">
                                        <h1 style="margin: 2px;">Total Amount</h1>
                                    </td>
                                </tr>
                            </thead>
                            <tbody>
                                <tr style="border: 1px solid #888; text-align: center;">
                                    <td colspan="2"
                                        style="border-right: 1px solid #888; width: 33%; text-align: left; padding-left: 2px;">
                                        <h1 style="margin: 2px;">Period: From <span style="font-weight: bold;"></span> To
                                            <span style="font-weight: bold;"></span></h1>
                                    </td>
                                    <td colspan="1"
                                        style="border: 1px solid #888; width: 33%; text-align: right; padding-right: 2px;">
                                        <h1 style="margin: 2px;"></h1>
                                    </td>
                                </tr>
                                <tr style="border: 1px solid #888; text-align: center;">
                                    <td colspan="2"
                                        style="border-right: 1px solid #888; width: 33%; text-align: left; padding-left: 2px;">
                                        <h1 style="margin: 2px;">Penalty</h1>
                                    </td>
                                    <td colspan="1"
                                        style="border: 1px solid #888; width: 33%; text-align: right; padding-right: 2px;">
                                        <h1 style="margin: 2px;"></h1>
                                    </td>
                                </tr>
                                <tr style="border: 1px solid #888; text-align: center;">
                                    <td colspan="2"
                                        style="border-right: 1px solid #888; width: 33%; text-align: left; padding-left: 2px;">
                                        <h1 style="margin: 2px;">Paid Amount</h1>
                                    </td>
                                    <td colspan="1"
                                        style="border: 1px solid #888; width: 33%; text-align: right; padding-right: 2px;">
                                        <h1 style="margin: 2px;"></h1>
                                    </td>
                                </tr>
                                <tr style="border: 1px solid #888; text-align: center;">
                                    <td colspan="2"
                                        style="border-right: 1px solid #888; width: 33%; text-align: left; padding-left: 2px;">
                                        <h1 style="margin: 2px;">Due Amount</h1>
                                    </td>
                                    <td colspan="1"
                                        style="border: 1px solid #888; width: 33%; text-align: right; padding-right: 2px;">
                                        <h1 style="margin: 2px;"></h1>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table style="width: 100%; margin-top: 0.5rem; font-size: 0.5rem;">
                            <tr>
                                <td>
                                    <div style="margin-bottom: 0.25rem;">
                                        <img src="{ulb_data()?.state_logo}" alt="QR Code"
                                            style="width: 64px; height: 64px;">
                                    </div>
                                    <div style="display: flex;">
                                        <h1 style="flex: 1; color: #333; font-family: sans-serif; margin: 0;">For Details
                                            Please Visit:</h1>
                                    </div>
                                    <div style="display: flex;">
                                        <h1 style="flex: 1; color: #333; font-family: sans-serif; margin: 0;">Or Call us at
                                            9118008907909</h1>
                                    </div>
                                </td>
                                <td style="text-align: right; margin-top: 1rem;">
                                    <div style="display: flex;">
                                        <h1 style="flex: 1; color: #333; font-family: sans-serif; margin: 0;">In
                                            Collaboration with</h1>
                                    </div>
                                    <div style="display: flex;">
                                        <h1 style="flex: 1; color: #333; font-family: sans-serif; margin: 0;"></h1>
                                    </div>
                                    <div style="display: flex;">
                                        <h1 style="flex: 1; color: #333; font-family: sans-serif; margin: 0;">M/S Swati
                                            Indusries</h1>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div style="width: 100%; margin-top: 0.5rem; text-align: center; font-size: 0.6rem; padding: 1rem;">
                        <div>
                            <h1 style="font-weight: bold;">**This is a computer-generated receipt and it does not require a
                                physical signature.**</h1>
                        </div>
                    </div>

                    <div style="width: 100%; text-align: center;">
                        <div>
                            <img src='https://zeevector.com/wp-content/uploads/LOGO/Swachh-Bharat-Logo-PNG.png'
                                style='width: 4rem; margin: 1rem auto;' />
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </body>
</html>