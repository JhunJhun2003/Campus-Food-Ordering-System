<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Simple admin-like check from session and permissions
require_once __DIR__ . '/../../inc/Database.php';
require_once __DIR__ . '/../../inc/access_control_helper.php';
$isAdmin = isAdminLike();

// Check if maintenance mode is actually on

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
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .gear-spin {
            animation: spin 12s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .gear-spin-reverse {
            animation: spin-reverse 8s linear infinite;
        }
        @keyframes spin-reverse {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(-360deg); }
        }
        .pulse-dot {
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.8); }
        }
        .status-card {
            transition: all 0.3s ease;
        }
        .status-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(0,0,0,0.1);
        }
        .btn-home {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .btn-admin {
            transition: all 0.3s ease;
        }
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(79, 70, 229, 0.3);
        }
        .brand-text {
            background: linear-gradient(135deg, #F59E0B, #EF4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 flex items-center justify-center p-4">
    
    <div class="max-w-2xl w-full">
        
        <!-- Main Card -->
        <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden relative">
            
            <!-- Decorative Background Elements -->
            <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-yellow-100/30 to-orange-100/30 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2"></div>
            <div class="absolute bottom-0 left-0 w-48 h-48 bg-gradient-to-tr from-indigo-100/20 to-purple-100/20 rounded-full blur-2xl translate-y-1/2 -translate-x-1/2"></div>
            
            <!-- Top Section with Icon -->
            <div class="relative bg-gradient-to-r from-yellow-500 via-orange-500 to-orange-600 px-6 py-14 text-center overflow-hidden">
                <!-- Decorative Gears -->
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute top-8 left-8 text-6xl gear-spin">⚙️</div>
                    <div class="absolute bottom-8 right-8 text-5xl gear-spin-reverse">⚙️</div>
                    <div class="absolute top-1/3 left-1/4 text-4xl gear-spin" style="animation-duration: 15s;">🔧</div>
                    <div class="absolute bottom-1/3 right-1/4 text-4xl gear-spin-reverse" style="animation-duration: 10s;">⚡</div>
                </div>
                
                <div class="relative z-10">
                    <!-- Icon -->
                    <div class="inline-flex items-center justify-center w-28 h-28 bg-white/20 backdrop-blur-sm rounded-full mb-6 maintenance-icon shadow-lg ring-4 ring-white/30">
                        <i class="fa-solid fa-screwdriver-wrench text-6xl text-white"></i>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="text-4xl font-black text-white tracking-tight mb-2">
                        Under Maintenance
                    </h1>
                    <p class="text-yellow-50/90 text-sm font-medium tracking-wide">
                        <i class="fa-regular fa-clock mr-2"></i>
                        We're improving your experience
                    </p>
                </div>
            </div>
            
            <!-- Content Section -->
            <div class="relative p-8 md:p-10">
                <!-- Status Badge -->
                <div class="flex justify-center mb-6">
                    <div class="inline-flex items-center gap-2.5 bg-yellow-50 border border-yellow-200 rounded-full px-5 py-2 shadow-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-yellow-500 pulse-dot"></span>
                        <span class="text-xs font-semibold text-yellow-700 uppercase tracking-wider">Maintenance Mode Active</span>
                    </div>
                </div>
                
                <!-- Message -->
                <div class="text-center mb-8">
                    <p class="text-slate-600 leading-relaxed max-w-md mx-auto">
                        <?php echo htmlspecialchars($maintenanceMessage); ?>
                    </p>
                </div>
                
                <!-- Status Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                    <div class="status-card bg-red-50 border border-red-100 rounded-2xl p-5 text-center shadow-sm">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <i class="fa-solid fa-circle-xmark text-red-500 text-xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-red-700">Login / Register</p>
                        <p class="text-xs text-red-400 mt-0.5">Temporarily disabled</p>
                    </div>
                    <div class="status-card bg-yellow-50 border border-yellow-100 rounded-2xl p-5 text-center shadow-sm">
                        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-3">
                            <i class="fa-solid fa-cart-shopping text-yellow-500 text-xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-yellow-700">Orders & Cart</p>
                        <p class="text-xs text-yellow-400 mt-0.5">Temporarily disabled</p>
                    </div>
                </div>
                
                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-slate-200"></div>
                    </div>
                    <div class="relative flex justify-center">
                        <span class="bg-white px-4 text-xs text-slate-400">
                            <i class="fa-regular fa-circle mr-1"></i>
                            We'll be back soon
                            <i class="fa-regular fa-circle ml-1"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="/Campus-Food-Ordering-System/Public" class="btn-home flex items-center justify-center gap-2 px-8 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm transition-all shadow-sm">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        <span>Go to Home</span>
                    </a>
                    <?php if ($isAdmin): ?>
                        <a href="/Campus-Food-Ordering-System/view/admin/admin-dashboard.php" class="btn-admin flex items-center justify-center gap-2 px-8 py-3.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-sm transition-all shadow-lg shadow-indigo-500/20">
                            <i class="fa-solid fa-gauge-high"></i>
                            <span>Go to Admin Panel</span>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-400">
                        &copy; <?php echo date('Y'); ?> FOODIE. All rights reserved.
                    </p>
                    <p class="text-xs text-slate-300 mt-1">
                        <i class="fa-regular fa-clock mr-1"></i>
                        Last checked: <?php echo date('F j, Y \a\t h:i A'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Small Brand Note -->
        <div class="text-center mt-4">
            <span class="text-xs text-slate-400">
                <i class="fa-solid fa-heart text-red-400 text-[10px] mr-1"></i>
                We appreciate your patience
            </span>
        </div>
    </div>
    
</body>
</html>