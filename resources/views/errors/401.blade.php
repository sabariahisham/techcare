<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Error - BTM TechCare</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
            <div class="text-center">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h1 class="mt-4 text-2xl font-bold text-gray-900">Authentication Error</h1>
                <p class="mt-2 text-gray-600">{{ $message ?? 'Ralat semasa pengesahan pengguna.' }}</p>
            </div>
            
            <div class="mt-6 space-y-3">
                <a href="/logout" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded text-center">
                    Cuba Log Masuk Semula
                </a>
                <a href="https://satuid.treasury.gov.my" class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded text-center">
                    Kembali ke SATUID
                </a>
            </div>
            
            @if(config('app.debug'))
            <div class="mt-6 p-4 bg-gray-50 rounded border border-gray-200">
                <p class="text-xs text-gray-600">
                    <strong>Debug Info:</strong><br>
                    Timestamp: {{ now()->toDateTimeString() }}<br>
                    Session ID: {{ session()->getId() }}
                </p>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
