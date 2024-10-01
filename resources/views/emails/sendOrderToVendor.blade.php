<!DOCTYPE html>
<html>
    <style>
        body {
            color: #000000;
        }
    </style>
<body>
    <h1>Purchase Order</h1>
    <p>Dear {{ $data['vendorDetails']['name'] ?? '-'}},</p>
    <p>We would like to place an order. Please find the attached PDF to check the order.</p>
    <p>Thank you!</p>
</body>
</html>
