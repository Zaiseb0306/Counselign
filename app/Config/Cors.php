<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    public array $default = [
        'allowedOrigins' => [
            'http://localhost',
            'http://localhost:80',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://127.0.0.1:80',
            'http://127.0.0.1:8080',
        ],
        'allowedOriginsPatterns' => [
            'https?://192\.168\.\d+\.\d+(:\d+)?',
            'https?://10\.\d+\.\d+\.\d+(:\d+)?',
            'https?://172\.(1[6-9]|2[0-9]|3[0-1])\.\d+\.\d+(:\d+)?',
            'https?://localhost(:\d+)?',
            'https?://127\.0\.0\.1(:\d+)?',
        ],
        'supportsCredentials' => true,
        'allowedHeaders' => [
            'Origin', 'X-Requested-With', 'Content-Type', 'Accept', 'Authorization', 'X-CSRF-TOKEN', 'X-XSRF-TOKEN', 'Cache-Control',
        ],
        'exposedHeaders' => ['X-CSRF-TOKEN', 'Content-Length', 'Content-Type'],
        'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'],
        'maxAge' => 7200,
    ];
}
