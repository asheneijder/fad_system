<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Travel Claim #{{ $travelClaim->id }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        
        body { 
            font-family: 'Inter', Arial, sans-serif; 
            margin: 15px;
            padding: 0;
            font-size: 11px;
            line-height: 1.3;
            color: #1f2937;
            background: #ffffff;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .company-info {
            flex: 1;
        }
        
        .company-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
            margin: 0;
        }
        
        .document-title {
            text-align: center;
            flex: 2;
        }
        
        .document-title h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .claim-info {
            flex: 1;
            text-align: right;
        }
        
        .claim-number {
            font-size: 14px;
            font-weight: 600;
            color: #1e40af;
            margin: 0;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            background: #10b981;
            color: white;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .grid-3col {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .section {
            margin-bottom: 15px;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .field {
            margin-bottom: 6px;
        }
        
        .field-label {
            font-weight: 500;
            color: #6b7280;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        
        .field-value {
            font-size: 10px;
            font-weight: 500;
            color: #1f2937;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin: 8px 0;
        }
        
        th, td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
            text-align: left;
        }
        
        th {
            background: #f3f4f6;
            font-weight: 600;
        }
        
        .total-row {
            background: #f8fafc;
            font-weight: 600;
        }
        
        .calculation-box {
            background: #f0f9ff;
            padding: 10px;
            border-radius: 4px;
            border-left: 3px solid #0ea5e9;
            margin: 10px 0;
        }
        
        .amount-highlight {
            font-size: 16px;
            font-weight: 700;
            color: #059669;
            text-align: center;
            margin: 10px 0;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
        
        .purpose-box {
            background: #f9fafb;
            padding: 8px 10px;
            border-radius: 4px;
            border-left: 3px solid #10b981;
            font-size: 10px;
            margin: 8px 0;
        }
        
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-primary { color: #3b82f6; }
        .text-success { color: #059669; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ config('app.name', 'COMPANY') }}</div>
            <div class="field-value">Travel Expense Claim</div>
        </div>
        
        <div class="document-title">
            <h1>EMPLOYEE TRAVEL CLAIM</h1>
            <div class="field-value">Date: {{ $currentDate }}</div>
        </div>
        
        <div class="claim-info">
            <div class="claim-number">#TRV-{{ str_pad($travelClaim->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="status-badge">{{ ucfirst($travelClaim->status) }}</div>
        </div>
    </div>

    <!-- Employee & Vehicle Info -->
    <div class="grid-2col">
        <div class="section">
            <div class="section-title">EMPLOYEE INFORMATION</div>
            <div class="field">
                <div class="field-label">Name</div>
                <div class="field-value">{{ $travelClaim->user->name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Department</div>
                <div class="field-value">{{ $travelClaim->user->department ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Position</div>
                <div class="field-value">{{ $travelClaim->user->job_title ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">VEHICLE DETAILS</div>
            <div class="field">
                <div class="field-label">Vehicle Type</div>
                <div class="field-value">{{ $travelClaim->vehicle_type ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Registration No.</div>
                <div class="field-value">{{ $travelClaim->registration_plate_number ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Engine Capacity</div>
                <div class="field-value">{{ $travelClaim->cubic_capacity ? number_format($travelClaim->cubic_capacity) . ' cc' : '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Approved Rate</div>
                <div class="field-value">{{ $formatCurrency($travelClaim->rate_per_km) }}/km</div>
            </div>
        </div>
    </div>

    <!-- Travel Details -->
    <div class="section">
        <div class="section-title">TRAVEL DETAILS</div>
        
        @if(!empty($travelClaim->travel_legs_data) && count($travelClaim->travel_legs_data) > 0)
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Distance</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($travelClaim->travel_legs_data as $index => $leg)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $formatDate($leg['date'] ?? null) }}</td>
                    <td>{{ $leg['from'] ?? '—' }}</td>
                    <td>{{ $leg['to'] ?? '—' }}</td>
                    <td class="text-center">{{ number_format($leg['distance'] ?? 0, 1) }} km</td>
                    <td class="text-right">{{ $formatCurrency(($leg['distance'] ?? 0) * $travelClaim->rate_per_km) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                    <td class="text-center"><strong>{{ number_format($travelClaim->total_distance, 1) }} km</strong></td>
                    <td class="text-right text-success"><strong>{{ $formatCurrency($travelClaim->total_cost) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="grid-2col">
            <div class="field">
                <div class="field-label">Route</div>
                <div class="field-value">{{ $travelClaim->travel_from ?? '—' }} → {{ $travelClaim->travel_to ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Travel Date</div>
                <div class="field-value">{{ $formatDate($travelClaim->date_of_travel) }}</div>
            </div>
        </div>
        @endif
    </div>

    <!-- Purpose & Calculation -->
    <div class="grid-2col">
        <div class="section">
            <div class="section-title">BUSINESS PURPOSE</div>
            <div class="purpose-box">
                {{ $travelClaim->purpose ?? 'No purpose specified' }}
            </div>
        </div>

        <div class="section">
            <div class="section-title">CALCULATION</div>
            <div class="calculation-box">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Distance:</span>
                    <span class="font-bold">{{ $travelClaim->total_distance }} km</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                    <span>Rate:</span>
                    <span class="font-bold">{{ $formatCurrency($travelClaim->rate_per_km) }}/km</span>
                </div>
                <hr style="border: none; border-top: 1px solid #cbd5e1; margin: 6px 0;">
                <div style="display: flex; justify-content: space-between; font-weight: 600;">
                    <span>Total:</span>
                    <span class="text-success">{{ $formatCurrency($travelClaim->total_cost) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Amount & Admin Info -->
    <div class="amount-highlight">
        TOTAL CLAIM AMOUNT: {{ $formatCurrency($travelClaim->total_cost) }}
    </div>

    <div class="grid-3col">
        <div class="field">
            <div class="field-label">Created</div>
            <div class="field-value">{{ $travelClaim->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="field">
            <div class="field-label">Claim Date</div>
            <div class="field-value">{{ $formatDate($travelClaim->claim_date) }}</div>
        </div>
        @if($travelClaim->approver)
        <div class="field">
            <div class="field-label">Approved By</div>
            <div class="field-value text-success">{{ $travelClaim->approver->name }}</div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Document ID: TRV-{{ str_pad($travelClaim->id, 6, '0', STR_PAD_LEFT) }} | Generated: {{ now()->format('d/m/Y H:i') }}</div>
        <div>Computer-generated document - Valid without signature</div>
    </div>
</body>
</html>