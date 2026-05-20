<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Pedido
 * Maneja todas las operaciones de base de datos relacionadas con pedidos
 */
class PedidoModel extends Model
{
    protected $table = 'Pedido';
    protected $primaryKey = 'id_pedido';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_mesa',
        'mesero_tipo_doc',
        'mesero_id_usuario',
        'cliente_tipo_doc',
        'cliente_id_usuario',
        'fecha_pedido',
        'estado',
        'prioridad',
        'cocinero_asignado',
        'tiempo_estimado',
        'observaciones'
    ];

    /**
     * Estados de pedido
     */
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_EN_PREPARACION = 'en_preparacion';
    public const ESTADO_LISTO = 'listo';
    public const ESTADO_ENTREGADO = 'entregado';

    /**
     * Prioridades
     */
    public const PRIORIDAD_NORMAL = 'normal';
    public const PRIORIDAD_URGENTE = 'urgente';

    /**
     * Obtiene todos los pedidos con informacion relacionada
     * @param array $filtros
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        try {
            $builder = $this->db->table('Pedido p');
            $builder->select('p.*, 
                m.Capacidad as mesa_capacidad, m.Ubicacion as mesa_ubicacion,
                CONCAT(mesero.Nom1_usu, " ", mesero.Ape1_usu) as mesero_nombre,
                CONCAT(cliente.Nom1_usu, " ", cliente.Ape1_usu) as cliente_nombre,
                CONCAT(cocinero.Nom1_usu, " ", cocinero.Ape1_usu) as cocinero_nombre');
            $builder->join('Mesa m', 'm.id_Mesa = p.id_mesa', 'left');
            $builder->join('Persona mesero', 'mesero.id_usuario = p.mesero_id_usuario AND mesero.pkfk_Tipo_doc = p.mesero_tipo_doc', 'left');
            $builder->join('Persona cliente', 'cliente.id_usuario = p.cliente_id_usuario AND cliente.pkfk_Tipo_doc = p.cliente_tipo_doc', 'left');
            $builder->join('Persona cocinero', 'cocinero.id_usuario = p.cocinero_asignado', 'left');

            if (isset($filtros['estado']) && !empty($filtros['estado'])) {
                if (is_array($filtros['estado'])) {
                    $builder->whereIn('p.estado', $filtros['estado']);
                } else {
                    $builder->where('p.estado', $filtros['estado']);
                }
            }

            if (isset($filtros['mesa']) && !empty($filtros['mesa'])) {
                $builder->where('p.id_mesa', $filtros['mesa']);
            }

            if (isset($filtros['mesero']) && !empty($filtros['mesero'])) {
                $builder->where('p.mesero_id_usuario', $filtros['mesero']);
            }

            if (isset($filtros['cocinero']) && !empty($filtros['cocinero'])) {
                $builder->where('p.cocinero_asignado', $filtros['cocinero']);
            }

            if (isset($filtros['prioridad']) && !empty($filtros['prioridad'])) {
                $builder->where('p.prioridad', $filtros['prioridad']);
            }

            if (isset($filtros['fecha']) && !empty($filtros['fecha'])) {
                $builder->where('DATE(p.fecha_pedido)', $filtros['fecha']);
            }

            if (isset($filtros['excluir_entregados']) && $filtros['excluir_entregados']) {
                $builder->where('p.estado !=', self::ESTADO_ENTREGADO);
            }

            $builder->orderBy('p.prioridad', 'DESC');
            $builder->orderBy('p.fecha_pedido', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener pedidos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene pedidos por estado
     * @param string $estado
     * @return array
     */
    public function obtenerPorEstado(string $estado): array
    {
        return $this->obtenerTodos(['estado' => $estado]);
    }

    /**
     * Obtiene pedidos pendientes
     * @return array
     */
    public function obtenerPendientes(): array
    {
        return $this->obtenerPorEstado(self::ESTADO_PENDIENTE);
    }

    /**
     * Obtiene pedidos en preparacion
     * @return array
     */
    public function obtenerEnPreparacion(): array
    {
        return $this->obtenerPorEstado(self::ESTADO_EN_PREPARACION);
    }

    /**
     * Obtiene pedidos listos
     * @return array
     */
    public function obtenerListos(): array
    {
        return $this->obtenerPorEstado(self::ESTADO_LISTO);
    }

    /**
     * Obtiene pedidos activos (no entregados)
     * @return array
     */
    public function obtenerActivos(): array
    {
        return $this->obtenerTodos([
            'estado' => [self::ESTADO_PENDIENTE, self::ESTADO_EN_PREPARACION, self::ESTADO_LISTO]
        ]);
    }

    /**
     * Obtiene un pedido por ID con detalles
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $builder = $this->db->table('Pedido p');
            $builder->select('p.*, 
                m.Capacidad as mesa_capacidad, m.Ubicacion as mesa_ubicacion,
                CONCAT(mesero.Nom1_usu, " ", mesero.Ape1_usu) as mesero_nombre,
                CONCAT(cliente.Nom1_usu, " ", cliente.Ape1_usu) as cliente_nombre,
                CONCAT(cocinero.Nom1_usu, " ", cocinero.Ape1_usu) as cocinero_nombre');
            $builder->join('Mesa m', 'm.id_Mesa = p.id_mesa', 'left');
            $builder->join('Persona mesero', 'mesero.id_usuario = p.mesero_id_usuario AND mesero.pkfk_Tipo_doc = p.mesero_tipo_doc', 'left');
            $builder->join('Persona cliente', 'cliente.id_usuario = p.cliente_id_usuario AND cliente.pkfk_Tipo_doc = p.cliente_tipo_doc', 'left');
            $builder->join('Persona cocinero', 'cocinero.id_usuario = p.cocinero_asignado', 'left');
            $builder->where('p.id_pedido', $id);

            $pedido = $builder->get()->getRowArray();

            if ($pedido) {
                // Obtener detalles del pedido
                $pedido['detalles'] = $this->obtenerDetalles($id);
                $pedido['total'] = $this->calcularTotal($id);
            }

            return $pedido ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener pedido: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los detalles de un pedido
     * @param int $pedidoId
     * @return array
     */
    public function obtenerDetalles(int $pedidoId): array
    {
        try {
            $builder = $this->db->table('Detalle_Pedido dp');
            $builder->select('dp.*, m.Productos as producto_nombre, m.Precio as precio_unitario');
            $builder->join('Menu m', 'm.id_menu = dp.id_menu', 'left');
            $builder->where('dp.id_pedido', $pedidoId);

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener detalles del pedido: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula el total de un pedido
     * @param int $pedidoId
     * @return float
     */
    public function calcularTotal(int $pedidoId): float
    {
        try {
            $resultado = $this->db->table('Detalle_Pedido')
                ->selectSum('valor_venta')
                ->where('id_pedido', $pedidoId)
                ->get()
                ->getRowArray();

            return (float) ($resultado['valor_venta'] ?? 0);

        } catch (Exception $e) {
            log_message('error', 'Error al calcular total: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Crea un nuevo pedido
     * @param array $datos
     * @return int|bool ID del pedido creado o false
     */
    public function crearPedido(array $datos)
    {
        try {
            $datosPedido = [
                'id_mesa' => $datos['id_mesa'],
                'mesero_tipo_doc' => $datos['mesero_tipo_doc'] ?? 1,
                'mesero_id_usuario' => $datos['mesero_id_usuario'],
                'cliente_tipo_doc' => $datos['cliente_tipo_doc'] ?? null,
                'cliente_id_usuario' => $datos['cliente_id_usuario'] ?? null,
                'fecha_pedido' => date('Y-m-d H:i:s'),
                'estado' => self::ESTADO_PENDIENTE,
                'prioridad' => $datos['prioridad'] ?? self::PRIORIDAD_NORMAL,
                'tiempo_estimado' => $datos['tiempo_estimado'] ?? 15,
                'observaciones' => $datos['observaciones'] ?? null
            ];

            $this->db->table('Pedido')->insert($datosPedido);
            
            return $this->db->insertID();

        } catch (Exception $e) {
            log_message('error', 'Error al crear pedido: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Agrega un producto al pedido
     * @param int $pedidoId
     * @param int $menuId
     * @param int $cantidad
     * @param string|null $observaciones
     * @return bool
     */
    public function agregarProducto(int $pedidoId, int $menuId, int $cantidad, ?string $observaciones = null): bool
    {
        try {
            // El trigger calculara el valor_venta automaticamente
            $datosDetalle = [
                'id_pedido' => $pedidoId,
                'id_menu' => $menuId,
                'cantidad' => $cantidad,
                'valor_venta' => 0, // El trigger lo calcula
                'observaciones' => $observaciones
            ];

            return $this->db->table('Detalle_Pedido')->insert($datosDetalle);

        } catch (Exception $e) {
            log_message('error', 'Error al agregar producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia el estado de un pedido
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        try {
            $estadosValidos = [
                self::ESTADO_PENDIENTE,
                self::ESTADO_EN_PREPARACION,
                self::ESTADO_LISTO,
                self::ESTADO_ENTREGADO
            ];

            if (!in_array($estado, $estadosValidos)) {
                throw new Exception('Estado no valido');
            }

            return $this->db->table('Pedido')
                ->where('id_pedido', $id)
                ->update(['estado' => $estado]);

        } catch (Exception $e) {
            log_message('error', 'Error al cambiar estado: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Asigna un cocinero a un pedido
     * @param int $pedidoId
     * @param int $cocineroId
     * @return bool
     */
    public function asignarCocinero(int $pedidoId, int $cocineroId): bool
    {
        try {
            return $this->db->table('Pedido')
                ->where('id_pedido', $pedidoId)
                ->update([
                    'cocinero_asignado' => $cocineroId,
                    'estado' => self::ESTADO_EN_PREPARACION
                ]);

        } catch (Exception $e) {
            log_message('error', 'Error al asignar cocinero: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca un pedido como listo
     * @param int $id
     * @return bool
     */
    public function marcarListo(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_LISTO);
    }

    /**
     * Marca un pedido como entregado
     * @param int $id
     * @return bool
     */
    public function marcarEntregado(int $id): bool
    {
        return $this->cambiarEstado($id, self::ESTADO_ENTREGADO);
    }

    /**
     * Obtiene pedidos por mesa
     * @param int $mesaId
     * @param bool $soloActivos
     * @return array
     */
    public function obtenerPorMesa(int $mesaId, bool $soloActivos = true): array
    {
        $filtros = ['mesa' => $mesaId];
        
        if ($soloActivos) {
            $filtros['excluir_entregados'] = true;
        }

        $pedidos = $this->obtenerTodos($filtros);

        // Agregar detalles a cada pedido
        foreach ($pedidos as &$pedido) {
            $pedido['detalles'] = $this->obtenerDetalles($pedido['id_pedido']);
            $pedido['total'] = $this->calcularTotal($pedido['id_pedido']);
        }

        return $pedidos;
    }

    /**
     * Obtiene estadisticas de pedidos
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $hoy = date('Y-m-d');

            $stats = [
                'total_hoy' => $this->db->table('Pedido')
                    ->where('DATE(fecha_pedido)', $hoy)
                    ->countAllResults(),
                'pendientes' => $this->db->table('Pedido')
                    ->where('estado', self::ESTADO_PENDIENTE)
                    ->countAllResults(),
                'en_preparacion' => $this->db->table('Pedido')
                    ->where('estado', self::ESTADO_EN_PREPARACION)
                    ->countAllResults(),
                'listos' => $this->db->table('Pedido')
                    ->where('estado', self::ESTADO_LISTO)
                    ->countAllResults(),
                'entregados_hoy' => $this->db->table('Pedido')
                    ->where('estado', self::ESTADO_ENTREGADO)
                    ->where('DATE(fecha_pedido)', $hoy)
                    ->countAllResults(),
                'urgentes' => $this->db->table('Pedido')
                    ->where('prioridad', self::PRIORIDAD_URGENTE)
                    ->whereIn('estado', [self::ESTADO_PENDIENTE, self::ESTADO_EN_PREPARACION])
                    ->countAllResults()
            ];

            // Calcular ventas del dia
            $ventas = $this->db->table('Factura f')
                ->join('Pedido p', 'p.id_pedido = f.id_pedido')
                ->where('DATE(f.Fecha_hora)', $hoy)
                ->selectSum('f.Total')
                ->get()
                ->getRowArray();

            $stats['ventas_hoy'] = (float) ($ventas['Total'] ?? 0);

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula tiempo transcurrido de un pedido
     * @param string $fechaPedido
     * @return int Minutos transcurridos
     */
    public function calcularTiempoTranscurrido(string $fechaPedido): int
    {
        $fecha = strtotime($fechaPedido);
        $ahora = time();
        return (int) round(($ahora - $fecha) / 60);
    }
}