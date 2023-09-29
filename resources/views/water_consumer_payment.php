<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt</title>
    <style>
        /* General styles */
        .transparent {
            width: 22rem;
            height: 22rem;
            position: absolute;
            z-index: 10;
            background-color: transparent;
            opacity: 0.2;
            margin-top: 16rem;
            margin-left: 17rem;
            border-radius: 50%;
            /* Add border properties as needed */
        }

        .table2 {
            width: 100%;
            margin-top: 0.5rem;
        }

        /* Heading styles */
        .heading1 {
            display: flex;
            color: rgb(17 24 39 / var(--tw-text-opacity));
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            font-size: 1.125rem;
            line-height: 1.75rem;
        }

        .heading4 {
            font-weight: 600;
            font-size: 1.125rem;
            text-align: center;
        }

        /* Layout styles */
        .td1 {
            float: right;
            margin-top: 3.5rem;
        }

        .class3 {
            display: flex;
        }

        .class4 {
            display: grid;
        }

        .down {
            margin-top: 4rem;
        }

        .main {
            border-width: 2px;
            border-style: dashed;
            border-color: rgb(75 85 99 / var(--tw-border-opacity));
            background-color: rgb(255 255 255 / var(--tw-bg-opacity));
            padding: 1.5rem;
            width: 250mm;
            height: auto;
            margin-left: 3rem;
            margin-left: auto;
            margin-right: auto;
        }

        /* Container styles */
        .container {
            max-width: 640px;
        }

        @media (min-width: 768px) {
            .container {
                max-width: 768px;
                padding-bottom: 3rem;
            }
        }

        /* Logo styles */
        .logo {
            grid-column: 1;
        }

        @media (min-width: 768px) {
            .logo {
                grid-column: 12;
            }
        }

        @media (min-width: 1024px) {
            .logo {
                grid-column: 12;
            }
        }

        .relative {
            position: relative;
        }

        .logosize {
            height: 5rem;
            margin-left: auto;
            margin-right: auto;
        }

        /* Grid container styles */
        .grid-container {
            display: grid;
            grid-template-columns: 1fr;
            padding: 0.5rem; /* You can adjust the padding value as needed */
        }

        /* Centered content styles */
        .centered-content {
            margin: 0 auto;
            text-align: center;
        }

        /* Heading styles */
        .heading {
            font-weight: bold;
            font-size: 1.25rem; /* This is equivalent to text-2xl in many frameworks */
            text-transform: uppercase;
            color: #333; /* You can adjust the color value */
            border: 1px solid #555; /* You can adjust the border color and width */
            width: 24rem;
            padding: 0.5rem;
        }

        /* Styles for the outer <div> */
        .outer-div {
            /* Add any styles you want for the outer div here */
        }

        /* Styles for the table */
        .table {
            width: 100%;
            padding: 0.5rem;
            margin-top: 0.5rem; /* Add any additional table styles here */
        }

        /* Styles for table rows */
        .table tr {
            /* Add any styles for table rows here */
        }

        /* Styles for table cells */
        .table td {
            /* Add any styles for table cells here */
        }

        /* Styles for flex containers */
        .flex-container {
            display: flex;
            padding: 0.25rem;
            font-size: 1.5rem;
            align-items: center;
        }

        /* Styles for text */
        .text {
            color: #333; /* Adjust the color as needed */
            font-family: sans-serif;
        }

        /* Styles for headings with font-semibold */
        .font-semibold {
            font-weight: bold;
        }

        /* Styles for individual pieces of data */
        .data {
            flex: 1;
            padding-left: 0.5rem; /* Adjust the padding as needed */
        }

        /* Styles for date formatting */
        .date {
            font-size: 1.5rem;
            font-family: sans-serif;
        }

        /* Styles for mobile number and category */
        .mobile-no,
        .category {
            font-size: 1.5rem;
            font-family: sans-serif;
        }

        /* Additional styles can be added as needed */

        /* Hide empty data elements */
        .empty-data {
            display: none;
        }

        /* Styles for the grid container */
        .online {
            grid-column: 1;
            padding: 8px;
            margin-top: 12px;
        }

        /* Styles for responsive grid columns */
        @media (min-width: 768px) {
            .online {
                grid-column: 12;
            }
        }

        @media (min-width: 1280px) {
            .online {
                grid-column: 12;
            }
        }

        /* Payment styles */
        .payment {
            font-weight: bold;
            font-size: 1.5rem; /* This is equivalent to text-xl in some frameworks */
            text-align: left;
        }

        /* Styles for the connection class */
        .connection {
            grid-column: 1;
            padding: 8px;
            margin-top: -1px;
        }

        /* Styles for responsive grid columns */
        @media (min-width: 768px) {
            .connection {
                grid-column: 12;
            }
        }

        @media (min-width: 1280px) {
            .connection {
                grid-column: 12;
            }
        }

        /* Container styles */
        .custom-table-container {
            width: 100%;
            margin: 20px auto;
            font-family: Arial, sans-serif;
        }

        /* Table styles */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #333;
        }

        /* Table header styles */
        .custom-table thead {
            background-color: transparent; /* Remove background color */
            color: black; /* Change text color to black */
            font-weight: bold;
        }

        .custom-table th,
        .custom-table td {
            padding: 10px;
            border: 1px solid #333;
            text-align: center;
        }

        /* Table cell styles */
        .custom-table th {
            width: 40%; /* Adjust as needed */
        }

        .custom-table td {
            width: 30%; /* Adjust as needed */
        }

        /* Alternate row background color */
        .custom-table tbody tr:nth-child(even) {
            background-color: transparent; /* Remove background color */
        }

        /* Center align text in the header */
        .custom-table th {
            text-align: center;
        }

        /* Left align text in the first column */
        .custom-table td:first-child {
            text-align: left;
        }

        /* Right align text in the last column */
        .custom-table td:last-child {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="main">
        <div class="logo">
            <div>
                <img src="https://zeevector.com/wp-content/uploads/LOGO/Swachh-Bharat-Logo-PNG.png" class="logosize" />
                <div class="transparent">
                    <img src="https://zeevector.com/wp-content/uploads/LOGO/Swachh-Bharat-Logo-PNG.png" />
                </div>
                <div class='ulb'>
                    <div class=''>
                        <h1 class='text-center text-4xl font-bold'>Akola Municipal Corporation</h1>
                    </div>
                </div>
                <div class='grid-container'>
                    <div class='centered-content'>
                        <h1 class='heading'>WATER USER CHARGE PAYMENT RECEIPT</h1>
                    </div>
                </div>
                <div class='outer-div'>
                    <table class='table'>
                        <tr>
                            <td>
                                <div class='flex-container'>
                                    <h1 class=''>Receipt No:{{$data}} :</h1>
                                    <h1 class=''></h1>
                                </div>
                                <div class=' flex-container'>
                                    <h1 class=''>Department/Section :</h1>
                                    <h1 class=''> Water </h1>
                                </div>
                                <div class='flex-container'>
                                    <h1 class=''>Account Description  :</h1>
                                    <h1 class=''>water user charge</h1>
                                </div>
                                <!-- Add more data elements here -->
                            </td>
                            <td>
                                <div class='flex-container'>
                                    <h1 class=''>Date :</h1>
                                    <h1 class=''></h1>
                                </div>
                                <div class='flex-container'>
                                    <h1 class=''>Ward No:</h1>
                                    <h1 class=''></h1>
                                </div>
                                <div class='flex-container'>
                                    <h1 class=''>Consumer No :</h1>
                                    <h1 class=''></h1>
                                </div>
                                <!-- Add more data elements here -->
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="down">
                    <div class='flex-container'>
                        <h1 class=''>Received From Mr/Mrs/Miss :</h1>
                        <h1 class=''></h1>
                    </div>
                    <div class='flex-container'>
                        <h1 class=''>Mobile No :</h1>
                        <h1 class=''></h1>
                    </div>
                    <div class='flex-container'>
                        <h1 class=''>Address:</h1>
                        <h1 class=''></h1>
                    </div>
                    <div class='flex-container'>
                        <h1 class=''>A sum of Rs :</h1>
                        <h1 class=''></h1>
                    </div>
                    <div class='flex-container'>
                        <h1 class=''>Towards : <span>vide <span></span> </vide:span> </span></h1>
                        <h1 class=''></h1>
                    </div>
                </div>
                <div class='online'>
                    <div class=''>
                        <h1 class='payment '>N.B. Online Payment/Cheque/Draft/Bankers Cheque are Subject to Realisation</h1>
                    </div>
                </div>
                <div class=''>
                    <div class=''>
                        <h1 class='payment '>WATER CONNECTION FEE DETAILS</h1>
                    </div>
                </div>
                <div class="custom-table-container">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th colspan="2">Description</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2">Connection Fee</td>
                                <td>522</td>
                            </tr>
                            <tr>
                                <td colspan="2">Payable Amount</td>
                                <td>522</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div>
                    <table class="table2">
                        <tr class=''>
                            <td class=' '>
                                <div class=''>
                                    <!-- QrCode component goes here -->
                                </div>
                                <div class='class3 '>
                                    <h1 class='heading1'>For Details Please Visit : {}</h1>
                                </div>
                                <div class='class3 '>
                                    <h1 class='heading1'>Or Call us at OR {}</h1>
                                </div>
                            </td>
                            <td class='td1 '>
                                <div class='class3 '>
                                    <h1 class='heading1'>In Collaboration with</h1>
                                </div>
                                <div class='class3'>
                                    <h1 class='heading1'>{}</h1>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class=''>
                    <div class=''>
                        <h1 class='heading4'>**This is a computer-generated receipt and it does not require a physical signature.**
                        </h1>
                    </div>
                </div>
                <div>
                    <img src="https://zeevector.com/wp-content/uploads/LOGO/Swachh-Bharat-Logo-PNG.png" class="logosize" />
                </div>
            </div>
        </div>
    </div>
</body>
</html>
