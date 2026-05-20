<?php

namespace App\Controllers;

use App\Models\PersonaModel;
use App\Models\PedidoModel;
use App\Models\MesaModel;
use App\Models\ProductoModel;
use App\Models\FacturaModel;
use Exception;

/**
 * Controlador de Dashboard
 * Maneja el panel de administracion principal
 */
class Dashboard extends BaseController
{
    /**
     * Modelos
     */
    protected PersonaModel $personaModel;
    protected PedidoModel $pedidoModel;
    protected MesaModel $mesaModel;
    protected ProductoModel $productoModel;
    protected FacturaModel $facturaModel;

    /**
     * Constructor - Inicializa los modelos
     */
    public function __construct()
    {
        $this->personaModel = new PersonaModel();
        $this->pedidoModel = new PedidoModel();
        $this->mesaModel = new MesaModel();
        $this->productoModel = new ProductoModel();
        $this->facturaModel = new FacturaModel();
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
     * Verifica si el usuario es administrador
     * @return bool
     */
    private function esAdmin(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['admin', 'Administrador']);
    }

    /**
     * Dashboard principal
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function index()
    {
        // Verificar sesion
        if (!$this->verificarSesion()) {
            session()->setFlashdata('error', 'Debes iniciar sesion');
            return redirect()->to('/');
        }

        // Verificar tiempo de sesion (2 horas)
        $tiempoLogin = session('tiempo_login') ?? 0;
        $tiempoActual = time();
        $tiempoMaximo = 7200;

        if (($tiempoActual - $tiempoLogin) > $tiempoMaximo) {
            session()->destroy();
            session()->setFlashdata('error', 'Tu sesion ha expirado');
            return redirect()->to('/');
        }

        // Obtener estadisticas desde la BD
        $datos = [
            'usuario' => session('nombre'),
            'rol' => session('rol'),
            'estadisticas' => $this->obtenerEstadisticasGenerales(),
            'pedidos_recientes' => $this->obtenerPedidosRecientes(),
            'mesas' => $this->mesaModel->obtenerTodas(),
            'ventas_hoy' => $this->facturaModel->obtenerEstadisticas()
        ];

        return view('dashboard', $datos);
    }

    /**
     * Obtiene estadisticas generales del sistema
     * @return array
     */
    private function obtenerEstadisticasGenerales(): array
    {
        try {
            $pedidosStats = $this->pedidoModel->obtenerEstadisticas();
            $mesasStats = $this->mesaModel->obtenerEstadisticas();
            $productosStats = $this->productoModel->obtenerEstadisticas();
            $ventasStats = $this->facturaModel->obtenerEstadisticas();

            return [
                'pedidos' => [
                    'total_hoy' => $pedidosStats['total_hoy'] ?? 0,
                    'pendientes' => $pedidosStats['pendientes'] ?? 0,
                    'en_preparacion' => $pedidosStats['en_preparacion'] ?? 0,
                    'listos' => $pedidosStats['listos'] ?? 0,
                    'entregados_hoy' => $pedidosStats['entregados_hoy'] ?? 0
                ],
                'mesas' => [
                    'total' => $mesasStats['total'] ?? 0,
                    'disponibles' => $mesasStats['disponibles'] ?? 0,
                    'ocupadas' => $mesasStats['ocupadas'] ?? 0,
                    'capacidad_total' => $mesasStats['capacidad_total'] ?? 0
                ],
                'productos' => [
                    'total' => $productosStats['total'] ?? 0,
                    'por_categoria' => $productosStats['por_categoria'] ?? []
                ],
                'ventas' => [
                    'hoy' => $ventasStats['ventas_hoy'] ?? 0,
                    'mes' => $ventasStats['ventas_mes'] ?? 0,
                    'facturas_hoy' => $ventasStats['total_facturas_hoy'] ?? 0,
                    'promedio' => $ventasStats['promedio_venta'] ?? 0
                ]
            ];

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadisticas: ' . $e->getMessage());
            return [
                'pedidos' => ['total_hoy' => 0, 'pendientes' => 0, 'en_preparacion' => 0, 'listos' => 0, 'entregados_hoy' => 0],
                'mesas' => ['total' => 0, 'disponibles' => 0, 'ocupadas' => 0, 'capacidad_total' => 0],
                'productos' => ['total' => 0, 'por_categoria' => []],
                'ventas' => ['hoy' => 0, 'mes' => 0, 'facturas_hoy' => 0, 'promedio' => 0]
            ];
        }
    }

    /**
     * Obtiene los pedidos mas recientes
     * @param int $limite
     * @return array
     */
    private function obtenerPedidosRecientes(int $limite = 5): array
    {
        try {
            $pedidos = $this->pedidoModel->obtenerActivos();
            return array_slice($pedidos, 0, $limite);
        } catch (Exception $e) {
            log_message('error', 'Error al obtener pedidos recientes: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Vista de usuarios (solo admin)
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function usuarios()
    {
        if (!$this->verificarSesion() || !$this->esAdmin()) {
            session()->setFlashdata('error', 'No tienes permisos para acceder');
            return redirect()->to('/dashboard');
        }

        $datos = [
            'personas' => $this->personaModel->obtenerTodos(),
            'estadisticas' => [
                'total' => count($this->personaModel->obtenerTodos()),
                'meseros' => count($this->personaModel->obtenerMeseros()),
                'cocineros' => count($this->personaModel->obtenerCocineros())
            ]
        ];

        return view('personas', $datos);
    }

    /**
     * Vista de reportes (solo admin)
     * @return string|\CodeIgniter\HTTP\RedirectResponse
     */
    public function reportes()
    {
        if (!$this->verificarSesion() || !$this->esAdmin()) {
            session()->setFlashdata('error', 'No tienes permisos para acceder');
            return redirect()->to('/dashboard');
        }

        $fechaInicio = $this->request->getGet('fecha_inicio') ?? date('Y-m-01');
        $fechaFin = $this->request->getGet('fecha_fin') ?? date('Y-m-d');

        $datos = [
            'reporte' => $this->facturaModel->generarReporteVentas($fechaInicio, $fechaFin),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        ];

        return view('reportes', $datos);
    }

    /**
     * API: Estadisticas en tiempo real
     * @return \CodeIgniter\HTTP\Response
     */
    public function apiEstadisticas()
    {
        try {
            if (!$this->verificarSesion()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(401);
            }

            $datos = [
                'error' => false,
                'estadisticas' => $this->obtenerEstadisticasGenerales(),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            return $this->response->setJSON($datos);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}