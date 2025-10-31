<x-mail::message>
# Asset Assignment - Action Required

**To:** {{ $user->name }}  
**Assigned By:** {{ $assignedBy->name }}

You have been assigned responsibility for the following asset. Please review the details and confirm your acceptance:

## Asset Details
- **Asset Name:** {{ $asset->asset_name }}
- **Asset Tag No:** {{ $asset->asset_tag_no }}
- **Serial No:** {{ $asset->serial_no ?? 'N/A' }}
- **Current Condition:** {{ $assignment->condition_assigned }}
- **Assignment Date:** {{ $assignment->assigned_at->format('M j, Y') }}

## Your Responsibilities
By confirming this assignment, you agree to:
- Use this asset for official business purposes only
- Report any damage, loss, or malfunction immediately
- Maintain the asset in good working condition
- Return the asset when no longer required or upon request
- Ensure proper care and security of the asset at all times

<x-mail::button :url="$confirmationUrl">
✅ Confirm & Accept Responsibility
</x-mail::button>

If you have any questions or concerns about this assignment, please contact {{ $assignedBy->name }} or your department supervisor.

<small>
*This is an auto-generated email. Please do not reply to this message.*
</small>

</x-mail::message>