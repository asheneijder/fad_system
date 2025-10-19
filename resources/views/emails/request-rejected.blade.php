<x-mail::message>
# Request Update

Your stationary request has been reviewed and we're unable to approve it at this time.

**Request Details:**
- **Request ID:** #{{ $requestItem->id }}
- **Purpose:** {{ $requestItem->purpose }}
- **Reviewed By:** {{ $requestItem->approvedBy->name ?? 'Administrator' }}
- **Reviewed On:** {{ $requestItem->approved_at->format('M j, Y g:i A') }}

**Reason for Rejection:**
{{ $rejectionReason }}

**Requested Items:**
<x-mail::table>
| Item | Quantity | Unit Price |
| :--- | :------- | :--------- |
@foreach($requestItem->items as $item)
| {{ $item->stationaryItem->name }} | {{ $item->quantity }} {{ $item->stationaryItem->unit }} | ${{ number_format($item->unit_price, 2) }} |
@endforeach
</x-mail::table>

If you have any questions or would like to discuss alternative options, please contact the admin department.

<x-mail::button :url="route('user.request-items.show', $requestItem->id)">
View Request Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>