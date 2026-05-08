/**
 * QR Code Countdown Timer Component
 * Displays a countdown timer for order (3 hours) and redemption (30 minutes) QR codes
 * 
 * Usage:
 * QRTimer.init({
 *   type: 'order' or 'reward',
 *   createdAt: '2024-01-01 12:00:00',
 *   timerElement: '#timer-text',
 *   badgeElement: '#qr-timer',
 *   onExpire: function() { console.log('QR Expired!'); }
 * });
 */

const QRTimer = (function() {
    'use strict';
    
    /**
     * Initialize a countdown timer
     * @param {Object} options Configuration options
     */
    function init(options) {
        const config = {
            type: options.type || 'order',
            createdAt: new Date(options.createdAt),
            timerElement: options.timerElement || '#timer-text',
            badgeElement: options.badgeElement || '#qr-timer',
            onExpire: options.onExpire || null,
            expiresAt: options.expiresAt || null
        };
        
        // Calculate expiry time
        const validityMinutes = config.type === 'order' ? 180 : 30; // 3 hours or 30 minutes
        const expiresAt = config.expiresAt 
            ? new Date(config.expiresAt) 
            : new Date(config.createdAt.getTime() + (validityMinutes * 60 * 1000));
        
        const timerEl = document.querySelector(config.timerElement);
        const badgeEl = document.querySelector(config.badgeElement);
        
        if (!timerEl) {
            console.error('QRTimer: Timer element not found');
            return;
        }
        
        let intervalId = null;
        
        function updateTimer() {
            const now = new Date();
            const remaining = expiresAt - now;
            
            if (remaining <= 0) {
                // Timer expired
                timerEl.textContent = 'QR Code Expired';
                
                if (badgeEl) {
                    badgeEl.style.backgroundColor = '#dc3545';
                    badgeEl.style.color = 'white';
                    badgeEl.classList.remove('bg-success', 'bg-info', 'bg-warning');
                    badgeEl.classList.add('bg-danger');
                }
                
                if (config.onExpire && typeof config.onExpire === 'function') {
                    config.onExpire();
                }
                
                if (intervalId) {
                    clearInterval(intervalId);
                }
                return;
            }
            
            // Calculate time components
            const hours = Math.floor(remaining / (1000 * 60 * 60));
            const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((remaining % (1000 * 60)) / 1000);
            
            // Format display
            let timeStr = '';
            if (hours > 0) {
                timeStr = hours + 'h ' + 
                         (minutes < 10 ? '0' : '') + minutes + 'm ' + 
                         (seconds < 10 ? '0' : '') + seconds + 's';
            } else {
                timeStr = (minutes < 10 ? '0' : '') + minutes + ':' + 
                         (seconds < 10 ? '0' : '') + seconds;
            }
            
            timerEl.textContent = 'Expires in ' + timeStr;
            
            // Update badge color based on remaining time
            if (badgeEl) {
                const totalTime = validityMinutes * 60 * 1000;
                const halfTime = totalTime / 2;
                const quarterTime = totalTime / 4;
                
                // Remove all color classes first
                badgeEl.classList.remove('bg-success', 'bg-info', 'bg-warning', 'bg-danger');
                
                if (remaining < quarterTime) {
                    // Less than 25% remaining - Red (urgent)
                    badgeEl.style.backgroundColor = '#dc3545';
                    badgeEl.style.color = 'white';
                    badgeEl.classList.add('bg-danger');
                } else if (remaining < halfTime) {
                    // Less than 50% remaining - Yellow (warning)
                    badgeEl.style.backgroundColor = '#ffc107';
                    badgeEl.style.color = '#000';
                    badgeEl.classList.add('bg-warning');
                } else {
                    // More than 50% remaining - Green (good)
                    badgeEl.style.backgroundColor = '#28a745';
                    badgeEl.style.color = 'white';
                    badgeEl.classList.add('bg-success');
                }
            }
        }
        
        // Initial update
        updateTimer();
        
        // Update every second
        intervalId = setInterval(updateTimer, 1000);
        
        // Return control object
        return {
            stop: function() {
                if (intervalId) {
                    clearInterval(intervalId);
                }
            },
            getRemaining: function() {
                return Math.max(0, expiresAt - new Date());
            }
        };
    }
    
    /**
     * Format milliseconds to human readable time
     * @param {number} ms Milliseconds
     * @return {string} Formatted time string
     */
    function formatTime(ms) {
        if (ms <= 0) return 'Expired';
        
        const hours = Math.floor(ms / (1000 * 60 * 60));
        const minutes = Math.floor((ms % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((ms % (1000 * 60)) / 1000);
        
        if (hours > 0) {
            return hours + 'h ' + 
                   (minutes < 10 ? '0' : '') + minutes + 'm ' + 
                   (seconds < 10 ? '0' : '') + seconds + 's';
        } else {
            return (minutes < 10 ? '0' : '') + minutes + ':' + 
                   (seconds < 10 ? '0' : '') + seconds;
        }
    }
    
    // Public API
    return {
        init: init,
        formatTime: formatTime
    };
})();

// Export for module systems if available
if (typeof module !== 'undefined' && module.exports) {
    module.exports = QRTimer;
}
