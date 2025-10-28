// Basic JavaScript for Helpdesk

// Dark Mode Toggle Functionality
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    const themeToggles = document.querySelectorAll('#theme-toggle');
    themeToggles.forEach(themeToggle => {
        themeToggle.textContent = savedTheme === 'dark' ? '☀️' : '🌙';
        themeToggle.addEventListener('click', toggleTheme);
    });
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);

    const themeToggles = document.querySelectorAll('#theme-toggle');
    themeToggles.forEach(themeToggle => {
        themeToggle.textContent = newTheme === 'dark' ? '☀️' : '🌙';
    });
}

// Example: Confirm logout
// Initialize theme on page load
initTheme();
const logoutLinks = document.querySelectorAll('a[href="logout.php"]');
logoutLinks.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
        logoutModal.show();
    });
});

// Handle confirm logout
const confirmLogoutBtn = document.getElementById('confirmLogout');
if (confirmLogoutBtn) {
    confirmLogoutBtn.addEventListener('click', function() {
        const logoutModal = bootstrap.Modal.getInstance(document.getElementById('logoutModal'));
        logoutModal.hide();
        const logoutLinks = document.querySelectorAll('a[href="logout.php"]');
        logoutLinks.forEach(link => {
            link.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging out...';
            link.style.pointerEvents = 'none'; // Disable further clicks
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 1000);
        });
    });
}

// Login button animation
const loginBtn = document.querySelector('.login-btn');
if (loginBtn) {
    console.log('Login button found');
    loginBtn.addEventListener('click', function(e) {
        console.log('Login button clicked');
        e.preventDefault(); // Prevent form submit
        loginBtn.disabled = true;
        loginBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in, please wait...';
        // Submit the form after a delay to show the animation
        const form = loginBtn.closest('form');
        setTimeout(() => {
            form.submit();
        }, 3000);
    });
} else {
    console.log('Login button not found');
}

// Auto-refresh chat messages
const chatBox = document.querySelector('.chat-box');
const messageForm = document.querySelector('#message-form');
if (chatBox && messageForm) {
    const ticketId = new URLSearchParams(window.location.search).get('id');
    if (ticketId) {
        function refreshMessages() {
            fetch('get_messages.php?ticket_id=' + ticketId)
                .then(response => response.json())
                .then(messages => {
                    chatBox.innerHTML = '';
                    if (messages.length === 0) {
                        chatBox.innerHTML = '<p>No messages yet.</p>';
                    } else {
                        messages.forEach(msg => {
                            const msgDiv = document.createElement('div');
                            msgDiv.className = 'message mb-2 d-flex align-items-start';

                            const imgDiv = document.createElement('div');
                            // Removed 'me-2' class to reduce gap
                            imgDiv.style.marginRight = '0px';
                            imgDiv.style.paddingRight = '0px';

                            if (msg.profile_picture) {
                                const img = document.createElement('img');
                                img.src = 'uploads/' + msg.profile_picture;
                                img.alt = 'Profile';
                                img.style.width = '40px';
                                img.style.height = '40px';
                                img.style.borderRadius = '50%';
                                img.style.objectFit = 'cover';
                                imgDiv.appendChild(img);
                            } else {
                                const initialsDiv = document.createElement('div');
                                initialsDiv.style.width = '40px';
                                initialsDiv.style.height = '40px';
                                initialsDiv.style.borderRadius = '50%';
                                initialsDiv.style.backgroundColor = '#ccc';
                                initialsDiv.style.display = 'flex';
                                initialsDiv.style.alignItems = 'center';
                                initialsDiv.style.justifyContent = 'center';
                                initialsDiv.style.fontWeight = 'bold';
                                initialsDiv.style.color = '#fff';
                                initialsDiv.textContent = msg.username.charAt(0).toUpperCase();
                                imgDiv.appendChild(initialsDiv);
                            }

                            const contentDiv = document.createElement('div');
                            contentDiv.innerHTML = '<strong>' + msg.username + ':</strong> ' + msg.message + ' <small class="text-muted">(' + msg.timestamp + ')</small>';

                            msgDiv.appendChild(imgDiv);
                            msgDiv.appendChild(contentDiv);

                            chatBox.appendChild(msgDiv);
                        });
                    }
                    chatBox.scrollTop = chatBox.scrollHeight; // Scroll to bottom
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        refreshMessages(); // Initial load
        setInterval(refreshMessages, 3000); // Refresh every 3 seconds

        // Refresh button handler
        const refreshBtn = document.getElementById('refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function(e) {
                e.preventDefault();
                // Instead of just refreshing messages, reload the whole page to refresh all content
                window.location.reload();
            });
        }

        // Handle message form submit via AJAX
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(messageForm);
            fetch('', { // Submit to same page
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageForm.reset(); // Clear the form
                    refreshMessages(); // Refresh immediately
                    showToast('Success', 'Message sent successfully.', 'success');
                } else {
                    showToast('Error', 'Failed to send message.', 'danger');
                }
            })

            .catch(error => {
                console.error('Error sending message:', error);
                showToast('Error', 'Failed to send message.', 'danger');
            });
    });
    }
}

// Toast for ticket submission success
const submitTicketForm = document.querySelector('form[method="POST"]');
if (submitTicketForm && window.location.pathname.includes('submit_ticket.php')) {
    submitTicketForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
        }
        fetch(form.action || window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
                form.reset();
                setTimeout(() => {
                    window.location.href = 'view_tickets.php?id=' + data.ticket_id;
                }, 2000);
            } else {
                showToast('Error', 'Failed to submit ticket.', 'danger');
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Ticket';
            }
        })
        .catch(() => {
            showToast('Error', 'Failed to submit ticket.', 'danger');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Submit Ticket';
            }
        });
    });
}

// Toast for ticket update success
const updateTicketForm = document.querySelector('form[method="POST"]');
if (updateTicketForm && window.location.pathname.includes('update_ticket.php')) {
    updateTicketForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const updateBtn = form.querySelector('button[type="submit"]');
        if (updateBtn && updateBtn.textContent.trim() === 'Update Ticket') {
            updateBtn.disabled = true;
            updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
            fetch(form.action || window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => {
                if (response.ok && response.url.includes('admin_dashboard.php')) {
                    showToast('Success', 'Ticket updated successfully.', 'success');
                    // Do not redirect, stay on page
                } else {
                    showToast('Error', 'Failed to update ticket.', 'danger');
                }
                if (updateBtn) {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = 'Update Ticket';
                }
            })
            .catch(() => {
                showToast('Error', 'Failed to update ticket.', 'danger');
                if (updateBtn) {
                    updateBtn.disabled = false;
                    updateBtn.innerHTML = 'Update Ticket';
                }
            });
        }
    });
}

// Function to show Bootstrap toast
function showToast(title, message, type) {
    if (!type) type = 'info';
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.position = 'fixed';
        toastContainer.style.top = '1rem';
        toastContainer.style.right = '1rem';
        toastContainer.style.zIndex = '1080';
        toastContainer.style.maxWidth = '400px';
        document.body.appendChild(toastContainer);
    }

    const toastId = 'toast' + Date.now();
    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center text-bg-' + type + ' border-0 shadow-lg';
    toastEl.id = toastId;
    toastEl.setAttribute('role', 'alert');
    toastEl.setAttribute('aria-live', 'assertive');
    toastEl.setAttribute('aria-atomic', 'true');
    toastEl.style.minWidth = '350px';
    toastEl.style.borderRadius = '8px';

    toastEl.innerHTML = '<div class="d-flex"><div class="toast-body"><strong>' + title + ':</strong> ' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';

    toastContainer.appendChild(toastEl);

    const toast = new bootstrap.Toast(toastEl, { delay: 7000 });
    toast.show();

    toastEl.addEventListener('hidden.bs.toast', () => {
        toastEl.remove();
    });
}

// Event delegation for mark-read-link clicks
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('mark-read-link')) {
        e.preventDefault();
        e.stopPropagation();
        const notifId = e.target.getAttribute('data-notif-id');
        const href = e.target.getAttribute('href');
        if (href === '#') return; // No ticket, do nothing
        // Send AJAX to mark as read
        fetch('notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'mark_read_id=' + encodeURIComponent(notifId)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // For notifications page, update UI
                const item = e.target.closest('.list-group-item');
                if (item && item.classList.contains('list-group-item-warning')) {
                    item.classList.remove('list-group-item-warning');
                }
                // Redirect to the ticket page
                window.location.href = href;
            } else {
                showToast('Error', 'Failed to mark as read.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error', 'Failed to mark as read.', 'danger');
        });
    }
});

// Mark notification as read when clicking unread notification item (for notifications.php)
document.addEventListener('DOMContentLoaded', function() {
    const unreadItems = document.querySelectorAll('.list-group-item.list-group-item-warning');
    unreadItems.forEach(item => {
        const link = item.querySelector('.mark-read-link');
        if (link) {
            item.style.cursor = 'pointer';
            item.addEventListener('click', function(e) {
                // Trigger the link click
                link.click();
            });
        }
    });
});

// File upload display functionality for profile picture
document.addEventListener('DOMContentLoaded', function() {
    const profilePictureInput = document.getElementById('profile_picture');
    const fileNameDisplay = document.getElementById('file-name');

    if (profilePictureInput && fileNameDisplay) {
        profilePictureInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileNameDisplay.textContent = this.files[0].name;
            } else {
                fileNameDisplay.textContent = '';
            }
        });
    }

    // Handle update profile modal form submission
    const updateProfileForm = document.querySelector('#updateProfileModal form');
    if (updateProfileForm) {
        updateProfileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const modal = bootstrap.Modal.getInstance(document.getElementById('updateProfileModal'));

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';

            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(data => {
                // Check if success by looking for success message in response
                if (data.includes('Profile updated successfully.')) {
                    modal.hide();
                    showToast('Success', 'Profile updated successfully.', 'success');
                    // Refresh the displayed profile info
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    // Extract error messages
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const errorList = doc.querySelector('.alert-danger ul');
                    if (errorList) {
                        const errors = Array.from(errorList.querySelectorAll('li')).map(li => li.textContent).join(' ');
                        showToast('Error', errors, 'danger');
                    } else {
                        showToast('Error', 'Failed to update profile.', 'danger');
                    }
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="form-icon type-update"></span>Update Profile';
            })
            .catch(() => {
                showToast('Error', 'Failed to update profile.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="form-icon type-update"></span>Update Profile';
            });
        });
    }

    // Handle change password modal form submission
    const changePasswordForm = document.querySelector('#changePasswordModal form');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const modal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Changing...';

            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(form)
            })
            .then(response => response.text())
            .then(data => {
                // Check if success by looking for success message in response
                if (data.includes('Password changed successfully.')) {
                    modal.hide();
                    showToast('Success', 'Password changed successfully.', 'success');
                    // Clear the form
                    form.reset();
                } else {
                    // Extract error messages
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const errorList = doc.querySelector('.alert-danger ul');
                    if (errorList) {
                        const errors = Array.from(errorList.querySelectorAll('li')).map(li => li.textContent).join(' ');
                        showToast('Error', errors, 'danger');
                    } else {
                        showToast('Error', 'Failed to change password.', 'danger');
                    }
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="form-icon type-update"></span>Change Password';
            })
            .catch(() => {
                showToast('Error', 'Failed to change password.', 'danger');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="form-icon type-update"></span>Change Password';
            });
        });
    }

    // Handle create user modal form submission
    const createUserForm = document.querySelector('#createUserModal form');
    if (createUserForm) {
        let isSubmitting = false; // Flag to prevent multiple submissions

        // Real-time password matching validation
        const passwordInput = createUserForm.querySelector('input[name="new_password"]');
        const confirmPasswordInput = createUserForm.querySelector('input[name="new_confirm_password"]');
        const confirmPasswordGroup = confirmPasswordInput.closest('.mb-3');

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const existingError = confirmPasswordGroup.querySelector('.password-error');

            if (existingError) {
                existingError.remove();
            }

            if (confirmPassword && password !== confirmPassword) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'password-error text-danger small mt-1';
                errorDiv.textContent = 'Passwords do not match.';
                confirmPasswordGroup.appendChild(errorDiv);
            }
        }

        confirmPasswordInput.addEventListener('input', checkPasswordMatch);
        passwordInput.addEventListener('input', checkPasswordMatch);

        createUserForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission for AJAX
            if (isSubmitting) return; // Prevent multiple submissions

            // Client-side validation: Check if passwords match
            const password = this.querySelector('input[name="new_password"]').value;
            const confirmPassword = this.querySelector('input[name="new_confirm_password"]').value;
            if (password !== confirmPassword) {
                showToast('Error', 'Passwords do not match.', 'danger');
                return;
            }

            isSubmitting = true;

            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const modal = bootstrap.Modal.getInstance(document.getElementById('createUserModal'));

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...';

            // Submit the form directly without CSRF token handling
            const formData = new FormData(form);
            formData.append('ajax', '1');
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData,
                credentials: 'same-origin'
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Server error: ' + response.status + ' ' + response.statusText);
                    }
                    return response.json();
                })
            .then(data => {
                if (data.success) {
                    modal.hide();
                    showToast('Success', 'User created successfully.', 'success');
                    // Refresh the page to show the new user
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error', data.error, 'danger');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="notification-icon type-new_ticket"></span>Create User';
                isSubmitting = false; // Reset flag
                // Refresh CSRF token after submission
                fetch(window.location.href, { method: 'GET' })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newToken = doc.querySelector('input[name="csrf_token"]');
                        if (newToken) {
                            form.querySelector('input[name="csrf_token"]').value = newToken.value;
                        }
                    })
                    .catch(() => {
                        // If token refresh fails, reload the page
                        window.location.reload();
                    });
            })
            .catch((error) => {
                console.error('Error creating user:', error);
                if (error.message.includes('403')) {
                    showToast('Error', 'Session expired or access denied. Please refresh the page and try again.', 'danger');
                } else {
                    showToast('Error', 'Failed to create user.', 'danger');
                }
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span class="notification-icon type-new_ticket"></span>Create User';
                isSubmitting = false; // Reset flag on error
                // Refresh CSRF token after submission even on error
                fetch(window.location.href, { method: 'GET' })
                    .then(response => response.text())
                    .then(html => {
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newToken = doc.querySelector('input[name="csrf_token"]');
                        if (newToken) {
                            form.querySelector('input[name="csrf_token"]').value = newToken.value;
                        }
                    })
                    .catch(() => {
                        // If token refresh fails, reload the page
                        window.location.reload();
                    });
            });
        });
    }

    // Handle delete user button clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-user-btn')) {
            e.preventDefault();
            const userId = e.target.getAttribute('data-user-id');
            const username = e.target.getAttribute('data-username');

            if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
                const btn = e.target;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';

                const formData = new FormData();
                formData.append('delete_user', '1');
                formData.append('user_id', userId);
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    // Check if success by looking for success message in response
                    if (data.includes('User deleted successfully.')) {
                        showToast('Success', 'User deleted successfully.', 'success');
                        // Remove the user row from the table
                        const row = btn.closest('tr');
                        if (row) {
                            row.remove();
                        }
                    } else {
                        // Extract error messages
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(data, 'text/html');
                        const errorList = doc.querySelector('.alert-danger ul');
                        if (errorList) {
                            const errors = Array.from(errorList.querySelectorAll('li')).map(li => li.textContent).join(' ');
                            showToast('Error', errors, 'danger');
                        } else {
                            showToast('Error', 'Failed to delete user.', 'danger');
                        }
                        btn.disabled = false;
                        btn.innerHTML = 'Delete';
                    }
                })
                .catch(() => {
                    showToast('Error', 'Failed to delete user.', 'danger');
                    btn.disabled = false;
                    btn.innerHTML = 'Delete';
                });
            }
        }
    });
});
