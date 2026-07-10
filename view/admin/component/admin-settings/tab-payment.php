<!-- TAB 2: PAYMENT METHODS -->
<div id="tab-payment" class="tab-content <?php echo $activeTab === 'payment' ? '' : 'hidden'; ?>">
    <div class="bg-white border border-slate-100 rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-credit-card text-indigo-500"></i>
                    <span>Payment Methods</span>
                </h2>
                <p class="text-sm text-slate-500">Manage payment methods available at checkout</p>
            </div>
            <button onclick="openAddPaymentModal()" 
                    class="flex items-center space-x-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition-colors">
                <i class="fa-solid fa-plus"></i>
                <span>Add Method</span>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Account Number</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (empty($paymentMethods)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">
                                <i class="fa-regular fa-credit-card text-3xl block mb-2"></i>
                                No payment methods added yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paymentMethods as $pm): ?>
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-slate-800">
                                    <?php echo htmlspecialchars($pm->getName()); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?php echo htmlspecialchars($pm->getAccountName() ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <?php echo htmlspecialchars($pm->getAccountNumber() ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $pm->isActive() ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $pm->isActive() ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <a href="?edit_payment=<?php echo $pm->getId(); ?>&tab=payment" 
                                       class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                        <i class="fa-regular fa-pen-to-square mr-1"></i>Edit
                                    </a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this payment method?');">
                                        <input type="hidden" name="delete_payment_method" value="1">
                                        <input type="hidden" name="payment_id" value="<?php echo $pm->getId(); ?>">
                                        <input type="hidden" name="tab" value="payment">
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">
                                            <i class="fa-regular fa-trash-can mr-1"></i>Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>