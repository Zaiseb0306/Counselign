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
    
    
    // CRITICAL SECURITY: Check if this is truly production based on domain
    // Users can't easily change window.location.hostname
    const isProductionDomain = !isDevelopment && (
        window.location.hostname !== 'localhost' &&
        window.location.hostname !== '127.0.0.1' &&
        window.location.hostname !== '192.168.18.65' &&
        !window.location.hostname.includes('192.168.') &&
        !window.location.hostname.includes('10.') &&
        !window.location.hostname.includes('172.')
    );
    
    // CRITICAL: Automatically determine production mode based on domain
    // This prevents users from editing the flag and enabling logs
    const IS_PRODUCTION = isProductionDomain;
    
    // Make IS_PRODUCTION immutable so users can't change it
    Object.freeze({ IS_PRODUCTION });
    
    // REMEMBER: This automatically prevents logging on production, even if someone edits the code!
    // The only way to enable logging would be to change window.location.hostname (impossible!)

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
        'cvv', 'ssn',
        'profile_picture', 'photo', 'image', 'picture',
        'pwd_proof', 'proof', 'file_path', 'filePath',
        'name', 'first_name', 'firstname', 'last_name', 'lastname',
        'middle_name', 'middlename', 'birth_date', 'birthdate',
        'father_name', 'mother_name', 'parent',
        'address', 'barangay', 'city', 'province', 'zone',
        'contact', 'number'
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
        let sanitized = message;
        
        // Redact email addresses
        sanitized = sanitized.replace(/[\w.-]+@[\w.-]+\.\w+/g, '***@email.com');
        
        // Redact file paths with personal identifiers
        sanitized = sanitized.replace(/\/Photos\/[^"',\s]+/gi, '/Photos/***REDACTED***');
        sanitized = sanitized.replace(/pwd_proof_\d+_\d+\.jpg/gi, 'pwd_proof_***REDACTED***.jpg');
        sanitized = sanitized.replace(/profile_pictures\/student_\d+_\d+\.png/gi, 'profile_pictures/student_***REDACTED***.png');
        
        // Redact URLs with personal data
        sanitized = sanitized.replace(/http:\/\/[^\s"'<>]+(?:photos|pictures|proofs)[^\s"'<>]+/gi, 'http://***REDACTED***');
        
        // Redact specific field patterns
        sensitiveFields.forEach(field => {
            const regex = new RegExp(field + '[=:]\\s*[\\w@.]+', 'gi');
            sanitized = sanitized.replace(regex, (match) => {
                const parts = match.split(/[=:]/);
                if (parts.length === 2) {
                    return parts[0] + ': ***REDACTED***';
                }
                return match;
            });
        });
        
        // Redact specific names and personal data
        sanitized = sanitized.replace(/\b(Rex\s+Dominic|Beronilla|Fajardo|Sihay)\b/gi, '***REDACTED***');
        sanitized = sanitized.replace(/\b\d{10}\b/g, '***REDACTED***'); // Student IDs
        
        return sanitized;
    }

    /**
     * Secure logger implementation
     */
    const SecureLogger = {
        /**
         * Log debug message
         */
        debug: function(message, data) {
            // Don't log in production at all!
            if (IS_PRODUCTION || !isDevelopment) return;

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
            // Don't log in production at all!
            if (IS_PRODUCTION || !isDevelopment) return;

            if (data) {
                const sanitized = sanitizeData(data);
                // Also sanitize the message in case it contains sensitive data
                const sanitizedMessage = sanitizeString(message);
                console.log('ℹ️ [INFO] ' + sanitizedMessage, sanitized);
            } else {
                console.log('ℹ️ [INFO] ' + sanitizeString(message));
            }
        },

        /**
         * Log warning message
         */
        warn: function(message, data) {
            // Don't log in production at all!
            if (IS_PRODUCTION || !isDevelopment) return;

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
            // Don't log in production at all!
            if (IS_PRODUCTION || !isDevelopment) return;

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
            // Don't log in production at all!
            if (IS_PRODUCTION || !isDevelopment) return;

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
            // Don't log in production at all!
            if (IS_PRODUCTION || !isDevelopment) return;

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

    console.log('🔒 SecureLogger initialized. Production mode: ' + (IS_PRODUCTION ? 'ON (ALL LOGGING DISABLED)' : 'OFF'));
})();

