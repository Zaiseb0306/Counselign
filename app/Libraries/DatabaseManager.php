<?php

namespace App\Libraries;

use Config\Database;

/**
 * Database Connection Manager
 * 
 * Handles database connection pooling, monitoring, and error recovery
 */
class DatabaseManager
{
    private static $instance = null;
    private $connections = [];
    private $maxConnections = 10;
    private $connectionTimeout = 30;
    
    private function __construct()
    {
        // Private constructor for singleton
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection with pooling
     */
    public function getConnection(string $group = 'default')
    {
        if (!isset($this->connections[$group])) {
            $this->connections[$group] = [];
        }
        
        // Check for available connection
        foreach ($this->connections[$group] as $index => $connection) {
            if ($this->isConnectionHealthy($connection)) {
                return $connection;
            } else {
                // Remove unhealthy connection
                unset($this->connections[$group][$index]);
            }
        }
        
        // Create new connection if under limit
        if (count($this->connections[$group]) < $this->maxConnections) {
            $connection = Database::connect($group);
            $this->connections[$group][] = $connection;
            return $connection;
        }
        
        // Fallback to new connection
        return Database::connect($group);
    }
    
    /**
     * Check if connection is healthy
     */
    private function isConnectionHealthy($connection): bool
    {
        try {
            $connection->query("SELECT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Close all connections
     */
    public function closeAllConnections()
    {
        foreach ($this->connections as $group => $connections) {
            foreach ($connections as $connection) {
                try {
                    $connection->close();
                } catch (\Exception $e) {
                    log_message('error', 'Error closing connection: ' . $e->getMessage());
                }
            }
        }
        $this->connections = [];
    }
    
    /**
     * Get connection statistics
     */
    public function getConnectionStats(): array
    {
        $stats = [];
        foreach ($this->connections as $group => $connections) {
            $stats[$group] = [
                'active' => count($connections),
                'max' => $this->maxConnections
            ];
        }
        return $stats;
    }
}
