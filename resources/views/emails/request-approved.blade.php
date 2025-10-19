<x-mail::message>
# Request Approved ✅

Good news! Your stationary request has been approved.

**Request Details:**
- **Request ID:** #{{ $requestItem->id }}
- **Purpose:** {{ $requestItem->purpose }}
- **Approved By:** {{ $requestItem->approvedBy->name ?? 'FAD Administrator' }}
- **Approved On:** {{ $requestItem->approved_at->format('M j, Y g:i A') }}

**Approved Items:**
<x-mail::table>
| Item | Requested | Approved | Unit Price |
| :--- | :-------- | :------- | :--------- |
@foreach($requestItem->items as $item)
| {{ $item->stationaryItem->name }} | {{ $item->quantity }} {{ $item->stationaryItem->unit }} | {{ $item->approved_quantity ?? $item->quantity }} {{ $item->stationaryItem->unit }} | ${{ number_format($item->unit_price, 2) }} |
@endforeach
</x-mail::table>

@if($requestItem->notes)
**Your Notes:**  
{{ $requestItem->notes }}
@endif

You can collect your items from the storage FAD department during working hours.

<x-mail::button :url="route('user.request-items.show', $requestItem->id)">
View Request Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>