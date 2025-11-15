<?php

namespace App\Models;


use App\Helpers\SecureLogHelper;
use CodeIgniter\Model;
use App\Libraries\TransactionManager;
use CodeIgniter\Exceptions\CodeIgniterException;

/**
 * Base Model with ACID Compliance Support
 * 
 * Provides atomic operations and transaction management for all models.
 * Ensures type safety and proper error handling.
 */
abstract class BaseModel extends Model
{
    protected TransactionManager $transactionManager;
    protected array $atomicOperations = [];

    public function __construct()
    {
        parent::__construct();
        $this->transactionManager = new TransactionManager();
    }

    /**
     * Execute multiple operations atomically
     * 
     * @param array $operations Array of operations to execute
     * @param array $options Transaction options
     * @return array Results of all operations
     * @throws CodeIgniterException On transaction failure
     */
    protected function executeAtomic(array $operations, array $options = []): array
    {
        return $this->transactionManager->executeInTransaction(function($db) use ($operations) {
            $results = [];
            
            foreach ($operations as $index => $operation) {
                try {
                    $result = $this->executeOperation($operation, $db);
                    $results[$index] = [
                        'success' => true,
                        'data' => $result,
                        'operation' => $operation['method'] ?? 'unknown'
                    ];
                } catch (\Exception $e) {
                    $results[$index] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'operation' => $operation['method'] ?? 'unknown'
                    ];
                    throw $e; // Re-throw to trigger rollback
                }
            }
            
            return $results;
        }, $options);
    }

    /**
     * Execute a single operation within transaction
     * 
     * @param array $operation Operation configuration
     * @param mixed $db Database connection
     * @return mixed Operation result
     * @throws \Exception On operation failure
     */
    private function executeOperation(array $operation, $db): mixed
    {
        $method = $operation['method'] ?? '';
        $params = $operation['params'] ?? [];
        $model = $operation['model'] ?? $this;

        if (!method_exists($model, $method)) {
            throw new \Exception("Method {$method} not found on " . get_class($model));
        }

        // Add database connection to params if method expects it
        if (in_array($method, ['insert', 'update', 'delete', 'find', 'findAll'])) {
            array_unshift($params, $db);
        }

        return $model->$method(...$params);
    }

    /**
     * Execute operations with row-level locking
     * 
     * @param array $operations Operations to execute
     * @param array $lockTables Tables to lock
     * @param array $options Transaction options
     * @return array Results of operations
     */
    protected function executeWithLocking(array $operations, array $lockTables = [], array $options = []): array
    {
        return $this->transactionManager->executeWithLocking(function($db) use ($operations, $options) {
            return $this->executeAtomic($operations, $options);
        }, $lockTables);
    }

    /**
     * Validate data before atomic operations
     * 
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return bool True if valid
     * @throws \Exception If validation fails
     */
    protected function validateAtomicData(array $data, array $rules = []): bool
    {
        if (empty($rules)) {
            $rules = $this->validationRules ?? [];
        }

        if (empty($rules)) {
            return true; // No validation rules defined
        }

        $validation = \Config\Services::validation();
        $validation->setRules($rules);

        if (!$validation->run($data)) {
            $errors = $validation->getErrors();
            throw new \Exception('Validation failed: ' . implode(', ', $errors));
        }

        return true;
    }

    /**
     * Create atomic operation configuration
     * 
     * @param string $method Method name
     * @param array $params Method parameters
     * @param mixed $model Model instance (defaults to current model)
     * @return array Operation configuration
     */
    protected function createAtomicOperation(string $method, array $params = [], $model = null): array
    {
        return [
            'method' => $method,
            'params' => $params,
            'model' => $model ?? $this
        ];
    }

    /**
     * Get transaction status for debugging
     * 
     * @return array Transaction status information
     */
    public function getTransactionStatus(): array
    {
        return $this->transactionManager->getTransactionStatus();
    }

    /**
     * Log atomic operation for monitoring
     * 
     * @param string $operation Operation name
     * @param array $data Operation data
     * @param bool $success Whether operation succeeded
     * @return void
     */
    protected function logAtomicOperation(string $operation, array $data, bool $success): void
    {
        log_message('info', sprintf(
            'Atomic operation %s: %s - Data: %s',
            $success ? 'SUCCESS' : 'FAILED',
            $operation,
            json_encode($data)
        ));
    }
}
