// AJAX Request Handler - Utility functions

function makeAjaxRequest(url, method, data, successCallback, errorCallback) {
    const options = {
        method: method,
        headers: {}
    };

    // Handle different data types
    if (data instanceof FormData) {
        options.body = data;
    } else if (method === 'POST' || method === 'PUT') {
        options.headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }

    fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (successCallback) {
                successCallback(data);
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            if (errorCallback) {
                errorCallback(error);
            }
        });
}

// Make a GET request
function ajaxGet(url, successCallback, errorCallback) {
    makeAjaxRequest(url, 'GET', null, successCallback, errorCallback);
}

// Make a POST request
function ajaxPost(url, data, successCallback, errorCallback) {
    makeAjaxRequest(url, 'POST', data, successCallback, errorCallback);
}

// Upload file with progress
function uploadFile(url, formData, progressCallback, successCallback, errorCallback) {
    const xhr = new XMLHttpRequest();

    // Upload progress
    xhr.upload.addEventListener('progress', function (e) {
        if (e.lengthComputable) {
            const percentage = Math.round((e.loaded / e.total) * 100);
            if (progressCallback) {
                progressCallback(percentage);
            }
        }
    });

    // Request complete
    xhr.addEventListener('load', function () {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (successCallback) {
                    successCallback(response);
                }
            } catch (error) {
                if (errorCallback) {
                    errorCallback(error);
                }
            }
        } else {
            if (errorCallback) {
                errorCallback(new Error(`HTTP error! status: ${xhr.status}`));
            }
        }
    });

    // Request error
    xhr.addEventListener('error', function () {
        if (errorCallback) {
            errorCallback(new Error('Network error'));
        }
    });

    xhr.open('POST', url);
    xhr.send(formData);
}

// Show loading spinner
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

// Hide loading spinner
function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        const spinner = element.querySelector('.loading-spinner');
        if (spinner) {
            spinner.remove();
        }
    }
}

// Show notification message
function showNotification(message, type = 'info', duration = 3000) {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close">&times;</button>
    `;

    // Add to document
    document.body.appendChild(notification);

    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.addEventListener('click', () => {
        hideNotification(notification);
    });

    // Auto-hide
    if (duration > 0) {
        setTimeout(() => {
            hideNotification(notification);
        }, duration);
    }
}

// Hide notification
function hideNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        notification.remove();
    }, 300);
}

// Get icon for notification type
function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || icons.info;
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        makeAjaxRequest,
        ajaxGet,
        ajaxPost,
        uploadFile,
        showLoading,
        hideLoading,
        showNotification,
        hideNotification,
        debounce
    };
}
