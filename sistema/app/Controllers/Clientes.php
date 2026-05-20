<?php

namespace App\Controllers;

use App\Models\PedidoModel;
use App\Models\MesaModel;
use App\Models\ProductoModel;
use App\Models\CategoriaModel;
use App\Models\NotificacionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Cliente
 * Maneja la vista y operaciones del cliente/mesa
 * Conectado con la base de datos real
 */
class Cliente extends BaseController
{
    /**
     * Modelos
     */
    protected PedidoModel $pedidoModel;
    protected MesaModel $mesaModel;
    protected ProductoModel $productoModel;
    protected CategoriaModel $categoriaModel;
    protected NotificacionModel $notificacionModel;

    /**
     * Constructor - Inicializa los modelos
     */
    public function __construct()
    {
        $this->pedidoModel = new PedidoModel();
        $this->mesaModel = new MesaModel();
        $this->productoModel = new ProductoModel();
        $this->categoriaModel = new CategoriaModel();
        $this->notificacionModel = new NotificacionModel();
    }

    /**
     * Verifica si el usuario tiene sesion activa
     * @return bool
     */
    private function verificarSesion(): bool
    {
        return session('logueado') === true;
    }

    /**
     * Verifica si el usuario es cliente o mesa
     * @return bool
     */
    private function esCliente(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['cliente', 'Cliente', 'mesa', 'admin', 'Administrador']);
    }

    /**
     * Registra actividad
     * @param string $accion
     * @param array $datos
     */
    private function registrarActividad(string $accion, array $datos = []): void
    {
        $log = [
            'fecha' => date('Y-m-d H:i:s'),
            'usuario_id' => session('id') ?? 'anonimo',
            'accion' => $accion,
            'datos' => json_encode($datos)
        ];
        log_message('info', 'Actividad Cliente: ' . json_encode($log));
    }

    /**
     * Vista principal del cliente
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // Verificar si hay sesion de mesa
        $codigoMesa = session('codigo_mesa') ?? null;
        
        if ($codigoMesa === null && !$this->verificarSesion()) {
            // Mostrar pantalla de acceso con codigo
            return view('cliente_acceso');
        }

        $this->registrarActividad('vista_cliente');

        // Datos para la vista desde la BD
        $datos = [
            'mesa' => session('mesa_numero') ?? 'N/A',
            'codigo_mesa' => $codigoMesa,
            'categorias' => $this->obtenerCategorias(),
            'productos' => $this->obtenerProductos(),
            'mis_pedidos' => $this->obtenerMisPedidos(),
            'total_cuenta' => $this->calcularTotalCuenta()
        ];

        return view('vista_cliente', $datos);
    }

    /**
     * Procesa el acceso con codigo de mesa
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function acceso()
    {
        try {
            $codigo = $this->request->getPost('codigo_mesa');

            if (empty($codigo) || strlen($codigo) !== 6) {
                throw new Exception('Codigo de mesa invalido');
            }

            // Verificar codigo en BD
            $sesionMesa = $this->mesaModel->verificarCodigoAcceso(strtoupper($codigo));

            if ($sesionMesa === null) {
                throw new Exception('Codigo de mesa no encontrado o expirado');
            }

            // Crear sesion de cliente
            session()->set([
                'codigo_mesa' => strtoupper($codigo),
                'mesa_numero' => $sesionMesa['id_mesa'],
                'mesa_capacidad' => $sesionMesa['Capacidad'],
                'logueado' => true,
                'rol' => 'mesa',
                'nombre' => 'Mesa ' . $sesionMesa['id_mesa']
            ]);

            $this->registrarActividad('acceso_mesa', [
                'codigo' => $codigo,
                'mesa' => $sesionMesa['id_mesa']
            ]);

            return redirect()->to('/cliente');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/cliente');
        }
    }

    /**
     * Muestra el menu digital
     * @return string
     */
    public function menu()
    {
        $this->registrarActividad('vista_menu');

        $datos = [
            'categorias' => $this->obtenerCategorias(),
            'productos' => $this->obtenerProductos(),
            'productosAgrupados' => $this->productoModel->obtenerAgrupadosPorCategoria()
        ];

        return view('menu', $datos);
    }

    /**
     * Muestra los pedidos del cliente
     * @return string
     */
    public function pedidos()
    {
        $datos = [
            'pedidos' => $this->obtenerMisPedidos(),
            'total' => $this->calcularTotalCuenta()
        ];

        return view('cliente_pedidos', $datos);
    }

    /**
     * Agrega un producto al pedido
     * @return \CodeIgniter\HTTP\Response
     */
    public function agregarProducto()
    {
        try {
            $productoId = $this->request->getPost('producto_id');
            $cantidad = $this->request->getPost('cantidad') ?? 1;
            $comentario = $this->request->getPost('comentario') ?? '';

            if (empty($productoId)) {
                throw new Exception('Producto no especificado');
            }

            $mesaNumero = session('mesa_numero');
            if (!$mesaNumero) {
                throw new Exception('No hay mesa asignada');
            }

            // Verificar si hay un pedido activo para esta mesa
            $pedidosActivos = $this->pedidoModel->obtenerPorMesa($mesaNumero);
            $pedidoId = null;

            if (!empty($pedidosActivos)) {
                // Usar el primer pedido activo
                $pedidoId = $pedidosActivos[0]['id_pedido'];
            } else {
                // Crear nuevo pedido
                $meseroId = 1053804357; // ID del mesero por defecto (se puede mejorar)
                $pedidoId = $this->pedidoModel->crearPedido([
                    'id_mesa' => $mesaNumero,
                    'mesero_id_usuario' => $meseroId,
                    'mesero_tipo_doc' => 1,
                    'prioridad' => PedidoModel::PRIORIDAD_NORMAL
                ]);

                if (!$pedidoId) {
                    throw new Exception('Error al crear el pedido');
                }

                // Notificar nuevo pedido a cocina
                $this->notificacionModel->notificarNuevoPedido($mesaNumero, $pedidoId);
            }

            // Agregar producto al pedido
            $resultado = $this->pedidoModel->agregarProducto($pedidoId, $productoId, $cantidad, $comentario);

            if (!$resultado) {
                throw new Exception('Error al agregar el producto');
            }

            $this->registrarActividad('agregar_producto', [
                'pedido_id' => $pedidoId,
                'producto_id' => $productoId,
                'cantidad' => $cantidad
            ]);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Producto agregado al pedido',
                'producto_id' => $productoId,
                'pedido_id' => $pedidoId,
                'cantidad' => $cantidad
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Confirma el pedido
     * @return \CodeIgniter\HTTP\Response
     */
    public function confirmarPedido()
    {
        try {
            $mesa = session('mesa_numero');
            
            if (!$mesa) {
                throw new Exception('No hay mesa asignada');
            }

            // Obtener pedido activo
            $pedidosActivos = $this->pedidoModel->obtenerPorMesa($mesa);

            if (empty($pedidosActivos)) {
                throw new Exception('No hay pedidos activos para confirmar');
            }

            $pedidoId = $pedidosActivos[0]['id_pedido'];

            // Notificar a cocina
            $this->notificacionModel->notificarNuevoPedido($mesa, $pedidoId);

            $this->registrarActividad('confirmar_pedido', [
                'pedido_id' => $pedidoId,
                'mesa' => $mesa
            ]);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Pedido enviado a cocina',
                'pedido_id' => $pedidoId
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * API: Obtiene estado de pedidos
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiPedidos()
    {
        try {
            $pedidos = $this->obtenerMisPedidos();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $pedidos,
                'total' => $this->calcularTotalCuenta(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Obtiene categorias del menu desde la BD
     * @return array
     */
    private function obtenerCategorias(): array
    {
        try {
            $categorias = $this->categoriaModel->obtenerTodas();
            
            // Agregar iconos para cada categoria
            $iconos = [
                'Hamburguesas' => 'burger',
                'Perros Calientes' => 'hotdog',
                'Salchipapa' => 'fries',
                'Bebidas' => 'drink',
                'Postres' => 'dessert',
                'Entradas' => 'appetizer'
            ];

            foreach ($categorias as &$categoria) {
                $categoria['icono'] = $iconos[$categoria['nom_categoria']] ?? 'default';
            }

            return $categorias;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener categorias: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene productos del menu desde la BD
     * @return array
     */
    private function obtenerProductos(): array
    {
        try {
            $productos = $this->productoModel->obtenerTodos();
            
            // Formatear para la vista
            $productosFormateados = [];
            foreach ($productos as $producto) {
                $productosFormateados[] = [
                    'id' => $producto['id_menu'],
                    'nombre' => $producto['Productos'],
                    'categoria_id' => $producto['pkfk_id_categoria'],
                    'categoria' => $producto['categoria_nombre'],
                    'precio' => $producto['Precio'],
                    'descripcion' => $producto['descripcion'],
                    'disponible' => true,
                    'imagen' => strtolower(str_replace(' ', '_', $producto['Productos'])) . '.jpg'
                ];
            }

            return $productosFormateados;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener productos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los pedidos del cliente actual desde la BD
     * @return array
     */
    private function obtenerMisPedidos(): array
    {
        try {
            $mesaNumero = session('mesa_numero');
            
            if (!$mesaNumero) {
                return [];
            }

            $pedidos = $this->pedidoModel->obtenerPorMesa($mesaNumero);
            
            // Formatear para la vista
            $pedidosFormateados = [];
            foreach ($pedidos as $pedido) {
                $productos = [];
                foreach ($pedido['detalles'] as $detalle) {
                    $productos[] = [
                        'nombre' => $detalle['producto_nombre'],
                        'cantidad' => $detalle['cantidad'],
                        'precio' => $detalle['precio_unitario'],
                        'subtotal' => $detalle['valor_venta']
                    ];
                }

                $pedidosFormateados[] = [
                    'id' => $pedido['id_pedido'],
                    'productos' => $productos,
                    'estado' => $pedido['estado'],
                    'hora' => date('H:i', strtotime($pedido['fecha_pedido'])),
                    'subtotal' => $pedido['total']
                ];
            }

            return $pedidosFormateados;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener mis pedidos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula el total de la cuenta
     * @return float
     */
    private function calcularTotalCuenta(): float
    {
        try {
            $pedidos = $this->obtenerMisPedidos();
            $total = 0;

            foreach ($pedidos as $pedido) {
                $total += $pedido['subtotal'];
            }

            return $total;

        } catch (Exception $e) {
            log_message('error', 'Error al calcular total: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Cierra la sesion del cliente/mesa
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function salir()
    {
        $this->registrarActividad('salir_cliente', [
            'mesa' => session('mesa_numero')
        ]);

        session()->remove(['codigo_mesa', 'mesa_numero', 'mesa_capacidad']);
        
        if (session('rol') === 'mesa') {
            session()->destroy();
        }

        return redirect()->to('/cliente');
    }
}
