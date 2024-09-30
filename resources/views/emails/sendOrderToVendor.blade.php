<!DOCTYPE html>
<html>
<body>
    <h1>Purchase Order</h1>
    <p>Dear {{ $data['vendorDetails']['name'] ?? '-'}},</p>
    <p>We would like to place an order, Please find attached pdf to check the order.</p>
    <p>Thank you!</p>
</body>
</html>
