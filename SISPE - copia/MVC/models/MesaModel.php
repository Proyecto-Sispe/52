<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Mesa
 * Maneja operaciones CRUD y logica de negocio para mesas
 */
class MesaModel extends Model {
    protected string $table = 'Mesa';
    protected string $primaryKey = 'id_Mesa';
    protected array $fillable = ['id_Mesa', 'Capacidad', 'Ubicacion', 'Estado'];
    
    // Mapeo de ubicaciones
    private array $ubicaciones = [
        1 => 'Interior',
        2 => 'Terraza',
        3 => 'VIP',
        4 => 'Barra'
    ];
    
    /**
     * Obtiene todas las mesas con informacion adicional
     * @return array
     */
    public function getAllWithInfo(): array {
        $mesas = $this->all('id_Mesa', 'ASC');
        
        // Agregar informacion calculada
        foreach ($mesas as &$mesa) {
            $mesa['ubicacion_texto'] = $this->getUbicacionTexto($mesa['Ubicacion']);
            $mesa['estado_texto'] = $mesa['Estado'] == MESA_DISPONIBLE ? 'Disponible' : 'Ocupada';
            $mesa['codigo_acceso'] = $this->getCodigoAcceso($mesa['id_Mesa']);
        }
        
        return $mesas;
    }
    
    /**
     * Filtra mesas por estado
     * @param int $estado Estado de la mesa
     * @return array
     */
    public function getByEstado(int $estado): array {
        return $this->findBy('Estado', $estado);
    }
    
    /**
     * Obtiene mesas disponibles
     * @return array
     */
    public function getDisponibles(): array {
        return $this->getByEstado(MESA_DISPONIBLE);
    }
    
    /**
     * Obtiene mesas ocupadas
     * @return array
     */
    public function getOcupadas(): array {
        return $this->getByEstado(MESA_OCUPADA);
    }
    
    /**
     * Filtra mesas por ubicacion
     * @param int $ubicacion ID de ubicacion
     * @return array
     */
    public function getByUbicacion(int $ubicacion): array {
        return $this->findBy('Ubicacion', $ubicacion);
    }
    
    /**
     * Busca mesas con capacidad minima
     * @param int $capacidadMinima Capacidad minima requerida
     * @return array
     */
    public function getByCapacidadMinima(int $capacidadMinima): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE Capacidad >= :capacidad 
                ORDER BY Capacidad ASC";
        
        return $this->query($sql, ['capacidad' => $capacidadMinima]);
    }
    
    /**
     * Cambia el estado de una mesa
     * @param int $idMesa ID de la mesa
     * @param int $nuevoEstado Nuevo estado
     * @return bool
     */
    public function cambiarEstado(int $idMesa, int $nuevoEstado): bool {
        // Validar estado valido
        if (!in_array($nuevoEstado, [MESA_DISPONIBLE, MESA_OCUPADA])) {
            $this->addError("Estado invalido");
            return false;
        }
        
        return $this->update($idMesa, ['Estado' => $nuevoEstado]);
    }
    
    /**
     * Ocupa una mesa
     * @param int $idMesa ID de la mesa
     * @return bool
     */
    public function ocupar(int $idMesa): bool {
        $mesa = $this->find($idMesa);
        
        if ($mesa === null) {
            $this->addError("Mesa no encontrada");
            return false;
        }
        
        if ($mesa['Estado'] == MESA_OCUPADA) {
            $this->addError("La mesa ya esta ocupada");
            return false;
        }
        
        return $this->cambiarEstado($idMesa, MESA_OCUPADA);
    }
    
    /**
     * Libera una mesa
     * @param int $idMesa ID de la mesa
     * @return bool
     */
    public function liberar(int $idMesa): bool {
        return $this->cambiarEstado($idMesa, MESA_DISPONIBLE);
    }
    
    /**
     * Genera codigo de acceso unico para la mesa
     * @param int $idMesa ID de la mesa
     * @return string
     */
    public function getCodigoAcceso(int $idMesa): string {
        // Codigo basado en ID y fecha actual (valido por dia)
        $seed = $idMesa . date('Ymd');
        return strtoupper(substr(md5($seed), 0, 6));
    }
    
    /**
     * Valida codigo de acceso
     * @param string $codigo Codigo ingresado
     * @return int|false ID de mesa o false si invalido
     */
    public function validarCodigo(string $codigo): int|false {
        $mesas = $this->all();
        
        foreach ($mesas as $mesa) {
            if ($this->getCodigoAcceso($mesa['id_Mesa']) === strtoupper($codigo)) {
                return (int)$mesa['id_Mesa'];
            }
        }
        
        return false;
    }
    
    /**
     * Obtiene texto de ubicacion
     * @param int $ubicacion ID de ubicacion
     * @return string
     */
    public function getUbicacionTexto(int $ubicacion): string {
        return $this->ubicaciones[$ubicacion] ?? 'Desconocida';
    }
    
    /**
     * Obtiene todas las ubicaciones disponibles
     * @return array
     */
    public function getUbicaciones(): array {
        return $this->ubicaciones;
    }
    
    /**
     * Cuenta mesas por estado
     * @return array
     */
    public function countByEstado(): array {
        $sql = "SELECT 
                    SUM(CASE WHEN Estado = 0 THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN Estado = 1 THEN 1 ELSE 0 END) as ocupadas,
                    COUNT(*) as total
                FROM {$this->table}";
        
        $result = $this->query($sql);
        return !empty($result) ? $result[0] : ['disponibles' => 0, 'ocupadas' => 0, 'total' => 0];
    }
    
    /**
     * Obtiene estadisticas de ocupacion por ubicacion
     * @return array
     */
    public function getEstadisticasPorUbicacion(): array {
        $sql = "SELECT 
                    Ubicacion,
                    COUNT(*) as total,
                    SUM(CASE WHEN Estado = 0 THEN 1 ELSE 0 END) as disponibles,
                    SUM(CASE WHEN Estado = 1 THEN 1 ELSE 0 END) as ocupadas
                FROM {$this->table}
                GROUP BY Ubicacion
                ORDER BY Ubicacion";
        
        $resultados = $this->query($sql);
        
        // Agregar texto de ubicacion
        foreach ($resultados as &$row) {
            $row['ubicacion_texto'] = $this->getUbicacionTexto($row['Ubicacion']);
        }
        
        return $resultados;
    }
    
    /**
     * Verifica si existe una mesa
     * @param int $idMesa ID de la mesa
     * @return bool
     */
    public function exists(int $idMesa): bool {
        return $this->find($idMesa) !== null;
    }
    
    /**
     * Crea mesa validando ID unico
     * @param array $data Datos de la mesa
     * @return bool
     */
    public function createMesa(array $data): bool {
        // Verificar si ya existe
        if ($this->exists((int)$data['id_Mesa'])) {
            $this->addError("Ya existe una mesa con ese ID");
            return false;
        }
        
        // Validar capacidad
        if ((int)$data['Capacidad'] < 1 || (int)$data['Capacidad'] > 20) {
            $this->addError("La capacidad debe estar entre 1 y 20");
            return false;
        }
        
        $sql = "INSERT INTO {$this->table} (id_Mesa, Capacidad, Ubicacion, Estado) 
                VALUES (:id, :capacidad, :ubicacion, :estado)";
        
        return $this->execute($sql, [
            'id' => $data['id_Mesa'],
            'capacidad' => $data['Capacidad'],
            'ubicacion' => $data['Ubicacion'],
            'estado' => $data['Estado'] ?? MESA_DISPONIBLE
        ]);
    }
}
?>
