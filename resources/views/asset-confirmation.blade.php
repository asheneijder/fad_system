<!DOCTYPE html>
<html>
<head>
    <title>Confirm Asset Assignment</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
        <div class="text-center mb-6">
            <div class="text-blue-500 text-5xl mb-4">📦</div>
            <h1 class="text-2xl font-bold text-gray-900">Confirm Asset Assignment</h1>
            <p class="text-gray-600">Review and accept responsibility for the assigned asset</p>
        </div>

        <!-- Assignment Details -->
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h2 class="font-semibold text-blue-800 mb-3">Assignment Details</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Assigned To:</span>
                    <span class="text-gray-900">{{ $assignment->user->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Asset Name:</span>
                    <span class="text-gray-900">{{ $assignment->asset->asset_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Asset Tag:</span>
                    <span class="text-gray-900">{{ $assignment->asset->asset_tag_no }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Serial No:</span>
                    <span class="text-gray-900">{{ $assignment->asset->serial_no ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Assigned By:</span>
                    <span class="text-gray-900">{{ $assignment->assignedBy->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700">Assignment Date:</span>
                    <span class="text-gray-900">{{ $assignment->assigned_at->format('M j, Y') }}</span>
                </div>
                <div>
                    <span class="font-medium text-gray-700">Current Condition:</span>
                    <p class="text-gray-900 mt-1">{{ $assignment->condition_assigned }}</p>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <h3 class="font-semibold text-amber-800 mb-3">Terms & Conditions</h3>
            <div class="space-y-2 text-sm text-amber-700">
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 mt-0.5">•</span>
                    <span>I will use this asset exclusively for official business purposes</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 mt-0.5">•</span>
                    <span>I will immediately report any damage, loss, or malfunction</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 mt-0.5">•</span>
                    <span>I will maintain the asset in good working condition</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 mt-0.5">•</span>
                    <span>I will return the asset when no longer required or upon request</span>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-amber-600 mt-0.5">•</span>
                    <span>I am responsible for the security and proper care of this asset</span>
                </div>
            </div>
        </div>

        <!-- Confirmation Form -->
        <form action="{{ route('asset-assignment.acknowledge', $assignment->id) }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required 
                               class="mt-1 w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500 focus:ring-2">
                        <label for="agree_terms" class="text-sm font-medium text-green-800 cursor-pointer">
                            I acknowledge receipt of this asset and accept full responsibility as outlined in the terms and conditions above.
                        </label>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="window.close()" 
                        class="flex-1 bg-gray-500 text-white px-4 py-3 rounded-lg hover:bg-gray-600 transition font-medium">
                        Cancel
                    </button>
                    <button type="submit" 
                        class="flex-1 bg-green-600 text-white px-4 py-3 rounded-lg hover:bg-green-700 transition font-semibold flex items-center justify-center gap-2">
                        <span>✅</span>
                        Confirm & Accept
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-4 text-center text-xs text-gray-500">
            This secure confirmation link expires in 7 days
        </div>
    </div>
</body>
</html>