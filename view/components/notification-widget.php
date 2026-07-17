<!-- Notification Widget -->
<div class="notification-widget relative" id="notificationWidget">
    <button 
        onclick="toggleNotifications()" 
        class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none transition-colors"
        id="notificationBell"
    >
        <i class="fa-regular fa-bell text-xl"></i>
        <?php if ($unreadCount > 0): ?>
            <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full min-w-[20px] min-h-[20px]">
                <?php echo min($unreadCount, 99); ?>
            </span>
        <?php endif; ?>
    </button>

    <!-- Dropdown -->
    <div 
        id="notificationDropdown" 
        class="hidden absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-2xl border border-gray-100 z-50 overflow-hidden transition-all duration-200"
    >
        <div class="flex items-center justify-between p-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
            <div class="flex items-center space-x-2">
                <?php if ($unreadCount > 0): ?>
                    <button 
                        onclick="markAllAsRead()" 
                        class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
                    >
                        Mark all as read
                    </button>
                    <span class="w-px h-4 bg-gray-200"></span>
                <?php endif; ?>
                <button 
                    onclick="toggleNotifications()" 
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
            <?php if (empty($notifications)): ?>
                <div class="p-8 text-center">
                    <div class="text-4xl text-gray-300 mb-3">
                        <i class="fa-regular fa-bell-slash"></i>
                    </div>
                    <p class="text-sm text-gray-500">No notifications yet</p>
                    <p class="text-xs text-gray-400 mt-1">We'll notify you when something happens</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <div 
                        class="notification-item px-4 py-3 hover:bg-gray-50 transition-colors border-b border-gray-50 last:border-0 cursor-pointer <?php echo $notification['is_read'] ? '' : 'bg-blue-50/30'; ?>"
                        data-id="<?php echo $notification['id']; ?>"
                        onclick="markAsRead(<?php echo $notification['id']; ?>)"
                    >
                        <div class="flex items-start space-x-3">
                            <!-- Icon -->
                            <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white text-sm <?php echo htmlspecialchars($notification['bg_class']); ?>">
                                <i class="<?php echo $notification['icon']; ?>"></i>
                            </div>
                            
                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                    </p>
                                    <?php if (!$notification['is_read']): ?>
                                        <span class="flex-shrink-0 w-2 h-2 mt-1.5 ml-2 rounded-full bg-blue-500"></span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </p>
                                <p class="text-xs text-gray-400 mt-1">
                                    <?php echo $notification['time_ago']; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="p-3 border-t border-gray-100 bg-gray-50/50 rounded-b-xl">
            <a 
                href="<?php echo htmlspecialchars($notificationsPageUrl ?? '/Campus-Food-Ordering-System/Public/notifications'); ?>" 
                class="block text-center text-xs text-indigo-600 hover:text-indigo-800 font-medium transition-colors"
            >
                View all notifications
            </a>
        </div>
    </div>
</div>

<style>
.notification-widget {
    position: relative;
}
#notificationDropdown {
    transform-origin: top right;
    animation: notificationSlideDown 0.2s ease-out;
}
@keyframes notificationSlideDown {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<script>
function toggleNotifications() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.toggle('hidden');
}

function markAsRead(notificationId) {
    const item = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
    
    fetch('/Campus-Food-Ordering-System/Public/api/notifications/mark-read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `notification_id=${notificationId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            if (item) {
                item.classList.remove('bg-blue-50/30');
                const dot = item.querySelector('.w-2.h-2');
                if (dot) dot.remove();
            }
            // Update badge count
            updateNotificationBadge();
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    fetch('/Campus-Food-Ordering-System/Public/api/notifications/mark-all-read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all items
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('bg-blue-50/30');
                const dot = item.querySelector('.w-2.h-2');
                if (dot) dot.remove();
            });
            // Update badge count
            updateNotificationBadge();
            // Remove mark all button
            const markAllBtn = document.querySelector('button[onclick="markAllAsRead()"]');
            if (markAllBtn) {
                const parent = markAllBtn.parentElement;
                const separator = parent.querySelector('.w-px');
                if (separator) separator.remove();
                markAllBtn.remove();
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function updateNotificationBadge() {
    fetch('/Campus-Food-Ordering-System/Public/api/notifications/unread-count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const count = data.data.count;
                const badge = document.querySelector('#notificationBell .absolute');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count > 99 ? '99+' : count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            }
        })
        .catch(error => console.error('Error:', error));
}

// Click outside to close dropdown
document.addEventListener('click', function(event) {
    const widget = document.getElementById('notificationWidget');
    if (!widget.contains(event.target)) {
        const dropdown = document.getElementById('notificationDropdown');
        if (!dropdown.classList.contains('hidden')) {
            dropdown.classList.add('hidden');
        }
    }
});
</script>