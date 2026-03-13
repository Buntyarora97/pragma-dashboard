/**
 * Unified Email Dashboard - JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const sidebar = document.getElementById('sidebar');
    const content = document.getElementById('content');
    
    if (sidebarCollapse) {
        sidebarCollapse.addEventListener('click', function() {
            sidebar.classList.toggle('active');
            content.classList.toggle('active');
        });
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Are you sure you want to proceed?')) {
                e.preventDefault();
            }
        });
    });
    
    // Email row click to read
    const emailRows = document.querySelectorAll('.email-table tbody tr');
    emailRows.forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Don't navigate if clicking on checkbox or action buttons
            if (e.target.type === 'checkbox' || e.target.closest('a') || e.target.closest('button')) {
                return;
            }
            
            const link = row.querySelector('a[href*="/email/read.php"]');
            if (link) {
                window.location.href = link.href;
            }
        });
    });
    
    // Select all checkbox
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.email-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // Bulk action confirmation
    const bulkActionSelect = document.querySelector('select[name="bulk_action"]');
    if (bulkActionSelect) {
        bulkActionSelect.addEventListener('change', function() {
            const selectedEmails = document.querySelectorAll('.email-checkbox:checked');
            
            if (this.value && selectedEmails.length === 0) {
                alert('Please select at least one email.');
                this.value = '';
                return;
            }
            
            if (this.value === 'delete') {
                if (!confirm('Are you sure you want to delete the selected emails?')) {
                    this.value = '';
                    return;
                }
            }
            
            if (this.value) {
                document.getElementById('bulkActionForm').submit();
            }
        });
    }
    
    // File input display filename
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const files = Array.from(this.files);
            const fileNames = files.map(f => f.name).join(', ');
            
            // Create or update file list display
            let fileList = this.parentElement.querySelector('.file-list');
            if (!fileList) {
                fileList = document.createElement('div');
                fileList.className = 'file-list mt-2';
                this.parentElement.appendChild(fileList);
            }
            
            if (files.length > 0) {
                fileList.innerHTML = '<small class="text-muted"><i class="bi bi-paperclip me-1"></i>' + 
                    files.length + ' file(s) selected: ' + fileNames + '</small>';
            } else {
                fileList.innerHTML = '';
            }
        });
    });
    
    // Provider settings auto-fill
    const providerSelect = document.getElementById('provider');
    if (providerSelect) {
        providerSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            const settings = selected.getAttribute('data-settings');
            
            if (settings) {
                const config = JSON.parse(settings);
                
                if (config.imap_host) {
                    document.getElementById('imap_host').value = config.imap_host;
                    document.getElementById('imap_port').value = config.imap_port;
                    document.getElementById('smtp_host').value = config.smtp_host;
                    document.getElementById('smtp_port').value = config.smtp_port;
                    document.getElementById('encryption').value = config.encryption;
                }
            }
        });
    }
    
    // Search debounce
    const searchInputs = document.querySelectorAll('input[name="search"]');
    searchInputs.forEach(function(input) {
        let timeout;
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                // Optional: Auto-submit search form after delay
                // input.closest('form').submit();
            }, 500);
        });
    });
    
    // Tooltip initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popover initialization
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

/**
 * Show loading spinner
 */
function showLoading(element, message = 'Loading...') {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        element.innerHTML = '<div class="text-center py-4"><div class="spinner mb-2"></div><p class="text-muted">' + message + '</p></div>';
    }
}

/**
 * Hide loading spinner
 */
function hideLoading(element, content) {
    if (typeof element === 'string') {
        element = document.querySelector(element);
    }
    
    if (element) {
        element.innerHTML = content;
    }
}

/**
 * AJAX request helper
 */
function ajaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    if (method === 'POST' && data) {
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    }
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                if (callback) {
                    callback(null, xhr.responseText);
                }
            } else {
                if (callback) {
                    callback(new Error('Request failed: ' + xhr.statusText), null);
                }
            }
        }
    };
    
    xhr.send(data);
}

/**
 * Format date
 */
function formatDate(dateString, format = 'MMM d, yyyy h:mm a') {
    const date = new Date(dateString);
    
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    
    const replacements = {
        'yyyy': date.getFullYear(),
        'yy': String(date.getFullYear()).slice(-2),
        'MMM': months[date.getMonth()],
        'MM': String(date.getMonth() + 1).padStart(2, '0'),
        'M': date.getMonth() + 1,
        'dd': String(date.getDate()).padStart(2, '0'),
        'd': date.getDate(),
        'hh': String(date.getHours()).padStart(2, '0'),
        'h': date.getHours(),
        'mm': String(date.getMinutes()).padStart(2, '0'),
        'm': date.getMinutes(),
        'a': date.getHours() >= 12 ? 'PM' : 'AM'
    };
    
    return format.replace(/yyyy|yy|MMM|MM|M|dd|d|hh|h|mm|m|a/g, match => replacements[match]);
}

/**
 * Truncate text
 */
function truncateText(text, length = 100) {
    if (!text || text.length <= length) return text;
    return text.substring(0, length) + '...';
}

/**
 * Copy to clipboard
 */
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function() {
            showToast('Copied to clipboard!');
        });
    } else {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('Copied to clipboard!');
    }
}

/**
 * Show toast notification
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = 'alert alert-' + type + ' alert-dismissible fade show position-fixed';
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 250px;';
    toast.innerHTML = message + '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    
    document.body.appendChild(toast);
    
    setTimeout(function() {
        const bsToast = new bootstrap.Alert(toast);
        bsToast.close();
    }, 3000);
}

/**
 * Confirm action
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Toggle fullscreen
 */
function toggleFullscreen(element) {
    if (!document.fullscreenElement) {
        element.requestFullscreen().catch(err => {
            console.error('Error attempting to enable fullscreen:', err);
        });
    } else {
        document.exitFullscreen();
    }
}
