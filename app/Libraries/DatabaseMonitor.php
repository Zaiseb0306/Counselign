<?php

namespace App\Libraries;

use CodeIgniter\Log\Logger;

/**
 * Database Performance Monitor
 * 
 * Tracks query performance, connection usage, and database health
 */
class DatabaseMonitor
{
    private $logger;
    private $queryTimes = [];
    private $slowQueryThreshold = 1.0; // seconds
    
    public function __construct()
    {
        $this->logger = \Config\Services::logger();
    }
    
    /**
     * Start timing a query
     */
    public function startQuery(string $queryId, string $sql): void
    {
        $this->queryTimes[$queryId] = [
            'sql' => $sql,
            'start_time' => microtime(true),
            'memory_start' => memory_get_usage()
        ];
    }
    
    /**
     * End timing a query and log if slow
     */
    public function endQuery(string $queryId): array
    {
        if (!isset($this->queryTimes[$queryId])) {
            return [];
        }
        
        $queryData = $this->queryTimes[$queryId];
        $executionTime = microtime(true) - $queryData['start_time'];
        $memoryUsed = memory_get_usage() - $queryData['memory_start'];
        
        $result = [
            'query_id' => $queryId,
            'sql' => $queryData['sql'],
            'execution_time' => $executionTime,
            'memory_used' => $memoryUsed,
            'is_slow' => $executionTime > $this->slowQueryThreshold
        ];
        
        // Log slow queries
        if ($result['is_slow']) {
            $this->logger->warning('Slow Query Detected', $result);
        }
        
        // Log all queries in debug mode
        if (ENVIRONMENT === 'development') {
            $this->logger->debug('Query Executed', $result);
        }
        
        unset($this->queryTimes[$queryId]);
        return $result;
    }
    
    /**
     * Log database connection events
     */
    public function logConnection(string $event, array $details = []): void
    {
        $this->logger->info("Database Connection: {$event}", $details);
    }
    
    /**
     * Log transaction events
     */
    public function logTransaction(string $event, array $details = []): void
    {
        $this->logger->info("Database Transaction: {$event}", $details);
    }
    
    /**
     * Get performance statistics
     */
    public function getStats(): array
    {
        return [
            'active_queries' => count($this->queryTimes),
            'slow_query_threshold' => $this->slowQueryThreshold,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }
    
    /**
     * Set slow query threshold
     */
    public function setSlowQueryThreshold(float $seconds): void
    {
        $this->slowQueryThreshold = $seconds;
    }
}
