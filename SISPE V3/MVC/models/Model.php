<?php
/**
 * Clase Model - Modelo base abstracto
 * Proporciona funcionalidad CRUD comun para todos los modelos
 * Usa excepciones y prepared statements para seguridad
 */
abstract class Model {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $errors = [];
    
    /**
     * Constructor - Obtiene conexion a BD
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene todos los registros
     * @param string $orderBy Columna para ordenar
     * @param string $direction Direccion (ASC/DESC)
     * @return array
     */
    public function all(string $orderBy = '', string $direction = 'ASC'): array {
        try {
            $sql = "SELECT * FROM {$this->table}";
            
            if (!empty($orderBy)) {
                $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                $sql .= " ORDER BY {$orderBy} {$direction}";
            }
            
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->addError("Error al obtener registros: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca un registro por su clave primaria
     * @param mixed $id Valor de la clave primaria
     * @return array|null
     */
    public function find($id): ?array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            
            $result = $stmt->fetch();
            return $result ?: null;
            
        } catch (PDOException $e) {
            $this->addError("Error al buscar registro: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca registros por una columna especifica
     * @param string $column Nombre de la columna
     * @param mixed $value Valor a buscar
     * @return array
     */
    public function findBy(string $column, $value): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['value' => $value]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->addError("Error en busqueda: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca un solo registro por columna
     * @param string $column Nombre de la columna
     * @param mixed $value Valor a buscar
     * @return array|null
     */
    public function findOneBy(string $column, $value): ?array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$column} = :value LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['value' => $value]);
            
            $result = $stmt->fetch();
            return $result ?: null;
            
        } catch (PDOException $e) {
            $this->addError("Error en busqueda: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crea un nuevo registro
     * @param array $data Datos a insertar
     * @return int|false ID del registro creado o false
     */
    public function create(array $data) {
        try {
            // Filtrar solo campos permitidos
            $data = $this->filterFillable($data);
            
            if (empty($data)) {
                $this->addError("No hay datos validos para insertar");
                return false;
            }
            
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            
            return (int)$this->db->lastInsertId();
            
        } catch (PDOException $e) {
            $this->addError("Error al crear registro: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza un registro existente
     * @param mixed $id Clave primaria
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function update($id, array $data): bool {
        try {
            // Filtrar solo campos permitidos
            $data = $this->filterFillable($data);
            
            if (empty($data)) {
                $this->addError("No hay datos validos para actualizar");
                return false;
            }
            
            $setParts = [];
            foreach (array_keys($data) as $column) {
                $setParts[] = "{$column} = :{$column}";
            }
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :pk_id";
            $data['pk_id'] = $id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($data);
            
        } catch (PDOException $e) {
            $this->addError("Error al actualizar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Elimina un registro
     * @param mixed $id Clave primaria
     * @return bool
     */
    public function delete($id): bool {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
            
        } catch (PDOException $e) {
            $this->addError("Error al eliminar: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cuenta registros totales o con condicion
     * @param string|null $column Columna para filtrar
     * @param mixed $value Valor del filtro
     * @return int
     */
    public function count(?string $column = null, $value = null): int {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $params = [];
            
            if ($column !== null && $value !== null) {
                $sql .= " WHERE {$column} = :value";
                $params['value'] = $value;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return (int)$stmt->fetch()['total'];
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Busqueda con LIKE
     * @param string $column Columna a buscar
     * @param string $search Termino de busqueda
     * @return array
     */
    public function search(string $column, string $search): array {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$column} LIKE :search";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['search' => "%{$search}%"]);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->addError("Error en busqueda: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ejecuta consulta SQL personalizada
     * @param string $sql Consulta SQL
     * @param array $params Parametros
     * @return array
     */
    public function query(string $sql, array $params = []): array {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->addError("Error en consulta: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ejecuta consulta que no retorna resultados
     * @param string $sql Consulta SQL
     * @param array $params Parametros
     * @return bool
     */
    public function execute(string $sql, array $params = []): bool {
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
            
        } catch (PDOException $e) {
            $this->addError("Error en ejecucion: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Filtra datos para incluir solo campos permitidos
     * @param array $data Datos a filtrar
     * @return array
     */
    protected function filterFillable(array $data): array {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Agrega un error
     * @param string $error Mensaje de error
     */
    protected function addError(string $error): void {
        $this->errors[] = $error;
    }
    
    /**
     * Obtiene todos los errores
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Verifica si hay errores
     * @return bool
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }
    
    /**
     * Limpia los errores
     */
    public function clearErrors(): void {
        $this->errors = [];
    }
    
    /**
     * Inicia transaccion
     */
    public function beginTransaction(): bool {
        return $this->db->beginTransaction();
    }
    
    /**
     * Confirma transaccion
     */
    public function commit(): bool {
        return $this->db->commit();
    }
    
    /**
     * Revierte transaccion
     */
    public function rollback(): bool {
        return $this->db->rollBack();
    }
}
?>
