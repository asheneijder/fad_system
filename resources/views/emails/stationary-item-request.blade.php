<x-mail::message>
# New Stationary Item Request

A new stationary item request has been submitted and requires your attention.

**Request Details:**
- **Request ID:** #{{ $requestItem->id }}
- **Submitted By:** {{ $requestItem->user->name }}
- **Purpose:** {{ $requestItem->purpose }}
- **Priority:** <span style="color: {{ $requestItem->priority === 'urgent' ? '#dc3545' : ($requestItem->priority === 'high' ? '#fd7e14' : ($requestItem->priority === 'medium' ? '#ffc107' : '#28a745')) }}">{{ ucfirst($requestItem->priority) }}</span>
- **Needed By:** {{ $requestItem->needed_by ? $requestItem->needed_by->format('M j, Y') : 'Not specified' }}
- **Submitted On:** {{ $requestItem->created_at->format('M j, Y g:i A') }}

**Requested Items:**
<x-mail::table>
| Item | Quantity | Unit Price | Notes |
| :--- | :------- | :--------- | :---- |
@foreach ($requestItem->items as $item)
| {{ $item->stationaryItem->name }} | {{ $item->quantity }} {{ $item->stationaryItem->unit }} | RM{{ number_format($item->unit_price, 2) }} | {{ $item->notes ?? 'N/A' }} |
@endforeach
</x-mail::table>

**Total Items:** {{ $requestItem->items->count() }}
**Total Estimated Cost:**
RM{{ number_format($requestItem->items->sum(function ($item) {return $item->quantity * $item->unit_price;}),2) }}

@if ($requestItem->notes)
**Additional Notes:**
{{ $requestItem->notes }}
@endif

<x-mail::button :url="route('admin.manage-request-items.show', $requestItem->id)">
Review Request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
