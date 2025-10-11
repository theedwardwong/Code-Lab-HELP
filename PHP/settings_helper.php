<?php
/**
 * Settings Helper Functions
 * Include this file in any page that needs to access system settings
 * Usage: include 'settings_helper.php';
 */

// Cache settings in memory to avoid multiple DB queries
$GLOBALS['system_settings_cache'] = null;

/**
 * Load all settings from database
 */
function loadSystemSettings() {
    global $conn;
    
    if ($GLOBALS['system_settings_cache'] !== null) {
        return $GLOBALS['system_settings_cache'];
    }
    
    $settings = [];
    
    $query = "SELECT setting_key, setting_value FROM system_settings";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
    
    $GLOBALS['system_settings_cache'] = $settings;
    return $settings;
}

/**
 * Get a specific setting value
 * 
 * @param string $key Setting key
 * @param mixed $default Default value if setting not found
 * @return mixed Setting value or default
 */
function getSystemSetting($key, $default = '') {
    $settings = loadSystemSettings();
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Check if a feature is enabled
 * 
 * @param string $feature Feature name (registration_enabled, notifications_enabled, etc.)
 * @return bool True if enabled
 */
function isFeatureEnabled($feature) {
    return getSystemSetting($feature, '0') === '1';
}

/**
 * Check if maintenance mode is active
 * 
 * @return bool True if in maintenance mode
 */
function isMaintenanceMode() {
    return isFeatureEnabled('maintenance_mode');
}

/**
 * Get site name
 * 
 * @return string Site name
 */
function getSiteName() {
    return getSystemSetting('site_name', 'Code Lab @ HELP');
}

/**
 * Get password minimum length
 * 
 * @return int Minimum password length
 */
function getPasswordMinLength() {
    return intval(getSystemSetting('password_min_length', 8));
}

/**
 * Get session timeout in seconds
 * 
 * @return int Session timeout
 */
function getSessionTimeout() {
    return intval(getSystemSetting('session_timeout', 1800));
}

/**
 * Get max login attempts
 * 
 * @return int Max login attempts
 */
function getMaxLoginAttempts() {
    return intval(getSystemSetting('max_login_attempts', 5));
}

/**
 * Get default exercise points
 * 
 * @return int Default points
 */
function getDefaultExercisePoints() {
    return intval(getSystemSetting('default_exercise_points', 10));
}

/**
 * Get default max attempts
 * 
 * @return int Default max attempts (0 = unlimited)
 */
function getDefaultMaxAttempts() {
    return intval(getSystemSetting('default_max_attempts', 0));
}

/**
 * Check if user can access the system (maintenance mode check)
 * 
 * @param string $user_role User's role
 * @return bool True if user can access
 */
function canAccessSystem($user_role) {
    if (!isMaintenanceMode()) {
        return true;
    }
    
    // Only admins can access during maintenance
    return $user_role === 'admin';
}
?>