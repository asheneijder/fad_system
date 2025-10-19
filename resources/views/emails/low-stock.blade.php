<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Low Stock Alert</title>
    <style>
        /* Base Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f7fa;
        }

        .container {
            max-width: 650px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 30px 20px;
            text-align: center;
            color: white;
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 600;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }

        /* Alert Banner */
        .alert-banner {
            background-color: #fff9e6;
            border-left: 5px solid #ffc107;
            padding: 20px;
            margin: 0;
        }

        .alert-banner h2 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 20px;
        }

        .alert-banner p {
            margin: 0;
            color: #856404;
        }

        /* Content */
        .content {
            padding: 25px 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e3c72;
            margin: 0 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #eaeaea;
        }

        /* Stock Table */
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 14px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stock-table th {
            background-color: #f8f9fa;
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }

        .stock-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }

        .stock-table tr:last-child td {
            border-bottom: none;
        }

        .stock-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Indicators */
        .stock-level {
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
        }

        .out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }

        .low-stock {
            background-color: #fff3cd;
            color: #856404;
        }

        /* Action Button */
        .action-section {
            text-align: center;
            margin: 30px 0 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 28px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(30, 60, 114, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(30, 60, 114, 0.4);
        }

        /* Footer */
        .footer {
            background-color: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
            border-top: 1px solid #e9ecef;
        }

        .footer p {
            margin: 5px 0;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 0;
            }

            .content {
                padding: 20px 15px;
            }

            .stock-table {
                font-size: 13px;
            }

            .stock-table th,
            .stock-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Low Stock Alert</h1>
            <p>Asset Management System</p>
        </div>

        <div class="alert-banner">
            <h2>Attention Required: Low Stock Items</h2>
            <p>The following stationary items are running low on stock and may need to be reordered soon to avoid
                disruption.</p>
        </div>

        <div class="content">
            <h3 class="section-title">Low Stock Items</h3>

            <table class="stock-table">
                <thead>
                    <tr>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Minimum Stock</th>
                        <th>Status</th>
                        <th>Supplier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ ucfirst($item->category) }}</td>
                            <td>
                                <span
                                    class="stock-level {{ $item->current_stock == 0 ? 'out-of-stock' : 'low-stock' }}">
                                    {{ $item->current_stock }} {{ $item->unit }}
                                </span>
                            </td>
                            <td>{{ $item->min_stock }} {{ $item->unit }}</td>
                            <td>
                                @if ($item->current_stock == 0)
                                    <span class="stock-level out-of-stock">Out of Stock</span>
                                @else
                                    <span class="stock-level low-stock">Low Stock</span>
                                @endif
                            </td>
                            <td>{{ $item->supplier ?: 'Not specified' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="action-section">
                <a href="{{ url('/admin/stationary-items') }}" class="btn">Manage Stationary Items</a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated notification from your Asset Management System.</p>
            <p>Please review these items and reorder as necessary to maintain operational efficiency.</p>
        </div>
    </div>
</body>

</html>
