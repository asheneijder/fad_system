<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Allowance #{{ $dailyAllowance->id }}</title>
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
        .status-approved { background: #10b981; }
        .status-rejected { background: #ef4444; }
        
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
            <div class="field-value">Daily Allowance Claim</div>
        </div>
        
        <div class="document-title">
            <h1>DAILY ALLOWANCE CLAIM</h1>
            <div class="field-value">Date: {{ $currentDate }}</div>
        </div>
        
        <div class="claim-info">
            <div class="claim-number">#DAL-{{ str_pad($dailyAllowance->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="status-badge status-{{ $dailyAllowance->status }}">{{ ucfirst($dailyAllowance->status) }}</div>
        </div>
    </div>

    <!-- Employee & Allowance Info -->
    <div class="grid-2col">
        <div class="section">
            <div class="section-title">EMPLOYEE INFORMATION</div>
            <div class="field">
                <div class="field-label">Name</div>
                <div class="field-value">{{ $dailyAllowance->user->name ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Department</div>
                <div class="field-value">{{ $dailyAllowance->user->department ?? '—' }}</div>
            </div>
            <div class="field">
                <div class="field-label">Position</div>
                <div class="field-value">{{ $dailyAllowance->user->job_title ?? '—' }}</div>
            </div>
        </div>

        <div class="section">
            <div class="section-title">ALLOWANCE DETAILS</div>
            <div class="field">
                <div class="field-label">Claim Date</div>
                <div class="field-value">{{ $formatDate($dailyAllowance->claim_date) }}</div>
            </div>
            <div class="field">
                <div class="field-label">Allowance Type</div>
                <div class="field-value">{{ $dailyAllowance->allowance_type_display }}</div>
            </div>
            <div class="field">
                <div class="field-label">Currency</div>
                <div class="field-value">{{ $dailyAllowance->currency_display }}</div>
            </div>
            <div class="field">
                <div class="field-label">Daily Rate</div>
                <div class="field-value">{{ $formatCurrency($dailyAllowance->daily_rate, $dailyAllowance->currency) }}</div>
            </div>
            <div class="field">
                <div class="field-label">Destination</div>
                <div class="field-value">{{ $dailyAllowance->destination ?? '—' }}</div>
            </div>
        </div>
    </div>

    <!-- Purpose -->
    <div class="section">
        <div class="section-title">BUSINESS PURPOSE</div>
        <div class="purpose-box">
            {{ $dailyAllowance->purpose ?? 'No purpose specified' }}
        </div>
    </div>

    <!-- Calculation & Amount -->
    <div class="section">
        <div class="section-title">ALLOWANCE CALCULATION</div>
        <div class="grid-2col">
            <div class="calculation-box">
                <h4 style="margin: 0 0 10px 0; color: #0369a1; font-size: 12px;">CALCULATION BREAKDOWN</h4>
                <div style="background: white; padding: 10px; border-radius: 4px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Daily Rate:</span>
                        <span class="font-bold">{{ $formatCurrency($dailyAllowance->daily_rate, $dailyAllowance->currency) }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Allowance Type:</span>
                        <span class="font-bold">{{ $dailyAllowance->allowance_type_display }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span>Claim Percentage:</span>
                        <span class="font-bold">{{ $dailyAllowance->claim_percentage }}%</span>
                    </div>
                    <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0;">
                    <div style="display: flex; justify-content: space-between; font-weight: 600;">
                        <span>Claim Amount:</span>
                        <span class="text-success">{{ $formatCurrency($dailyAllowance->claim_amount, $dailyAllowance->currency) }}</span>
                    </div>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: center;">
                <div style="text-align: center;">
                    <div class="field-label" style="font-size: 11px; margin-bottom: 8px;">TOTAL CLAIM AMOUNT</div>
                    <div style="font-size: 24px; font-weight: 700; color: #059669;">
                        {{ $formatCurrency($dailyAllowance->claim_amount, $dailyAllowance->currency) }}
                    </div>
                    <div style="font-size: 9px; color: #6b7280; margin-top: 4px;">
                        {{ $dailyAllowance->currency }} • {{ $formatDate($dailyAllowance->claim_date) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formula Explanation -->
    <div class="section">
        <div class="section-title">CALCULATION FORMULA</div>
        <div style="background: #f8fafc; padding: 10px; border-radius: 4px; font-size: 10px;">
            <div style="text-align: center; margin-bottom: 5px;">
                <strong>Daily Rate × Claim Percentage = Claim Amount</strong>
            </div>
            <div style="text-align: center;">
                {{ $formatCurrency($dailyAllowance->daily_rate, $dailyAllowance->currency) }} × {{ $dailyAllowance->claim_percentage }}% = 
                <strong class="text-success">{{ $formatCurrency($dailyAllowance->claim_amount, $dailyAllowance->currency) }}</strong>
            </div>
        </div>
    </div>

    <!-- Admin Info -->
    <div class="grid-3col">
        <div class="field">
            <div class="field-label">Document Created</div>
            <div class="field-value">{{ $dailyAllowance->created_at->format('d/m/Y H:i') }}</div>
        </div>
        <div class="field">
            <div class="field-label">Claim Date</div>
            <div class="field-value">{{ $formatDate($dailyAllowance->claim_date) }}</div>
        </div>
        @if($dailyAllowance->approver)
        <div class="field">
            <div class="field-label">Approved By</div>
            <div class="field-value text-success">{{ $dailyAllowance->approver->name }}</div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Document ID: DAL-{{ str_pad($dailyAllowance->id, 6, '0', STR_PAD_LEFT) }} | Generated: {{ now()->format('d/m/Y H:i') }}</div>
        <div>Computer-generated document - Valid without signature</div>
    </div>
</body>
</html>