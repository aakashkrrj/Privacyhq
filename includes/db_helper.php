<?php
/**
 * Database Helper Functions
 * 
 * Foundational wrapper for common database operations to ensure consistency
 * and prevent SQL injection.
 * Do not implement specific business logic or CRUD here yet.
 */

require_once __DIR__ . '/../config/database.php';

class DBHelper {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Executes a SQL query with optional parameters.
     * Use for INSERT, UPDATE, DELETE.
     *
     * @param string $sql The SQL query
     * @param array $params An array of parameters to bind
     * @return bool True on success, false on failure
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            // Log error in production
            error_log("DB Execute Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches all rows from a SELECT query.
     *
     * @param string $sql The SQL query
     * @param array $params An array of parameters to bind
     * @return array|false The result set as an associative array, or false on failure
     */
    public function fetchAll($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("DB FetchAll Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a single row from a SELECT query.
     *
     * @param string $sql The SQL query
     * @param array $params An array of parameters to bind
     * @return array|false The single row as an associative array, or false on failure
     */
    public function fetchOne($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("DB FetchOne Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns the ID of the last inserted row.
     *
     * @return string The ID of the last inserted row
     */
    public function getLastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * Begins a transaction.
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction.
     */
    public function commit() {
        return $this->pdo->commit();
    }

    /**
     * Rolls back a transaction.
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}
