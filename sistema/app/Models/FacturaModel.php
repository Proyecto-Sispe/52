<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Factura
 * Maneja todas las operaciones de base de datos relacionadas con facturas
 */
class FacturaModel extends Model
{
    protected $table = 'Factura';
    protected $primaryKey = 'id_factura';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_pedido',
        'Fecha_hora',
        'Total'
    ];

    /**
     * Obtiene todas las facturas con informacion relacionada
     * @param array $filtros
     * @return array
     */
    public function obtenerTodas(array $filtros = []): array
    {
        try {
            $builder = $this->db->table('Factura f');
            $builder->select('f.*, 
                p.id_mesa, p.fecha_pedido,
                CONCAT(cliente.Nom1_usu, " ", cliente.Ape1_usu) as cliente_nombre,
                CONCAT(mesero.Nom1_usu, " ", mesero.Ape1_usu) as mesero_nombre');
            $builder->join('Pedido p', 'p.id_pedido = f.id_pedido', 'left');
            $builder->join('Persona cliente', 'cliente.id_usuario = p.cliente_id_usuario AND cliente.pkfk_Tipo_doc = p.cliente_tipo_doc', 'left');
            $builder->join('Persona mesero', 'mesero.id_usuario = p.mesero_id_usuario AND mesero.pkfk_Tipo_doc = p.mesero_tipo_doc', 'left');

            if (isset($filtros['fecha_inicio']) && !empty($filtros['fecha_inicio'])) {
                $builder->where('DATE(f.Fecha_hora) >=', $filtros['fecha_inicio']);
            }

            if (isset($filtros['fecha_fin']) && !empty($filtros['fecha_fin'])) {
                $builder->where('DATE(f.Fecha_hora) <=', $filtros['fecha_fin']);
            }

            if (isset($filtros['mesa']) && !empty($filtros['mesa'])) {
                $builder->where('p.id_mesa', $filtros['mesa']);
            }

            if (isset($filtros['mesero']) && !empty($filtros['mesero'])) {
                $builder->where('p.mesero_id_usuario', $filtros['mesero']);
            }

            $builder->orderBy('f.Fecha_hora', 'DESC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener facturas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una factura por ID con detalles
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $builder = $this->db->table('Factura f');
            $builder->select('f.*, 
                p.id_mesa, p.fecha_pedido, p.observaciones,
                CONCAT(cliente.Nom1_usu, " ", cliente.Ape1_usu) as cliente_nombre,
                cliente.id_usuario as cliente_id,
                CONCAT(mesero.Nom1_usu, " ", mesero.Ape1_usu) as mesero_nombre,
                m.Ubicacion as mesa_ubicacion');
            $builder->join('Pedido p', 'p.id_pedido = f.id_pedido', 'left');
            $builder->join('Mesa m', 'm.id_Mesa = p.id_mesa', 'left');
            $builder->join('Persona cliente', 'cliente.id_usuario = p.cliente_id_usuario AND cliente.pkfk_Tipo_doc = p.cliente_tipo_doc', 'left');
            $builder->join('Persona mesero', 'mesero.id_usuario = p.mesero_id_usuario AND mesero.pkfk_Tipo_doc = p.mesero_tipo_doc', 'left');
            $builder->where('f.id_factura', $id);

            $factura = $builder->get()->getRowArray();

            if ($factura) {
                // Obtener detalles del pedido
                $factura['detalles'] = $this->obtenerDetalles($factura['id_pedido']);
                // Obtener metodos de pago
                $factura['pagos'] = $this->obtenerMetodosPago($id);
            }

            return $factura ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener factura: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene una factura por ID de pedido
     * @param int $pedidoId
     * @return array|null
     */
    public function obtenerPorPedido(int $pedidoId): ?array
    {
        try {
            $factura = $this->db->table('Factura')
                ->where('id_pedido', $pedidoId)
                ->get()
                ->getRowArray();

            if ($factura) {
                return $this->obtenerPorId($factura['id_factura']);
            }

            return null;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener factura por pedido: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene los detalles de productos de una factura
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
            log_message('error', 'Error al obtener detalles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los metodos de pago de una factura
     * @param int $facturaId
     * @return array
     */
    public function obtenerMetodosPago(int $facturaId): array
    {
        try {
            $builder = $this->db->table('Factura_has_Metodo_pago fmp');
            $builder->select('fmp.*, mp.Tipo_pago');
            $builder->join('Metodo_pago mp', 'mp.id_pago = fmp.pkfk_metodo_pago', 'left');
            $builder->where('fmp.pkfk_n_factura', $facturaId);

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener metodos de pago: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea una nueva factura
     * @param int $pedidoId
     * @param float $total
     * @return int|bool ID de la factura creada o false
     */
    public function crearFactura(int $pedidoId, float $total)
    {
        try {
            // Verificar que no exista factura para este pedido
            $existe = $this->db->table('Factura')
                ->where('id_pedido', $pedidoId)
                ->countAllResults();

            if ($existe > 0) {
                throw new Exception('Ya existe una factura para este pedido');
            }

            $datosFactura = [
                'id_pedido' => $pedidoId,
                'Fecha_hora' => date('Y-m-d H:i:s'),
                'Total' => $total
            ];

            $this->db->table('Factura')->insert($datosFactura);

            return $this->db->insertID();

        } catch (Exception $e) {
            log_message('error', 'Error al crear factura: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un pago para una factura
     * @param int $facturaId
     * @param int $metodoPagoId
     * @param float $monto
     * @return bool
     */
    public function registrarPago(int $facturaId, int $metodoPagoId, float $monto): bool
    {
        try {
            $datosPago = [
                'pkfk_n_factura' => $facturaId,
                'pkfk_metodo_pago' => $metodoPagoId,
                'monto' => $monto
            ];

            return $this->db->table('Factura_has_Metodo_pago')->insert($datosPago);

        } catch (Exception $e) {
            log_message('error', 'Error al registrar pago: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene los metodos de pago disponibles
     * @return array
     */
    public function obtenerMetodosPagoDisponibles(): array
    {
        try {
            return $this->db->table('Metodo_pago')
                ->orderBy('Tipo_pago', 'ASC')
                ->get()
                ->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener metodos de pago: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadisticas de ventas
     * @param string|null $fechaInicio
     * @param string|null $fechaFin
     * @return array
     */
    public function obtenerEstadisticas(?string $fechaInicio = null, ?string $fechaFin = null): array
    {
        try {
            $hoy = date('Y-m-d');
            $mesActual = date('Y-m');

            $stats = [
                'ventas_hoy' => 0,
                'ventas_mes' => 0,
                'total_facturas_hoy' => 0,
                'total_facturas_mes' => 0,
                'promedio_venta' => 0,
                'por_metodo_pago' => []
            ];

            // Ventas del dia
            $ventasHoy = $this->db->table('Factura')
                ->where('DATE(Fecha_hora)', $hoy)
                ->selectSum('Total')
                ->selectCount('id_factura', 'cantidad')
                ->get()
                ->getRowArray();

            $stats['ventas_hoy'] = (float) ($ventasHoy['Total'] ?? 0);
            $stats['total_facturas_hoy'] = (int) ($ventasHoy['cantidad'] ?? 0);

            // Ventas del mes
            $ventasMes = $this->db->table('Factura')
                ->where('DATE_FORMAT(Fecha_hora, "%Y-%m")', $mesActual)
                ->selectSum('Total')
                ->selectCount('id_factura', 'cantidad')
                ->get()
                ->getRowArray();

            $stats['ventas_mes'] = (float) ($ventasMes['Total'] ?? 0);
            $stats['total_facturas_mes'] = (int) ($ventasMes['cantidad'] ?? 0);

            // Promedio de venta
            if ($stats['total_facturas_mes'] > 0) {
                $stats['promedio_venta'] = $stats['ventas_mes'] / $stats['total_facturas_mes'];
            }

            // Ventas por metodo de pago
            $porMetodo = $this->db->table('Factura_has_Metodo_pago fmp')
                ->select('mp.Tipo_pago, SUM(fmp.monto) as total')
                ->join('Metodo_pago mp', 'mp.id_pago = fmp.pkfk_metodo_pago')
                ->join('Factura f', 'f.id_factura = fmp.pkfk_n_factura')
                ->where('DATE_FORMAT(f.Fecha_hora, "%Y-%m")', $mesActual)
                ->groupBy('mp.id_pago')
                ->get()
                ->getResultArray();

            foreach ($porMetodo as $metodo) {
                $stats['por_metodo_pago'][$metodo['Tipo_pago']] = (float) $metodo['total'];
            }

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene facturas del dia
     * @return array
     */
    public function obtenerFacturasHoy(): array
    {
        return $this->obtenerTodas([
            'fecha_inicio' => date('Y-m-d'),
            'fecha_fin' => date('Y-m-d')
        ]);
    }

    /**
     * Genera reporte de ventas
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public function generarReporteVentas(string $fechaInicio, string $fechaFin): array
    {
        try {
            $reporte = [
                'periodo' => [
                    'inicio' => $fechaInicio,
                    'fin' => $fechaFin
                ],
                'facturas' => $this->obtenerTodas([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]),
                'resumen' => []
            ];

            // Calcular resumen
            $totalVentas = 0;
            $totalFacturas = count($reporte['facturas']);

            foreach ($reporte['facturas'] as $factura) {
                $totalVentas += (float) $factura['Total'];
            }

            $reporte['resumen'] = [
                'total_facturas' => $totalFacturas,
                'total_ventas' => $totalVentas,
                'promedio_venta' => $totalFacturas > 0 ? $totalVentas / $totalFacturas : 0
            ];

            return $reporte;

        } catch (Exception $e) {
            log_message('error', 'Error al generar reporte: ' . $e->getMessage());
            return [];
        }
    }
}
