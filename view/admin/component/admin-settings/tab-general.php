<!-- TAB 1: GENERAL SETTINGS -->
<div id="tab-general" class="tab-content <?php echo $activeTab === 'general' ? '' : 'hidden'; ?>">
    <form method="POST" action="">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- General Settings -->
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-sliders text-indigo-500"></i>
                    <span>General</span>
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Site Name</label>
                        <input type="text" name="setting_site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'FOODIE'); ?>" 
                               class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contact Email</label>
                        <input type="email" name="setting_site_email" 
                               value="<?php echo htmlspecialchars($settings['site_email'] ?? 'admin@foodie.com'); ?>" 
                               class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Contact Phone</label>
                        <input type="text" name="setting_site_phone" 
                               value="<?php echo htmlspecialchars($settings['site_phone'] ?? '+1234567890'); ?>" 
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
            <div class="bg-white border border-slate-100 rounded-xl p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-truck text-indigo-500"></i>
                    <span>Order</span>
                </h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Default Preparation Time (minutes)</label>
                        <input type="number" name="setting_preparation_time" 
                               value="<?php echo htmlspecialchars($settings['preparation_time'] ?? 15); ?>" 
                               class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Cancellation Time (minutes)</label>
                        <input type="number" name="setting_cancellation_time" 
                               value="<?php echo htmlspecialchars($settings['cancellation_time'] ?? 5); ?>" 
                               class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
                        <select name="setting_currency" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
                            <option value="USD" <?php echo ($settings['currency'] ?? '') == 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="PHP" <?php echo ($settings['currency'] ?? '') == 'PHP' ? 'selected' : ''; ?>>PHP (₱)</option>
                            <option value="EUR" <?php echo ($settings['currency'] ?? '') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                            <option value="GBP" <?php echo ($settings['currency'] ?? '') == 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 flex justify-end">
            <button type="submit" name="save_settings" 
                    class="inline-flex items-center space-x-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold shadow-sm transition-all">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Settings</span>
            </button>
        </div>
    </form>
</div>