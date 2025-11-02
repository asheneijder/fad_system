<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Accommodation Claim #{{ $accommodationClaim->id }}</title>
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
        
        .status-draft { background: #6b7280; }
        .status-submitted { background: #f59e0b; }
        .status-pending { background: #f59e0b; }
        .status-approved { background: #10b981; }
        .status-rejected { background: #ef4444; }
        .status-paid { background: #3b82f6; }
        
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
        
        .remarks-box {
            background: #fef3c7;
            padding: 8px 10px;
            border-radius: 4px;
            border-left: 3px solid #f59e0b;
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
            <div class="field-value">Accommodation Claim</div>
        </div>
        
        <div class="document-title">
            <h1>ACCOMMODATION CLAIM</h1>
            <div class="field-value">Date: {{ $currentDate }}</div>
        </div>
        
        <div class="claim-info">
            <div class="claim-number">#ACC-{{ str_pad($accommodationClaim->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="status-badge status-{{ $accommodationClaim->status }}">{{ ucfirst($accommodationClaim->status) }}</div>
        </div>
    </div>

    <!-- Employee & Hotel Info -->
    <div class="grid-2col">
        <div class="section">
            <div class="section-title">EMPLOYEE INFORMATION</div>
            <div class="field">
                <div class="field-label">Name</div>
                <div class="field-value">{{ $accommodationClaim->user->name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Department</div>
                <div class="field-value">{{ $accommodationClaim->user->department ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Position</div>
                <div class="field-value">{{ $accommodationClaim->user->job_title ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">ACCOMMODATION DETAILS</div>
            <div class="field">
                <div class="field-label">Hotel Name</div>
                <div class="field-value">{{ $accommodationClaim->hotel_name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Stay Period</div>
                <div class="field-value">{{ $formatDate($accommodationClaim->check_in_date) }} - {{ $formatDate($accommodationClaim->check_out_date) }}</div>
            </div>
            <div class="field">
                <div class="field-label">Number of Nights</div>
                <div class="field-value">{{ $accommodationClaim->number_of_nights ?? 0 }}</div>
            </div>
            <div class="field">
                <div class="field-label">Currency</div>
                <div class="field-value">{{ $accommodationClaim->currency_display }}</div>
            </div>
            <div class="field">
                <div class="field-label">Destination</div>
                <div class="field-value">{{ $accommodationClaim->destination_city ?? '—' }}, {{ $accommodationClaim->destination_country ?? '—' }}</div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown -->
    <div class="section">
        <div class="section-title">COST BREAKDOWN</div>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Room Rate ({{ $accommodationClaim->number_of_nights }} nights × {{ $formatCurrency($accommodationClaim->rate_per_night, $accommodationClaim->currency) }}/night)</td>
                    <td class="text-right">{{ $formatCurrency($accommodationClaim->subtotal_amount, $accommodationClaim->currency) }}</td>
                </tr>
                <tr>
                    <td>Tax ({{ $accommodationClaim->tax_percentage }}%)</td>
                    <td class="text-right">{{ $formatCurrency($accommodationClaim->tax_amount, $accommodationClaim->currency) }}</td>
                </tr>
                <tr>
                    <td>Service Charge ({{ $accommodationClaim->service_charge_percentage }}%)</td>
                    <td class="text-right">{{ $formatCurrency($accommodationClaim->service_charge_amount, $accommodationClaim->currency) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>TOTAL CLAIM AMOUNT</strong></td>
                    <td class="text-right text-success"><strong>{{ $formatCurrency($accommodationClaim->total_amount, $accommodationClaim->currency) }}</strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Purpose & Calculation -->
    <div class="grid-2col">
        <div class="section">
            <div class="section-title">BUSINESS PURPOSE</div>
            <div class="purpose-box">
                {{ $accommodationClaim->purpose ?? 'No purpose specified' }}
            </div>
            
            @if($accommodationClaim->remarks)
            <div class="section-title" style="margin-top: 15px;">ADDITIONAL REMARKS</div>
            <div class="remarks-box">
                {{ $accommodationClaim->remarks }}
            </div>
            @endif
        </div>

        <div class="section">
            <div class="section-title">CALCULATION SUMMARY</div>
            <div class="calculation-box">
                <h4 style="margin: 0 0 10px 0; color: #0369a1; font-size: 12px;">BREAKDOWN</h4>
                <div style="background: white; padding: 10px; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Room Rate:</span>
                        <span class="font-bold">{{ $formatCurrency($accommodationClaim->rate_per_night, $accommodationClaim->currency) }} × {{ $accommodationClaim->number_of_nights }} nights</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Tax:</span>
                        <span class="font-bold">{{ $accommodationClaim->tax_percentage }}%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                        <span>Service Charge:</span>
                        <span class="font-bold">{{ $accommodationClaim->service_charge_percentage }}%</span>
                    </div>
                    <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 6px 0;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600;">
                        <span>Total:</span>
                        <span class="text-success">{{ $formatCurrency($accommodationClaim->total_amount, $accommodationClaim->currency) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Amount & Admin Info -->
    <div class="amount-highlight">
        TOTAL CLAIM AMOUNT: {{ $formatCurrency($accommodationClaim->total_amount, $accommodationClaim->currency) }}
    </div>

    <div class="grid-3col">
        <div class="field">
            <div class="field-label">Document Created</div>
            <div class="field-value">{{ $accommodationClaim->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="field">
            <div class="field-label">Check-in Date</div>
            <div class="field-value">{{ $formatDate($accommodationClaim->check_in_date) }}</div>
        </div>
        @if($accommodationClaim->approver)
        <div class="field">
            <div class="field-label">Approved By</div>
            <div class="field-value text-success">{{ $accommodationClaim->approver->name }}</div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Document ID: ACC-{{ str_pad($accommodationClaim->id, 6, '0', STR_PAD_LEFT) }} | Generated: {{ now()->format('d/m/Y H:i') }}</div>
        <div>Computer-generated document - Valid without signature</div>
    </div>
</body>
</html>