<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if maintenance mode is actually on
require_once __DIR__ . '/../../inc/access_control_helper.php';
require_once __DIR__ . '/../../inc/Database.php';

use Inc\Database;

$isMaintenanceMode = false;
try {
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $isMaintenanceMode = $result && (int) $result['setting_value'] === 1;
} catch (\Exception $e) {
    $isMaintenanceMode = false;
}

// If maintenance is off, redirect to home
if (!$isMaintenanceMode) {
    header('Location: /Campus-Food-Ordering-System/');
    exit();
}

// Get maintenance message from session or use default
$maintenanceMessage = $_SESSION['maintenance_message'] ?? 'The system is currently under maintenance. Login, registration, and ordering are temporarily unavailable. Please try again later.';
unset($_SESSION['maintenance_message']);

$pageTitle = 'Under Maintenance - FOODIE';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        .maintenance-icon {
            animation: pulse 2s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
        }
        .gear-spin {
            animation: spin 8s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .gear-spin-reverse {
            animation: spin-reverse 6s linear infinite;
        }
        @keyframes spin-reverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 flex items-center justify-center p-4">
    
    <div class="max-w-2xl w-full bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden">
        
        <!-- Top Section with Icon -->
        <div class="relative bg-gradient-to-r from-yellow-500 to-orange-500 px-6 py-12 text-center">
            <div class="absolute inset-0 opacity-10">
                <div class="absolute top-10 left-10 text-7xl gear-spin">⚙️</div>
                <div class="absolute bottom-10 right-10 text-6xl gear-spin-reverse">⚙️</div>
                <div class="absolute top-1/2 left-1/4 text-5xl gear-spin" style="animation-duration: 12s;">🔧</div>
            </div>
            
            <div class="relative z-10">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-white/20 backdrop-blur-sm rounded-full mb-4 maintenance-icon">
                    <i class="fa-solid fa-screwdriver-wrench text-5xl text-white"></i>
                </div>
                <h1 class="text-3xl font-black text-white tracking-tight">Under Maintenance</h1>
                <p class="text-yellow-50 mt-2 text-sm font-medium">We're improving your experience</p>
            </div>
        </div>
        
        <!-- Content Section -->
        <div class="p-8 md:p-10">
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-2 bg-yellow-50 border border-yellow-200 rounded-full px-4 py-1.5 mb-4">
                    <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span>
                    <span class="text-xs font-medium text-yellow-700">Maintenance Mode Active</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    <?php echo htmlspecialchars($maintenanceMessage); ?>
                </p>
            </div>
            
            <!-- Status Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
                <div class="bg-red-50 border border-red-100 rounded-xl p-4 text-center">
                    <i class="fa-solid fa-circle-xmark text-red-400 text-xl mb-2"></i>
                    <p class="text-xs font-semibold text-red-600">Login/Register</p>
                    <p class="text-[10px] text-red-400">Temporarily disabled</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 text-center">
                    <i class="fa-solid fa-cart-shopping text-yellow-400 text-xl mb-2"></i>
                    <p class="text-xs font-semibold text-yellow-600">Orders</p>
                    <p class="text-[10px] text-yellow-400">Temporarily disabled</p>
                </div>
                <div class="bg-green-50 border border-green-100 rounded-xl p-4 text-center">
                    <i class="fa-solid fa-eye text-green-400 text-xl mb-2"></i>
                    <p class="text-xs font-semibold text-green-600">Menu</p>
                    <p class="text-[10px] text-green-400">Still available</p>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="bg-slate-100 rounded-full h-2.5 mb-6 overflow-hidden">
                <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-2.5 rounded-full" style="width: 65%;"></div>
            </div>
            <p class="text-[10px] text-slate-400 text-center">Estimated completion: 65%</p>
            
            <!-- Actions -->
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="/Campus-Food-Ordering-System/" class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-colors">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Go to Home</span>
                </a>
                <?php if (isset($_SESSION['user_id']) && isAdmin()): ?>
                    <a href="/Campus-Food-Ordering-System/view/admin/admin-dashboard.php" class="inline-flex items-center justify-center space-x-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-colors">
                        <i class="fa-solid fa-gauge-high"></i>
                        <span>Go to Admin Panel</span>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                <p class="text-[10px] text-slate-400">
                    &copy; <?php echo date('Y'); ?> FOODIE. All rights reserved.
                </p>
                <p class="text-[10px] text-slate-400 mt-1">
                    <i class="fa-regular fa-clock mr-1"></i>
                    Last checked: <?php echo date('F j, Y \a\t h:i A'); ?>
                </p>
            </div>
        </div>
    </div>
    
</body>
</html>