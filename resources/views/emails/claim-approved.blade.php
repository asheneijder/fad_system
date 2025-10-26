<x-mail::message>
# Claim Approved!

Dear {{ $user->name }},

We are pleased to inform you that your **{{ $claimTypeDisplay }}** claim has been approved.

<x-mail::panel>
## Claim Details:
- **Claim ID:** #{{ $claimData['claim_id'] }}
- **Claim Date:** {{ \Carbon\Carbon::parse($claimData['claim_date'])->format('M d, Y') }}
- **Purpose:** {{ $claimData['purpose'] }}
- **Approved Amount:** RM {{ number_format($claimData['amount'], 2) }}
- **Approved By:** {{ $approver->name }}
- **Approval Date:** {{ now()->format('M d, Y \a\t h:i A') }}

@if($claimType === 'daily')
- **Allowance Type:** {{ ucfirst(str_replace('_', ' ', $claimData['allowance_type'])) }}
- **Destination:** {{ $claimData['destination'] }}
@elseif($claimType === 'travel')
- **Vehicle Type:** {{ ucfirst($claimData['vehicle_type']) }}
- **Total Distance:** {{ $claimData['total_distance'] }} km
@elseif($claimType === 'accommodation')
- **Hotel:** {{ $claimData['hotel_name'] }}
- **Stay Duration:** {{ \Carbon\Carbon::parse($claimData['check_in_date'])->format('M d') }} - {{ \Carbon\Carbon::parse($claimData['check_out_date'])->format('M d, Y') }}
@elseif($claimType === 'transportation')
- **Transport Type:** {{ ucfirst($claimData['transport_type']) }}
- **Route:** {{ $claimData['from_location'] }} to {{ $claimData['to_location'] }}
@endif
</x-mail::panel>

Your claim has been processed successfully and will be included in the next payment cycle.

If you have any questions, please contact the finance department.

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::subcopy>
This is an automated notification. Please do not reply to this email.
</x-mail::subcopy>