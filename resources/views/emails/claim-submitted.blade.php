<x-mail::message>
# New Claim Submitted for Approval

Dear {{ $approver->name }},

A new **{{ $claimTypeDisplay }}** claim has been submitted and requires your approval.

<x-mail::panel>
## Claim Details:
- **Claim ID:** #{{ $claimData['claim_id'] }}
- **Submitted By:** {{ $claimData['submitter_name'] }} ({{ $claimData['submitter_email'] }})
- **Claim Date:** {{ \Carbon\Carbon::parse($claimData['claim_date'])->format('M d, Y') }}
- **Purpose:** {{ $claimData['purpose'] }}
- **Claim Amount:** RM {{ number_format($claimData['amount'], 2) }}

@if($claimType === 'daily')
- **Allowance Type:** {{ ucfirst(str_replace('_', ' ', $claimData['allowance_type'])) }}
- **Destination:** {{ $claimData['destination'] }}
@elseif($claimType === 'travel')
- **Vehicle Type:** {{ ucfirst($claimData['vehicle_type']) }}
- **Total Distance:** {{ $claimData['total_distance'] }} km
- **Route:** {{ $claimData['travel_from'] }} to {{ $claimData['travel_to'] }}
@elseif($claimType === 'accommodation')
- **Hotel:** {{ $claimData['hotel_name'] }}
- **Stay Duration:** {{ \Carbon\Carbon::parse($claimData['check_in_date'])->format('M d') }} - {{ \Carbon\Carbon::parse($claimData['check_out_date'])->format('M d, Y') }}
- **Location:** {{ $claimData['destination_city'] }}, {{ $claimData['destination_country'] }}
@elseif($claimType === 'transportation')
- **Transport Type:** {{ ucfirst($claimData['transport_type']) }}
- **Route:** {{ $claimData['from_location'] }} to {{ $claimData['to_location'] }}
- **Trip Type:** {{ ucfirst($claimData['trip_type']) }}
@endif
</x-mail::panel>

Please review this claim in the system and take appropriate action.

<x-mail::button :url="route('admin.claim-request.index')">
Review Claim
</x-mail::button>

Thank you for your attention to this matter.

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::subcopy>
This is an automated notification. Please do not reply to this email.
</x-mail::subcopy>