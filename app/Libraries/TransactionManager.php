<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * Transaction Manager
 * 
 * Centralized transaction management with retry logic and proper error handling.
 * Ensures ACID compliance across all database operations.
 */
class TransactionManager
{
    private BaseConnection $db;
    private int $transactionLevel = 0;
    private array $retryConfig = [
        'max_retries' => 3,
        'base_delay_ms' => 100,
        'max_delay_ms' => 1000
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Execute operations within a transaction with proper isolation level
     * 
     * @param callable $callback Function to execute within transaction
     * @param array $options Transaction options
     * @return mixed Result of callback execution
     * @throws \Exception On transaction failure
     */
    public function executeInTransaction(callable $callback, array $options = []): mixed
    {
        $isolationLevel = $options['isolation'] ?? 'READ COMMITTED';
        $timeout = $options['timeout'] ?? 30;
        $retryOnDeadlock = $options['retry_on_deadlock'] ?? true;

        $attempt = 0;
        $maxRetries = $retryOnDeadlock ? $this->retryConfig['max_retries'] : 1;

        while ($attempt < $maxRetries) {
            try {
                return $this->executeTransactionAttempt($callback, $isolationLevel, $timeout);
            } catch (\Exception $e) {
                if ($this->isDeadlockError($e) && $attempt < $maxRetries - 1) {
                    $attempt++;
                    $this->handleRetryDelay($attempt);
                    continue;
                }
                
                $this->logTransactionError($e, $attempt);
                throw $e;
            }
        }

        throw new \Exception('Transaction failed after maximum retries');
    }

    /**
     * Execute a single transaction attempt
     * 
     * @param callable $callback
     * @param string $isolationLevel
     * @param int $timeout
     * @return mixed
     * @throws \Exception
     */
    private function executeTransactionAttempt(callable $callback, string $isolationLevel, int $timeout): mixed
    {
        // Set transaction isolation level
        $this->db->query("SET TRANSACTION ISOLATION LEVEL {$isolationLevel}");
        
        // Set transaction timeout
        $this->db->query("SET SESSION innodb_lock_wait_timeout = {$timeout}");

        $this->db->transStart();
        
        try {
            $result = $callback($this->db);
            
            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \Exception('Transaction status indicates failure');
            }
            
            $this->db->transComplete();
            return $result;
            
        } catch (\Exception $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }

    /**
     * Check if error is a deadlock
     * 
     * @param \Exception $e
     * @return bool
     */
    private function isDeadlockError(\Exception $e): bool
    {
        $message = $e->getMessage();
        return strpos($message, 'Deadlock') !== false || 
               strpos($message, 'Lock wait timeout') !== false;
    }

    /**
     * Handle retry delay with exponential backoff
     * 
     * @param int $attempt
     * @return void
     */
    private function handleRetryDelay(int $attempt): void
    {
        $delay = min(
            $this->retryConfig['base_delay_ms'] * pow(2, $attempt - 1),
            $this->retryConfig['max_delay_ms']
        );
        
        usleep($delay * 1000); // Convert to microseconds
    }

    /**
     * Log transaction errors for monitoring
     * 
     * @param \Exception $e
     * @param int $attempt
     * @return void
     */
    private function logTransactionError(\Exception $e, int $attempt): void
    {
        log_message('error', sprintf(
            'Transaction failed (attempt %d): %s - %s',
            $attempt + 1,
            get_class($e),
            $e->getMessage()
        ));
    }

    /**
     * Execute with row-level locking for critical operations
     * 
     * @param callable $callback
     * @param array $lockTables Array of tables to lock
     * @return mixed
     */
    public function executeWithLocking(callable $callback, array $lockTables = []): mixed
    {
        return $this->executeInTransaction(function($db) use ($callback, $lockTables) {
            // Lock specified tables
            foreach ($lockTables as $table) {
                $db->query("LOCK TABLES {$table} WRITE");
            }
            
            try {
                $result = $callback($db);
                return $result;
            } finally {
                // Unlock tables
                $db->query("UNLOCK TABLES");
            }
        });
    }

    /**
     * Get current transaction status
     * 
     * @return array
     */
    public function getTransactionStatus(): array
    {
        return [
            'in_transaction' => $this->db->transStatus() !== false,
            'transaction_level' => $this->transactionLevel,
            'connection_id' => spl_object_hash($this->db)
        ];
    }
}
