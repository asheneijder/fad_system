<x-mail::message>
# Asset Acknowledgment Reminder

Hello {{ $user->name }},

This is a friendly reminder that you have **{{ $pendingCount }} pending asset assignment(s)** that require your acknowledgment.

## Pending Assets:

@foreach($pendingAssignments as $assignment)
- **{{ $assignment->asset->asset_name }}**  
  Tag: {{ $assignment->asset->asset_tag_no }}  
  Serial: {{ $assignment->asset->serial_no ?? 'N/A' }}  
  Assigned: {{ $assignment->assigned_at->format('M j, Y') }}
@endforeach

<x-mail::button :url="$acknowledgmentUrl">
Acknowledge Assets Now
</x-mail::button>

Please log in to the system and acknowledge these assets at your earliest convenience. Your prompt attention ensures proper asset tracking and compliance with company policies.

If you have any questions or concerns, please contact the FAD Department.

Thank you for your cooperation!

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>