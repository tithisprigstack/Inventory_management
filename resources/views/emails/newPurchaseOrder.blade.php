<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview PO Details</title>
    <style>
        body {
            font-family: "Poppins", sans-serif;
            margin: 0;
            padding: 0;
            /* background-color: #f9f9f9; */
        }
        .container {
            width: 80%;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h3 {
            text-align: center;
            /* font-size: 24px; */
            margin-bottom: 20px;
            font-weight: 700;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .header p {
            margin: 0;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            text-align: left;
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px 15px;
            font-size: 14px;
        }
        th {
            background-color: #f4f4f4;
        }
        .text-right {
            text-align: right;
        }
        .table-summary {
            margin-top: 20px;
            font-size: 14px;
            text-align: right;
        }
        .table-summary strong {
            font-size: 14px;
        }
        .total-row {
            background-color: #f4f4f4;
            font-weight: bold;
        }
        .vendor-details p {
            margin-bottom: 10px;
        }
        hr{
            margin: 20px 0;
            border: 0;
            height: 1px;
            background: #ddd;
        }
    </style>
</head>
<body>

<div class="container">
    <h3>Purchase Order</h3>
    <hr>
    <div class="header">
        <div class="text-right">
            <p><strong>Generated on:</strong> {{ date('Y-m-d') }}</p>
        </div>
        <div class="vendor-details">
            <p><strong>Vendor Name:</strong> {{ $data['vendorDetails']['name'] ?? '-' }}</p>
            <p><strong>Address:</strong> {{ $data['vendorDetails']['address'] ?? '-' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total Price</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($data['inventoryDetails'] as $inventory)
            <tr>
                <td>{{ $inventory['inventory']['name'] }}</td>
                <td>{{ $inventory['poItemDetails']['quantity'] ?? '-' }}</td>
                <td>{{ $inventory['poItemDetails']['price']  }}</td>
                <td>{{ ($inventory['poItemDetails']['quantity'] ) * ($inventory['poItemDetails']['price'] ) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="table-summary">
        <p><strong>Total Price:</strong> {{ array_sum(array_map(function($inventory) {
            return ($inventory['poItemDetails']['quantity']) * ($inventory['poItemDetails']['price']);
        }, $data['inventoryDetails'])) }}</p>
    </div>
</div>

</body>
</html>
