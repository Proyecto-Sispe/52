<?php
/**
 * Clase Database - Singleton Pattern
 * Maneja la conexion a la base de datos usando PDO
 * Implementa excepciones y patron singleton para una unica instancia
 */
class Database {
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    
    private string $host = 'localhost';
    private string $dbname = 'divina_comida';
    private string $username = 'root';
    private string $password = '';
    private string $charset = 'utf8mb4';
    
    /**
     * Constructor privado - Patron Singleton
     * @throws DatabaseException si no puede conectar
     */
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];
            
            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Error de conexion a la base de datos: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Obtiene la instancia unica de Database
     * @return Database
     */
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtiene la conexion PDO
     * @return PDO
     */
    public function getConnection(): PDO {
        return $this->connection;
    }
    
    /**
     * Ejecuta una consulta preparada
     * @param string $sql Consulta SQL
     * @param array $params Parametros para la consulta
     * @return PDOStatement
     * @throws DatabaseException
     */
    public function query(string $sql, array $params = []): PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException(
                "Error en consulta: " . $e->getMessage(),
                (int)$e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Inicia una transaccion
     */
    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirma una transaccion
     */
    public function commit(): bool {
        return $this->connection->commit();
    }
    
    /**
     * Revierte una transaccion
     */
    public function rollback(): bool {
        return $this->connection->rollBack();
    }
    
    /**
     * Obtiene el ultimo ID insertado
     */
    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }
    
    // Prevenir clonacion
    private function __clone() {}
    
    // Prevenir deserializacion
    public function __wakeup() {
        throw new DatabaseException("No se puede deserializar un singleton");
    }
}

/**
 * Excepcion personalizada para errores de base de datos
 */
class DatabaseException extends Exception {
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
    
    public function __toString(): string {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
?>
