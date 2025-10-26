<x-mail::message>
# Daily Pending Claims Reminder

Dear {{ $approver->name }},

You have **{{ $totalClaims }}** claim(s) pending your approval.

## Summary:
- **Total Pending Claims:** {{ $totalClaims }}
- **Total Amount:** RM {{ number_format($totalAmount, 2) }}
- **Oldest Claim:** {{ $oldestClaimTime }} pending

<x-mail::panel>
## Pending Claims Overview:

@foreach($pendingClaims as $claim)
**{{ $claim['type_display'] }} Claim** - #{{ $claim['id'] }}
- **Submitted By:** {{ $claim['user_name'] }}
- **Purpose:** {{ Str::limit($claim['purpose'], 100) }}
- **Amount:** RM {{ number_format($claim['amount'], 2) }}
- **Submitted:** {{ $claim['submitted_date'] }}
- **Pending For:** {{ $claim['pending_display'] }}

@endforeach
</x-mail::panel>

Please review these claims promptly to ensure timely processing for your team members.

<x-mail::button :url="route('admin.claim-request.index')">
Review All Pending Claims
</x-mail::button>

**Note:** This is an automated daily reminder that runs on weekdays only.

Thank you for your attention to these matters.

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::subcopy>
This is an automated notification. Please do not reply to this email.
</x-mail::subcopy>