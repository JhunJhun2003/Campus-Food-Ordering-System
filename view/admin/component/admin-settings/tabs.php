<!-- Tabs Navigation -->
<div class="mb-6 border-b border-slate-200">
    <nav class="flex space-x-6" id="settingsTabs">
        <button onclick="switchTab('general')" 
                class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'general' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" 
                data-tab="general">
            <i class="fa-solid fa-sliders mr-2"></i>General
        </button>
        <button onclick="switchTab('payment')" 
                class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'payment' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" 
                data-tab="payment">
            <i class="fa-solid fa-credit-card mr-2"></i>Payment Methods
        </button>
        <button onclick="switchTab('access-control')" 
                class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'access-control' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" 
                data-tab="access-control">
            <i class="fa-solid fa-lock mr-2"></i>Access Control
        </button>
        <button onclick="switchTab('system')" 
                class="tab-btn px-1 py-3 text-sm font-medium border-b-2 <?php echo $activeTab === 'system' ? 'active border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'; ?>" 
                data-tab="system">
            <i class="fa-solid fa-server mr-2"></i>System
        </button>
    </nav>
</div>