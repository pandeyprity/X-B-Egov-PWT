<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }

        .container {
            text-align: center;
            margin: 100px auto;
            max-width: 600px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .message {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #DC3545;
            /* Red color for error */
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #C82333;
            /* Darker red on hover */
        }
    </style>
</head>

<body>
    <div class="container">
        <p class="message">Oops! Something went wrong.</p>
        <p>We encountered an error while processing your payment.</p>
        <p>Please click the button below to retry</p>
        <a href="{{ $redirectUrl }}" class="button">Retry Payment</a>
    </div>
</body>

</html>