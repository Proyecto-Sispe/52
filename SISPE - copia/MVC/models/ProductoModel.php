<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Producto (Menu)
 * Maneja operaciones CRUD para productos del menu
 */
class ProductoModel extends Model {
    protected string $table = 'Menu';
    protected string $primaryKey = 'id_menu';
    protected array $fillable = ['id_menu', 'Productos', 'Precio', 'descripcion', 'pkfk_id_categoria'];
    
    /**
     * Obtiene todos los productos con su categoria
     * @return array
     */
    public function getAllWithCategoria(): array {
        $sql = "SELECT m.*, c.nom_categoria
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                ORDER BY c.nom_categoria, m.Productos";
        
        return $this->query($sql);
    }
    
    /**
     * Obtiene productos agrupados por categoria
     * @return array Array asociativo [categoria => productos]
     */
    public function getGroupedByCategoria(): array {
        $productos = $this->getAllWithCategoria();
        $grouped = [];
        
        foreach ($productos as $producto) {
            $categoria = $producto['nom_categoria'];
            
            if (!isset($grouped[$categoria])) {
                $grouped[$categoria] = [];
            }
            
            $grouped[$categoria][] = $producto;
        }
        
        return $grouped;
    }
    
    /**
     * Filtra productos por categoria
     * @param int $categoriaId ID de la categoria
     * @return array
     */
    public function getByCategoria(int $categoriaId): array {
        $sql = "SELECT m.*, c.nom_categoria
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                WHERE m.pkfk_id_categoria = :categoriaId
                ORDER BY m.Productos";
        
        return $this->query($sql, ['categoriaId' => $categoriaId]);
    }
    
    /**
     * Busca productos por nombre o descripcion
     * @param string $termino Termino de busqueda
     * @return array
     */
    public function searchProductos(string $termino): array {
        $sql = "SELECT m.*, c.nom_categoria
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                WHERE m.Productos LIKE :termino OR m.descripcion LIKE :termino
                ORDER BY m.Productos";
        
        return $this->query($sql, ['termino' => "%{$termino}%"]);
    }
    
    /**
     * Filtra productos por rango de precio
     * @param float $precioMin Precio minimo
     * @param float $precioMax Precio maximo
     * @return array
     */
    public function getByPrecioRange(float $precioMin, float $precioMax): array {
        $sql = "SELECT m.*, c.nom_categoria
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                WHERE m.Precio BETWEEN :precioMin AND :precioMax
                ORDER BY m.Precio ASC";
        
        return $this->query($sql, [
            'precioMin' => $precioMin,
            'precioMax' => $precioMax
        ]);
    }
    
    /**
     * Obtiene el precio promedio por categoria
     * @return array
     */
    public function getPrecioPromedioPorCategoria(): array {
        $sql = "SELECT c.nom_categoria, 
                       AVG(m.Precio) as precio_promedio,
                       MIN(m.Precio) as precio_minimo,
                       MAX(m.Precio) as precio_maximo,
                       COUNT(*) as cantidad
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                GROUP BY c.id_categoria, c.nom_categoria
                ORDER BY c.nom_categoria";
        
        return $this->query($sql);
    }
    
    /**
     * Obtiene los productos mas caros
     * @param int $limite Cantidad de productos
     * @return array
     */
    public function getMasCaros(int $limite = 5): array {
        $sql = "SELECT m.*, c.nom_categoria
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                ORDER BY m.Precio DESC
                LIMIT :limite";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Obtiene los productos mas vendidos
     * @param int $limite Cantidad de productos
     * @return array
     */
    public function getMasVendidos(int $limite = 10): array {
        $sql = "SELECT m.*, c.nom_categoria, 
                       SUM(p.cantidad) as total_vendido,
                       SUM(p.valor_venta * p.cantidad) as total_ingresos
                FROM Menu m
                JOIN Categoria c ON m.pkfk_id_categoria = c.id_categoria
                LEFT JOIN Pedido p ON m.id_menu = p.pkfk_id_menu
                GROUP BY m.id_menu
                ORDER BY total_vendido DESC
                LIMIT :limite";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Crea un nuevo producto
     * @param array $data Datos del producto
     * @return bool
     */
    public function createProducto(array $data): bool {
        // Validar precio positivo
        if ((float)$data['Precio'] <= 0) {
            $this->addError("El precio debe ser mayor a 0");
            return false;
        }
        
        // Validar nombre no vacio
        if (empty(trim($data['Productos']))) {
            $this->addError("El nombre del producto es requerido");
            return false;
        }
        
        $sql = "INSERT INTO Menu (Productos, Precio, descripcion, pkfk_id_categoria) 
                VALUES (:nombre, :precio, :descripcion, :categoria)";
        
        return $this->execute($sql, [
            'nombre' => trim($data['Productos']),
            'precio' => (float)$data['Precio'],
            'descripcion' => trim($data['descripcion'] ?? ''),
            'categoria' => (int)$data['pkfk_id_categoria']
        ]);
    }
    
    /**
     * Actualiza precio de un producto
     * @param int $idProducto ID del producto
     * @param float $nuevoPrecio Nuevo precio
     * @return bool
     */
    public function actualizarPrecio(int $idProducto, float $nuevoPrecio): bool {
        if ($nuevoPrecio <= 0) {
            $this->addError("El precio debe ser mayor a 0");
            return false;
        }
        
        return $this->update($idProducto, ['Precio' => $nuevoPrecio]);
    }
    
    /**
     * Calcula el total de un pedido
     * @param array $items Array de [id_producto => cantidad]
     * @return float
     */
    public function calcularTotal(array $items): float {
        $total = 0.0;
        
        foreach ($items as $idProducto => $cantidad) {
            $producto = $this->find($idProducto);
            
            if ($producto !== null && $cantidad > 0) {
                $total += $producto['Precio'] * $cantidad;
            }
        }
        
        return $total;
    }
    
    /**
     * Obtiene todas las categorias
     * @return array
     */
    public function getCategorias(): array {
        $sql = "SELECT * FROM Categoria ORDER BY nom_categoria";
        return $this->query($sql);
    }
    
    /**
     * Cuenta productos por categoria
     * @return array
     */
    public function countByCategoria(): array {
        $sql = "SELECT c.nom_categoria, COUNT(m.id_menu) as total
                FROM Categoria c
                LEFT JOIN Menu m ON c.id_categoria = m.pkfk_id_categoria
                GROUP BY c.id_categoria, c.nom_categoria
                ORDER BY c.nom_categoria";
        
        return $this->query($sql);
    }
}
?>
