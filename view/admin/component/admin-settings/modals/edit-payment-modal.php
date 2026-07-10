<!-- Edit Payment Method Modal -->
<div id="editPaymentModal" class="modal-overlay fixed inset-0 bg-slate-950/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-6 shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-slate-900">Edit Payment Method</h2>
            <a href="?tab=payment" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fa-solid fa-xmark text-xl"></i>
            </a>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="update_payment_method" value="1">
            <input type="hidden" name="payment_id" value="<?php echo $editPayment->getId(); ?>">
            <input type="hidden" name="tab" value="payment">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Payment Method Name <span class="text-red-500">*</span></label>
                <input type="text" name="payment_name" value="<?php echo htmlspecialchars($editPayment->getName()); ?>" required class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Name</label>
                <input type="text" name="payment_account_name" value="<?php echo htmlspecialchars($editPayment->getAccountName() ?? ''); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-700 mb-1">Account Number</label>
                <input type="text" name="payment_account_number" value="<?php echo htmlspecialchars($editPayment->getAccountNumber() ?? ''); ?>" class="w-full px-4 py-2 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500 text-sm">
            </div>
            <div class="mb-4">
                <label class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-slate-700">Active</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="payment_is_active" class="sr-only peer" <?php echo $editPayment->isActive() ? 'checked' : ''; ?>>
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                    </label>
                </label>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-lg transition-colors text-sm">Update Payment Method</button>
            <a href="?tab=payment" class="w-full mt-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2.5 rounded-lg transition-colors text-sm block text-center">Cancel</a>
        </form>
    </div>
</div>