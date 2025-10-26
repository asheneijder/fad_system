<!DOCTYPE html>
<html>
<head>
    <title>Confirm Assignment - Sahkan Penyerahan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full">
        <div class="text-center mb-6">
            <div class="text-blue-500 text-5xl mb-4">📦</div>
            <h1 class="text-2xl font-bold text-gray-900">Confirm Asset Assignment</h1>
            <p class="text-gray-600">Sahkan Penyerahan Aset</p>
        </div>

        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <h2 class="font-semibold mb-2">Assignment Details / Butiran Penyerahan:</h2>
            <p><strong>Assigned To / Kepada:</strong> {{ $assignment->user->name }}</p>
            <p><strong>Asset Name / Nama Aset:</strong> {{ $assignment->asset->asset_name }}</p>
            <p><strong>Tag No. / No. Tag:</strong> {{ $assignment->asset->asset_tag_no }}</p>
            <p><strong>Serial No. / No. Siri:</strong> {{ $assignment->asset->serial_no ?? 'N/A' }}</p>
            <p><strong>Condition / Keadaan:</strong> {{ $assignment->condition_assigned }}</p>
            <p><strong>Assigned By / Diserahkan Oleh:</strong> {{ $assignment->assignedBy->name }}</p>
        </div>

        <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <strong>By confirming, you accept responsibility for this asset:</strong>
                <br><br>
                • Use asset for official purposes only<br>
                • Report any damage or issues immediately<br>
                • Maintain the asset in good condition<br>
                • Return when no longer required
                <br><br>
                <strong>Dengan mengesahkan, anda menerima tanggungjawab untuk aset ini:</strong>
                <br><br>
                • Gunakan aset untuk tujuan rasmi sahaja<br>
                • Laporkan sebarang kerosakan dengan segera<br>
                • Jaga aset dalam keadaan baik<br>
                • Kembalikan apabila tidak diperlukan
            </p>
        </div>

        <form action="{{ route('asset-assignment.acknowledge', $assignment->id) }}" method="POST">
            @csrf
            <div class="flex gap-3">
                <button type="button" onclick="window.close()" 
                    class="flex-1 bg-gray-500 text-white px-4 py-3 rounded-lg hover:bg-gray-600 transition">
                    Cancel / Batal
                </button>
                <button type="submit" 
                    class="flex-1 bg-green-500 text-white px-4 py-3 rounded-lg hover:bg-green-600 transition font-semibold">
                    ✅ Confirm & Accept / Sahkan & Terima
                </button>
            </div>
        </form>

        <div class="mt-4 text-center text-xs text-gray-500">
            This secure link will expire in 7 days / Pautan selamat ini akan tamat dalam 7 hari
        </div>
    </div>
</body>
</html>