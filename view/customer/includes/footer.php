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
    <footer class="bg-white border-t border-slate-100 mt-10 py-10">
    <div class="max-w-6xl mx-auto px-4 text-center">
        <!-- Brand -->
        <div class="flex items-center justify-center space-x-3 mb-4">
            <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.9.14/dist/dotlottie-wc.js" type="module"></script>
            <dotlottie-wc src="https://lottie.host/ea75b4fe-1d6d-4e5e-97eb-df01f2e490df/FTXFOlVlea.lottie" style="width: 30px;height: 45px" autoplay loop></dotlottie-wc>
            <span class="text-lg font-black tracking-wider text-slate-900"><?php echo htmlspecialchars(app_site_name()); ?></span>
        </div>
        
        <!-- Contact Info -->
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm text-slate-500 mb-4">
            <span>
                <i class="fa-regular fa-envelope text-emerald-500 mr-1.5"></i>
                <a href="mailto:<?php echo htmlspecialchars(app_setting('site_email', 'support@foodie.com')); ?>" class="hover:text-emerald-500 transition-colors">
                    <?php echo htmlspecialchars(app_setting('site_email', 'support@foodie.com')); ?>
                </a>
            </span>
            <span class="text-slate-300">|</span>
            <span>
                <i class="fa-solid fa-phone text-emerald-500 mr-1.5"></i>
                <a href="tel:<?php echo htmlspecialchars(app_setting('site_phone', '+1234567890')); ?>" class="hover:text-emerald-500 transition-colors">
                    <?php echo htmlspecialchars(app_setting('site_phone', '+1234567890')); ?>
                </a>
            </span>
        </div>
        
        <!-- Divider -->
        <div class="w-20 h-0.5 bg-emerald-200 mx-auto mb-4 rounded-full"></div>
        
        <!-- Copyright -->
        <p class="text-xs text-slate-400">
            &copy; <?php echo date('Y'); ?> <span class="font-semibold text-slate-600"><?php echo htmlspecialchars(app_site_name()); ?></span>. All rights reserved. Delicious Food, Delivered Fast.
        </p>
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