<?php

namespace App\Controllers;

use App\Models\PedidoModel;
use App\Models\MesaModel;
use App\Models\NotificacionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Mesero
 * Manages the waiter's view and operations
 * Conectado con la base de datos real
 */
class Mesero extends BaseController
{
    /**
     * Modelos
     */
    protected PedidoModel $pedidoModel;
    protected MesaModel $mesaModel;
    protected NotificacionModel $notificacionModel;

    /**
     * Constructor - Inicializa los modelos
     */
    public function __construct()
    {
        $this->pedidoModel = new PedidoModel();
        $this->mesaModel = new MesaModel();
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
     * Verifica si el usuario es mesero o admin
     * @return bool
     */
    private function esMesero(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['waiter', 'Waiter', 'admin', 'Administrator']);;
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
        log_message('info', 'Actividad Mesero: ' . json_encode($log));
    }

    /**
     * Vista principal del mesero
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // Verificar sesion y rol
        if (!$this->verificarSesion()) {
            session()->setFlashdata('error', 'Debes iniciar sesion');
            return redirect()->to('/');
        }

        if (!$this->esMesero()) {
            session()->setFlashdata('error', 'No tienes permisos para acceder a esta seccion');
            return redirect()->to('/dashboard');
        }

        $this->registrarActividad('vista_mesero');

        // Obtener datos desde la BD
        $datos = [
            'pedidos' => $this->obtenerTodosPedidos(),
            'notificaciones' => $this->obtenerNotificaciones(),
            'mesas' => $this->obtenerMesas(),
            'estadisticas' => $this->obtenerEstadisticas()
        ];

        return view('vista_mesero', $datos);
    }

    /**
     * Vista de mesas para el mesero
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function mesas()
    {
        if (!$this->verificarSesion() || !$this->esMesero()) {
            return redirect()->to('/');
        }

        $datos = [
            'mesas' => $this->obtenerMesas(),
            'estadisticas' => $this->mesaModel->obtenerEstadisticas()
        ];

        return view('vista_mesas', $datos);
    }

    /**
     * Marca un pedido como entregado
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function marcarEntregado(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->esMesero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            // Actualizar en BD
            $resultado = $this->pedidoModel->marcarEntregado($id);

            if (!$resultado) {
                throw new Exception('Error al marcar pedido como entregado');
            }

            $this->registrarActividad('pedido_entregado', ['pedido_id' => $id]);

            return $this->response->setJSON([
                'error' => false,
                'mensaje' => 'Pedido marcado como entregado',
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
     * Obtiene notificaciones de pedidos listos
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiNotificaciones()
    {
        try {
            if (!$this->verificarSesion() || !$this->esMesero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            $notificaciones = $this->obtenerNotificaciones();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $notificaciones,
                'total' => count($notificaciones),
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
     * API: Obtiene todos los pedidos activos
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiPedidos()
    {
        try {
            if (!$this->verificarSesion() || !$this->esMesero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            $pedidos = $this->obtenerTodosPedidos();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $pedidos,
                'total' => count($pedidos),
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
     * Mark notification as read
     * @param int $id
     * @return \CodeIgniter\HTTP\Response
     */
    public function marcarNotificacionLeida(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->esMesero()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(403);
            }

            $resultado = $this->notificacionModel->marcarLeida($id);

            return $this->response->setJSON([
                'error' => !$resultado,
                'mensaje' => $resultado ? 'Notificacion marcada como leida' : 'Error al marcar notificacion'
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Obtiene todos los pedidos activos desde la BD
     * @return array
     */
    private function obtenerTodosPedidos(): array
    {
        try {
            $pedidos = $this->pedidoModel->obtenerActivos();
            
            // Formatear pedidos para la vista
            $pedidosFormateados = [];
            foreach ($pedidos as $pedido) {
                $detalles = $this->pedidoModel->obtenerDetalles($pedido['id_pedido']);
                $productosTexto = [];
                
                foreach ($detalles as $detalle) {
                    $productosTexto[] = $detalle['producto_nombre'] . ' x' . $detalle['cantidad'];
                }

                $pedidosFormateados[] = [
                    'id' => $pedido['id_pedido'],
                    'mesa' => $pedido['id_mesa'],
                    'cliente' => $pedido['cliente_nombre'] ?? 'Cliente Mesa ' . $pedido['id_mesa'],
                    'productos' => implode(', ', $productosTexto),
                    'estado' => $pedido['estado'],
                    'hora_pedido' => date('H:i', strtotime($pedido['fecha_pedido'])),
                    'tiempo_espera' => $this->pedidoModel->calcularTiempoTranscurrido($pedido['fecha_pedido']),
                    'urgente' => $pedido['prioridad'] === PedidoModel::PRIORIDAD_URGENTE,
                    'total' => $this->pedidoModel->calcularTotal($pedido['id_pedido']),
                    'mesero' => $pedido['mesero_nombre'] ?? 'Sin asignar'
                ];
            }

            return $pedidosFormateados;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener pedidos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene notificaciones (pedidos listos) desde la BD
     * @return array
     */
    private function obtenerNotificaciones(): array
    {
        try {
            return $this->notificacionModel->obtenerParaMeseros();
        } catch (Exception $e) {
            log_message('error', 'Error al obtener notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
    * Obtiene mesas con su estado desde la BD
     * @return array
     */
    private function obtenerMesas(): array
    {
        try {
            $mesas = $this->mesaModel->obtenerTodas();
            
            // Formatear para la vista
            $mesasFormateadas = [];
            foreach ($mesas as $mesa) {
                $estadoTexto = 'disponible';
                if ($mesa['Estado'] == MesaModel::ESTADO_OCUPADA) {
                    $estadoTexto = 'ocupada';
                } elseif ($mesa['Estado'] == MesaModel::ESTADO_RESERVADA) {
                    $estadoTexto = 'reservada';
                }

                $mesasFormateadas[] = [
                    'id' => $mesa['id_Mesa'],
                    'numero' => $mesa['id_Mesa'],
                    'capacidad' => $mesa['Capacidad'],
                    'estado' => $estadoTexto,
                    'pedidos_activos' => $mesa['pedidos_activos'] ?? 0,
                    'ubicacion' => $mesa['Ubicacion']
                ];
            }

            return $mesasFormateadas;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener mesas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadisticas para el dashboard del mesero
     * @return array
     */
    private function obtenerEstadisticas(): array
    {
        try {
            $pedidos = $this->obtenerTodosPedidos();
            $mesas = $this->obtenerMesas();

            $pedidosListos = array_filter($pedidos, fn($p) => $p['estado'] === 'listo');
            $mesasOcupadas = array_filter($mesas, fn($m) => $m['estado'] === 'ocupada');
            $mesasDisponibles = array_filter($mesas, fn($m) => $m['estado'] === 'disponible');

            return [
                'pedidos_activos' => count($pedidos),
                'pedidos_listos' => count($pedidosListos),
                'mesas_ocupadas' => count($mesasOcupadas),
                'mesas_disponibles' => count($mesasDisponibles)
            ];

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [
                'pedidos_activos' => 0,
                'pedidos_listos' => 0,
                'mesas_ocupadas' => 0,
                'mesas_disponibles' => 0
            ];
        }
    }
}