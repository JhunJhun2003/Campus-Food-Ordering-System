<!-- TAB 4: SYSTEM SETTINGS -->
<div id="tab-system" class="tab-content <?php echo $activeTab === 'system' ? '' : 'hidden'; ?>">
    <form method="POST" action="">
        <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-server text-indigo-500"></i>
                <span>System Settings</span>
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Maintenance Mode -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Maintenance Mode</label>
                    <div class="flex items-center space-x-4">
                        <select name="setting_maintenance_mode" 
                                class="flex-1 px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            <option value="0" <?php echo ($settings['maintenance_mode'] ?? '0') == '0' ? 'selected' : ''; ?>>Off - System fully accessible</option>
                            <option value="1" <?php echo ($settings['maintenance_mode'] ?? '0') == '1' ? 'selected' : ''; ?>>On - Maintenance mode active</option>
                        </select>
                        <?php if (($settings['maintenance_mode'] ?? '0') == '1'): ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <i class="fa-solid fa-circle mr-1.5 text-red-500 animate-pulse"></i> Active
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                <i class="fa-solid fa-circle mr-1.5 text-green-500"></i> Inactive
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3 p-4 bg-slate-50 rounded-lg border border-slate-200">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            <i class="fa-solid fa-info-circle text-indigo-500 mr-1.5"></i>
                            <span class="font-medium">When maintenance mode is ON:</span>
                        </p>
                        <ul class="text-xs text-slate-500 mt-1.5 space-y-1 list-disc list-inside">
                            <li>Customers and staff cannot login or register</li>
                            <!-- <li>Logged-in customers and staff will be automatically logged out</li> -->
                            <li>Admin can still access the system</li>
                            <!-- <li>Guests can still view the landing page and browse menu</li> -->
                        </ul>
                    </div>
                </div>
                
                <!-- Notification Email -->
                <div>
                    <!-- <label class="block text-sm font-medium text-slate-700 mb-1">Notification Email</label>
                    <input type="email" name="setting_notification_email" 
                           value="<?php echo htmlspecialchars($settings['notification_email'] ?? 'orders@foodie.com'); ?>" 
                           class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    <p class="text-xs text-slate-400 mt-2">Email address for system notifications</p> -->
                    
                    <!-- Preview of maintenance message -->
                    <!-- <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <p class="text-xs font-medium text-yellow-800">Maintenance Message Preview:</p>
                        <p class="text-xs text-yellow-700 mt-1">"The system is currently under maintenance. Login and registration are temporarily unavailable. Please try again later."</p>
                    </div> -->
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" name="save_settings" 
                        class="inline-flex items-center space-x-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save Settings</span>
                </button>
            </div>
        </div>
    </form>
</div>