<?php

namespace App\Controllers;

use App\Models\ProductoModel;
use App\Models\CategoriaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Productos
 * Maneja listado, detalles y operaciones CRUD de productos
 * Conectado con la base de datos real
 */
class Productos extends BaseController
{
    /**
     * Modelo de producto
     * @var ProductoModel
     */
    protected ProductoModel $productoModel;

    /**
     * Modelo de categoria
     * @var CategoriaModel
     */
    protected CategoriaModel $categoriaModel;

    /**
     * Constructor - Inicializa los modelos
     */
    public function __construct()
    {
        $this->productoModel = new ProductoModel();
        $this->categoriaModel = new CategoriaModel();
    }

    // ==========================================
    // FUNCIONES AUXILIARES (Operaciones)
    // ==========================================

    /**
     * Verifica si el usuario tiene sesion activa
     * @return bool
     */
    private function verificarSesion(): bool
    {
        return session('logueado') === true;
    }

    /**
     * Verifica si el usuario tiene rol permitido
     * @param array $rolesPermitidos
     * @return bool
     */
    private function verificarRol(array $rolesPermitidos): bool
    {
        $rolUsuario = session('rol') ?? '';
        return in_array($rolUsuario, $rolesPermitidos);
    }

    /**
     * Verifica si el usuario es administrador
     * @return bool
     */
    private function esAdmin(): bool
    {
        return session('rol') === 'admin';
    }

    /**
     * Registra actividad del usuario
     * @param string $accion
     * @param array $datos
     * @return void
     */
    private function registrarActividad(string $accion, array $datos = []): void
    {
        $log = [
            'fecha' => date('Y-m-d H:i:s'),
            'usuario_id' => session('id') ?? 'anonimo',
            'accion' => $accion,
            'datos' => json_encode($datos)
        ];

        log_message('info', 'Actividad Productos: ' . json_encode($log));
    }

    /**
     * Formatea precio con simbolo de moneda
     * Operacion: Formato numerico
     * @param float $precio
     * @param string $moneda
     * @return string
     */
    private function formatearPrecio(float $precio, string $moneda = '$'): string
    {
        return $moneda . number_format($precio, 2, ',', '.');
    }

    /**
     * Calcula precio con descuento
     * Operacion: Calculo porcentual
     * @param float $precio
     * @param float $descuento
     * @return float
     */
    private function calcularPrecioConDescuento(float $precio, float $descuento): float
    {
        // Condicion: validar descuento
        if ($descuento < 0 || $descuento > 100) {
            return $precio;
        }

        // Operacion: calcular descuento
        return $precio - ($precio * ($descuento / 100));
    }

    /**
     * Sanitiza datos de entrada
     * @param array $datos
     * @return array
     */
    private function sanitizarDatos(array $datos): array
    {
        $sanitizados = [];

        // Bucle: procesar cada campo
        foreach ($datos as $clave => $valor) {
            if (is_string($valor)) {
                $sanitizados[$clave] = htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
            } elseif (is_numeric($valor)) {
                $sanitizados[$clave] = $valor;
            } else {
                $sanitizados[$clave] = $valor;
            }
        }

        return $sanitizados;
    }

    /**
     * Valida datos del producto
     * @param array $datos
     * @return array ['valido' => bool, 'errores' => array]
     */
    private function validarProducto(array $datos): array
    {
        $errores = [];

        // Condicion: validar nombre
        if (empty($datos['nombre'])) {
            $errores[] = 'El nombre es obligatorio';
        } elseif (strlen($datos['nombre']) < 3) {
            $errores[] = 'El nombre debe tener al menos 3 caracteres';
        }

        // Condicion: validar precio
        if (!isset($datos['precio']) || !is_numeric($datos['precio'])) {
            $errores[] = 'El precio es obligatorio y debe ser numerico';
        } elseif ($datos['precio'] < 0) {
            $errores[] = 'El precio no puede ser negativo';
        }

        // Condicion: validar categoria
        if (empty($datos['categoria'])) {
            $errores[] = 'La categoria es obligatoria';
        } elseif (!array_key_exists($datos['categoria'], self::CATEGORIAS)) {
            $errores[] = 'Categoria no valida';
        }

        // Condicion: validar stock si existe
        if (isset($datos['stock']) && $datos['stock'] < 0) {
            $errores[] = 'El stock no puede ser negativo';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Agrupa productos por categoria
     * Usa bucle foreach
     * @param array $productos
     * @return array
     */
    private function agruparPorCategoria(array $productos): array
    {
        $agrupados = [];

        // Bucle: procesar cada producto
        foreach ($productos as $producto) {
            $categoria = $producto['categoria'] ?? 'otros';

            // Condicion: inicializar categoria si no existe
            if (!isset($agrupados[$categoria])) {
                $agrupados[$categoria] = [
                    'nombre' => self::CATEGORIAS[$categoria]['nombre'] ?? 'Otros',
                    'icono' => self::CATEGORIAS[$categoria]['icono'] ?? 'fa-question',
                    'productos' => []
                ];
            }

            $agrupados[$categoria]['productos'][] = $producto;
        }

        return $agrupados;
    }

    /**
     * Calcula estadisticas de productos
     * Usa bucles y operaciones
     * @param array $productos
     * @return array
     */
    private function calcularEstadisticas(array $productos): array
    {
        $stats = [
            'total' => 0,
            'disponibles' => 0,
            'agotados' => 0,
            'por_categoria' => [],
            'precio_promedio' => 0,
            'precio_minimo' => PHP_FLOAT_MAX,
            'precio_maximo' => 0,
            'valor_inventario' => 0
        ];

        $sumaPrecio = 0;

        // Bucle: calcular estadisticas
        foreach ($productos as $producto) {
            $stats['total']++;
            $precio = (float)($producto['precio'] ?? 0);
            $stock = (int)($producto['stock'] ?? 0);
            $categoria = $producto['categoria'] ?? 'otros';

            // Operacion: sumar para promedio
            $sumaPrecio += $precio;

            // Condicion: verificar estado
            if (($producto['estado'] ?? 'disponible') === 'disponible') {
                $stats['disponibles']++;
            } else {
                $stats['agotados']++;
            }

            // Condicion: actualizar minimo y maximo
            if ($precio < $stats['precio_minimo']) {
                $stats['precio_minimo'] = $precio;
            }
            if ($precio > $stats['precio_maximo']) {
                $stats['precio_maximo'] = $precio;
            }

            // Operacion: calcular valor inventario
            $stats['valor_inventario'] += $precio * $stock;

            // Contar por categoria
            if (!isset($stats['por_categoria'][$categoria])) {
                $stats['por_categoria'][$categoria] = 0;
            }
            $stats['por_categoria'][$categoria]++;
        }

        // Operacion: calcular promedio
        if ($stats['total'] > 0) {
            $stats['precio_promedio'] = $sumaPrecio / $stats['total'];
        }

        // Condicion: ajustar minimo si no hay productos
        if ($stats['precio_minimo'] === PHP_FLOAT_MAX) {
            $stats['precio_minimo'] = 0;
        }

        return $stats;
    }

    /**
     * Filtra productos segun criterios
     * Usa bucles y condiciones
     * @param array $productos
     * @param array $filtros
     * @return array
     */
    private function filtrarProductos(array $productos, array $filtros): array
    {
        $filtrados = [];

        // Bucle: procesar cada producto
        foreach ($productos as $producto) {
            $incluir = true;

            // Condicion: filtrar por categoria
            if (!empty($filtros['categoria'])) {
                if ($producto['categoria'] !== $filtros['categoria']) {
                    $incluir = false;
                }
            }

            // Condicion: filtrar por estado
            if (!empty($filtros['estado'])) {
                if (($producto['estado'] ?? 'disponible') !== $filtros['estado']) {
                    $incluir = false;
                }
            }

            // Condicion: filtrar por rango de precio
            if (isset($filtros['precio_min']) && $filtros['precio_min'] > 0) {
                if ((float)$producto['precio'] < $filtros['precio_min']) {
                    $incluir = false;
                }
            }

            if (isset($filtros['precio_max']) && $filtros['precio_max'] > 0) {
                if ((float)$producto['precio'] > $filtros['precio_max']) {
                    $incluir = false;
                }
            }

            // Condicion: filtrar por busqueda de texto
            if (!empty($filtros['busqueda'])) {
                $busqueda = strtolower($filtros['busqueda']);
                $nombre = strtolower($producto['nombre'] ?? '');
                $descripcion = strtolower($producto['descripcion'] ?? '');

                if (strpos($nombre, $busqueda) === false && strpos($descripcion, $busqueda) === false) {
                    $incluir = false;
                }
            }

            if ($incluir) {
                $filtrados[] = $producto;
            }
        }

        return $filtrados;
    }

    /**
     * Ordena productos segun criterio
     * @param array $productos
     * @param string $campo
     * @param string $direccion
     * @return array
     */
    private function ordenarProductos(array $productos, string $campo = 'nombre', string $direccion = 'ASC'): array
    {
        // Operacion: usar usort con funcion de comparacion
        usort($productos, function ($a, $b) use ($campo, $direccion) {
            $valorA = $a[$campo] ?? '';
            $valorB = $b[$campo] ?? '';

            // Condicion: comparacion segun tipo
            if (is_numeric($valorA) && is_numeric($valorB)) {
                $comparacion = $valorA <=> $valorB;
            } else {
                $comparacion = strcasecmp($valorA, $valorB);
            }

            // Condicion: invertir si es descendente
            return ($direccion === 'DESC') ? -$comparacion : $comparacion;
        });

        return $productos;
    }

    // ==========================================
    // VISTAS PUBLICAS
    // ==========================================

    /**
     * Lista todos los productos (Menu publico)
     * @return string
     */
    public function index()
    {
        try {
            $this->registrarActividad('vista_productos');

            // Obtener todos los productos
            $productos = $this->productoModel->obtenerTodos();

            // Obtener categorias de la BD
            $categorias = $this->categoriaModel->obtenerTodas();

            // Obtener filtros de la URL
            $filtros = [
                'categoria' => $this->request->getGet('categoria'),
                'busqueda' => $this->request->getGet('buscar'),
                'precio_min' => $this->request->getGet('precio_min'),
                'precio_max' => $this->request->getGet('precio_max')
            ];

            // Aplicar filtros
            $productosFiltrados = $this->filtrarProductos($productos, $filtros);

            // Obtener ordenamiento
            $ordenCampo = $this->request->getGet('orden') ?? 'nombre';
            $ordenDir = $this->request->getGet('dir') ?? 'ASC';

            // Ordenar productos
            $productosOrdenados = $this->ordenarProductos($productosFiltrados, $ordenCampo, $ordenDir);

            // Agrupar por categoria para la vista de menu
            $productosAgrupados = $this->agruparPorCategoria($productosOrdenados);

            // Calcular estadisticas
            $estadisticas = $this->calcularEstadisticas($productos);

            // Preparar datos para la vista
            $datos = [
                'productos' => $productosOrdenados,
                'productosAgrupados' => $productosAgrupados,
                'categorias' => $categorias,
                'estadisticas' => $estadisticas,
                'filtros' => $filtros,
                'orden' => ['campo' => $ordenCampo, 'direccion' => $ordenDir]
            ];

            return view('productos', $datos);

        } catch (Exception $e) {
            log_message('error', 'Error en lista de productos: ' . $e->getMessage());
            session()->setFlashdata('error', 'Error al cargar los productos');
            return redirect()->to('/');
        }
    }

    /**
     * Muestra detalle de un producto
     * @param int $id
     * @return string
     */
    public function detalle(int $id)
    {
        try {
            // Condicion: validar ID
            if ($id <= 0) {
                throw new PageNotFoundException('Producto no encontrado');
            }

            // Buscar producto
            $producto = $this->productoModel->obtenerPorId($id);

            // Condicion: verificar existencia
            if ($producto === null) {
                throw new PageNotFoundException('Producto no encontrado');
            }

            $this->registrarActividad('vista_detalle_producto', ['producto_id' => $id]);

            // Obtener productos relacionados (misma categoria)
            $relacionados = $this->productoModel->obtenerPorCategoria($producto['categoria'], 4, $id);

            // Preparar datos
            $datos = [
                'producto' => $producto,
                'relacionados' => $relacionados,
                'categorias' => self::CATEGORIAS,
                'precioFormateado' => $this->formatearPrecio($producto['precio'])
            ];

            return view('detalle_producto', $datos);

        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            log_message('error', 'Error en detalle de producto: ' . $e->getMessage());
            session()->setFlashdata('error', 'Error al cargar el producto');
            return redirect()->to('/productos');
        }
    }

    // ==========================================
    // GESTION DE PRODUCTOS (Admin)
    // ==========================================

    /**
     * Lista productos para administracion
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function gestion()
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion()) {
                session()->setFlashdata('error', 'Debes iniciar sesión');
                return redirect()->to('/auth/login');
            }

            if (!$this->verificarRol(['admin', 'cocinero'])) {
                session()->setFlashdata('error', 'No tienes permisos para esta sección');
                return redirect()->to('/dashboard');
            }

            // Obtener todos los productos (incluyendo no disponibles)
            $productos = $this->productoModel->obtenerTodos();

            // Calcular estadisticas
            $estadisticas = $this->calcularEstadisticas($productos);

            $datos = [
                'productos' => $productos,
                'categorias' => self::CATEGORIAS,
                'estados' => self::ESTADOS,
                'estadisticas' => $estadisticas
            ];

            return view('gestion_productos', $datos);

        } catch (Exception $e) {
            log_message('error', 'Error en gestion de productos: ' . $e->getMessage());
            session()->setFlashdata('error', 'Error al cargar la gestion de productos');
            return redirect()->to('/dashboard');
        }
    }

    /**
     * Muestra formulario para agregar producto
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function agregar()
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->verificarRol(['admin', 'cocinero'])) {
                session()->setFlashdata('error', 'No tienes permisos');
                return redirect()->to('/dashboard');
            }

            $datos = [
                'categorias' => self::CATEGORIAS,
                'estados' => self::ESTADOS,
                'producto' => null
            ];

            return view('agregar_producto', $datos);

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/productos/gestion');
        }
    }

    /**
     * Guarda nuevo producto
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function guardar()
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->verificarRol(['admin', 'cocinero'])) {
                throw new Exception('No tienes permisos para esta accion');
            }

            // Obtener datos del formulario
            $datosRaw = [
                'nombre' => $this->request->getPost('nombre'),
                'descripcion' => $this->request->getPost('descripcion'),
                'precio' => $this->request->getPost('precio'),
                'categoria' => $this->request->getPost('categoria'),
                'stock' => $this->request->getPost('stock') ?? 0,
                'estado' => $this->request->getPost('estado') ?? 'disponible',
                'imagen' => $this->request->getPost('imagen')
            ];

            // Sanitizar datos
            $datos = $this->sanitizarDatos($datosRaw);

            // Validar datos
            $validacion = $this->validarProducto($datos);
            if (!$validacion['valido']) {
                throw new Exception(implode('. ', $validacion['errores']));
            }

            // Insertar producto
            $resultado = $this->productoModel->insert($datos);

            if ($resultado === false) {
                throw new Exception('Error al guardar el producto');
            }

            $this->registrarActividad('crear_producto', ['producto_id' => $resultado]);

            session()->setFlashdata('exito', 'Producto creado correctamente');
            return redirect()->to('/productos/gestion');

        } catch (Exception $e) {
            $this->registrarActividad('error_crear_producto', ['error' => $e->getMessage()]);
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/productos/agregar');
        }
    }

    /**
     * Muestra formulario de edicion
     * @param int $id
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function editar(int $id)
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->verificarRol(['admin', 'cocinero'])) {
                throw new Exception('No tienes permisos');
            }

            // Condicion: validar ID
            if ($id <= 0) {
                throw new PageNotFoundException('Producto no encontrado');
            }

            $producto = $this->productoModel->find($id);

            if ($producto === null) {
                throw new PageNotFoundException('Producto no encontrado');
            }

            $datos = [
                'producto' => $producto,
                'categorias' => self::CATEGORIAS,
                'estados' => self::ESTADOS
            ];

            return view('agregar_producto', $datos);

        } catch (PageNotFoundException $e) {
            throw $e;
        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/productos/gestion');
        }
    }

    /**
     * Actualiza producto existente
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function actualizar(int $id)
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->verificarRol(['admin', 'cocinero'])) {
                throw new Exception('No tienes permisos');
            }

            // Condicion: validar ID
            if ($id <= 0) {
                throw new Exception('ID de producto invalido');
            }

            // Verificar existencia
            $productoExistente = $this->productoModel->find($id);
            if ($productoExistente === null) {
                throw new Exception('Producto no encontrado');
            }

            // Obtener datos
            $datosRaw = [
                'nombre' => $this->request->getPost('nombre'),
                'descripcion' => $this->request->getPost('descripcion'),
                'precio' => $this->request->getPost('precio'),
                'categoria' => $this->request->getPost('categoria'),
                'stock' => $this->request->getPost('stock') ?? 0,
                'estado' => $this->request->getPost('estado') ?? 'disponible',
                'imagen' => $this->request->getPost('imagen')
            ];

            // Sanitizar y validar
            $datos = $this->sanitizarDatos($datosRaw);
            $validacion = $this->validarProducto($datos);

            if (!$validacion['valido']) {
                throw new Exception(implode('. ', $validacion['errores']));
            }

            // Actualizar
            $resultado = $this->productoModel->update($id, $datos);

            if ($resultado === false) {
                throw new Exception('Error al actualizar el producto');
            }

            $this->registrarActividad('actualizar_producto', ['producto_id' => $id]);

            session()->setFlashdata('exito', 'Producto actualizado correctamente');
            return redirect()->to('/productos/gestion');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/productos/editar/' . $id);
        }
    }

    /**
     * Elimina producto
     * @param int $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function eliminar(int $id)
    {
        try {
            // Condicion: verificar permisos (solo admin)
            if (!$this->verificarSesion() || !$this->esAdmin()) {
                throw new Exception('No tienes permisos para eliminar productos');
            }

            // Condicion: validar ID
            if ($id <= 0) {
                throw new Exception('ID de producto invalido');
            }

            // Verificar existencia
            $producto = $this->productoModel->find($id);
            if ($producto === null) {
                throw new Exception('Producto no encontrado');
            }

            // Eliminar
            $resultado = $this->productoModel->delete($id);

            if ($resultado === false) {
                throw new Exception('Error al eliminar el producto');
            }

            $this->registrarActividad('eliminar_producto', ['producto_id' => $id, 'nombre' => $producto['nombre']]);

            session()->setFlashdata('exito', 'Producto eliminado correctamente');
            return redirect()->to('/productos/gestion');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/productos/gestion');
        }
    }

    /**
     * Cambia estado de producto (disponible/agotado)
     * @param int $id
     * @param string $estado
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function cambiarEstado(int $id, string $estado)
    {
        try {
            // Condicion: verificar permisos
            if (!$this->verificarSesion() || !$this->verificarRol(['admin', 'cocinero'])) {
                throw new Exception('No tienes permisos');
            }

            // Condicion: validar estado
            if (!array_key_exists($estado, self::ESTADOS)) {
                throw new Exception('Estado no valido');
            }

            // Actualizar estado
            $resultado = $this->productoModel->update($id, ['estado' => $estado]);

            if ($resultado === false) {
                throw new Exception('Error al cambiar estado');
            }

            $this->registrarActividad('cambiar_estado_producto', ['producto_id' => $id, 'estado' => $estado]);

            session()->setFlashdata('exito', 'Estado actualizado');
            return redirect()->to('/productos/gestion');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/productos/gestion');
        }
    }

    // ==========================================
    // API ENDPOINTS
    // ==========================================

    /**
     * API: Obtiene todos los productos en JSON
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiProductos()
    {
        try {
            $productos = $this->productoModel->obtenerTodos(['estado' => 'disponible']);

            // Procesar productos para la respuesta
            $productosFormateados = [];

            // Bucle: formatear cada producto
            foreach ($productos as $producto) {
                $productosFormateados[] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'descripcion' => $producto['descripcion'] ?? '',
                    'precio' => (float)$producto['precio'],
                    'precio_formateado' => $this->formatearPrecio($producto['precio']),
                    'categoria' => $producto['categoria'],
                    'categoria_nombre' => self::CATEGORIAS[$producto['categoria']]['nombre'] ?? 'Otros',
                    'imagen' => $producto['imagen'] ?? null,
                    'disponible' => ($producto['estado'] ?? 'disponible') === 'disponible'
                ];
            }

            return $this->response->setJSON([
                'exito' => true,
                'datos' => $productosFormateados,
                'total' => count($productosFormateados)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * API: Obtiene productos por categoria
     * @param string $categoria
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiPorCategoria(string $categoria)
    {
        try {
            // Condicion: validar categoria
            if (!array_key_exists($categoria, self::CATEGORIAS)) {
                return $this->response->setJSON([
                    'exito' => false,
                    'mensaje' => 'Categoria no valida'
                ])->setStatusCode(400);
            }

            $productos = $this->productoModel->obtenerPorCategoria($categoria);

            return $this->response->setJSON([
                'exito' => true,
                'categoria' => self::CATEGORIAS[$categoria],
                'datos' => $productos,
                'total' => count($productos)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * API: Busca productos
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiBuscar()
    {
        try {
            $termino = $this->request->getGet('q') ?? '';

            // Condicion: validar termino de busqueda
            if (strlen($termino) < 2) {
                return $this->response->setJSON([
                    'exito' => false,
                    'mensaje' => 'El termino de busqueda debe tener al menos 2 caracteres'
                ])->setStatusCode(400);
            }

            $productos = $this->productoModel->buscar($termino);

            return $this->response->setJSON([
                'exito' => true,
                'termino' => $termino,
                'datos' => $productos,
                'total' => count($productos)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'exito' => false,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}
