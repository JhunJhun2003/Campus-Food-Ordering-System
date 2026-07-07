<?php
/**
 * Entrance Page Footer - Toast notification and closing tags
 */
?>
    <!-- Toast Notification -->
    <div id="toast-notification" class="toast-notification">
        <div id="toast-icon" class="toast-icon-success">
            <i class="fa-solid fa-circle-check text-lg"></i>
        </div>
        <div>
            <p id="toast-message" class="text-sm font-semibold"></p>
        </div>
    </div>

    <script>
        function showNotification(message, isSuccess = true) {
            const toast = document.getElementById('toast-notification');
            const messageEl = document.getElementById('toast-message');
            const iconEl = document.getElementById('toast-icon');

            messageEl.innerText = message;
            iconEl.className = isSuccess ? 'toast-icon-success' : 'toast-icon-error';
            iconEl.innerHTML = isSuccess 
                ? '<i class="fa-solid fa-circle-check text-lg"></i>'
                : '<i class="fa-solid fa-circle-xmark text-lg"></i>';

            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        function handleSocialAuth(provider) {
            showNotification(`Sign-in procedure initialized via ${provider}.`);
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password-input');
            const toggleIcon = document.getElementById('password-toggle-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fa-regular fa-eye';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fa-regular fa-eye-slash';
            }
        }

        function triggerForgotPassword(event) {
            event.preventDefault();
            const identity = document.getElementById('identity-input')?.value;
            if (!identity) {
                showNotification("Please enter your email or username above.", false);
            } else {
                showNotification(`Password reset link sent to: ${identity}`);
            }
        }

        function switchTab(mode) {
            if (currentMode === mode) return;
            currentMode = mode;

            const tabLogin = document.getElementById('tab-login');
            const tabRegister = document.getElementById('tab-register');
            const submitBtn = document.getElementById('submit-btn');
            const registerFields = document.getElementById('register-fields');
            const registerPhone = document.getElementById('register-phone');
            const identityLabel = document.getElementById('identity-label');
            const identityInput = document.getElementById('identity-input');
            const bottomHint = document.getElementById('bottom-hint');
            const forgotPassword = document.getElementById('forgot-password');

            if (mode === 'login') {
                tabLogin.className = "auth-tab active";
                tabRegister.className = "auth-tab";
                submitBtn.innerText = "Login";
                submitBtn.name = "login";
                submitBtn.value = "1";
                identityLabel.innerText = "Email / Username";
                identityInput.placeholder = "Enter email or username";
                forgotPassword.style.display = "block";

                registerFields.classList.remove('visible');
                registerPhone.classList.remove('visible');
                setTimeout(() => {
                    registerFields.style.display = 'none';
                    registerPhone.style.display = 'none';
                }, 300);

                bottomHint.innerHTML = 'Don\'t have an account? <a href="#" onclick="switchTab(\'register\'); event.preventDefault();" class="text-slate-800 hover:text-emerald-600 font-bold underline transition-colors decoration-1 underline-offset-2">Register</a>';
            } else {
                tabRegister.className = "auth-tab active";
                tabLogin.className = "auth-tab";
                submitBtn.innerText = "Register";
                submitBtn.name = "register";
                submitBtn.value = "1";
                identityLabel.innerText = "Email Address";
                identityInput.placeholder = "Enter email address";
                forgotPassword.style.display = "none";

                registerFields.style.display = 'block';
                registerPhone.style.display = 'block';
                setTimeout(() => {
                    registerFields.classList.add('visible');
                    registerPhone.classList.add('visible');
                }, 50);

                bottomHint.innerHTML = 'Already have an account? <a href="#" onclick="switchTab(\'login\'); event.preventDefault();" class="text-slate-800 hover:text-emerald-600 font-bold underline transition-colors decoration-1 underline-offset-2">Login</a>';
            }
        }

        // Initialize
        let currentMode = 'login';
    </script>
</body>
</html>