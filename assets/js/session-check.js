/**
 * Session-Based Page Access Control
 * Ensures users stay on correct pages based on login status
 */
(function() {
    'use strict';
    
    var currentPath = window.location.pathname;
    var isLoginPage = currentPath.includes('login.php');
    var isDashboard = currentPath.includes('admin/dashboard.php') || 
                      currentPath.includes('owner/') || 
                      currentPath.includes('admin/');
    
    // Check if on login page - PHP should handle redirect, but this is JS failsafe
    if (isLoginPage) {
        // Force reload on back button to trigger PHP session check
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    }
    
    // If on dashboard, prevent ALL back navigation
    if (isDashboard) {
        // Method 1: Replace state immediately
        history.pushState(null, null, location.href);
        
        // Method 2: Block popstate
        window.onpopstate = function() {
            history.go(1);
        };
        
        // Method 3: Add multiple entries to make it harder to go back
        for (var i = 0; i < 3; i++) {
            history.pushState(null, null, location.href);
        }
        
        // Method 4: Listen and block
        window.addEventListener('popstate', function(event) {
            event.preventDefault();
            history.pushState(null, null, location.href);
            return false;
        });
        
        // Method 5: Reload on cached page
        window.onpageshow = function(event) {
            if (event.persisted) {
                window.location.reload();
            }
        };
    }
})();
