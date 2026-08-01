<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/../../inc/user_helpers.php';
require_once __DIR__ . '/../../inc/recaptcha_helper.php';

// Redirect if already logged in
redirectIfLoggedIn();

$pageTitle = 'Foodie - Forgot Password';
$customCss = '/Campus-Food-Ordering-System/view/entrance/css/login.css'; 
$simpleHeader = true;

include __DIR__ . '/includes/header.php';
?>

<!-- Card Frame - Same as login page -->
<div class="flex-1 flex items-center justify-center p-4 sm:p-6 md:p-8">
    <div class="auth-card">
        
        <!-- Left Column: Brand Panel -->
        <?php 
        $brandTitle = 'FOODIE';
        $brandSubtitle = 'Reset Your Password';
        include __DIR__ . '/includes/brand-panel.php'; 
        ?>

        <!-- Right Column: Form -->
        <div class="form-panel">
            <div>
                <!-- Alert Messages -->
                <div id="alert-container"></div>

                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-slate-900">Forgot Password</h2>
                    <p class="text-sm text-slate-500 mt-1">Enter your email to receive a reset code</p>
                </div>

                <!-- Step 1: Request Code -->
                <div id="step1">
                    <form id="forgotForm" method="POST" action="/Campus-Food-Ordering-System/Public/forgot-password.php">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <input type="email" name="email" id="email" placeholder="Enter your email" class="form-input" required>
                            </div>
                        </div>

                        <!-- reCAPTCHA -->
                        <?php if (is_recaptcha_enabled()): ?>
                        <div class="form-group">
                            <?php echo render_recaptcha_widget(); ?>
                            <div id="captcha-error" class="text-red-500 text-xs mt-1 hidden">Please complete the reCAPTCHA verification.</div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-submit" id="sendCodeBtn">
                            <i class="fa-solid fa-paper-plane mr-2"></i> Send Reset Code
                        </button>
                    </form>

                    <div class="bottom-hint mt-4">
                        Remember your password? 
                        <a href="login.php">Login</a>
                    </div>
                </div>

                <!-- Step 2: Verify Code -->
                <div id="step2" class="hidden">
                    <form id="verifyForm" method="POST" action="/Campus-Food-Ordering-System/Public/verify-reset-code.php">
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-regular fa-envelope"></i>
                                </span>
                                <input type="email" name="email" id="verifyEmail" placeholder="Enter your email" class="form-input" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Reset Code</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-solid fa-key"></i>
                                </span>
                                <input type="text" name="code" id="resetCode" placeholder="Enter 4-digit code" class="form-input" maxlength="4" required>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Check your email for the 4-digit reset code</p>
                        </div>

                        <button type="submit" class="btn-submit" id="verifyCodeBtn">
                            <i class="fa-solid fa-check mr-2"></i> Verify Code
                        </button>
                    </form>

                    <div class="bottom-hint mt-4">
                        Didn't receive code? 
                        <a href="#" onclick="resendCode()">Resend</a>
                        &nbsp;|&nbsp;
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>

                <!-- Step 3: Reset Password -->
                <div id="step3" class="hidden">
                    <form id="resetForm" method="POST" action="/Campus-Food-Ordering-System/Public/reset-password.php">
                        <input type="hidden" name="email" id="resetEmail">
                        <input type="hidden" name="code" id="resetCodeHidden">

                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" class="form-input" required>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters</p>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <div class="form-input-wrapper">
                                <span class="form-input-icon">
                                    <i class="fa-solid fa-lock"></i>
                                </span>
                                <input type="password" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" class="form-input" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" id="resetBtn">
                            <i class="fa-solid fa-key mr-2"></i> Reset Password
                        </button>
                    </form>

                    <div class="bottom-hint mt-4">
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// FORGOT PASSWORD FLOW
// ============================================

let currentEmail = '';

function showAlert(message, type = 'error') {
    const container = document.getElementById('alert-container');
    const colors = {
        error: 'bg-red-50 border-red-200 text-red-700',
        success: 'bg-green-50 border-green-200 text-green-700',
        info: 'bg-blue-50 border-blue-200 text-blue-700'
    };
    container.innerHTML = `
        <div class="p-3 mb-4 border rounded-lg flex items-center space-x-2 ${colors[type] || colors.error}">
            <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : type === 'info' ? 'fa-info-circle' : 'fa-exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
    `;
}

function showStep(step) {
    document.getElementById('step1').classList.add('hidden');
    document.getElementById('step2').classList.add('hidden');
    document.getElementById('step3').classList.add('hidden');
    document.getElementById(`step${step}`).classList.remove('hidden');
    document.getElementById('alert-container').innerHTML = '';
}

function validateCaptcha() {
    if (typeof grecaptcha === 'undefined' || !grecaptcha.getResponse) {
        return true;
    }
    
    try {
        const response = grecaptcha.getResponse();
        const errorEl = document.getElementById('captcha-error');
        
        if (!response || response.length === 0) {
            if (errorEl) {
                errorEl.classList.remove('hidden');
                errorEl.textContent = 'Please complete the reCAPTCHA verification.';
            }
            return false;
        }
        
        if (errorEl) {
            errorEl.classList.add('hidden');
        }
        return true;
        
    } catch (error) {
        console.error('reCAPTCHA validation error:', error);
        return false;
    }
}

function resetCaptcha() {
    if (typeof grecaptcha !== 'undefined' && grecaptcha.reset) {
        try {
            grecaptcha.reset();
            const errorEl = document.getElementById('captcha-error');
            if (errorEl) {
                errorEl.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error resetting reCAPTCHA:', error);
        }
    }
}

// ============================================
// Step 1: Request Reset Code
// ============================================
document.getElementById('forgotForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const captchaWidget = this.querySelector('.g-recaptcha');
    if (captchaWidget) {
        if (!validateCaptcha()) {
            return;
        }
    }

    const email = document.getElementById('email').value;
    const btn = document.getElementById('sendCodeBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Sending...';

    const formData = new FormData();
    formData.append('email', email);
    
    if (typeof grecaptcha !== 'undefined' && grecaptcha.getResponse) {
        formData.append('g-recaptcha-response', grecaptcha.getResponse());
    }

    // ✅ Use physical file URL
    fetch('/Campus-Food-Ordering-System/Public/forgot-password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            currentEmail = email;
            document.getElementById('verifyEmail').value = email;
            showAlert(data.message, 'success');
            showStep(2);
            resetCaptcha();
        } else {
            showAlert(data.message || 'Failed to send reset code', 'error');
            resetCaptcha();
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Send Reset Code';
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to send reset code. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane mr-2"></i> Send Reset Code';
    });
});

// ============================================
// Step 2: Verify Reset Code
// ============================================
document.getElementById('verifyForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const email = document.getElementById('verifyEmail').value;
    const code = document.getElementById('resetCode').value;
    const btn = document.getElementById('verifyCodeBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Verifying...';

    const formData = new FormData();
    formData.append('email', email);
    formData.append('code', code);

    // ✅ Use physical file URL
    fetch('/Campus-Food-Ordering-System/Public/verify-reset-code.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            document.getElementById('resetEmail').value = email;
            document.getElementById('resetCodeHidden').value = code;
            showAlert('Code verified! Please enter your new password.', 'success');
            showStep(3);
        } else {
            showAlert(data.message || 'Invalid reset code', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Verify Code';
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to verify code. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Verify Code';
    });
});

// ============================================
// Step 3: Reset Password
// ============================================
document.getElementById('resetForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword.length < 8) {
        showAlert('Password must be at least 8 characters', 'error');
        return;
    }

    if (newPassword !== confirmPassword) {
        showAlert('Passwords do not match', 'error');
        return;
    }

    const btn = document.getElementById('resetBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Resetting...';

    const formData = new FormData();
    formData.append('email', document.getElementById('resetEmail').value);
    formData.append('code', document.getElementById('resetCodeHidden').value);
    formData.append('new_password', newPassword);
    formData.append('confirm_password', confirmPassword);

    // ✅ Use physical file URL
    fetch('/Campus-Food-Ordering-System/Public/reset-password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'login.php';
            }, 3000);
        } else {
            showAlert(data.message || 'Failed to reset password', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-key mr-2"></i> Reset Password';
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to reset password. Please try again.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-key mr-2"></i> Reset Password';
    });
});

// ============================================
// Resend Code
// ============================================
function resendCode() {
    const email = document.getElementById('verifyEmail').value;
    if (!email) {
        showAlert('Please enter your email address', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('email', email);

    // ✅ Use physical file URL
    fetch('/Campus-Food-Ordering-System/Public/forgot-password.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            showAlert('New reset code sent! Check your email.', 'success');
        } else {
            showAlert(data.message || 'Failed to resend code', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to resend code. Please try again.', 'error');
    });
}

// ============================================
// Enter Key Support
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('email')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('forgotForm').dispatchEvent(new Event('submit'));
        }
    });
    document.getElementById('resetCode')?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('verifyForm').dispatchEvent(new Event('submit'));
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>