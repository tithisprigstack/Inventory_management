<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Low-Stock Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: black !important;
            margin: 0;
            padding: 20px; /* Add padding to the body */
            background-color: #ffffff; /* White background for the body */
        }
        h2 {
            margin: 0;
            font-size: 24px; /* Adjust font size for the header */
        }
        p {
            line-height: 1.5;
            margin: 0.5em 0;
            font-weight: 500
        }
        .footer {
            text-align:start;
            padding: 10px 0; /* Add some padding to the footer */
        }
    </style>
</head>
<body>
    <h2>Low-Stock Reminder</h2>
    <p>Hello,</p>
    <p>You receive this reminder because your item <strong>{{ $data['inventoryDetails']['name'] ?? '-' }}</strong> current stock is lower than the minimum quantity you have set.</p>
    <p><strong>Item Name:</strong> {{ $data['inventoryDetails']['name'] ?? '-' }}</p>
    <p><strong>Current Stock:</strong> {{ $data['inventoryDetails']['quantity'] ?? '-' }}</p>
    <p><strong>Minimum Quantity:</strong> {{ $data['inventoryDetails']['reminder_quantity'] ?? '-' }}</p>
    <div class="footer">
        <p>Thanks!</p>
    </div>
</body>
</html>
