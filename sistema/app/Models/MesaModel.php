<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Mesa
 * Maneja todas las operaciones de base de datos relacionadas con mesas
 */
class MesaModel extends Model
{
    protected $table = 'Mesa';
    protected $primaryKey = 'id_Mesa';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_Mesa',
        'Capacidad',
        'Ubicacion',
        'Estado'
    ];

    protected $validationRules = [
        'id_Mesa' => 'required|numeric',
        'Capacidad' => 'required|numeric|greater_than[0]',
        'Ubicacion' => 'required|max_length[50]',
        'Estado' => 'required|numeric'
    ];

    /**
     * Estados de mesa
     */
    public const ESTADO_DISPONIBLE = 0;
    public const ESTADO_OCUPADA = 1;
    public const ESTADO_RESERVADA = 2;

    /**
     * Obtiene todas las mesas
     * @param array $filtros
     * @return array
     */
    public function obtenerTodas(array $filtros = []): array
    {
        try {
            $builder = $this->db->table('Mesa m');
            $builder->select('m.*, 
                (SELECT COUNT(*) FROM Pedido p WHERE p.id_mesa = m.id_Mesa AND p.estado NOT IN ("entregado")) as pedidos_activos');

            if (isset($filtros['estado'])) {
                $builder->where('m.Estado', $filtros['estado']);
            }

            if (isset($filtros['ubicacion']) && !empty($filtros['ubicacion'])) {
                $builder->where('m.Ubicacion', $filtros['ubicacion']);
            }

            if (isset($filtros['capacidad_min'])) {
                $builder->where('m.Capacidad >=', $filtros['capacidad_min']);
            }

            $builder->orderBy('m.id_Mesa', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener mesas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene mesas disponibles
     * @return array
     */
    public function obtenerDisponibles(): array
    {
        return $this->obtenerTodas(['estado' => self::ESTADO_DISPONIBLE]);
    }

    /**
     * Obtiene mesas ocupadas
     * @return array
     */
    public function obtenerOcupadas(): array
    {
        return $this->obtenerTodas(['estado' => self::ESTADO_OCUPADA]);
    }

    /**
     * Obtiene una mesa por ID
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $builder = $this->db->table('Mesa m');
            $builder->select('m.*, 
                (SELECT COUNT(*) FROM Pedido p WHERE p.id_mesa = m.id_Mesa AND p.estado NOT IN ("entregado")) as pedidos_activos');
            $builder->where('m.id_Mesa', $id);

            $resultado = $builder->get()->getRowArray();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener mesa: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cambia el estado de una mesa
     * @param int $id
     * @param int $estado
     * @return bool
     */
    public function cambiarEstado(int $id, int $estado): bool
    {
        try {
            return $this->db->table('Mesa')
                ->where('id_Mesa', $id)
                ->update(['Estado' => $estado]);

        } catch (Exception $e) {
            log_message('error', 'Error al cambiar estado de mesa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ocupa una mesa
     * @param int $id
     * @return bool
     */
    public function ocuparMesa(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_OCUPADA);
    }

    /**
     * Libera una mesa
     * @param int $id
     * @return bool
     */
    public function liberarMesa(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_DISPONIBLE);
    }

    /**
     * Crea una nueva mesa
     * @param array $datos
     * @return bool
     */
    public function crearMesa(array $datos): bool
    {
        try {
            $datosMesa = [
                'id_Mesa' => $datos['id_Mesa'],
                'Capacidad' => $datos['Capacidad'],
                'Ubicacion' => $datos['Ubicacion'],
                'Estado' => $datos['Estado'] ?? self::ESTADO_DISPONIBLE
            ];

            return $this->db->table('Mesa')->insert($datosMesa);

        } catch (Exception $e) {
            log_message('error', 'Error al crear mesa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una mesa
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizarMesa(int $id, array $datos): bool
    {
        try {
            $datosActualizar = [];

            if (isset($datos['Capacidad'])) {
                $datosActualizar['Capacidad'] = $datos['Capacidad'];
            }
            if (isset($datos['Ubicacion'])) {
                $datosActualizar['Ubicacion'] = $datos['Ubicacion'];
            }
            if (isset($datos['Estado'])) {
                $datosActualizar['Estado'] = $datos['Estado'];
            }

            if (empty($datosActualizar)) {
                return true;
            }

            return $this->db->table('Mesa')
                ->where('id_Mesa', $id)
                ->update($datosActualizar);

        } catch (Exception $e) {
            log_message('error', 'Error al actualizar mesa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una mesa
     * @param int $id
     * @return bool
     */
    public function eliminarMesa(int $id): bool
    {
        try {
            // Verificar que no tenga pedidos activos
            $mesa = $this->obtenerPorId($id);
            if ($mesa && $mesa['pedidos_activos'] > 0) {
                throw new Exception('No se puede eliminar una mesa con pedidos activos');
            }

            return $this->db->table('Mesa')
                ->where('id_Mesa', $id)
                ->delete();

        } catch (Exception $e) {
            log_message('error', 'Error al eliminar mesa: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene estadisticas de mesas
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $todas = $this->obtenerTodas();
            
            $stats = [
                'total' => count($todas),
                'disponibles' => 0,
                'ocupadas' => 0,
                'reservadas' => 0,
                'capacidad_total' => 0,
                'por_ubicacion' => []
            ];

            foreach ($todas as $mesa) {
                $stats['capacidad_total'] += $mesa['Capacidad'];
                
                switch ($mesa['Estado']) {
                    case self::ESTADO_DISPONIBLE:
                        $stats['disponibles']++;
                        break;
                    case self::ESTADO_OCUPADA:
                        $stats['ocupadas']++;
                        break;
                    case self::ESTADO_RESERVADA:
                        $stats['reservadas']++;
                        break;
                }

                $ubicacion = $mesa['Ubicacion'];
                if (!isset($stats['por_ubicacion'][$ubicacion])) {
                    $stats['por_ubicacion'][$ubicacion] = 0;
                }
                $stats['por_ubicacion'][$ubicacion]++;
            }

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas de mesas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el texto del estado
     * @param int $estado
     * @return string
     */
    public function getTextoEstado(int $estado): string
    {
        $estados = [
            self::ESTADO_DISPONIBLE => 'Disponible',
            self::ESTADO_OCUPADA => 'Ocupada',
            self::ESTADO_RESERVADA => 'Reservada'
        ];

        return $estados[$estado] ?? 'Desconocido';
    }

    /**
     * Verifica codigo de acceso de mesa
     * @param string $codigo
     * @return array|null
     */
    public function verificarCodigoAcceso(string $codigo): ?array
    {
        try {
            $builder = $this->db->table('Sesion_Mesa sm');
            $builder->select('sm.*, m.Capacidad, m.Ubicacion, m.Estado as estado_mesa');
            $builder->join('Mesa m', 'm.id_Mesa = sm.id_mesa');
            $builder->where('sm.codigo_acceso', $codigo);
            $builder->where('sm.activa', 1);
            $builder->where('sm.fecha_fin IS NULL');

            $resultado = $builder->get()->getRowArray();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al verificar codigo de acceso: ' . $e->getMessage());
            return null;
        }
    }
}