<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable | 503 Error</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        
        .floating {
            animation: float 6s ease-in-out infinite;
        }
        
        .pulse-slow {
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            50% {
                transform: scale(1.05);
                opacity: 0.8;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900 flex items-center justify-center p-4 overflow-hidden">
    <!-- Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <!-- Floating circles -->
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-blue-500/10 rounded-full floating"></div>
        <div class="absolute top-1/3 right-1/4 w-48 h-48 bg-purple-500/10 rounded-full floating" style="animation-delay: 1s;"></div>
        <div class="absolute bottom-1/4 left-1/3 w-56 h-56 bg-indigo-500/10 rounded-full floating" style="animation-delay: 2s;"></div>
    </div>

    <div class="relative w-full max-w-2xl mx-auto z-10 text-center">
        <!-- Main Content -->
        <div class="bg-white/10 backdrop-blur-lg rounded-3xl border border-white/20 shadow-2xl p-12">
            <!-- Icon -->
            <div class="w-40 h-40 mx-auto mb-8 bg-white/20 rounded-full flex items-center justify-center pulse-slow border border-white/30">
                <i class="fas fa-tools text-white text-6xl"></i>
            </div>
            
            <!-- Error Code -->
            <div class="text-8xl font-bold text-white mb-6">503</div>
            
            <!-- Main Message -->
            <h1 class="text-4xl font-bold text-white mb-6">System Under Maintenance</h1>
            
            <!-- Simple Description -->
            <p class="text-xl text-white/80 mb-10 max-w-md mx-auto">
                The system is currently in maintenance mode. Please check back later.
            </p>
            
            <!-- Single Action Button -->
            <button id="refresh-btn" class="px-12 py-4 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-xl transition-all duration-300 border border-white/30 hover:border-white/50 flex items-center justify-center gap-3 mx-auto text-lg">
                <i class="fas fa-redo-alt"></i>
                Try Again
            </button>
        </div>
        
        <!-- Simple Footer -->
        <div class="mt-8 text-white/60 text-sm">
            <p>We apologize for any inconvenience caused.</p>
        </div>
    </div>

    <script>
        // Simple refresh functionality
        document.getElementById('refresh-btn').addEventListener('click', function() {
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        });
        
        // Auto-refresh every 2 minutes
        setTimeout(() => {
            window.location.reload();
        }, 120000);
    </script>
</body>
</html>