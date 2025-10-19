<!DOCTYPE html>
<html>

<head>
    <title>License Expiration Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
        }

        .alert {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .critical {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
        }

        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
        }

        .license-list {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .license-list th,
        .license-list td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .license-list th {
            background: #f8f9fa;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>License Expiration Alert</h1>
            <p>Asset Management System</p>
        </div>

        <div class="alert {{ $alertLevel }}">
            <h2>{{ $subject }}</h2>
            <p>{{ $message }}</p>
        </div>

        <h3>Expiring Licenses (Next {{ $days }} days):</h3>

        <table class="license-list">
            <thead>
                <tr>
                    <th>License Name</th>
                    <th>Manufacturer</th>
                    <th>Expiration Date</th>
                    <th>Days Left</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($licenses as $license)
                    <tr>
                        <td>{{ $license->license_name }}</td>
                        <td>{{ $license->manufacturer }}</td>
                        <td>{{ $license->expiration_date->format('M d, Y') }}</td>
                        <td>
                            <strong>{{ $license->expiration_date->diffInDays(now()) }} days</strong>
                        </td>
                        <td>
                            @if ($license->expiration_date->diffInDays(now()) <= 7)
                                <span style="color: #dc3545;">Critical</span>
                            @elseif($license->expiration_date->diffInDays(now()) <= 30)
                                <span style="color: #ffc107;">Warning</span>
                            @else
                                <span style="color: #28a745;">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top: 30px; text-align: center;">
            <a href="{{ url('/admin/licenses') }}" class="btn">View All Licenses</a>
        </div>

        <footer
            style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666;">
            <p>This is an automated notification from your Asset Management System.</p>
            <p>Please review these licenses and take appropriate action.</p>
        </footer>
    </div>
</body>

</html>
