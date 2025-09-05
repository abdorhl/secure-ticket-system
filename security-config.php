<?php
/**
 * Security Configuration for Ticket System
 * This file contains security settings and constants
 */

// Prevent direct access
if (!defined('SECURITY_CONFIG_LOADED')) {
    define('SECURITY_CONFIG_LOADED', true);
}

// Security Constants
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 900); // 15 minutes
define('SESSION_LIFETIME', 3600); // 1 hour
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_FILE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Security Headers Configuration
define('SECURITY_HEADERS', [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self';"
]);

// Input Validation Rules
define('INPUT_VALIDATION', [
    'email' => FILTER_VALIDATE_EMAIL,
    'url' => FILTER_VALIDATE_URL,
    'int' => FILTER_VALIDATE_INT,
    'float' => FILTER_VALIDATE_FLOAT,
    'boolean' => FILTER_VALIDATE_BOOLEAN
]);

// SQL Injection Prevention
define('SQL_INJECTION_PATTERNS', [
    '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
    '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
    '/(\b(OR|AND)\s+[\'"]\s*=\s*[\'"])/i',
    '/(\b(OR|AND)\s+[\'"]\s*LIKE\s*[\'"])/i',
    '/(\b(OR|AND)\s+[\'"]\s*IN\s*[\'"])/i'
]);

// XSS Prevention Patterns
define('XSS_PATTERNS', [
    '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
    '/javascript:/i',
    '/on\w+\s*=/i',
    '/<iframe\b[^>]*>/i',
    '/<object\b[^>]*>/i',
    '/<embed\b[^>]*>/i'
]);

// File Upload Security
define('FILE_UPLOAD_SECURITY', [
    'max_size' => MAX_FILE_SIZE,
    'allowed_types' => ALLOWED_FILE_TYPES,
    'allowed_extensions' => ALLOWED_FILE_EXTENSIONS,
    'scan_uploads' => true,
    'quarantine_suspicious' => true,
    'upload_path' => 'uploads/screenshots/',
    'secure_filename' => true
]);

// Password Requirements
define('PASSWORD_REQUIREMENTS', [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_special_chars' => true,
    'max_length' => 128
]);

// Logging Configuration
define('SECURITY_LOGGING', [
    'log_failed_logins' => true,
    'log_sql_errors' => true,
    'log_file_uploads' => true,
    'log_admin_actions' => true,
    'log_security_events' => true,
    'log_level' => 'error',
    'log_file' => 'logs/security.log'
]);

// Rate Limiting Configuration
define('RATE_LIMITING', [
    'login_attempts' => MAX_LOGIN_ATTEMPTS,
    'lockout_duration' => LOGIN_LOCKOUT_DURATION,
    'api_requests_per_minute' => 60,
    'file_uploads_per_hour' => 10
]);

// Security Functions
class SecurityConfig {
    
    /**
     * Get security headers array
     */
    public static function getSecurityHeaders() {
        return SECURITY_HEADERS;
    }
    
    /**
     * Get file upload security settings
     */
    public static function getFileUploadSecurity() {
        return FILE_UPLOAD_SECURITY;
    }
    
    /**
     * Get password requirements
     */
    public static function getPasswordRequirements() {
        return PASSWORD_REQUIREMENTS;
    }
    
    /**
     * Get input validation rules
     */
    public static function getInputValidationRules() {
        return INPUT_VALIDATION;
    }
    
    /**
     * Get SQL injection patterns
     */
    public static function getSQLInjectionPatterns() {
        return SQL_INJECTION_PATTERNS;
    }
    
    /**
     * Get XSS patterns
     */
    public static function getXSSPatterns() {
        return XSS_PATTERNS;
    }
    
    /**
     * Get rate limiting configuration
     */
    public static function getRateLimitingConfig() {
        return RATE_LIMITING;
    }
    
    /**
     * Get logging configuration
     */
    public static function getLoggingConfig() {
        return SECURITY_LOGGING;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $requirements = self::getPasswordRequirements();
        $errors = [];
        
        if (strlen($password) < $requirements['min_length']) {
            $errors[] = "Password must be at least {$requirements['min_length']} characters long";
        }
        
        if (strlen($password) > $requirements['max_length']) {
            $errors[] = "Password must be no more than {$requirements['max_length']} characters long";
        }
        
        if ($requirements['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        
        if ($requirements['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        
        if ($requirements['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        
        if ($requirements['require_special_chars'] && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Check for SQL injection patterns
     */
    public static function detectSQLInjection($input) {
        $patterns = self::getSQLInjectionPatterns();
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check for XSS patterns
     */
    public static function detectXSS($input) {
        $patterns = self::getXSSPatterns();
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $config = self::getLoggingConfig();
        if (!$config['log_security_events']) {
            return;
        }
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logMessage = json_encode($logEntry) . PHP_EOL;
        error_log($logMessage, 3, $config['log_file']);
    }
}
