/**
 * Global Notification System
 * 
 * This module creates a global notification system that works across all pages.
 * It initializes as soon as the page loads and remains active throughout the user session.
 */

// Initialize global notification system when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Global notification system initializing...');
    
    // Initialize the global notification system
    initGlobalNotificationSystem();
    
    // Start polling for notifications if user is a clinic admin or doctor
    if (isClinicUser() || isDoctorUser()) {
        startGlobalNotificationPolling();
    }
});

/**
 * Initialize the global notification system
 */
function initGlobalNotificationSystem() {
    // Store a reference to notification containers
    window.globalNotificationSystem = {
        initialized: true,
        containers: {
            notifications: document.getElementById('global-notification-container'),
            popups: document.getElementById('global-popup-notification-container')
        },
        soundElement: document.getElementById('global-notification-sound'),
        settings: {
            soundEnabled: localStorage.getItem('notification_sound_enabled') !== 'false',
            checkInterval: 10000, // 10 seconds by default
            lastCheckedTime: localStorage.getItem('lastNotificationCheck') || new Date(0).toISOString()
        },
        notificationCount: 0
    };
    
    console.log('Global notification system initialized');
    
    // Listen for custom events to show notifications
    document.addEventListener('showNotification', function(e) {
        if (e.detail && e.detail.notification) {
            showGlobalNotification(e.detail.notification);
        }
    });
}

/**
 * Check if current user is a clinic user
 */
function isClinicUser() {
    // This function checks if the current user is a clinic admin
    // We can assume if the global notification container exists, the user is a clinic admin
    return document.getElementById('global-notification-container') !== null;
}

/**
 * Check if current user is a doctor user
 */
function isDoctorUser() {
    // Check if current page is under doctor routes or has doctor-specific elements
    const currentPath = window.location.pathname;
    return currentPath.startsWith('/doctor') || 
           document.body.classList.contains('doctor-layout') ||
           document.querySelector('[data-role="doctor"]') !== null;
}

/**
 * Start polling for new notifications
 */
function startGlobalNotificationPolling() {
    // Set up the polling interval
    const pollInterval = setInterval(pollForNewNotifications, 
        window.globalNotificationSystem.settings.checkInterval);
    
    // Store the interval reference for potential cleanup
    window.globalNotificationSystem.pollInterval = pollInterval;
    
    // Do an immediate check
    pollForNewNotifications();
    
    console.log('Global notification polling started');
}

/**
 * Poll server for new notifications
 */
function pollForNewNotifications() {
    if (!isClinicUser() && !isDoctorUser()) return;
    
    const lastCheckedTime = window.globalNotificationSystem.settings.lastCheckedTime;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Determine the correct endpoint based on user role
    let endpoint = '/check-new-notifications';
    if (isDoctorUser()) {
        endpoint = '/doctor/notifications/check';
    }
    
    fetch(endpoint + '?last_check=' + encodeURIComponent(lastCheckedTime), {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.notifications && data.notifications.length > 0) {
            // Process new notifications
            processNewNotifications(data.notifications);
            
            // Update last checked time
            window.globalNotificationSystem.settings.lastCheckedTime = data.last_check || data.now;
            localStorage.setItem('lastNotificationCheck', data.last_check || data.now);
        }
    })
    .catch(error => {
        console.error('Error checking for new notifications:', error);
    });
}

/**
 * Process new notifications received from server
 */
function processNewNotifications(notifications) {
    if (!Array.isArray(notifications) || notifications.length === 0) return;
    
    // Sort notifications by creation time (newest first)
    notifications.sort((a, b) => {
        return new Date(b.created_at) - new Date(a.created_at);
    });
    
    // Play notification sound once for batch of notifications
    playGlobalNotificationSound();
    
    // Display notifications
    notifications.forEach(notification => {
        showGlobalNotification(notification);
    });
    
    // Update notification count in badge or UI if needed
    updateNotificationCount(notifications.length);
}

/**
 * Display a global notification
 */
function showGlobalNotification(notification) {
    if (!notification) return;
    
    // Create notification element
    const notificationElement = createNotificationElement(notification);
    
    // Add to global container
    if (window.globalNotificationSystem.containers.notifications) {
        window.globalNotificationSystem.containers.notifications.appendChild(notificationElement);
    }
    
    // Don't show popup at bottom, only show at top
    // Removed: showGlobalPopupNotification(notification);
    
    // Auto-remove after delay
    setTimeout(() => {
        if (notificationElement.parentNode) {
            notificationElement.classList.add('opacity-0');
            setTimeout(() => notificationElement.remove(), 300);
        }
    }, 5000);
}

/**
 * Create a notification element
 */
function createNotificationElement(notification) {
    const div = document.createElement('div');
    div.className = 'bg-white border-l-4 border-blue-500 p-4 mb-2 shadow-md rounded transition-opacity duration-300 opacity-100 pointer-events-auto';
    div.dataset.id = notification.id;
    
    // Format creation date
    const createdAt = new Date(notification.created_at);
    const formattedDate = createdAt.toLocaleString();
    
    // Parse notification data
    const data = notification.data || {};
    
    div.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="text-sm font-medium text-blue-600">
                    ${data.title || 'Notification'}
                </div>
            </div>
            <div class="ml-2">
                <button class="mark-read-btn text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="mt-1 text-sm text-gray-900">
            ${data.body || ''}
        </div>
        <div class="mt-2 text-xs text-gray-500">
            ${formattedDate}
        </div>
    `;
    
    // Add event listener for mark as read button
    const markReadBtn = div.querySelector('.mark-read-btn');
    if (markReadBtn) {
        markReadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            markNotificationAsRead(notification.id, div);
        });
    }
    
    // Add click event for the notification itself
    div.addEventListener('click', function() {
        handleNotificationClick(notification);
    });
    
    return div;
}

/**
 * Show a popup notification
 */
function showGlobalPopupNotification(notification) {
    if (!notification) return;
    
    // Parse notification data
    const data = notification.data || {};
    
    // Create popup element
    const popup = document.createElement('div');
    popup.className = 'fixed bottom-4 right-4 bg-white border-l-4 border-blue-500 p-4 shadow-lg rounded-lg max-w-sm transform transition-transform duration-300 pointer-events-auto';
    popup.style.transform = 'translateX(110%)';
    
    popup.innerHTML = `
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="text-sm font-bold text-blue-600">
                    ${data.title || 'New Notification'}
                </div>
            </div>
            <div class="ml-2">
                <button class="close-popup-btn text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="mt-1 text-sm text-gray-900">
            ${data.body || ''}
        </div>
    `;
    
    // Add to popup container
    if (window.globalNotificationSystem.containers.popups) {
        window.globalNotificationSystem.containers.popups.appendChild(popup);
    }
    
    // Animate in
    setTimeout(() => {
        popup.style.transform = 'translateX(0)';
    }, 10);
    
    // Add event listener for close button
    const closeBtn = popup.querySelector('.close-popup-btn');
    if (closeBtn) {
        closeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Animate out
            popup.style.transform = 'translateX(110%)';
            setTimeout(() => {
                if (popup.parentNode) {
                    popup.remove();
                }
            }, 300);
        });
    }
    
    // Add click event for the popup itself
    popup.addEventListener('click', function() {
        handleNotificationClick(notification);
    });
    
    // Auto-remove after delay
    setTimeout(() => {
        if (popup.parentNode) {
            popup.style.transform = 'translateX(110%)';
            setTimeout(() => {
                if (popup.parentNode) {
                    popup.remove();
                }
            }, 300);
        }
    }, 8000);
}

/**
 * Play notification sound
 */
function playGlobalNotificationSound() {
    // Check if sound is enabled in settings
    if (!window.globalNotificationSystem.settings.soundEnabled) return;
    
    try {
        console.log('Playing global notification sound');
        
        // Try to play the sound using the global element
        if (window.globalNotificationSystem.soundElement) {
            // Reset to beginning in case it's already playing
            window.globalNotificationSystem.soundElement.currentTime = 0;
            
            // Play the sound with error handling
            const playPromise = window.globalNotificationSystem.soundElement.play();
            
            // Handle promise rejection (modern browsers requirement)
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.error('Error playing global notification sound:', error);
                    
                    // Try to play on user interaction if autoplay is blocked
                    document.addEventListener('click', function playOnUserAction() {
                        window.globalNotificationSystem.soundElement.play()
                            .catch(e => console.error('Still failed to play sound:', e));
                        document.removeEventListener('click', playOnUserAction);
                    }, { once: true });
                });
            }
        } else {
            // Fallback - create a new audio instance
            const audio = new Audio('/sounds/noti.mp3');
            audio.play().catch(e => console.error('Could not play fallback notification sound:', e));
        }
    } catch (e) {
        console.error('Error in playGlobalNotificationSound:', e);
    }
}

/**
 * Mark a notification as read
 */
function markNotificationAsRead(notificationId, element) {
    if (!notificationId) return;
    
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    fetch(`/notifications/${notificationId}/read`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove notification element if present
            if (element && element.parentNode) {
                element.classList.add('opacity-0');
                setTimeout(() => element.remove(), 300);
            }
            
            // Update notification count
            updateNotificationCount(-1);
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
    });
}

/**
 * Handle notification click
 */
function handleNotificationClick(notification) {
    // Mark as read first
    markNotificationAsRead(notification.id);
    
    // Parse notification data
    const data = notification.data || {};
    
    // Determine if we need to navigate based on notification type
    if (notification.type === 'clinic_new_appointment' && data.appointment_id) {
        // Navigate to appointment details page
        window.location.href = `/clinic/appointments/${data.appointment_id}`;
    } else if (notification.type === 'clinic_appointment_completed' && data.appointment_id) {
        // Navigate to appointment details page
        window.location.href = `/clinic/appointments/${data.appointment_id}`;
    }
    // Add more navigation logic based on notification types
}

/**
 * Update notification count
 */
function updateNotificationCount(change) {
    // Update the internal count
    if (typeof change === 'number') {
        window.globalNotificationSystem.notificationCount += change;
    }
    
    // Make sure it doesn't go below zero
    if (window.globalNotificationSystem.notificationCount < 0) {
        window.globalNotificationSystem.notificationCount = 0;
    }
    
    // Update any UI elements showing the count
    const countElements = document.querySelectorAll('.notification-count');
    countElements.forEach(element => {
        element.textContent = window.globalNotificationSystem.notificationCount;
        
        // Toggle visibility based on count
        if (window.globalNotificationSystem.notificationCount > 0) {
            element.classList.remove('hidden');
        } else {
            element.classList.add('hidden');
        }
    });
    
    // Update page title or favicon if needed
    updatePageTitleWithNotificationCount();
}

/**
 * Update page title to show notification count
 */
function updatePageTitleWithNotificationCount() {
    const count = window.globalNotificationSystem.notificationCount;
    const originalTitle = document.title.replace(/^\(\d+\)\s/, '');
    
    if (count > 0) {
        document.title = `(${count}) ${originalTitle}`;
    } else {
        document.title = originalTitle;
    }
}