<?php
/**
 * Staff Page Footer - Toast notification and closing tags
 */
?>
    <!-- ===== TOAST ===== -->
    <div id="toast" class="toast"></div>

    <style>
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            padding: 16px 24px;
            border-radius: 12px;
            color: white;
            font-weight: 500;
            font-size: 14px;
            z-index: 2000;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            max-width: 400px;
        }
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        .toast.success {
            background: #10B981;
        }
        .toast.error {
            background: #EF4444;
        }
    </style>

    <script>
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type;
            setTimeout(() => toast.classList.add('show'), 10);
            setTimeout(() => toast.classList.remove('show'), 3000);
        }

        <?php if (isset($success) && $success): ?>
            showToast('<?php echo htmlspecialchars($success); ?>', 'success');
        <?php endif; ?>
        
        <?php if (isset($error) && $error): ?>
            showToast('<?php echo htmlspecialchars($error); ?>', 'error');
        <?php endif; ?>
    </script>
</body>
</html>