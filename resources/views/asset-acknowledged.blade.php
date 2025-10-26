<!DOCTYPE html>
<html>
<head>
    <title>Thank You - Terima Kasih</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
        <div class="text-green-500 text-6xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Thank You!</h1>
        <h2 class="text-xl text-gray-700 mb-2">Terima Kasih!</h2>
        
        <div class="mb-4 p-4 bg-green-50 rounded-lg">
            <p class="text-green-800 font-semibold">
                Asset assignment successfully acknowledged!
            </p>
            <p class="text-green-700 text-sm mt-1">
                Penyerahan aset berjaya disahkan!
            </p>
        </div>

        <div class="text-left bg-gray-50 p-4 rounded-lg mb-6">
            <p><strong>Asset:</strong> {{ $assignment->asset->asset_name }}</p>
            <p><strong>Tag No:</strong> {{ $assignment->asset->asset_tag_no }}</p>
            <p><strong>Acknowledged by:</strong> {{ $assignment->user->name }}</p>
            <p><strong>Date:</strong> {{ now()->format('d/m/Y H:i') }}</p>
        </div>

        <p class="text-sm text-gray-500 mb-6">
            You may close this window.
            <br>
            Anda boleh tutup tetingkap ini.
        </p>
        <button onclick="window.close()" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
            Close / Tutup
        </button>
    </div>
</body>
</html>