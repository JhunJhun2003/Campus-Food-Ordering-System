<?php
/**
 * Entrance Page Footer - Reusable footer for login/register pages
 */
?>
    <!-- TOAST POPUP -->
    <div id="add-toast" class="fixed bottom-6 right-6 bg-slate-950 text-white px-5 py-4 rounded-2xl shadow-2xl flex items-center space-x-3.5 transform translate-y-24 opacity-0 transition-all duration-300 pointer-events-none z-50 border border-slate-800 max-w-sm">
        <div class="text-emerald-400 bg-emerald-500/10 p-2 rounded-xl">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div>
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Success</h4>
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
        // ============================================
        // TOAST NOTIFICATION
        // ============================================
        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('add-toast');
            const messageEl = document.getElementById('toast-message');
            const iconEl = toast.querySelector('.text-emerald-400');

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

        function showNotification(message, isSuccess = true) {
            showToast(message, isSuccess);
        }
  // ============================================
// reCAPTCHA Validation - IMPROVED
// ============================================

/**
 * Validate reCAPTCHA response
 * @returns {boolean} True if valid, false otherwise
 */
function validateCaptcha() {
    // Check if grecaptcha is available
    if (typeof grecaptcha === 'undefined') {
        console.warn('reCAPTCHA not loaded');
        return true; // Skip validation if not loaded (optional)
    }
    
    try {
        const response = grecaptcha.getResponse();
        const errorEl = document.getElementById('captcha-error');
        
        if (!response || response.length === 0) {
            // Show error
            if (errorEl) {
                errorEl.classList.remove('hidden');
                errorEl.textContent = 'Please complete the reCAPTCHA verification.';
            }
            // Highlight the captcha widget
            const captchaWidget = document.querySelector('.g-recaptcha');
            if (captchaWidget) {
                captchaWidget.style.border = '2px solid #ef4444';
                captchaWidget.style.borderRadius = '4px';
                captchaWidget.style.padding = '2px';
            }
            return false;
        }
        
        // Valid - hide error
        if (errorEl) {
            errorEl.classList.add('hidden');
        }
        // Remove highlight
        const captchaWidget = document.querySelector('.g-recaptcha');
        if (captchaWidget) {
            captchaWidget.style.border = 'none';
            captchaWidget.style.padding = '0';
        }
        return true;
        
    } catch (error) {
        console.error('reCAPTCHA validation error:', error);
        return false;
    }
}

/**
 * Reset reCAPTCHA (useful after form submission errors)
 */
function resetCaptcha() {
    if (typeof grecaptcha !== 'undefined') {
        try {
            grecaptcha.reset();
            const errorEl = document.getElementById('captcha-error');
            if (errorEl) {
                errorEl.classList.add('hidden');
            }
            const captchaWidget = document.querySelector('.g-recaptcha');
            if (captchaWidget) {
                captchaWidget.style.border = 'none';
                captchaWidget.style.padding = '0';
            }
        } catch (error) {
            console.error('Error resetting reCAPTCHA:', error);
        }
    }
}

// ============================================
// Attach validation to ALL forms on the page
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    // Select all forms on the page
    const forms = document.querySelectorAll('form');
    
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            // Check if this form has a reCAPTCHA widget
            const hasCaptcha = form.querySelector('.g-recaptcha');
            
            if (hasCaptcha && typeof grecaptcha !== 'undefined') {
                if (!validateCaptcha()) {
                    e.preventDefault();
                    // Scroll to captcha error
                    const errorEl = document.getElementById('captcha-error');
                    if (errorEl) {
                        errorEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return false;
                }
            }
            return true;
        });
    });
});

// ============================================
// Reset captcha when switching login/register tabs
// ============================================

// If you have a switchTab function, add this:
function resetCaptchaOnTabSwitch() {
    resetCaptcha();
}
    </script>
</body>
</html>