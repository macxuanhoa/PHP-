<?php
/**
 * Theme Manager - Unified theme management for Student Portal
 * This file handles all theme-related functionality including:
 * - Theme toggle button functionality
 * - Theme persistence in localStorage
 * - Initial theme application
 */

// Check if this is the first time loading (to prevent duplicate script execution)
if (!isset($GLOBALS['theme_manager_loaded'])) {
    $GLOBALS['theme_manager_loaded'] = true;
?>
<script>
// Unified Theme Manager for Student Portal
(function() {
    'use strict';
    
    // Prevent multiple initializations
    if (window.themeManagerInitialized) {
        return;
    }
    window.themeManagerInitialized = true;
    
    // Theme Manager Class
    class ThemeManager {
        constructor() {
            this.storageKey = 'student_portal_theme';
            this.themeBtn = null;
            this.body = document.body;
            this.isDark = false;
            
            this.init();
        }
        
        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.setup());
            } else {
                this.setup();
            }
        }
        
        setup() {
            // Get theme button
            this.themeBtn = document.getElementById('themeBtn');
            
            // Apply saved theme
            this.applySavedTheme();
            
            // Setup event listeners
            this.setupEventListeners();
        }
        
        applySavedTheme() {
            // Check for legacy theme keys and migrate them
            const legacyTheme = this.getLegacyTheme();
            if (legacyTheme) {
                this.isDark = legacyTheme === 'dark';
                // Save with new key
                localStorage.setItem(this.storageKey, this.isDark ? 'dark' : 'light');
                // Remove old keys
                localStorage.removeItem('dashboard_theme');
                localStorage.removeItem('student_theme');
            } else {
                // Get current theme
                const savedTheme = localStorage.getItem(this.storageKey) || 'light';
                this.isDark = savedTheme === 'dark';
            }
            
            // Apply theme to body
            this.updateTheme();
        }
        
        getLegacyTheme() {
            // Check for old theme keys and migrate
            const dashboardTheme = localStorage.getItem('dashboard_theme');
            const studentTheme = localStorage.getItem('student_theme');
            
            if (dashboardTheme) return dashboardTheme;
            if (studentTheme) return studentTheme;
            
            return null;
        }
        
        setupEventListeners() {
            if (this.themeBtn) {
                this.themeBtn.addEventListener('click', () => this.toggleTheme());
            }
        }
        
        toggleTheme() {
            this.isDark = !this.isDark;
            this.updateTheme();
            this.saveTheme();
            
            // Dispatch custom event for theme change
            window.dispatchEvent(new CustomEvent('themeChanged', {
                detail: { isDark: this.isDark }
            }));
        }
        
        updateTheme() {
            // Update body class
            if (this.isDark) {
                this.body.classList.add('dark-mode');
            } else {
                this.body.classList.remove('dark-mode');
            }
            
            // Update button state
            if (this.themeBtn) {
                if (this.isDark) {
                    this.themeBtn.classList.add('dark');
                } else {
                    this.themeBtn.classList.remove('dark');
                }
            }
        }
        
        saveTheme() {
            localStorage.setItem(this.storageKey, this.isDark ? 'dark' : 'light');
        }
        
        // Public methods
        getCurrentTheme() {
            return this.isDark ? 'dark' : 'light';
        }
        
        setTheme(theme) {
            this.isDark = theme === 'dark';
            this.updateTheme();
            this.saveTheme();
        }
    }
    
    // Initialize theme manager
    window.themeManager = new ThemeManager();
    
    // Make it globally accessible
    window.toggleTheme = () => {
        if (window.themeManager) {
            window.themeManager.toggleTheme();
        }
    };
    
    // Apply theme immediately (before DOM ready for no flash)
    const savedTheme = localStorage.getItem('student_portal_theme') || 
                       localStorage.getItem('dashboard_theme') || 
                       localStorage.getItem('student_theme') || 
                       'light';
    
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
    
})();
</script>
<?php
} // End of check for theme_manager_loaded
?>
