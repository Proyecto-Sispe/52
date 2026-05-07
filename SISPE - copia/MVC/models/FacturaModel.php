<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Factura
 * Maneja operaciones CRUD y logica de negocio para facturas
 */
class FacturaModel extends Model {
    protected string $table = 'Factura';
    protected string $primaryKey = 'id_factura';
    protected array $fillable = [
        'Fecha_hora', 
        'Total', 
        'pkfk_id_Mesa', 
        'pkfk_Tipo_doc', 
        'pkfk_mesero_id_usuario',
        'pkfk_cliente_tipo_doc',
        'Cliente_Persona_id_usuario'
    ];
    
    /**
     * Obtiene todas las facturas con detalles
     * @return array
     */
    public function getAllWithDetails(): array {
        $sql = "SELECT f.*, 
                       m.id_Mesa, m.Capacidad as capacidad_mesa,
                       CONCAT(pm.Nom1_usu, ' ', pm.Ape1_usu) as nombre_mesero,
                       CONCAT(pc.Nom1_usu, ' ', pc.Ape1_usu) as nombre_cliente
                FROM Factura f
                JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
                LEFT JOIN Persona pm ON f.pkfk_mesero_id_usuario = pm.id_usuario 
                    AND f.pkfk_Tipo_doc = pm.pkfk_Tipo_doc
                LEFT JOIN Persona pc ON f.Cliente_Persona_id_usuario = pc.id_usuario 
                    AND f.pkfk_cliente_tipo_doc = pc.pkfk_Tipo_doc
                ORDER BY f.Fecha_hora DESC";
        
        return $this->query($sql);
    }
    
    /**
     * Obtiene facturas por mesa
     * @param int $idMesa ID de la mesa
     * @return array
     */
    public function getByMesa(int $idMesa): array {
        $sql = "SELECT f.*, 
                       CONCAT(pm.Nom1_usu, ' ', pm.Ape1_usu) as nombre_mesero
                FROM Factura f
                LEFT JOIN Persona pm ON f.pkfk_mesero_id_usuario = pm.id_usuario
                WHERE f.pkfk_id_Mesa = :idMesa
                ORDER BY f.Fecha_hora DESC";
        
        return $this->query($sql, ['idMesa' => $idMesa]);
    }
    
    /**
     * Obtiene factura activa de una mesa
     * @param int $idMesa ID de la mesa
     * @return array|null
     */
    public function getFacturaActivaMesa(int $idMesa): ?array {
        $sql = "SELECT f.* FROM Factura f
                JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
                WHERE f.pkfk_id_Mesa = :idMesa 
                AND m.Estado = 1
                ORDER BY f.Fecha_hora DESC
                LIMIT 1";
        
        $result = $this->query($sql, ['idMesa' => $idMesa]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Obtiene facturas por fecha
     * @param string $fecha Fecha en formato Y-m-d
     * @return array
     */
    public function getByFecha(string $fecha): array {
        $sql = "SELECT f.*, m.id_Mesa,
                       CONCAT(pm.Nom1_usu, ' ', pm.Ape1_usu) as nombre_mesero
                FROM Factura f
                JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
                LEFT JOIN Persona pm ON f.pkfk_mesero_id_usuario = pm.id_usuario
                WHERE DATE(f.Fecha_hora) = :fecha
                ORDER BY f.Fecha_hora DESC";
        
        return $this->query($sql, ['fecha' => $fecha]);
    }
    
    /**
     * Obtiene facturas entre fechas
     * @param string $fechaInicio Fecha inicio
     * @param string $fechaFin Fecha fin
     * @return array
     */
    public function getByRangoFechas(string $fechaInicio, string $fechaFin): array {
        $sql = "SELECT f.*, m.id_Mesa,
                       CONCAT(pm.Nom1_usu, ' ', pm.Ape1_usu) as nombre_mesero
                FROM Factura f
                JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
                LEFT JOIN Persona pm ON f.pkfk_mesero_id_usuario = pm.id_usuario
                WHERE DATE(f.Fecha_hora) BETWEEN :inicio AND :fin
                ORDER BY f.Fecha_hora DESC";
        
        return $this->query($sql, [
            'inicio' => $fechaInicio,
            'fin' => $fechaFin
        ]);
    }
    
    /**
     * Crea una nueva factura con transaccion
     * @param array $data Datos de la factura
     * @return int|false ID de factura creada o false
     */
    public function createFactura(array $data): int|false {
        try {
            $this->beginTransaction();
            
            // Obtener siguiente ID
            $sql = "SELECT COALESCE(MAX(id_factura), 0) + 1 as next_id FROM Factura";
            $result = $this->query($sql);
            $nextId = $result[0]['next_id'];
            
            // Insertar factura
            $sql = "INSERT INTO Factura (id_factura, Fecha_hora, Total, pkfk_id_Mesa, 
                                         pkfk_Tipo_doc, pkfk_mesero_id_usuario, 
                                         pkfk_cliente_tipo_doc, Cliente_Persona_id_usuario)
                    VALUES (:id, NOW(), :total, :idMesa, :tipoDocMesero, :idMesero, 
                            :tipoDocCliente, :idCliente)";
            
            $this->execute($sql, [
                'id' => $nextId,
                'total' => $data['Total'] ?? 0,
                'idMesa' => $data['pkfk_id_Mesa'],
                'tipoDocMesero' => $data['pkfk_Tipo_doc'],
                'idMesero' => $data['pkfk_mesero_id_usuario'],
                'tipoDocCliente' => $data['pkfk_cliente_tipo_doc'],
                'idCliente' => $data['Cliente_Persona_id_usuario']
            ]);
            
            // Ocupar la mesa
            $sqlMesa = "UPDATE Mesa SET Estado = 1 WHERE id_Mesa = :idMesa";
            $this->execute($sqlMesa, ['idMesa' => $data['pkfk_id_Mesa']]);
            
            $this->commit();
            return $nextId;
            
        } catch (Exception $e) {
            $this->rollback();
            $this->addError("Error al crear factura: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cierra una factura y libera la mesa
     * @param int $idFactura ID de la factura
     * @return bool
     */
    public function cerrarFactura(int $idFactura): bool {
        try {
            $this->beginTransaction();
            
            // Obtener factura
            $factura = $this->find($idFactura);
            
            if ($factura === null) {
                throw new Exception("Factura no encontrada");
            }
            
            // Calcular total de pedidos
            $sqlTotal = "SELECT SUM(valor_venta * cantidad) as total 
                         FROM Pedido 
                         WHERE pkfk_id_factura = :idFactura
                         AND COALESCE(estado, 'pendiente') != 'cancelado'";
            $result = $this->query($sqlTotal, ['idFactura' => $idFactura]);
            $total = $result[0]['total'] ?? 0;
            
            // Actualizar total de factura
            $this->update($idFactura, ['Total' => $total]);
            
            // Liberar mesa
            $sqlMesa = "UPDATE Mesa SET Estado = 0 WHERE id_Mesa = :idMesa";
            $this->execute($sqlMesa, ['idMesa' => $factura['pkfk_id_Mesa']]);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            $this->addError("Error al cerrar factura: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el total de ventas del dia
     * @return float
     */
    public function getTotalVentasHoy(): float {
        $sql = "SELECT SUM(Total) as total FROM Factura 
                WHERE DATE(Fecha_hora) = CURDATE()";
        
        $result = $this->query($sql);
        return (float)($result[0]['total'] ?? 0);
    }
    
    /**
     * Obtiene estadisticas de ventas
     * @param int $dias Numero de dias a analizar
     * @return array
     */
    public function getEstadisticasVentas(int $dias = 30): array {
        $sql = "SELECT 
                    DATE(Fecha_hora) as fecha,
                    COUNT(*) as cantidad_facturas,
                    SUM(Total) as total_ventas
                FROM Factura
                WHERE Fecha_hora >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
                GROUP BY DATE(Fecha_hora)
                ORDER BY fecha DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene las mejores mesas por ventas
     * @param int $limite Cantidad de mesas
     * @return array
     */
    public function getMejoresMesas(int $limite = 5): array {
        $sql = "SELECT 
                    m.id_Mesa,
                    m.Ubicacion,
                    COUNT(f.id_factura) as total_facturas,
                    SUM(f.Total) as total_ventas
                FROM Factura f
                JOIN Mesa m ON f.pkfk_id_Mesa = m.id_Mesa
                GROUP BY m.id_Mesa
                ORDER BY total_ventas DESC
                LIMIT :limite";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene detalles de pedidos de una factura
     * @param int $idFactura ID de la factura
     * @return array
     */
    public function getPedidosFactura(int $idFactura): array {
        $sql = "SELECT p.*, m.Productos, m.Precio, c.nom_categoria,
                       (p.cantidad * p.valor_venta) as subtotal
                FROM Pedido p
                JOIN Menu m ON p.pkfk_id_menu = m.id_menu
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                WHERE p.pkfk_id_factura = :idFactura
                ORDER BY p.pkfk_id_menu";
        
        return $this->query($sql, ['idFactura' => $idFactura]);
    }
    
    /**
     * Cuenta facturas por metodo de pago
     * @return array
     */
    public function countByMetodoPago(): array {
        $sql = "SELECT mp.Tipo_pago, COUNT(*) as total, SUM(fmp.monto) as monto_total
                FROM Factura_has_Metodo_pago fmp
                JOIN Metodo_pago mp ON fmp.pkfk_metodo_pago = mp.id_pago
                GROUP BY mp.id_pago, mp.Tipo_pago
                ORDER BY total DESC";
        
        return $this->query($sql);
    }
}
?>
