</main>
            </div>
        </div>

        <!-- JavaScript Libraries -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

        <!-- Custom JavaScript -->
        <script>
            // Global JavaScript functions and event handlers
            
            // Show toast notification
            function showToast(title, message, type = 'info') {
                const toast = document.getElementById('toast');
                const toastTitle = document.getElementById('toast-title');
                const toastBody = document.getElementById('toast-body');
                
                // Set content
                toastTitle.textContent = title;
                toastBody.textContent = message;
                
                // Set icon based on type
                const iconMap = {
                    success: 'bi-check-circle-fill text-success',
                    error: 'bi-x-circle-fill text-danger',
                    warning: 'bi-exclamation-triangle-fill text-warning',
                    info: 'bi-info-circle-fill text-primary'
                };
                
                const icon = toast.querySelector('.toast-header i');
                icon.className = `${iconMap[type] || iconMap.info} me-2`;
                
                // Show toast
                const bsToast = new bootstrap.Toast(toast);
                bsToast.show();
            }
            
            // Load notifications
            function loadNotifications() {
                <?php if (isLoggedIn()): ?>
                fetch('/api/notifications.php')
                    .then(response => response.json())
                    .then(data => {
                        const count = document.getElementById('notification-count');
                        const dropdown = document.getElementById('notifications-dropdown');
                        
                        if (data.unread_count > 0) {
                            count.textContent = data.unread_count;
                            count.style.display = 'inline';
                        } else {
                            count.style.display = 'none';
                        }
                        
                        dropdown.innerHTML = '';
                        if (data.notifications.length > 0) {
                            data.notifications.forEach(notification => {
                                const item = document.createElement('li');
                                item.innerHTML = `
                                    <a class="dropdown-item ${notification.is_read ? '' : 'fw-bold'}" href="#" 
                                       onclick="markNotificationRead(${notification.id})">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">${notification.title}</h6>
                                                <p class="mb-1 text-muted small">${notification.message}</p>
                                                <small class="text-muted">${new Date(notification.created_at).toLocaleString()}</small>
                                            </div>
                                        </div>
                                    </a>
                                `;
                                dropdown.appendChild(item);
                            });
                            
                            // Add view all link
                            const viewAll = document.createElement('li');
                            viewAll.innerHTML = '<hr class="dropdown-divider"><a class="dropdown-item text-center" href="/pages/notifications.php">View All Notifications</a>';
                            dropdown.appendChild(viewAll);
                        } else {
                            dropdown.innerHTML = '<li><a class="dropdown-item">No notifications</a></li>';
                        }
                    })
                    .catch(error => console.error('Error loading notifications:', error));
                <?php endif; ?>
            }
            
            // Mark notification as read
            function markNotificationRead(notificationId) {
                fetch('/api/notifications.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'mark_read',
                        notification_id: notificationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                    }
                })
                .catch(error => console.error('Error marking notification as read:', error));
            }
            
            // Initialize DataTables with default settings
            function initDataTable(selector, options = {}) {
                const defaultOptions = {
                    responsive: true,
                    pageLength: <?php echo RECORDS_PER_PAGE; ?>,
                    language: {
                        search: "Search:",
                        lengthMenu: "Show _MENU_ entries",
                        info: "Showing _START_ to _END_ of _TOTAL_ entries",
                        paginate: {
                            first: "First",
                            last: "Last",
                            next: "Next",
                            previous: "Previous"
                        }
                    },
                    dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                    ...options
                };
                
                return $(selector).DataTable(defaultOptions);
            }
            
            // Confirm deletion
            function confirmDelete(message = 'Are you sure you want to delete this item?') {
                return confirm(message);
            }
            
            // Format currency
            function formatCurrency(amount) {
                return 'Rs. ' + parseFloat(amount).toLocaleString('en-LK', {minimumFractionDigits: 2});
            }
            
            // Format date
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-GB');
            }
            
            // Print QR Code
            function printQRCode(qrCodeUrl, itemName) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>QR Code - ${itemName}</title>
                            <style>
                                body { 
                                    font-family: Arial, sans-serif; 
                                    text-align: center; 
                                    margin: 20px; 
                                }
                                .qr-container {
                                    border: 2px solid #000;
                                    padding: 20px;
                                    display: inline-block;
                                    margin: 20px;
                                }
                                .item-name {
                                    font-weight: bold;
                                    margin-bottom: 10px;
                                    font-size: 16px;
                                }
                                .qr-code {
                                    margin: 20px 0;
                                }
                                .footer {
                                    font-size: 12px;
                                    color: #666;
                                    margin-top: 10px;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="qr-container">
                                <div class="item-name">${itemName}</div>
                                <div class="qr-code">
                                    <img src="${qrCodeUrl}" alt="QR Code" style="max-width: 200px;">
                                </div>
                                <div class="footer">NBTS Inventory System</div>
                            </div>
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
            
            // Auto-refresh notifications every 5 minutes
            setInterval(loadNotifications, 300000);
            
            // Load notifications on page load
            document.addEventListener('DOMContentLoaded', function() {
                loadNotifications();
                
                // Auto-fade in elements with fade-in class
                document.querySelectorAll('.fade-in').forEach(el => {
                    el.style.opacity = '0';
                    setTimeout(() => {
                        el.style.transition = 'opacity 0.5s ease-in-out';
                        el.style.opacity = '1';
                    }, 100);
                });
                
                // Initialize tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
            
            // Show success/error messages from URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success')) {
                showToast('Success', urlParams.get('success'), 'success');
            }
            if (urlParams.get('error')) {
                showToast('Error', urlParams.get('error'), 'error');
            }
            if (urlParams.get('warning')) {
                showToast('Warning', urlParams.get('warning'), 'warning');
            }
            if (urlParams.get('info')) {
                showToast('Info', urlParams.get('info'), 'info');
            }
            
            // AJAX form submission helper
            function submitForm(formData, url, successCallback, errorCallback) {
                fetch(url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        if (successCallback) {
                            successCallback(data);
                        } else {
                            showToast('Success', data.message, 'success');
                        }
                    } else {
                        if (errorCallback) {
                            errorCallback(data);
                        } else {
                            showToast('Error', data.message, 'error');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error', 'An unexpected error occurred', 'error');
                });
            }
        </script>
        
        <?php if (isset($additional_js)): ?>
            <?php echo $additional_js; ?>
        <?php endif; ?>
        
    </body>
</html>