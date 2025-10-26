<x-mail::message>
# Pending Requests Alert ⚠️

You have **{{ $pendingRequests->count() }}** pending stationary requests that require your attention.

## Summary:
- **Total Pending:** {{ $pendingRequests->count() }}
- **Urgent Priority:** {{ $pendingRequests->where('priority', 'urgent')->count() }}
- **High Priority:** {{ $pendingRequests->where('priority', 'high')->count() }}
- **Overdue:** {{ $pendingRequests->filter(function($request) { return $request->needed_by && $request->needed_by->isPast(); })->count() }}

## Pending Requests:
<x-mail::table>
| Request ID | User | Purpose | Priority | Needed By | Days Pending |
| :--------- | :--- | :------ | :------- | :-------- | :---------- |
@foreach($pendingRequests as $request)
@php
    $daysPending = $request->created_at->startOfDay()->diffInDays(now()->startOfDay());
    $daysText = $daysPending == 0 ? 'Today' : ($daysPending == 1 ? '1 day' : $daysPending . ' days');
@endphp
| #{{ $request->id }} | {{ $request->user->name }} | {{ Str::limit($request->purpose, 30) }} | <span style="color: {{ $request->priority === 'urgent' ? '#dc3545' : ($request->priority === 'high' ? '#fd7e14' : ($request->priority === 'medium' ? '#ffc107' : '#28a745')) }}">{{ ucfirst($request->priority) }}</span> | {{ $request->needed_by ? $request->needed_by->format('M j') : 'N/A' }} | {{ $daysText }} |
@endforeach
</x-mail::table>

## Urgent Requests Needing Immediate Attention:
@foreach($pendingRequests->where('priority', 'urgent') as $urgentRequest)
- **#{{ $urgentRequest->id }}** - {{ $urgentRequest->user->name }}: {{ Str::limit($urgentRequest->purpose, 50) }} ({{ $urgentRequest->created_at->diffForHumans() }})
@endforeach

@if($pendingRequests->where('priority', 'urgent')->isEmpty())
No urgent requests at the moment.
@endif

<x-mail::button :url="route('admin.manage-request-items.index', ['status' => 'pending'])">
Review Pending Requests
</x-mail::button>

**Next Steps:**
1. Review pending requests by priority
2. Check for overdue requests (needed by date has passed)
3. Approve or reject requests promptly
4. Contact users if additional information is needed

This is an automated daily alert. You will receive this email only when there are pending requests.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>