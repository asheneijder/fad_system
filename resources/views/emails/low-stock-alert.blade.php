<x-mail::message>
# 📦 Low Stock Alert

Dear Administrator,

## ⚠️ Attention Required

The following stationary items are running low on stock and may need to be reordered soon to avoid disruption.

### Low Stock Items Summary:

@foreach($items as $item)
**{{ $loop->iteration }}. {{ $item->name }}** 
- **Category:** {{ ucfirst($item->category) }}
- **Current Stock:** {{ $item->current_stock }} {{ $item->unit }}
- **Minimum Stock:** {{ $item->min_stock }} {{ $item->unit }}
- **Status:** 
@if($item->current_stock == 0)
🔴 **Out of Stock**
@else
🟡 **Low Stock** ({{ $item->current_stock - $item->min_stock }} below minimum)
@endif
- **Supplier:** {{ $item->supplier ?: 'Not specified' }}

---
@endforeach

<x-mail::panel>
### 📊 Quick Summary
- **Total Items with Low Stock:** {{ count($items) }}
- **Out of Stock Items:** {{ $items->where('current_stock', 0)->count() }}
- **Low Stock Items:** {{ $items->where('current_stock', '>', 0)->count() }}
</x-mail::panel>

<x-mail::button :url="url('/admin/stationary-items')" color="success">
📋 Manage Stationary Items
</x-mail::button>

### 🚨 Recommended Actions:
1. Review the low stock items above
2. Contact suppliers for restocking
3. Update inventory records after restocking
4. Adjust minimum stock levels if needed

Best regards,<br>
{{ config('app.name') }}
</x-mail::message>