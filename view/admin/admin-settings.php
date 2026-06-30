<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\AdminController;

$adminController = new AdminController();
$currentUser = $adminController->getCurrentUser();

// Get settings
$settings = $adminController->getSettings();

// Handle form submission
$success = '';
$error = '';
$updateResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $result = $adminController->updateSettingsFromRequest();
    
    if (empty($result['failed'])) {
        $success = 'Settings updated successfully!';
        // Refresh settings
        $settings = $adminController->getSettings();
    } else {
        $error = 'Failed to update some settings.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foodie - Settings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .sidebar-link.active {
            background-color: #EEF2FF;
            color: #4F46E5;
        }
        .sidebar-link:hover {
            background-color: #F9FAFB;
            color: #111827;
        }
        .setting-section {
            transition: all 0.2s ease;
        }
        .setting-section:hover {
            border-color: #C7D2FE;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] flex h-screen text-slate-800 antialiased overflow-hidden">

    <!-- ===== SIDEBAR ===== -->
    <aside class="w-64 bg-white border-r border-slate-100 flex flex-col justify-between py-6 flex-shrink-0">
        <div>
            <div class="flex flex-col items-center justify-center mb-8 px-6">
                <div class="relative flex items-center justify-center text-slate-900 mb-1">
                    <span class="fa-stack fa-xl">
                        <i class="fa-solid fa-building fa-stack-2x opacity-10 -translate-y-1"></i>
                        <i class="fa-solid fa-hamburger fa-stack-1x text-slate-950"></i>
                    </span>
                </div>
                <span class="text-xl font-black tracking-wider text-slate-950">FOODIE</span>
                <span class="text-xs text-gray-400 font-medium mt-1">Admin Panel</span>
            </div>

            <nav class="space-y-1 px-3">
                <a href="admin-dashboard.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-house text-lg w-6 text-center"></i>
                    <span>Dashboard</span>
                </a>
                <a href="admin-users.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-regular fa-user text-lg w-6 text-center"></i>
                    <span>Users</span>
                </a>
                <a href="admin-menu.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-book-open text-lg w-6 text-center"></i>
                    <span>Menu</span>
                </a>
                <a href="admin-orders.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-receipt text-lg w-6 text-center"></i>
                    <span>Orders</span>
                </a>
                <a href="admin-reports.php" class="sidebar-link flex items-center space-x-4 px-4 py-3 text-slate-500 rounded-lg font-medium transition-colors">
                    <i class="fa-solid fa-chart-simple text-lg w-6 text-center"></i>
                    <span>Reports</span>
                </a>
                <a href="admin-settings.php" class="sidebar-link active flex items-center space-x-4 px-4 py-3 rounded-lg font-medium transition-colors relative">
                    <div class="absolute left-0 top-3 bottom-3 w-1 bg-indigo-600 rounded-r-md"></div>
                    <i class="fa-solid fa-gear text-lg w-6 text-center"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </div>

        <div class="px-3">
            <div class="flex items-center space-x-3 px-4 py-3 mb-2 rounded-lg bg-gray-50">
                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                    <?php echo strtoupper(substr($currentUser['name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-gray-400">Administrator</p>
                </div>
            </div>
            <a href="../entrance/logout.php" class="flex items-center space-x-4 px-4 py-3 text-slate-500 hover:bg-rose-50 hover:text-rose-600 rounded-lg font-medium transition-colors">
                <i class="fa-solid fa-right-from-bracket text-lg w-6 text-center"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="flex-1 p-8 overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-950">Settings</h1>
                <p class="text-sm text-slate-500">Manage your system configuration</p>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg text-emerald-700 flex items-center space-x-2">
                <i class="fa-solid fa-check-circle"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 flex items-center space-x-2">
                <i class="fa-solid fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Settings Form -->
        <form method="POST" action="">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- General Settings -->
                <div class="setting-section bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                        <i class="fa-solid fa-sliders text-indigo-500"></i>
                        <span>General</span>
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Site Name</label>
                            <input type="text" name="setting_site_name" value="<?php echo htmlspecialchars($settings['site_name'] ?? 'FOODIE'); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contact Email</label>
                            <input type="email" name="setting_site_email" value="<?php echo htmlspecialchars($settings['site_email'] ?? 'admin@foodie.com'); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Contact Phone</label>
                            <input type="text" name="setting_site_phone" value="<?php echo htmlspecialchars($settings['site_phone'] ?? '+1234567890'); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Timezone</label>
                            <select name="setting_timezone" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="Asia/Manila" <?php echo ($settings['timezone'] ?? '') == 'Asia/Manila' ? 'selected' : ''; ?>>Asia/Manila</option>
                                <option value="Asia/Singapore" <?php echo ($settings['timezone'] ?? '') == 'Asia/Singapore' ? 'selected' : ''; ?>>Asia/Singapore</option>
                                <option value="Asia/Tokyo" <?php echo ($settings['timezone'] ?? '') == 'Asia/Tokyo' ? 'selected' : ''; ?>>Asia/Tokyo</option>
                                <option value="America/New_York" <?php echo ($settings['timezone'] ?? '') == 'America/New_York' ? 'selected' : ''; ?>>America/New_York</option>
                                <option value="Europe/London" <?php echo ($settings['timezone'] ?? '') == 'Europe/London' ? 'selected' : ''; ?>>Europe/London</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Order Settings -->
                <div class="setting-section bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                        <i class="fa-solid fa-truck text-indigo-500"></i>
                        <span>Order</span>
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Default Preparation Time (minutes)</label>
                            <input type="number" name="setting_preparation_time" value="<?php echo htmlspecialchars($settings['preparation_time'] ?? 15); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cancellation Time (minutes)</label>
                            <input type="number" name="setting_cancellation_time" value="<?php echo htmlspecialchars($settings['cancellation_time'] ?? 5); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Max Orders Per Day</label>
                            <input type="number" name="setting_max_orders_per_day" value="<?php echo htmlspecialchars($settings['max_orders_per_day'] ?? 100); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                    </div>
                </div>

                <!-- Payment Settings -->
                <div class="setting-section bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                        <i class="fa-solid fa-credit-card text-indigo-500"></i>
                        <span>Payment</span>
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                            <select name="setting_currency" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="PHP" <?php echo ($settings['currency'] ?? '') == 'PHP' ? 'selected' : ''; ?>>PHP (₱)</option>
                                <option value="EUR" <?php echo ($settings['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="GBP" <?php echo ($settings['currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Cash on Delivery</label>
                            <select name="setting_cash_on_delivery" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="1" <?php echo ($settings['cash_on_delivery'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo ($settings['cash_on_delivery'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">QR Payment</label>
                            <select name="setting_qr_payment" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="1" <?php echo ($settings['qr_payment'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                <option value="0" <?php echo ($settings['qr_payment'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="setting-section bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                        <i class="fa-solid fa-bell text-indigo-500"></i>
                        <span>Notification</span>
                    </h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Notification Email</label>
                            <input type="email" name="setting_notification_email" value="<?php echo htmlspecialchars($settings['notification_email'] ?? 'orders@foodie.com'); ?>" 
                                   class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="setting-section lg:col-span-2 bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                    <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                        <i class="fa-solid fa-server text-indigo-500"></i>
                        <span>System</span>
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Maintenance Mode</label>
                            <select name="setting_maintenance_mode" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                                <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Off</option>
                                <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>On</option>
                            </select>
                            <p class="text-xs text-slate-400 mt-1">When enabled, only admins can access the site</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end">
                <button type="submit" name="save_settings" class="inline-flex items-center space-x-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Settings</span>
                </button>
            </div>
        </form>
    </main>

</body>
</html>