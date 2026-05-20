<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Producto (Menu)
 * Maneja todas las operaciones de base de datos relacionadas con productos del menu
 */
class ProductoModel extends Model
{
    protected $table = 'Menu';
    protected $primaryKey = 'id_menu';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_menu',
        'Productos',
        'Precio',
        'descripcion',
        'pkfk_id_categoria'
    ];

    protected $validationRules = [
        'id_menu' => 'required|numeric',
        'Productos' => 'required|max_length[50]',
        'Precio' => 'required|numeric|greater_than[0]',
        'descripcion' => 'required',
        'pkfk_id_categoria' => 'required|numeric'
    ];

    protected $validationMessages = [
        'Productos' => [
            'required' => 'El nombre del producto es obligatorio',
            'max_length' => 'El nombre no puede exceder 50 caracteres'
        ],
        'Precio' => [
            'required' => 'El precio es obligatorio',
            'numeric' => 'El precio debe ser numerico',
            'greater_than' => 'El precio debe ser mayor a 0'
        ]
    ];

    /**
     * Obtiene todos los productos con categoria
     * @param array $filtros
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        try {
            $builder = $this->db->table('Menu m');
            $builder->select('m.*, c.nom_categoria as categoria_nombre');
            $builder->join('Categoria c', 'c.id_categoria = m.pkfk_id_categoria', 'left');

            if (isset($filtros['categoria']) && !empty($filtros['categoria'])) {
                $builder->where('m.pkfk_id_categoria', $filtros['categoria']);
            }

            if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
                $builder->groupStart()
                    ->like('m.Productos', $filtros['busqueda'])
                    ->orLike('m.descripcion', $filtros['busqueda'])
                    ->groupEnd();
            }

            if (isset($filtros['precio_min'])) {
                $builder->where('m.Precio >=', $filtros['precio_min']);
            }

            if (isset($filtros['precio_max'])) {
                $builder->where('m.Precio <=', $filtros['precio_max']);
            }

            $builder->orderBy('c.nom_categoria', 'ASC');
            $builder->orderBy('m.Productos', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener productos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene productos por categoria
     * @param int $categoriaId
     * @param int $limite
     * @param int|null $excluirId
     * @return array
     */
    public function obtenerPorCategoria(int $categoriaId, int $limite = 0, ?int $excluirId = null): array
    {
        try {
            $builder = $this->db->table('Menu m');
            $builder->select('m.*, c.nom_categoria as categoria_nombre');
            $builder->join('Categoria c', 'c.id_categoria = m.pkfk_id_categoria', 'left');
            $builder->where('m.pkfk_id_categoria', $categoriaId);

            if ($excluirId !== null) {
                $builder->where('m.id_menu !=', $excluirId);
            }

            if ($limite > 0) {
                $builder->limit($limite);
            }

            $builder->orderBy('m.Productos', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener productos por categoria: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene un producto por ID
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $builder = $this->db->table('Menu m');
            $builder->select('m.*, c.nom_categoria as categoria_nombre');
            $builder->join('Categoria c', 'c.id_categoria = m.pkfk_id_categoria', 'left');
            $builder->where('m.id_menu', $id);

            $resultado = $builder->get()->getRowArray();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener producto: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea un nuevo producto
     * @param array $datos
     * @return bool
     */
    public function crearProducto(array $datos): bool
    {
        try {
            $datosProducto = [
                'id_menu' => $datos['id_menu'],
                'Productos' => $datos['Productos'],
                'Precio' => $datos['Precio'],
                'descripcion' => $datos['descripcion'],
                'pkfk_id_categoria' => $datos['pkfk_id_categoria']
            ];

            return $this->db->table('Menu')->insert($datosProducto);

        } catch (Exception $e) {
            log_message('error', 'Error al crear producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un producto
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizarProducto(int $id, array $datos): bool
    {
        try {
            $datosActualizar = [];

            if (isset($datos['Productos'])) {
                $datosActualizar['Productos'] = $datos['Productos'];
            }
            if (isset($datos['Precio'])) {
                $datosActualizar['Precio'] = $datos['Precio'];
            }
            if (isset($datos['descripcion'])) {
                $datosActualizar['descripcion'] = $datos['descripcion'];
            }
            if (isset($datos['pkfk_id_categoria'])) {
                $datosActualizar['pkfk_id_categoria'] = $datos['pkfk_id_categoria'];
            }

            if (empty($datosActualizar)) {
                return true;
            }

            return $this->db->table('Menu')
                ->where('id_menu', $id)
                ->update($datosActualizar);

        } catch (Exception $e) {
            log_message('error', 'Error al actualizar producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina un producto
     * @param int $id
     * @return bool
     */
    public function eliminarProducto(int $id): bool
    {
        try {
            // Verificar que no tenga pedidos asociados
            $pedidos = $this->db->table('Detalle_Pedido')
                ->where('id_menu', $id)
                ->countAllResults();

            if ($pedidos > 0) {
                throw new Exception('No se puede eliminar un producto con pedidos asociados');
            }

            return $this->db->table('Menu')
                ->where('id_menu', $id)
                ->delete();

        } catch (Exception $e) {
            log_message('error', 'Error al eliminar producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene productos agrupados por categoria
     * @return array
     */
    public function obtenerAgrupadosPorCategoria(): array
    {
        try {
            $productos = $this->obtenerTodos();
            $agrupados = [];

            foreach ($productos as $producto) {
                $categoriaId = $producto['pkfk_id_categoria'];
                $categoriaNombre = $producto['categoria_nombre'] ?? 'Sin categoria';

                if (!isset($agrupados[$categoriaId])) {
                    $agrupados[$categoriaId] = [
                        'id' => $categoriaId,
                        'nombre' => $categoriaNombre,
                        'productos' => []
                    ];
                }

                $agrupados[$categoriaId]['productos'][] = $producto;
            }

            return array_values($agrupados);

        } catch (Exception $e) {
            log_message('error', 'Error al agrupar productos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadisticas de productos
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $productos = $this->obtenerTodos();
            
            $stats = [
                'total' => count($productos),
                'por_categoria' => [],
                'precio_promedio' => 0,
                'precio_minimo' => PHP_FLOAT_MAX,
                'precio_maximo' => 0
            ];

            $sumaPrecio = 0;

            foreach ($productos as $producto) {
                $precio = (float) $producto['Precio'];
                $sumaPrecio += $precio;

                if ($precio < $stats['precio_minimo']) {
                    $stats['precio_minimo'] = $precio;
                }
                if ($precio > $stats['precio_maximo']) {
                    $stats['precio_maximo'] = $precio;
                }

                $categoria = $producto['categoria_nombre'] ?? 'Sin categoria';
                if (!isset($stats['por_categoria'][$categoria])) {
                    $stats['por_categoria'][$categoria] = 0;
                }
                $stats['por_categoria'][$categoria]++;
            }

            if ($stats['total'] > 0) {
                $stats['precio_promedio'] = $sumaPrecio / $stats['total'];
            }

            if ($stats['precio_minimo'] === PHP_FLOAT_MAX) {
                $stats['precio_minimo'] = 0;
            }

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca productos
     * @param string $termino
     * @return array
     */
    public function buscar(string $termino): array
    {
        try {
            $builder = $this->db->table('Menu m');
            $builder->select('m.*, c.nom_categoria as categoria_nombre');
            $builder->join('Categoria c', 'c.id_categoria = m.pkfk_id_categoria', 'left');
            $builder->groupStart()
                ->like('m.Productos', $termino)
                ->orLike('m.descripcion', $termino)
                ->orLike('c.nom_categoria', $termino)
                ->groupEnd();
            $builder->orderBy('m.Productos', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al buscar productos: ' . $e->getMessage());
            return [];
        }
    }
}