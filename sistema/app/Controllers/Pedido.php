<?php

namespace App\Controllers;

use App\Models\PedidoModel;
use App\Models\MesaModel;
use App\Models\ProductoModel;
use App\Models\PersonaModel;
use App\Models\FacturaModel;
use App\Models\NotificacionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Pedido
 * Maneja todas las operaciones CRUD de pedidos
 */
class Pedido extends BaseController
{
    /**
     * Modelos
     */
    protected PedidoModel $pedidoModel;
    protected MesaModel $mesaModel;
    protected ProductoModel $productoModel;
    protected PersonaModel $personaModel;
    protected FacturaModel $facturaModel;
    protected NotificacionModel $notificacionModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->pedidoModel = new PedidoModel();
        $this->mesaModel = new MesaModel();
        $this->productoModel = new ProductoModel();
        $this->personaModel = new PersonaModel();
        $this->facturaModel = new FacturaModel();
        $this->notificacionModel = new NotificacionModel();
    }

    /**
     * Verifica si el usuario tiene sesion activa
     */
    private function verificarSesion(): bool
    {
        return session('logueado') === true;
    }

    /**
     * Verifica si el usuario tiene permiso
     */
    private function tienePermiso(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['admin', 'Administrador', 'mesero', 'Mesero', 'cocinero', 'Cocinero']);
    }

    /**
     * Lista todos los pedidos
     */
    public function index()
    {
        if (!$this->verificarSesion()) {
            return redirect()->to('/');
        }

        $estado = $this->request->getGet('estado');
        $filtros = [];
        
        if (!empty($estado)) {
            $filtros['estado'] = $estado;
        }

        $datos = [
            'pedidos' => $this->pedidoModel->obtenerTodos($filtros),
            'estadisticas' => $this->pedidoModel->obtenerEstadisticas()
        ];

        return view('pedidos', $datos);
    }

    /**
     * Formulario para agregar pedido
     */
    public function agregar()
    {
        if (!$this->verificarSesion() || !$this->tienePermiso()) {
            return redirect()->to('/dashboard');
        }

        $datos = [
            'mesas' => $this->mesaModel->obtenerDisponibles(),
            'productos' => $this->productoModel->obtenerTodos(),
            'meseros' => $this->personaModel->obtenerMeseros()
        ];

        return view('agregar_pedido', $datos);
    }

    /**
     * Guarda un nuevo pedido
     */
    public function guardar()
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $mesaId = $this->request->getPost('id_mesa');
            $meseroId = $this->request->getPost('mesero_id') ?? session('id');
            $prioridad = $this->request->getPost('prioridad') ?? PedidoModel::PRIORIDAD_NORMAL;
            $observaciones = $this->request->getPost('observaciones');
            $productos = $this->request->getPost('productos'); // Array de productos

            if (empty($mesaId)) {
                throw new Exception('Debe seleccionar una mesa');
            }

            // Crear el pedido
            $datosPedido = [
                'id_mesa' => $mesaId,
                'mesero_id_usuario' => $meseroId,
                'mesero_tipo_doc' => 1,
                'prioridad' => $prioridad,
                'observaciones' => $observaciones
            ];

            $pedidoId = $this->pedidoModel->crearPedido($datosPedido);

            if (!$pedidoId) {
                throw new Exception('Error al crear el pedido');
            }

            // Agregar productos al pedido
            if (!empty($productos) && is_array($productos)) {
                foreach ($productos as $producto) {
                    if (!empty($producto['id']) && !empty($producto['cantidad'])) {
                        $this->pedidoModel->agregarProducto(
                            $pedidoId,
                            $producto['id'],
                            $producto['cantidad'],
                            $producto['observaciones'] ?? null
                        );
                    }
                }
            }

            // Notificar a cocina
            if ($prioridad === PedidoModel::PRIORIDAD_URGENTE) {
                $this->notificacionModel->notificarPedidoUrgente($mesaId, $pedidoId);
            } else {
                $this->notificacionModel->notificarNuevoPedido($mesaId, $pedidoId);
            }

            session()->setFlashdata('exito', 'Pedido creado correctamente');
            return redirect()->to('/pedidos');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/pedidos/agregar');
        }
    }

    /**
     * Ver detalle de pedido
     */
    public function ver(int $id)
    {
        if (!$this->verificarSesion()) {
            return redirect()->to('/');
        }

        $pedido = $this->pedidoModel->obtenerPorId($id);

        if ($pedido === null) {
            throw new PageNotFoundException('Pedido no encontrado');
        }

        $datos = [
            'pedido' => $pedido,
            'detalles' => $this->pedidoModel->obtenerDetalles($id),
            'total' => $this->pedidoModel->calcularTotal($id)
        ];

        return view('ver_pedido', $datos);
    }

    /**
     * Cambia el estado de un pedido
     */
    public function cambiarEstado(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $nuevoEstado = $this->request->getGet('estado') ?? $this->request->getPost('estado');

            if (empty($nuevoEstado)) {
                throw new Exception('Estado no especificado');
            }

            $resultado = $this->pedidoModel->cambiarEstado($id, $nuevoEstado);

            if (!$resultado) {
                throw new Exception('Error al cambiar estado');
            }

            // Si el pedido esta listo, notificar a meseros
            if ($nuevoEstado === PedidoModel::ESTADO_LISTO) {
                $pedido = $this->pedidoModel->obtenerPorId($id);
                if ($pedido) {
                    $this->notificacionModel->notificarPedidoListo($pedido['id_mesa'], $id);
                }
            }

            session()->setFlashdata('exito', 'Estado actualizado correctamente');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
        }

        return redirect()->to('/pedidos');
    }

    /**
     * Cancela un pedido
     */
    public function cancelar(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            // Verificar que el pedido no este entregado
            $pedido = $this->pedidoModel->obtenerPorId($id);
            
            if ($pedido === null) {
                throw new Exception('Pedido no encontrado');
            }

            if ($pedido['estado'] === PedidoModel::ESTADO_ENTREGADO) {
                throw new Exception('No se puede cancelar un pedido entregado');
            }

            // Cambiar estado a cancelado (eliminar pedido)
            $resultado = $this->pedidoModel->delete($id);

            if (!$resultado) {
                throw new Exception('Error al cancelar el pedido');
            }

            session()->setFlashdata('exito', 'Pedido cancelado correctamente');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
        }

        return redirect()->to('/pedidos');
    }

    /**
     * API: Obtiene todos los pedidos
     */
    public function apiPedidos()
    {
        try {
            if (!$this->verificarSesion()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(401);
            }

            $pedidos = $this->pedidoModel->obtenerActivos();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $pedidos,
                'total' => count($pedidos)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * API: Obtiene pedidos pendientes
     */
    public function apiPendientes()
    {
        try {
            $pedidos = $this->pedidoModel->obtenerPendientes();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $pedidos,
                'total' => count($pedidos)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}