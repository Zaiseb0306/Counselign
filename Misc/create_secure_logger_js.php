<?php

/**
 * Create Secure JavaScript Logger
 * 
 * This script creates a secure logging helper for JavaScript that:
 * - Automatically redacts sensitive data
 * - Only logs in development mode
 * - Prevents sensitive information exposure
 */

$secureLoggerJs = <<<'EOF'
/**
 * Secure Logger for JavaScript
 * 
 * Automatically redacts sensitive information and only logs in development mode.
 * This prevents sensitive data from appearing in browser console in production.
 */

(function() {
    'use strict';

    // Check if we're in development mode
    const isDevelopment = window.location.hostname === 'localhost' || 
                          window.location.hostname === '127.0.0.1' ||
                          window.location.hostname === '192.168.18.65';

    // List of sensitive field names
    const sensitiveFields = [
        'password', 'pass', 'pwd', 'passwd',
        'user_id', 'userId', 'username',
        'email', 'phone', 'mobile',
        'token', 'api_key', 'apiKey',
        'secret', 'secret_key', 'secretKey',
        'session_id', 'sessionId',
        'auth_token', 'authToken',
        'access_token', 'accessToken',
        'refresh_token', 'refreshToken',
        'csrf_token', 'csrfToken',
        'cookie', 'session',
        'authorization', 'Authorization',
        'credit_card', 'creditCard',
        'card_number', 'cardNumber',
        'cvv', 'ssn'
    ];

    /**
     * Check if a key is sensitive
     */
    function isSensitiveField(key) {
        const lowerKey = key.toLowerCase();
        return sensitiveFields.some(field => 
            lowerKey.includes(field.toLowerCase())
        );
    }

    /**
     * Sanitize an object by redacting sensitive fields
     */
    function sanitizeData(data) {
        if (data === null || data === undefined) {
            return data;
        }

        // Handle arrays
        if (Array.isArray(data)) {
            return data.map(item => sanitizeData(item));
        }

        // Handle objects
        if (typeof data === 'object') {
            const sanitized = {};
            for (const key in data) {
                if (data.hasOwnProperty(key)) {
                    if (isSensitiveField(key)) {
                        sanitized[key] = '***REDACTED***';
                    } else {
                        sanitized[key] = sanitizeData(data[key]);
                    }
                }
            }
            return sanitized;
        }

        return data;
    }

    /**
     * Sanitize a string that might contain sensitive data
     */
    function sanitizeString(message) {
        sensitiveFields.forEach(field => {
            const regex = new RegExp(field + '[=:]\\s*[\\w]+', 'gi');
            message = message.replace(regex, (match) => {
                const parts = match.split(/[=:]/);
                if (parts.length === 2) {
                    return parts[0] + ': ***REDACTED***';
                }
                return match;
            });
        });
        return message;
    }

    /**
     * Secure logger implementation
     */
    const SecureLogger = {
        /**
         * Log debug message
         */
        debug: function(message, data) {
            if (!isDevelopment) return;
            
            if (data) {
                const sanitized = sanitizeData(data);
                console.log('🔍 [DEBUG] ' + sanitizeString(message), sanitized);
            } else {
                console.log('🔍 [DEBUG] ' + sanitizeString(message));
            }
        },

        /**
         * Log info message
         */
        info: function(message, data) {
            if (!isDevelopment) return;
            
            if (data) {
                const sanitized = sanitizeData(data);
                console.log('ℹ️ [INFO] ' + sanitizeString(message), sanitized);
            } else {
                console.log('ℹ️ [INFO] ' + sanitizeString(message));
            }
        },

        /**
         * Log warning message
         */
        warn: function(message, data) {
            if (!isDevelopment) return;
            
            if (data) {
                const sanitized = sanitizeData(data);
                console.warn('⚠️ [WARNING] ' + sanitizeString(message), sanitized);
            } else {
                console.warn('⚠️ [WARNING] ' + sanitizeString(message));
            }
        },

        /**
         * Log error message
         */
        error: function(message, error, data) {
            if (!isDevelopment) return;
            
            if (data) {
                const sanitized = sanitizeData(data);
                console.error('❌ [ERROR] ' + sanitizeString(message), sanitized, error);
            } else {
                console.error('❌ [ERROR] ' + sanitizeString(message), error);
            }
        },

        /**
         * Log success message
         */
        success: function(message, data) {
            if (!isDevelopment) return;
            
            if (data) {
                const sanitized = sanitizeData(data);
                console.log('✅ [SUCCESS] ' + sanitizeString(message), sanitized);
            } else {
                console.log('✅ [SUCCESS] ' + sanitizeString(message));
            }
        },

        /**
         * Log request
         */
        request: function(method, url, headers, body) {
            if (!isDevelopment) return;
            
            console.log('📤 [REQUEST] ' + method + ' ' + url);
            if (headers) {
                const sanitizedHeaders = sanitizeData(headers);
                console.log('Headers:', sanitizedHeaders);
            }
            if (body) {
                const sanitizedBody = sanitizeData(body);
                console.log('Body:', sanitizedBody);
            }
        },

        /**
         * Log response
         */
        response: function(statusCode, headers, body) {
            if (!isDevelopment) return;
            
            console.log('📥 [RESPONSE] Status: ' + statusCode);
            if (headers) {
                const sanitizedHeaders = sanitizeData(headers);
                console.log('Headers:', sanitizedHeaders);
            }
            if (body) {
                // Try to sanitize JSON body
                try {
                    const parsed = JSON.parse(body);
                    const sanitized = sanitizeData(parsed);
                    console.log('Body:', sanitized);
                } catch (e) {
                    console.log('Body:', sanitizeString(body));
                }
            }
        }
    };

    // Make SecureLogger globally available
    window.SecureLogger = SecureLogger;

    // Override console methods in production to prevent accidental logging
    if (!isDevelopment) {
        const originalLog = console.log;
        const originalDebug = console.debug;
        const originalInfo = console.info;
        
        console.log = function() {
            // Silently ignore in production
        };
        console.debug = function() {
            // Silently ignore in production
        };
        console.info = function() {
            // Silently ignore in production
        };

        // Keep error logging for production
        console.error = originalLog.bind(console);
        console.warn = originalLog.bind(console);
    }

    console.log('🔒 SecureLogger initialized. Production mode: ' + (!isDevelopment ? 'ON' : 'OFF'));
})();

EOF;

// Write to file
file_put_contents(__DIR__ . '/app/Helpers/secureLogger.js', $secureLoggerJs);

echo "✅ Secure JavaScript Logger created successfully!\n";
echo "📁 Location: Counselign/public/js/utils/secureLogger.js\n";
echo "🚀 Add this to your HTML: <script src=\"js/utils/secureLogger.js\"></script>\n";
echo "📝 Usage: SecureLogger.info('Message', data);\n";

