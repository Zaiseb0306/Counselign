<?php

namespace App\Controllers\Admin;


use App\Helpers\SecureLogHelper;
use App\Controllers\BaseController;
use App\Libraries\DatabaseManager;
use App\Libraries\DatabaseMonitor;
use App\Libraries\QueryCache;
use CodeIgniter\API\ResponseTrait;

/**
 * Database Health Monitoring Controller
 */
class DatabaseHealth extends BaseController
{
    use ResponseTrait;
    
    private $dbManager;
    private $monitor;
    private $cache;
    
    public function __construct()
    {
        $this->dbManager = DatabaseManager::getInstance();
        $this->monitor = new DatabaseMonitor();
        $this->cache = new QueryCache();
    }
    
    /**
     * GET /admin/database/health
     * Get database health status
     */
    public function getHealth()
    {
        // Check admin authorization
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }
        
        try {
            $health = [
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'connections' => $this->dbManager->getConnectionStats(),
                'performance' => $this->monitor->getStats(),
                'cache_status' => $this->getCacheStatus(),
                'database_status' => $this->checkDatabaseStatus()
            ];
            
            return $this->respond($health);
            
        } catch (\Exception $e) {
            return $this->respond([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], 500);
        }
    }
    
    /**
     * GET /admin/database/performance
     * Get database performance metrics
     */
    public function getPerformance()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Get MySQL performance metrics
            $metrics = $this->getDatabaseMetrics($db);
            
            return $this->respond([
                'status' => 'success',
                'metrics' => $metrics,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $this->respond(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /admin/database/optimize
     * Run database optimization
     */
    public function optimize()
    {
        if (!session()->get('logged_in') || session()->get('role') !== 'admin') {
            return $this->respond(['error' => 'Unauthorized'], 401);
        }
        
        try {
            $db = \Config\Database::connect();
            $results = [];
            
            // Optimize tables
            $tables = ['users', 'appointments', 'counselors', 'messages'];
            foreach ($tables as $table) {
                $db->query("OPTIMIZE TABLE {$table}");
                $results[] = "Optimized table: {$table}";
            }
            
            // Clear query cache
            $this->cache->forget('query_*');
            $results[] = "Cleared query cache";
            
            return $this->respond([
                'status' => 'success',
                'message' => 'Database optimization completed',
                'results' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (\Exception $e) {
            return $this->respond(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get cache status
     */
    private function getCacheStatus(): array
    {
        return [
            'driver' => 'file', // or redis, memcached, etc.
            'status' => 'active',
            'memory_usage' => memory_get_usage(true)
        ];
    }
    
    /**
     * Check database connection status
     */
    private function checkDatabaseStatus(): array
    {
        try {
            $db = \Config\Database::connect();
            $result = $db->query("SELECT 1 as status")->getRow();
            
            return [
                'connected' => true,
                'response_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
                'version' => $db->getVersion()
            ];
        } catch (\Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get database performance metrics
     */
    private function getDatabaseMetrics($db): array
    {
        $metrics = [];
        
        try {
            // Get table sizes
            $tables = $db->query("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY (data_length + index_length) DESC
            ")->getResultArray();
            
            $metrics['table_sizes'] = $tables;
            
            // Get slow query log status
            $slowQueries = $db->query("SHOW VARIABLES LIKE 'slow_query_log'")->getRow();
            $metrics['slow_query_log'] = $slowQueries ? $slowQueries->Value : 'Unknown';
            
            // Get connection count
            $connections = $db->query("SHOW STATUS LIKE 'Threads_connected'")->getRow();
            $metrics['active_connections'] = $connections ? $connections->Value : 'Unknown';
            
        } catch (\Exception $e) {
            $metrics['error'] = $e->getMessage();
        }
        
        return $metrics;
    }
}
