<?php

namespace App\Controllers;

use App\Models\PedidoModel;
use App\Models\NotificacionModel;
use App\Models\MesaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Cocina
 * Maneja la vista y operaciones del cocinero
 * Conectado con la base de datos real
*/
class Cocina extends BaseController
{
    /**
     * Modelos
     */
    protected PedidoModel $pedidoModel;
    protected NotificacionModel $notificacionModel;
    protected MesaModel $mesaModel;

    /**
     * Constructor - Inicializa los modelos
     */
    public function __construct()
    {
        $this->pedidoModel = new PedidoModel();
        $this->notificacionModel = new NotificacionModel();
        $this->mesaModel = new MesaModel();
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
     * Verifica si el usuario es cocinero o admin
     * @return bool
     */
    private function esCocinero(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['cook', 'Cook', 'admin', 'Administrator']);
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
        log_message('info', 'Actividad Cocina: ' . json_encode($log));
    }

    /**
     * Vista principal del cocinero
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // Verificar sesion y rol
        if (!$this->verificarSesion()) {
            session()->setFlashdata('error', 'Debes iniciar sesion');
            return redirect()->to('/');
        }

        if (!$this->esCocinero()) {
            session()->setFlashdata('error', 'No tienes permisos para acceder a esta seccion');
            return redirect()->to('/dashboard');
        }

        $this->registrarActividad('kitchen_view');

        // Obtener pedidos desde la BD
        $datos = [
            'orders_pending' => $this->obtenerPedidosPorEstado(PedidoModel::STATUS_PENDING),
            'pedidos_preparacion' => $this->obtenerPedidosPorEstado(PedidoModel::ESTADO_EN_PREPARACION),
            'pedidos_listos' => $this->obtenerPedidosPorEstado(PedidoModel::ESTADO_LISTO),
            'estadisticas' => $this->obtenerEstadisticas(),
            'notificaciones' => $this->notificacionModel->obtenerParaCocineros()
        ];

        return view('vista_cocinero', $datos);
    }

    /**
     * Marca un pedido como listo
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function marcarListo(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->esCocinero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

           // Get the order to know the table
            $pedido = $this->pedidoModel->obtenerPorId($id);
            
            if ($pedido === null) {
                throw new Exception('Pedido no encontrado');
            }

            // Actualizar estado en BD
            $resultado = $this->pedidoModel->marcarListo($id);

            if (!$resultado) {
                throw new Exception('Error al actualizar el pedido');
            }

            // Crear notificacion para meseros
            $this->notificacionModel->notificarPedidoListo($pedido['id_mesa'], $id);

            $this->registrarActividad('order_ready', ['order_id' => $id]);
            
            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Pedido marcado como listo',
                'pedido_id' => $id
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Cambia el estado de un pedido
     * @param int $id
     * @param string $estado
     * @return \CodeIgniter\HTTP\Response
     */
    public function cambiarEstado(int $id, string $estado)
    {
        try {
            if (!$this->verificarSesion() || !$this->esCocinero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            $statesValid = [
                PedidoModel::ESTADO_PENDIENTE,
                PedidoModel::ESTADO_EN_PREPARACION,
                PedidoModel::ESTADO_LISTO,
                PedidoModel::ESTADO_ENTREGADO
            ];
            if (!in_array($estado, $estadosValidos)) {
                throw new Exception('Estado no valido');
            }

             // Actualizar en BD
            $resultado = $this->pedidoModel->cambiarEstado($id, $estado);

            if (!$resultado) {
                throw new Exception('Error al cambiar estado');
            }

            $this->registrarActividad('cambiar_estado_pedido', [
                'pedido_id' => $id,
                'nuevo_estado' => $estado
            ]);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Estado actualizado a: ' . $estado,
                'pedido_id' => $id,
                'nuevo_estado' => $estado
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Toma un pedido para preparacion
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function tomarPedido(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->esCocinero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            $cocineroId = session('id');

            // Asignar cocinero y cambiar estado
            $resultado = $this->pedidoModel->asignarCocinero($id, $cocineroId);

            if (!$resultado) {
                throw new Exception('Error al tomar el pedido');
            }

            $this->registrarActividad('tomar_pedido', [
                'pedido_id' => $id,
                'cocinero_id' => $cocineroId
            ]);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Pedido tomado para preparacion',
                'pedido_id' => $id
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * API: Obtiene pedidos por estado
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiPedidos()
    {
        try {
            if (!$this->verificarSesion() || !$this->esCocinero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            $pedidos = [
                'pendientes' => $this->obtenerPedidosPorEstado(PedidoModel::ESTADO_PENDIENTE),
                'preparacion' => $this->obtenerPedidosPorEstado(PedidoModel::ESTADO_EN_PREPARACION),
                'listos' => $this->obtenerPedidosPorEstado(PedidoModel::ESTADO_LISTO)
            ];

            return $this->response->setJSON([
                'error' => false,
                'datos' => $pedidos,
                'estadisticas' => $this->obtenerEstadisticas(),
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
     * Obtiene pedidos por estado desde la base de datos
     * @param string $estado
     * @return array
     */
    private function obtenerPedidosPorEstado(string $estado): array
    {
        try {
            $pedidos = $this->pedidoModel->obtenerPorEstado($estado);
            
            // Agregar detalles y calcular tiempos
            foreach ($pedidos as &$pedido) {
                $pedido['detalles'] = $this->pedidoModel->obtenerDetalles($pedido['id_pedido']);
                $pedido['tiempo_transcurrido'] = $this->pedidoModel->calcularTiempoTranscurrido($pedido['fecha_pedido']);
                $pedido['hora_pedido'] = date('H:i', strtotime($pedido['fecha_pedido']));
                $pedido['urgente'] = $pedido['prioridad'] === PedidoModel::PRIORIDAD_URGENTE;
                
                // Formatear productos para vista
                $productos = [];
                foreach ($pedido['detalles'] as $detalle) {
                    $productos[] = [
                        'nombre' => $detalle['producto_nombre'],
                        'cantidad' => $detalle['cantidad'],
                        'observacion' => $detalle['observaciones'] ?? ''
                    ];
                }
                $pedido['productos'] = $productos;
            }

            return $pedidos;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener pedidos por estado: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadisticas para el dashboard del cocinero
     * @return array
     */
    private function obtenerEstadisticas(): array
    {
        try {
            $stats = $this->pedidoModel->obtenerEstadisticas();
            
            return [
                'pendientes' => $stats['pendientes'] ?? 0,
                'preparacion' => $stats['en_preparacion'] ?? 0,
                'listos' => $stats['listos'] ?? 0,
                'urgentes' => $stats['urgentes'] ?? 0,
                'total_hoy' => $stats['total_hoy'] ?? 0
            ];

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [
                'pendientes' => 0,
                'preparacion' => 0,
                'listos' => 0,
                'urgentes' => 0,
                'total_hoy' => 0
            ];
        }
    }
}