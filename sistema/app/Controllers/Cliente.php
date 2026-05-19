<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Cliente
 * Maneja la vista y operaciones del cliente/mesa
 */
class Cliente extends BaseController
{
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
        return in_array($rol, ['cliente', 'mesa', 'admin']);
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

        // Datos para la vista
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

            // En produccion: verificar codigo en BD
            // $mesa = $this->mesaModel->verificarCodigo($codigo);

            // Simulacion: codigo valido
            $codigosValidos = [
                'ABC123' => ['mesa' => 1, 'capacidad' => 4],
                'DEF456' => ['mesa' => 2, 'capacidad' => 2],
                'GHI789' => ['mesa' => 3, 'capacidad' => 6],
                'JKL012' => ['mesa' => 4, 'capacidad' => 4],
                'MNO345' => ['mesa' => 5, 'capacidad' => 8]
            ];

            $codigoUpper = strtoupper($codigo);
            
            if (!isset($codigosValidos[$codigoUpper])) {
                throw new Exception('Codigo de mesa no encontrado');
            }

            // Crear sesion de cliente
            session()->set([
                'codigo_mesa' => $codigoUpper,
                'mesa_numero' => $codigosValidos[$codigoUpper]['mesa'],
                'mesa_capacidad' => $codigosValidos[$codigoUpper]['capacidad'],
                'logueado' => true,
                'rol' => 'mesa',
                'nombre' => 'Mesa ' . $codigosValidos[$codigoUpper]['mesa']
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
        $datos = [
            'categorias' => $this->obtenerCategorias(),
            'productos' => $this->obtenerProductos()
        ];

        return view('cliente_menu', $datos);
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

            // En produccion: guardar en BD
            // $this->pedidoModel->agregarProducto($productoId, $cantidad, $comentario);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Producto agregado al pedido',
                'producto_id' => $productoId,
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

            // En produccion: crear pedido en BD
            // $this->pedidoModel->crearPedido($mesa, $productos);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Pedido enviado a cocina',
                'pedido_id' => rand(100, 999)
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
     * Obtiene categorias del menu
     * @return array
     */
    private function obtenerCategorias(): array
    {
        return [
            ['id' => 1, 'nombre' => 'Entradas', 'icono' => 'appetizer'],
            ['id' => 2, 'nombre' => 'Platos Fuertes', 'icono' => 'main'],
            ['id' => 3, 'nombre' => 'Hamburguesas', 'icono' => 'burger'],
            ['id' => 4, 'nombre' => 'Pizzas', 'icono' => 'pizza'],
            ['id' => 5, 'nombre' => 'Ensaladas', 'icono' => 'salad'],
            ['id' => 6, 'nombre' => 'Bebidas', 'icono' => 'drink'],
            ['id' => 7, 'nombre' => 'Postres', 'icono' => 'dessert']
        ];
    }

    /**
     * Obtiene productos del menu
     * @return array
     */
    private function obtenerProductos(): array
    {
        return [
            // Entradas
            ['id' => 1, 'nombre' => 'Nachos con Queso', 'categoria_id' => 1, 'precio' => 18000, 'descripcion' => 'Nachos crujientes con queso cheddar derretido', 'imagen' => 'nachos.jpg', 'disponible' => true],
            ['id' => 2, 'nombre' => 'Aros de Cebolla', 'categoria_id' => 1, 'precio' => 15000, 'descripcion' => 'Aros de cebolla empanizados', 'imagen' => 'aros.jpg', 'disponible' => true],
            
            // Platos Fuertes
            ['id' => 3, 'nombre' => 'Bandeja Paisa', 'categoria_id' => 2, 'precio' => 28000, 'descripcion' => 'Tradicional bandeja paisa con todos sus ingredientes', 'imagen' => 'bandeja.jpg', 'disponible' => true],
            ['id' => 4, 'nombre' => 'Lomo de Cerdo', 'categoria_id' => 2, 'precio' => 32000, 'descripcion' => 'Lomo de cerdo en salsa BBQ con papas', 'imagen' => 'lomo.jpg', 'disponible' => true],
            
            // Hamburguesas
            ['id' => 5, 'nombre' => 'Hamburguesa Clasica', 'categoria_id' => 3, 'precio' => 22000, 'descripcion' => 'Carne de res, lechuga, tomate, cebolla', 'imagen' => 'hamburguesa.jpg', 'disponible' => true],
            ['id' => 6, 'nombre' => 'Hamburguesa Doble', 'categoria_id' => 3, 'precio' => 28000, 'descripcion' => 'Doble carne, doble queso, tocino', 'imagen' => 'doble.jpg', 'disponible' => true],
            
            // Pizzas
            ['id' => 7, 'nombre' => 'Pizza Pepperoni', 'categoria_id' => 4, 'precio' => 35000, 'descripcion' => 'Pepperoni, mozzarella, salsa de tomate', 'imagen' => 'pepperoni.jpg', 'disponible' => true],
            ['id' => 8, 'nombre' => 'Pizza Hawaiana', 'categoria_id' => 4, 'precio' => 35000, 'descripcion' => 'Jamon, pina, mozzarella', 'imagen' => 'hawaiana.jpg', 'disponible' => true],
            
            // Ensaladas
            ['id' => 9, 'nombre' => 'Ensalada Caesar', 'categoria_id' => 5, 'precio' => 22000, 'descripcion' => 'Lechuga romana, pollo, crutones, parmesano', 'imagen' => 'caesar.jpg', 'disponible' => true],
            
            // Bebidas
            ['id' => 10, 'nombre' => 'Gaseosa', 'categoria_id' => 6, 'precio' => 5000, 'descripcion' => 'Coca-Cola, Sprite, Fanta', 'imagen' => 'gaseosa.jpg', 'disponible' => true],
            ['id' => 11, 'nombre' => 'Jugo Natural', 'categoria_id' => 6, 'precio' => 8000, 'descripcion' => 'Naranja, limon, maracuya', 'imagen' => 'jugo.jpg', 'disponible' => true],
            
            // Postres
            ['id' => 12, 'nombre' => 'Brownie con Helado', 'categoria_id' => 7, 'precio' => 15000, 'descripcion' => 'Brownie caliente con helado de vainilla', 'imagen' => 'brownie.jpg', 'disponible' => true]
        ];
    }

    /**
     * Obtiene los pedidos del cliente actual
     * @return array
     */
    private function obtenerMisPedidos(): array
    {
        // En produccion: obtener de BD filtrado por mesa
        return [
            [
                'id' => 1,
                'productos' => [
                    ['nombre' => 'Hamburguesa Clasica', 'cantidad' => 2, 'precio' => 22000, 'subtotal' => 44000],
                    ['nombre' => 'Papas Fritas', 'cantidad' => 1, 'precio' => 12000, 'subtotal' => 12000]
                ],
                'estado' => 'preparacion',
                'hora' => date('H:i', strtotime('-10 minutes')),
                'subtotal' => 56000
            ],
            [
                'id' => 2,
                'productos' => [
                    ['nombre' => 'Gaseosa', 'cantidad' => 2, 'precio' => 5000, 'subtotal' => 10000]
                ],
                'estado' => 'listo',
                'hora' => date('H:i', strtotime('-15 minutes')),
                'subtotal' => 10000
            ]
        ];
    }

    /**
     * Calcula el total de la cuenta
     * @return int
     */
    private function calcularTotalCuenta(): int
    {
        $pedidos = $this->obtenerMisPedidos();
        $total = 0;

        foreach ($pedidos as $pedido) {
            $total += $pedido['subtotal'];
        }

        return $total;
    }

    /**
     * Cierra la sesion del cliente/mesa
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function salir()
    {
        session()->remove(['codigo_mesa', 'mesa_numero', 'mesa_capacidad']);
        
        if (session('rol') === 'mesa') {
            session()->destroy();
        }

        return redirect()->to('/cliente');
    }
}
