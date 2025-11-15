<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface;

/**
 * Query Cache Manager
 * 
 * Implements intelligent caching for frequently accessed database queries
 */
class QueryCache
{
    private $cache;
    private $defaultTTL = 300; // 5 minutes
    
    public function __construct()
    {
        $this->cache = \Config\Services::cache();
    }
    
    /**
     * Cache a query result
     */
    public function remember(string $key, callable $callback, int $ttl = null): mixed
    {
        $ttl = $ttl ?? $this->defaultTTL;
        
        $cached = $this->cache->get($key);
        if ($cached !== null) {
            return $cached;
        }
        
        $result = $callback();
        $this->cache->save($key, $result, $ttl);
        
        return $result;
    }
    
    /**
     * Get cached result or null
     */
    public function get(string $key): mixed
    {
        return $this->cache->get($key);
    }
    
    /**
     * Store result in cache
     */
    public function put(string $key, mixed $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTTL;
        return $this->cache->save($key, $value, $ttl);
    }
    
    /**
     * Invalidate cache by pattern
     */
    public function forget(string $pattern): bool
    {
        return $this->cache->delete($pattern);
    }
    
    /**
     * Generate cache key for query
     */
    public function generateKey(string $table, array $conditions = [], string $method = ''): string
    {
        $key = "query_{$table}_{$method}_" . md5(serialize($conditions));
        return $key;
    }
    
    /**
     * Cache user-specific data
     */
    public function rememberUser(string $userId, string $key, callable $callback, int $ttl = null): mixed
    {
        $fullKey = "user_{$userId}_{$key}";
        return $this->remember($fullKey, $callback, $ttl);
    }
    
    /**
     * Invalidate user-specific cache
     */
    public function forgetUser(string $userId, string $pattern = ''): bool
    {
        $key = "user_{$userId}_{$pattern}";
        return $this->forget($key);
    }
}
