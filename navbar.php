<?php
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $is_logged_in ? $_SESSION['role'] : null;
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
        <a class="navbar-brand" href="index.php"><img src="image/3.png" alt="Logo">Helpdesk</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <?php if (!$is_logged_in): ?>
            <button class="theme-toggle d-lg-none" id="theme-toggle" aria-label="Toggle Dark Mode">🌙</button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Register</a>
                    <button class="theme-toggle d-none d-lg-block" id="theme-toggle" aria-label="Toggle Dark Mode" type="button">🌙</button>
                </div>
            </div>
        <?php else: ?>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav me-auto">
                    <button class="theme-toggle d-none d-lg-block" id="theme-toggle" aria-label="Toggle Dark Mode" type="button">🌙</button>
                </div>
                <div class="navbar-nav ms-auto">
                    <div class="dropdown d-none d-lg-block">
                        <button class="btn btn-link nav-link dropdown-toggle" type="button" id="notificationDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            🔔 <span id="unread-count" class="badge bg-danger"></span>
                        </button>
                        <ul class="dropdown-menu p-3" aria-labelledby="notificationDropdown" id="notification-list" style="min-width: 280px; max-height: 400px; overflow-y: auto;">
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="notifications.php"><span style="font-size: 1.2em;">👁️</span> View All Notifications</a></li>
                            <li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item d-flex align-items-center gap-2" href="#" id="mark-all-read"><span style="font-size: 1.2em;">✔️✔️</span> Mark All as Read</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item d-flex align-items-center gap-2" href="#" id="clear-all"><span style="font-size: 1.2em;">🗑️</span> Delete All Notifications</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="dropdown-header">Recent Notifications</li>
                            <div id="notification-items-container" style="max-height: 250px; overflow-y: auto;"></div>
                        </ul>
                    </div>
                    <div class="dropdown d-lg-none">
                        <button class="btn btn-link nav-link dropdown-toggle" type="button" id="notificationDropdownMobile" data-bs-toggle="dropdown" aria-expanded="false">
                            🔔 <span id="unread-count-mobile" class="badge bg-danger"></span>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="notificationDropdownMobile" id="notification-list-mobile">
                            <li><a class="dropdown-item" href="notifications.php">View All Notifications</a></li>
                            <li><hr class="dropdown-divider"></li>
<li><a class="dropdown-item" href="#" id="mark-all-read-mobile">✓✓ Mark All as Read</a></li>
                            <li><a class="dropdown-item" href="#" id="clear-all-mobile">Delete All Notifications</a></li>
                        </ul>
                    </div>
                    <?php if ($user_role == 'admin'): ?>
                        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                <li><a class="dropdown-item" href="admin_reports.php">Reports</a></li>
                                <li><a class="dropdown-item" href="admin_analytics.php"><span style="font-size: 1.2em;">📊</span> Data and Analytics</a></li>
                                <li><a class="dropdown-item" href="admin_settings.php">Settings</a></li>
                                <li><a class="dropdown-item" href="user_management.php">User Management</a></li>
                                <li><a class="dropdown-item" href="audit_logs.php">Audit Logs</a></li>
                                <li><a class="dropdown-item" href="backup.php">Backup</a></li>
                                <li><a class="dropdown-item" href="archive_tickets.php">Archived Tickets</a></li>
                                <li><a class="dropdown-item" href="admin_faq.php">FAQ Management</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a class="nav-link" href="view_tickets.php">View Tickets</a>
                        <a class="nav-link" href="submit_ticket.php">Submit Ticket</a>
                        <a class="nav-link" href="faq.php">FAQ</a>
                    <?php endif; ?>
                    <a class="nav-link" href="profile.php">Profile</a>
                    <a class="nav-link" href="logout.php">Logout</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</nav>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmLogout">Logout</button>
            </div>
        </div>
    </div>
</div>
<?php if ($is_logged_in): ?>
<script>
    // Set user role on body for JavaScript access
    document.body.dataset.userRole = '<?php echo $user_role; ?>';

    // Track if we had zero notifications before to play sound only when first notification arrives
    let hadZeroNotifications = true;

    // Notifications fetch and display
    async function fetchNotifications() {
        try {
            const response = await fetch('get_notifications.php');
            if (!response.ok) throw new Error('Network response was not ok');
            const data = await response.json();

            const unreadCount = data.unread_count || 0;
            const notifications = data.notifications || [];

            // Play sound only when going from zero to one or more notifications
            if (hadZeroNotifications && unreadCount > 0) {
                playNotificationSound();
                hadZeroNotifications = false;
            }
            // Reset flag if all notifications are read
            else if (unreadCount === 0) {
                hadZeroNotifications = true;
            }

            // Update unread count badges
            document.querySelectorAll('#unread-count, #unread-count-mobile').forEach(el => {
                el.textContent = unreadCount > 0 ? unreadCount : '';
            });

            // Update notification lists
            const notificationList = document.getElementById('notification-list');
            const notificationListMobile = document.getElementById('notification-list-mobile');

            if (notificationList && notificationListMobile) {
                // Clear existing notification items
                notificationList.querySelectorAll('li.notification-item').forEach(li => li.remove());
                notificationListMobile.querySelectorAll('li.notification-item').forEach(li => li.remove());

                // Sort notifications: unread first, then by created_at descending
                notifications.sort((a, b) => {
                    if (a.is_read === b.is_read) {
                        return new Date(b.created_at) - new Date(a.created_at);
                    }
                    return a.is_read ? 1 : -1; // unread (is_read=false) first
                });

                notifications.forEach(notif => {
                    const li = document.createElement('li');
                    li.className = 'notification-item';
                    const a = document.createElement('a');
                    a.className = 'dropdown-item mark-read-link';
                    a.setAttribute('data-notif-id', notif.id);
                    const userRole = document.body.dataset.userRole;
                    const ticketPage = userRole === 'admin' ? 'update_ticket.php' : 'view_tickets.php';
                    a.href = notif.ticket_id ? `${ticketPage}?id=${notif.ticket_id}` : '#';

                    // Add red dot for unread notifications
                    if (!notif.is_read) {
                        const redDot = document.createElement('span');
                        redDot.className = 'notification-red-dot';
                        redDot.innerHTML = '●';
                        a.appendChild(redDot);
                    }

                    const messageSpan = document.createElement('span');
                    messageSpan.textContent = notif.message;
                    a.appendChild(messageSpan);

                    li.appendChild(a);
                    notificationList.insertBefore(li, notificationList.lastElementChild);
                    notificationListMobile.insertBefore(li.cloneNode(true), notificationListMobile.lastElementChild);
                });
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    }

    // Play notification sound
    function playNotificationSound() {
        // Try to play custom sound file first
        const audio = new Audio('audio/ring.mp3');
        audio.play().catch(e => {
            // Fallback: Create a simple beep sound using Web Audio API
            console.warn('Custom sound file not found, using fallback beep:', e);
            playFallbackBeep();
        });
    }

    // Fallback beep sound using Web Audio API
    function playFallbackBeep() {
        try {
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.frequency.setValueAtTime(800, audioContext.currentTime); // 800Hz beep
            oscillator.type = 'sine';

            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.5);
        } catch (e) {
            console.warn('Fallback beep also failed:', e);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        fetchNotifications();
        setInterval(fetchNotifications, 60000); // Refresh every 60 seconds
    });

    // Mark individual notification as read when clicked (only for navbar dropdown)
    document.addEventListener('click', function(e) {
        // Check if clicked element or any parent has mark-read-link class
        let target = e.target;
        while (target && target !== e.currentTarget) {
            if (target.classList && target.classList.contains('mark-read-link')) {
                // Only handle if it's in a dropdown menu (navbar notifications)
                if (target.closest('.dropdown-menu')) {
                    e.preventDefault();
                    const notifId = target.getAttribute('data-notif-id');
                    const href = target.getAttribute('href');

                    // Mark as read via AJAX
                    fetch('notifications.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: 'mark_read_id=' + encodeURIComponent(notifId)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI optimistically first
                            const unreadBadges = document.querySelectorAll('#unread-count, #unread-count-mobile');
                            unreadBadges.forEach(badge => {
                                const currentCount = parseInt(badge.textContent) || 0;
                                if (currentCount > 0) {
                                    badge.textContent = currentCount - 1 || '';
                                }
                            });

                            // Remove red dot from clicked notification
                            const redDot = target.querySelector('.notification-red-dot');
                            if (redDot) {
                                redDot.remove();
                            }

                            // Navigate after a short delay to allow UI update
                            setTimeout(() => {
                                if (href && href !== '#') {
                                    window.location.href = href;
                                }
                            }, 100);
                        }
                    })
                    .catch(error => {
                        console.error('Error marking notification as read:', error);
                        // Still navigate even if marking as read fails
                        if (href && href !== '#') {
                            window.location.href = href;
                        }
                    });
                }
                break;
            }
            target = target.parentElement;
        }
    });

    // Clear all notifications
    document.addEventListener('click', function(e) {
        if (e.target.id === 'clear-all' || e.target.id === 'clear-all-mobile') {
            e.preventDefault();
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'delete_all=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications(); // Refresh the dropdown
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
        // Mark all as read
        else if (e.target.id === 'mark-all-read' || e.target.id === 'mark-all-read-mobile') {
            e.preventDefault();
            fetch('notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'mark_all_read=1'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    fetchNotifications(); // Refresh the dropdown
                }
            })
            .catch(error => {
                console.error('Error marking all as read:', error);
            });
        }
    });
</script>
<?php endif; ?>
