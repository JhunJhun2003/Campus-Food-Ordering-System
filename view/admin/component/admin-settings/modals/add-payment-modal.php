<!-- Add Payment Method Modal -->
<div id="addPaymentModal" class="modal-overlay hidden fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Add Payment Method</h2>
            <button onclick="closeAddPaymentModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="add_payment_method" value="1">
            <input type="hidden" name="tab" value="payment">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method Name <span class="text-red-500">*</span></label>
                <input type="text" name="payment_name" placeholder="e.g., K Pay, Wave Pay" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Name</label>
                <input type="text" name="payment_account_name" placeholder="e.g., Foodie Restaurant" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Number</label>
                <input type="text" name="payment_account_number" placeholder="e.g., 0987654321" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Add Payment Method</button>
            <button type="button" onclick="closeAddPaymentModal()" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm">Cancel</button>
        </form>
    </div>
</div>