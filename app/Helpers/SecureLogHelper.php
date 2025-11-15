<?php

namespace App\Helpers;

/**
 * Secure Logging Helper
 * 
 * Provides secure logging functionality that automatically redacts
 * sensitive information from log messages to prevent data exposure.
 * 
 * This is critical for production security and compliance with
 * GDPR, PCI DSS, and other data protection regulations.
 */
class SecureLogHelper
{
    /**
     * List of field names that should be redacted
     * 
     * @var array<string>
     */
    private static array $sensitiveFields = [
        'password',
        'pass',
        'pwd',
        'passwd',
        'user_id',
        'userId',
        'username',
        'email',
        'phone',
        'mobile',
        'token',
        'api_key',
        'apiKey',
        'secret',
        'secret_key',
        'secretKey',
        'session_id',
        'sessionId',
        'session_token',
        'sessionToken',
        'auth_token',
        'authToken',
        'access_token',
        'accessToken',
        'refresh_token',
        'refreshToken',
        'csrf_token',
        'csrfToken',
        'cookie',
        'session',
        'authorization',
        'Authorization',
        'credit_card',
        'creditCard',
        'card_number',
        'cardNumber',
        'cvv',
        'ssn',
        'social_security',
        'personal_id',
        'national_id',
    ];

    /**
     * Log a message with sanitized data
     * 
     * Automatically removes sensitive information from the data array
     * before logging.
     * 
     * @param string $level Log level (debug, info, notice, warning, error, critical, alert, emergency)
     * @param string $message Log message
     * @param array|null $data Optional data array to sanitize and log
     * @return void
     */
    public static function logSecure(
        string $level,
        string $message,
        ?array $data = null
    ): void {
        if ($data !== null) {
            $sanitizedData = self::sanitizeData($data);
            $message .= ' Data: ' . json_encode($sanitizedData, JSON_UNESCAPED_UNICODE);
        }
        
        log_message($level, $message);
    }

    /**
     * Log debug information securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function debug(string $message, ?array $data = null): void
    {
        self::logSecure('debug', $message, $data);
    }

    /**
     * Log informational message securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function info(string $message, ?array $data = null): void
    {
        self::logSecure('info', $message, $data);
    }

    /**
     * Log notice securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function notice(string $message, ?array $data = null): void
    {
        self::logSecure('notice', $message, $data);
    }

    /**
     * Log warning securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function warning(string $message, ?array $data = null): void
    {
        self::logSecure('warning', $message, $data);
    }

    /**
     * Log error securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function error(string $message, ?array $data = null): void
    {
        self::logSecure('error', $message, $data);
    }

    /**
     * Log critical error securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function critical(string $message, ?array $data = null): void
    {
        self::logSecure('critical', $message, $data);
    }

    /**
     * Log alert securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function alert(string $message, ?array $data = null): void
    {
        self::logSecure('alert', $message, $data);
    }

    /**
     * Log emergency securely
     * 
     * @param string $message Log message
     * @param array|null $data Optional data array
     * @return void
     */
    public static function emergency(string $message, ?array $data = null): void
    {
        self::logSecure('emergency', $message, $data);
    }

    /**
     * Sanitize data array by redacting sensitive fields
     * 
     * @param array $data Data array to sanitize
     * @return array Sanitized data array
     */
    private static function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            $keyLower = strtolower($key);
            
            // Check if field name is sensitive
            if (self::isSensitiveField($keyLower)) {
                $sanitized[$key] = '***REDACTED***';
                continue;
            }

            // Recursively sanitize nested arrays
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Check if a field name is sensitive
     * 
     * @param string $fieldName Field name to check
     * @return bool True if field is sensitive
     */
    private static function isSensitiveField(string $fieldName): bool
    {
        foreach (self::$sensitiveFields as $sensitiveField) {
            if (strpos($fieldName, $sensitiveField) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log user action securely
     * 
     * Logs user actions without exposing sensitive user data
     * 
     * @param string $action Action description
     * @param string|null $userId User ID (will be redacted if in sensitive fields)
     * @param array|null $additionalData Additional data to log
     * @return void
     */
    public static function logUserAction(
        string $action,
        ?string $userId = null,
        ?array $additionalData = null
    ): void {
        $data = [];
        
        if ($userId !== null) {
            // Only show first 3 characters of user ID
            $data['user_id'] = substr($userId, 0, 3) . '***';
        }

        if ($additionalData !== null) {
            $data = array_merge($data, $additionalData);
        }

        self::info($action, $data);
    }

    /**
     * Log API request securely
     * 
     * @param string $method HTTP method
     * @param string $url Request URL
     * @param array|null $headers Request headers
     * @param array|null $body Request body
     * @return void
     */
    public static function logApiRequest(
        string $method,
        string $url,
        ?array $headers = null,
        ?array $body = null
    ): void {
        $data = [
            'method' => $method,
            'url' => $url,
        ];

        if ($headers !== null) {
            $data['headers'] = $headers;
        }

        if ($body !== null) {
            $data['body'] = $body;
        }

        self::debug("API Request: {$method} {$url}", $data);
    }

    /**
     * Log database query securely
     * 
     * @param string $query SQL query (sensitive parts will be redacted)
     * @param array|null $params Query parameters
     * @return void
     */
    public static function logQuery(
        string $query,
        ?array $params = null
    ): void {
        $data = [
            'query' => $query,
        ];

        if ($params !== null) {
            $data['params'] = $params;
        }

        self::debug('Database Query', $data);
    }
}

