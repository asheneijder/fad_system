<x-mail::message>
# Claim Rejected

Dear {{ $user->name }},

We regret to inform you that your **{{ $claimTypeDisplay }}** claim has been rejected.

<x-mail::panel>
## Claim Details:
- **Claim ID:** #{{ $claimData['claim_id'] }}
- **Claim Date:** {{ $claimData['claim_date'] }}
- **Purpose:** {{ $claimData['purpose'] }}
- **Claim Amount:** RM {{ number_format($claimData['amount'], 2) }}
- **Rejected By:** {{ $approver->name }}
- **Rejection Date:** {{ now()->format('M d, Y \\a\\t h:i A') }}

@if($claimType === 'daily')
- **Allowance Type:** {{ ucfirst(str_replace('_', ' ', $claimData['allowance_type'])) }}
- **Destination:** {{ $claimData['destination'] }}
@elseif($claimType === 'travel')
- **Vehicle Type:** {{ ucfirst($claimData['vehicle_type']) }}
- **Total Distance:** {{ $claimData['total_distance'] }} km
@elseif($claimType === 'accommodation')
- **Hotel:** {{ $claimData['hotel_name'] }}
- **Stay Duration:** {{ $claimData['check_in_date'] }} to {{ $claimData['check_out_date'] }}
@elseif($claimType === 'transportation')
- **Transport Type:** {{ ucfirst($claimData['transport_type']) }}
- **Route:** {{ $claimData['from_location'] }} to {{ $claimData['to_location'] }}
@endif
</x-mail::panel>

## Rejection Reason:
<x-mail::panel>
{{ $rejectionReason }}
</x-mail::panel>

Please review the rejection reason above. If you have any questions or would like to discuss this further, please contact the approver or your department head.

You may need to submit a new claim with the necessary corrections.

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::subcopy>
This is an automated notification. Please do not reply to this email.
</x-mail::subcopy>