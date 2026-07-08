<?php
/**
 * Customer Page Footer
 */
?>
    <!-- TOAST POPUP -->
    <div id="add-toast" class="fixed bottom-6 right-6 bg-slate-950 text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center space-x-3.5 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50 border border-slate-800 max-w-sm">
        <div id="toast-icon" class="text-emerald-400 bg-emerald-500/10 p-2 rounded-xl">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Notification</h4>
            <p id="toast-message" class="text-sm font-semibold text-slate-100"></p>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-100 mt-20 py-8">
        <div class="max-w-6xl mx-auto px-4 text-center text-slate-400 text-xs font-semibold uppercase tracking-wider">
            &copy; <?php echo date('Y'); ?> FOODIE INC. All rights reserved. Delicious Food, Delivered Fast.
        </div>
    </footer>

    <script>
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('add-toast');
            const messageEl = document.getElementById('toast-message');
            const iconEl = document.getElementById('toast-icon');

            messageEl.innerText = message;
            
            if (isSuccess) {
                iconEl.innerHTML = '<i class="fa-solid fa-circle-check text-lg"></i>';
                iconEl.className = 'text-emerald-400 bg-emerald-500/10 p-2 rounded-xl';
            } else {
                iconEl.innerHTML = '<i class="fa-solid fa-circle-exclamation text-lg"></i>';
                iconEl.className = 'text-red-400 bg-red-500/10 p-2 rounded-xl';
            }

            toast.classList.remove('translate-y-24', 'opacity-0', 'pointer-events-none');
            toast.classList.add('translate-y-0', 'opacity-100');

            setTimeout(() => {
                toast.classList.add('translate-y-24', 'opacity-0', 'pointer-events-none');
                toast.classList.remove('translate-y-0', 'opacity-100');
            }, 3000);
        }
    </script>
</body>
</html>