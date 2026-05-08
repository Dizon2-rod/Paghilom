/**
 * Prevent Back Navigation After Login
 * Prevents users from going back to login page after logging in
 */
(function() {
    'use strict';
    
    // Disable back button completely for logged-in users
    history.pushState(null, null, location.href);
    
    window.onpopstate = function() {
        history.go(1);
    };
    
    // Additional layer: prevent back via popstate
    window.addEventListener('popstate', function(event) {
        event.preventDefault();
        history.pushState(null, null, location.href);
        return false;
    });
    
    // Prevent cache on back button
    window.onpageshow = function(event) {
        if (event.persisted) {
            window.location.reload();
        }
    };
    
    // Disable keyboard shortcuts for back (Backspace, Alt+Left)
    document.addEventListener('keydown', function(e) {
        // Backspace key
        if (e.keyCode === 8) {
            var target = e.target || e.srcElement;
            if (target.tagName !== 'INPUT' && target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                return false;
            }
        }
        // Alt + Left Arrow (back)
        if (e.altKey && e.keyCode === 37) {
            e.preventDefault();
            return false;
        }
    });
})();
