<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Pedido
 * Maneja operaciones CRUD y logica de negocio para pedidos
 */
class PedidoModel extends Model {
    protected string $table = 'Pedido';
    protected array $fillable = [
        'pkfk_id_factura', 
        'pkfk_id_menu', 
        'cantidad', 
        'observaciones', 
        'valor_venta',
        'estado',
        'fecha_pedido'
    ];
    
    /**
     * Obtiene todos los pedidos con detalles completos
     * @return array
     */
    public function getAllWithDetails(): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, c.nom_categoria,
                       f.id_factura, f.pkfk_id_Mesa, me.Capacidad,
                       COALESCE(p.estado, 'pendiente') as estado,
                       COALESCE(p.fecha_pedido, f.Fecha_hora) as fecha_pedido
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                JOIN Mesa me ON f.pkfk_id_Mesa = me.id_Mesa
                ORDER BY p.fecha_pedido DESC";
        
        return $this->query($sql);
    }
    
    /**
     * Obtiene pedidos por estado
     * @param string $estado Estado del pedido
     * @return array
     */
    public function getByEstado(string $estado): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, c.nom_categoria,
                       f.id_factura, f.pkfk_id_Mesa,
                       COALESCE(p.fecha_pedido, f.Fecha_hora) as fecha_pedido
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                WHERE COALESCE(p.estado, 'pendiente') = :estado
                ORDER BY p.fecha_pedido ASC";
        
        return $this->query($sql, ['estado' => $estado]);
    }
    
    /**
     * Obtiene pedidos pendientes (para cocinero)
     * @return array
     */
    public function getPendientes(): array {
        return $this->getByEstado(ESTADO_PENDIENTE);
    }
    
    /**
     * Obtiene pedidos en preparacion (para cocinero)
     * @return array
     */
    public function getEnPreparacion(): array {
        return $this->getByEstado(ESTADO_EN_PREPARACION);
    }
    
    /**
     * Obtiene pedidos listos (para mesero)
     * @return array
     */
    public function getListos(): array {
        return $this->getByEstado(ESTADO_LISTO);
    }
    
    /**
     * Obtiene pedidos activos (no entregados ni cancelados)
     * @return array
     */
    public function getActivos(): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, c.nom_categoria,
                       f.id_factura, f.pkfk_id_Mesa,
                       COALESCE(p.estado, 'pendiente') as estado,
                       COALESCE(p.fecha_pedido, f.Fecha_hora) as fecha_pedido
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                WHERE COALESCE(p.estado, 'pendiente') NOT IN ('entregado', 'cancelado')
                ORDER BY 
                    CASE COALESCE(p.estado, 'pendiente')
                        WHEN 'listo' THEN 1
                        WHEN 'en_preparacion' THEN 2
                        WHEN 'pendiente' THEN 3
                    END,
                    p.fecha_pedido ASC";
        
        return $this->query($sql);
    }
    
    /**
     * Obtiene pedidos por mesa
     * @param int $idMesa ID de la mesa
     * @return array
     */
    public function getByMesa(int $idMesa): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, c.nom_categoria,
                       COALESCE(p.estado, 'pendiente') as estado,
                       COALESCE(p.fecha_pedido, f.Fecha_hora) as fecha_pedido
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                WHERE f.pkfk_id_Mesa = :idMesa
                ORDER BY p.fecha_pedido DESC";
        
        return $this->query($sql, ['idMesa' => $idMesa]);
    }
    
    /**
     * Obtiene pedidos activos por mesa
     * @param int $idMesa ID de la mesa
     * @return array
     */
    public function getActivosByMesa(int $idMesa): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, c.nom_categoria,
                       COALESCE(p.estado, 'pendiente') as estado,
                       COALESCE(p.fecha_pedido, f.Fecha_hora) as fecha_pedido
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                WHERE f.pkfk_id_Mesa = :idMesa
                AND COALESCE(p.estado, 'pendiente') NOT IN ('entregado', 'cancelado')
                ORDER BY p.fecha_pedido ASC";
        
        return $this->query($sql, ['idMesa' => $idMesa]);
    }
    
    /**
     * Cambia el estado de un pedido
     * @param int $idFactura ID de factura
     * @param int $idMenu ID del producto
     * @param string $nuevoEstado Nuevo estado
     * @return bool
     */
    public function cambiarEstado(int $idFactura, int $idMenu, string $nuevoEstado): bool {
        // Validar estado valido
        $estadosValidos = [
            ESTADO_PENDIENTE, 
            ESTADO_EN_PREPARACION, 
            ESTADO_LISTO, 
            ESTADO_ENTREGADO, 
            ESTADO_CANCELADO
        ];
        
        if (!in_array($nuevoEstado, $estadosValidos)) {
            $this->addError("Estado no valido: {$nuevoEstado}");
            return false;
        }
        
        $sql = "UPDATE Pedido 
                SET estado = :estado 
                WHERE pkfk_id_factura = :idFactura AND pkfk_id_menu = :idMenu";
        
        return $this->execute($sql, [
            'estado' => $nuevoEstado,
            'idFactura' => $idFactura,
            'idMenu' => $idMenu
        ]);
    }
    
    /**
     * Inicia preparacion de un pedido
     * @param int $idFactura ID de factura
     * @param int $idMenu ID del producto
     * @return bool
     */
    public function iniciarPreparacion(int $idFactura, int $idMenu): bool {
        return $this->cambiarEstado($idFactura, $idMenu, ESTADO_EN_PREPARACION);
    }
    
    /**
     * Marca pedido como listo
     * @param int $idFactura ID de factura
     * @param int $idMenu ID del producto
     * @return bool
     */
    public function marcarListo(int $idFactura, int $idMenu): bool {
        return $this->cambiarEstado($idFactura, $idMenu, ESTADO_LISTO);
    }
    
    /**
     * Marca pedido como entregado
     * @param int $idFactura ID de factura
     * @param int $idMenu ID del producto
     * @return bool
     */
    public function marcarEntregado(int $idFactura, int $idMenu): bool {
        return $this->cambiarEstado($idFactura, $idMenu, ESTADO_ENTREGADO);
    }
    
    /**
     * Cancela un pedido
     * @param int $idFactura ID de factura
     * @param int $idMenu ID del producto
     * @return bool
     */
    public function cancelar(int $idFactura, int $idMenu): bool {
        return $this->cambiarEstado($idFactura, $idMenu, ESTADO_CANCELADO);
    }
    
    /**
     * Crea un nuevo pedido
     * @param array $data Datos del pedido
     * @return bool
     */
    public function createPedido(array $data): bool {
        // Validar cantidad
        if ((int)$data['cantidad'] < 1) {
            $this->addError("La cantidad debe ser al menos 1");
            return false;
        }
        
        $sql = "INSERT INTO Pedido (pkfk_id_factura, pkfk_id_menu, cantidad, 
                                    observaciones, valor_venta, estado, fecha_pedido)
                VALUES (:idFactura, :idMenu, :cantidad, :observaciones, 
                        :valorVenta, :estado, NOW())";
        
        return $this->execute($sql, [
            'idFactura' => $data['pkfk_id_factura'],
            'idMenu' => $data['pkfk_id_menu'],
            'cantidad' => (int)$data['cantidad'],
            'observaciones' => $data['observaciones'] ?? null,
            'valorVenta' => (float)$data['valor_venta'],
            'estado' => ESTADO_PENDIENTE
        ]);
    }
    
    /**
     * Calcula el total de una factura
     * @param int $idFactura ID de la factura
     * @return float
     */
    public function calcularTotalFactura(int $idFactura): float {
        $sql = "SELECT SUM(valor_venta * cantidad) as total 
                FROM Pedido 
                WHERE pkfk_id_factura = :idFactura
                AND COALESCE(estado, 'pendiente') != 'cancelado'";
        
        $result = $this->query($sql, ['idFactura' => $idFactura]);
        
        return !empty($result) ? (float)($result[0]['total'] ?? 0) : 0.0;
    }
    
    /**
     * Cuenta pedidos por estado
     * @return array
     */
    public function countByEstado(): array {
        $sql = "SELECT 
                    COALESCE(estado, 'pendiente') as estado,
                    COUNT(*) as total
                FROM Pedido
                GROUP BY COALESCE(estado, 'pendiente')";
        
        $result = $this->query($sql);
        $counts = [];
        
        foreach ($result as $row) {
            $counts[$row['estado']] = (int)$row['total'];
        }
        
        // Asegurar que todos los estados esten presentes
        $estadosValidos = [
            ESTADO_PENDIENTE, 
            ESTADO_EN_PREPARACION, 
            ESTADO_LISTO, 
            ESTADO_ENTREGADO, 
            ESTADO_CANCELADO
        ];
        
        foreach ($estadosValidos as $estado) {
            if (!isset($counts[$estado])) {
                $counts[$estado] = 0;
            }
        }
        
        return $counts;
    }
    
    /**
     * Obtiene pedidos urgentes (mas de X minutos)
     * @param int $minutosLimite Tiempo limite en minutos
     * @return array
     */
    public function getUrgentes(int $minutosLimite = 15): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, f.pkfk_id_Mesa,
                       COALESCE(p.estado, 'pendiente') as estado,
                       TIMESTAMPDIFF(MINUTE, COALESCE(p.fecha_pedido, f.Fecha_hora), NOW()) as minutos_transcurridos
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                WHERE COALESCE(p.estado, 'pendiente') IN ('pendiente', 'en_preparacion')
                AND TIMESTAMPDIFF(MINUTE, COALESCE(p.fecha_pedido, f.Fecha_hora), NOW()) >= :minutos
                ORDER BY minutos_transcurridos DESC";
        
        return $this->query($sql, ['minutos' => $minutosLimite]);
    }
    
    /**
     * Busca pedidos por producto
     * @param string $termino Termino de busqueda
     * @return array
     */
    public function searchByProducto(string $termino): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, f.pkfk_id_Mesa,
                       COALESCE(p.estado, 'pendiente') as estado,
                       COALESCE(p.fecha_pedido, f.Fecha_hora) as fecha_pedido
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Factura f ON p.pkfk_id_factura = f.id_factura
                WHERE m.Productos LIKE :termino
                ORDER BY p.fecha_pedido DESC";
        
        return $this->query($sql, ['termino' => "%{$termino}%"]);
    }
    
    /**
     * Obtiene estadisticas de tiempo de preparacion
     * @return array
     */
    public function getEstadisticasTiempo(): array {
        $sql = "SELECT 
                    AVG(TIMESTAMPDIFF(MINUTE, fecha_pedido, NOW())) as tiempo_promedio,
                    MAX(TIMESTAMPDIFF(MINUTE, fecha_pedido, NOW())) as tiempo_maximo,
                    MIN(TIMESTAMPDIFF(MINUTE, fecha_pedido, NOW())) as tiempo_minimo
                FROM Pedido
                WHERE estado = 'entregado'
                AND fecha_pedido IS NOT NULL";
        
        $result = $this->query($sql);
        return !empty($result) ? $result[0] : [];
    }
}
?>
