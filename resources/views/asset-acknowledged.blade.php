<!DOCTYPE html>
<html>
<head>
    <title>Assignment Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <div class="text-green-500 text-6xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Assignment Confirmed</h1>
        <p class="text-gray-600 mb-4">Thank you for acknowledging your responsibility</p>
        
        <!-- Success Message -->
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-800 font-semibold">
                Asset assignment successfully acknowledged!
            </p>
            <p class="text-green-600 text-sm mt-1">
                You have accepted responsibility for the assigned asset.
            </p>
        </div>

        <!-- Assignment Summary -->
        <div class="text-left bg-gray-50 p-4 rounded-lg mb-6">
            <h3 class="font-semibold text-gray-800 mb-3">Assignment Summary</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">Asset:</span>
                    <span class="text-gray-900">{{ $assignment->asset->asset_name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">Asset Tag:</span>
                    <span class="text-gray-900">{{ $assignment->asset->asset_tag_no }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">Confirmed By:</span>
                    <span class="text-gray-900">{{ $assignment->user->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="font-medium text-gray-600">Confirmation Date:</span>
                    <span class="text-gray-900">{{ now()->format('F j, Y \\a\\t g:i A') }}</span>
                </div>
            </div>
        </div>

        <p class="text-sm text-gray-500 mb-6">
            A confirmation email has been sent to your records. You may now close this window.
        </p>
        
        <button onclick="window.close()" 
            class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
            Close Window
        </button>
    </div>
</body>
</html>