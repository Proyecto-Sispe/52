<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Producto
 * Maneja todas las operaciones de base de datos relacionadas con productos
 * 
 * Funciones PHP utilizadas:
 * - Excepciones (try-catch) para manejo de errores
 * - Condiciones (if-else) para validaciones y filtros
 * - Bucles (foreach) para procesamiento de datos
 * - Operaciones para calculos y transformaciones
 */
class ProductoModel extends Model
{
    /**
     * Nombre de la tabla
     * @var string
     */
    protected $table = 'productos';

    /**
     * Clave primaria
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Usar timestamps automaticos
     * @var bool
     */
    protected $useTimestamps = true;

    /**
     * Campos de fecha
     */
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Campos permitidos para insercion/actualizacion
     * @var array
     */
    protected $allowedFields = [
        'nombre',
        'descripcion',
        'precio',
        'categoria',
        'stock',
        'estado',
        'imagen',
        'destacado',
        'descuento'
    ];

    /**
     * Tipo de retorno
     * @var string
     */
    protected $returnType = 'array';

    /**
     * Reglas de validacion
     * @var array
     */
    protected $validationRules = [
        'nombre' => 'required|min_length[3]|max_length[150]',
        'precio' => 'required|numeric|greater_than[0]',
        'categoria' => 'required|in_list[entradas,platos_fuertes,bebidas,postres,acompanantes,especiales]',
        'estado' => 'in_list[disponible,agotado,oculto]'
    ];

    /**
     * Mensajes de validacion personalizados
     * @var array
     */
    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre del producto es obligatorio',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede exceder 150 caracteres'
        ],
        'precio' => [
            'required' => 'El precio es obligatorio',
            'numeric' => 'El precio debe ser un número',
            'greater_than' => 'El precio debe ser mayor a 0'
        ],
        'categoria' => [
            'required' => 'La categoría es obligatoria',
            'in_list' => 'Categoría no válida'
        ]
    ];

    /**
     * Categorias disponibles
     * @var array
     */
    private const CATEGORIAS = [
        'entradas' => 'Entradas',
        'platos_fuertes' => 'Platos Fuertes',
        'bebidas' => 'Bebidas',
        'postres' => 'Postres',
        'acompanantes' => 'Acompañantes',
        'especiales' => 'Especiales del Día'
    ];

    // ==========================================
    // OPERACIONES CRUD
    // ==========================================

    /**
     * Obtiene todos los productos con filtros opcionales
     * Usa condiciones y bucles
     * @param array $filtros
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        try {
            $builder = $this->builder();

            // Condicion: aplicar filtro de categoria
            if (!empty($filtros['categoria'])) {
                $builder->where('categoria', $filtros['categoria']);
            }

            // Condicion: aplicar filtro de estado
            if (!empty($filtros['estado'])) {
                $builder->where('estado', $filtros['estado']);
            }

            // Condicion: aplicar filtro de precio minimo
            if (isset($filtros['precio_min']) && $filtros['precio_min'] > 0) {
                $builder->where('precio >=', $filtros['precio_min']);
            }

            // Condicion: aplicar filtro de precio maximo
            if (isset($filtros['precio_max']) && $filtros['precio_max'] > 0) {
                $builder->where('precio <=', $filtros['precio_max']);
            }

            // Condicion: aplicar busqueda
            if (!empty($filtros['busqueda'])) {
                $builder->groupStart()
                    ->like('nombre', $filtros['busqueda'])
                    ->orLike('descripcion', $filtros['busqueda'])
                    ->groupEnd();
            }

            // Condicion: solo destacados
            if (!empty($filtros['destacado'])) {
                $builder->where('destacado', 1);
            }

            // Ordenamiento
            $ordenCampo = $filtros['orden_campo'] ?? 'nombre';
            $ordenDir = $filtros['orden_dir'] ?? 'ASC';
            $builder->orderBy($ordenCampo, $ordenDir);

            // Limite
            if (isset($filtros['limite']) && $filtros['limite'] > 0) {
                $offset = $filtros['offset'] ?? 0;
                $builder->limit($filtros['limite'], $offset);
            }

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener productos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene productos por categoria
     * @param string $categoria
     * @param int $limite
     * @param int|null $excluirId
     * @return array
     */
    public function obtenerPorCategoria(string $categoria, int $limite = 0, ?int $excluirId = null): array
    {
        try {
            // Condicion: validar categoria
            if (!array_key_exists($categoria, self::CATEGORIAS)) {
                return [];
            }

            $builder = $this->builder();
            $builder->where('categoria', $categoria)
                    ->where('estado', 'disponible');

            // Condicion: excluir producto especifico
            if ($excluirId !== null && $excluirId > 0) {
                $builder->where('id !=', $excluirId);
            }

            $builder->orderBy('nombre', 'ASC');

            // Condicion: aplicar limite
            if ($limite > 0) {
                $builder->limit($limite);
            }

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener productos por categoria: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca productos por termino
     * Usa bucle para procesar resultados
     * @param string $termino
     * @param int $limite
     * @return array
     */
    public function buscar(string $termino, int $limite = 20): array
    {
        try {
            // Condicion: validar termino
            if (strlen($termino) < 2) {
                return [];
            }

            // Sanitizar termino
            $termino = htmlspecialchars(trim($termino), ENT_QUOTES, 'UTF-8');

            $resultados = $this->like('nombre', $termino)
                ->orLike('descripcion', $termino)
                ->where('estado', 'disponible')
                ->limit($limite)
                ->findAll();

            return $resultados;

        } catch (Exception $e) {
            log_message('error', 'Error en busqueda de productos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea un nuevo producto
     * @param array $datos
     * @return int|bool
     */
    public function crearProducto(array $datos)
    {
        try {
            // Validar datos requeridos
            $camposRequeridos = ['nombre', 'precio', 'categoria'];

            // Bucle: verificar campos
            foreach ($camposRequeridos as $campo) {
                if (!isset($datos[$campo]) || empty($datos[$campo])) {
                    throw new Exception("El campo '$campo' es requerido");
                }
            }

            // Sanitizar nombre
            $datos['nombre'] = $this->sanitizarTexto($datos['nombre']);

            // Condicion: validar precio
            if (!is_numeric($datos['precio']) || $datos['precio'] <= 0) {
                throw new Exception('El precio debe ser un número mayor a 0');
            }

            // Establecer valores por defecto
            $datos['estado'] = $datos['estado'] ?? 'disponible';
            $datos['stock'] = $datos['stock'] ?? 0;
            $datos['destacado'] = $datos['destacado'] ?? 0;

            return $this->insert($datos);

        } catch (Exception $e) {
            log_message('error', 'Error al crear producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza un producto existente
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizarProducto(int $id, array $datos): bool
    {
        try {
            // Condicion: validar ID
            if ($id <= 0) {
                throw new Exception('ID de producto inválido');
            }

            // Verificar existencia
            $producto = $this->find($id);
            if ($producto === null) {
                throw new Exception('Producto no encontrado');
            }

            // Preparar datos a actualizar
            $datosActualizar = [];
            $camposPermitidos = ['nombre', 'descripcion', 'precio', 'categoria', 'stock', 'estado', 'imagen', 'destacado', 'descuento'];

            // Bucle: procesar campos
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    // Condicion: sanitizar texto
                    if (in_array($campo, ['nombre', 'descripcion'])) {
                        $datosActualizar[$campo] = $this->sanitizarTexto($datos[$campo]);
                    } else {
                        $datosActualizar[$campo] = $datos[$campo];
                    }
                }
            }

            // Condicion: si no hay datos que actualizar
            if (empty($datosActualizar)) {
                return true;
            }

            return $this->update($id, $datosActualizar);

        } catch (Exception $e) {
            log_message('error', 'Error al actualizar producto: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cambia el estado de un producto
     * @param int $id
     * @param string $estado
     * @return bool
     */
    public function cambiarEstado(int $id, string $estado): bool
    {
        try {
            $estadosValidos = ['disponible', 'agotado', 'oculto'];

            // Condicion: validar estado
            if (!in_array($estado, $estadosValidos)) {
                throw new Exception('Estado no válido');
            }

            return $this->update($id, ['estado' => $estado]);

        } catch (Exception $e) {
            log_message('error', 'Error al cambiar estado: ' . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // FUNCIONES AUXILIARES
    // ==========================================

    /**
     * Sanitiza texto de entrada
     * @param string $texto
     * @return string
     */
    private function sanitizarTexto(string $texto): string
    {
        return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtiene todas las categorias
     * @return array
     */
    public function obtenerCategorias(): array
    {
        return self::CATEGORIAS;
    }

    // ==========================================
    // ESTADISTICAS
    // ==========================================

    /**
     * Obtiene estadisticas de productos
     * Usa bucles y operaciones
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $stats = [
                'total' => $this->countAll(),
                'disponibles' => $this->where('estado', 'disponible')->countAllResults(false),
                'agotados' => $this->where('estado', 'agotado')->countAllResults(false),
                'por_categoria' => [],
                'precio_promedio' => 0,
                'destacados' => $this->where('destacado', 1)->countAllResults(false)
            ];

            // Bucle: contar por categoria
            foreach (array_keys(self::CATEGORIAS) as $categoria) {
                $stats['por_categoria'][$categoria] = [
                    'nombre' => self::CATEGORIAS[$categoria],
                    'cantidad' => $this->where('categoria', $categoria)->countAllResults(false)
                ];
            }

            // Operacion: calcular precio promedio
            $builder = $this->builder();
            $resultado = $builder->selectAvg('precio', 'promedio')->get()->getRowArray();
            $stats['precio_promedio'] = round($resultado['promedio'] ?? 0, 2);

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene productos mas vendidos (simulado)
     * @param int $limite
     * @return array
     */
    public function obtenerMasVendidos(int $limite = 5): array
    {
        try {
            // En produccion, esto se calcularia con datos reales de ventas
            return $this->where('estado', 'disponible')
                ->where('destacado', 1)
                ->orderBy('precio', 'DESC')
                ->limit($limite)
                ->findAll();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener mas vendidos: ' . $e->getMessage());
            return [];
        }
    }
}
