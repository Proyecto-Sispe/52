<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Mesero
 * Maneja la vista y operaciones del mesero
 */
class Mesero extends BaseController
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
     * Verifica si el usuario es mesero o admin
     * @return bool
     */
    private function esMesero(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['mesero', 'admin']);
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

        // Datos de ejemplo
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
            'mesas' => $this->obtenerMesas()
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

            // En produccion: actualizar en BD
            // $this->pedidoModel->update($id, ['estado' => 'entregado']);

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
     * Obtiene todos los pedidos activos (datos de ejemplo)
     * @return array
     */
    private function obtenerTodosPedidos(): array
    {
        return [
            [
                'id' => 1,
                'mesa' => 3,
                'cliente' => 'Cliente Mesa 3',
                'productos' => 'Hamburguesa x2, Papas x2',
                'estado' => 'pendiente',
                'hora_pedido' => date('H:i', strtotime('-15 minutes')),
                'tiempo_espera' => 15,
                'urgente' => false,
                'total' => 45000
            ],
            [
                'id' => 2,
                'mesa' => 5,
                'cliente' => 'Cliente Mesa 5',
                'productos' => 'Pizza Pepperoni x1, Gaseosa x2',
                'estado' => 'listo',
                'hora_pedido' => date('H:i', strtotime('-25 minutes')),
                'tiempo_espera' => 25,
                'urgente' => true,
                'total' => 38000
            ],
            [
                'id' => 3,
                'mesa' => 1,
                'cliente' => 'Cliente Mesa 1',
                'productos' => 'Ensalada Caesar x1',
                'estado' => 'preparacion',
                'hora_pedido' => date('H:i', strtotime('-10 minutes')),
                'tiempo_espera' => 10,
                'urgente' => false,
                'total' => 22000
            ],
            [
                'id' => 4,
                'mesa' => 8,
                'cliente' => 'Cliente Mesa 8',
                'productos' => 'Bandeja Paisa x2, Jugo x2',
                'estado' => 'listo',
                'hora_pedido' => date('H:i', strtotime('-22 minutes')),
                'tiempo_espera' => 22,
                'urgente' => true,
                'total' => 56000
            ]
        ];
    }

    /**
     * Obtiene notificaciones (pedidos listos)
     * @return array
     */
    private function obtenerNotificaciones(): array
    {
        $pedidos = $this->obtenerTodosPedidos();
        
        // Filtrar pedidos listos
        return array_filter($pedidos, function($p) {
            return $p['estado'] === 'listo';
        });
    }

    /**
     * Obtiene mesas con su estado
     * @return array
     */
    private function obtenerMesas(): array
    {
        return [
            ['id' => 1, 'numero' => 1, 'capacidad' => 4, 'estado' => 'ocupada', 'pedidos_activos' => 1, 'ubicacion' => 'Interior'],
            ['id' => 2, 'numero' => 2, 'capacidad' => 2, 'estado' => 'disponible', 'pedidos_activos' => 0, 'ubicacion' => 'Interior'],
            ['id' => 3, 'numero' => 3, 'capacidad' => 6, 'estado' => 'ocupada', 'pedidos_activos' => 2, 'ubicacion' => 'Interior'],
            ['id' => 4, 'numero' => 4, 'capacidad' => 4, 'estado' => 'disponible', 'pedidos_activos' => 0, 'ubicacion' => 'Terraza'],
            ['id' => 5, 'numero' => 5, 'capacidad' => 8, 'estado' => 'ocupada', 'pedidos_activos' => 1, 'ubicacion' => 'Terraza'],
            ['id' => 6, 'numero' => 6, 'capacidad' => 4, 'estado' => 'reservada', 'pedidos_activos' => 0, 'ubicacion' => 'Interior'],
            ['id' => 7, 'numero' => 7, 'capacidad' => 2, 'estado' => 'disponible', 'pedidos_activos' => 0, 'ubicacion' => 'Barra'],
            ['id' => 8, 'numero' => 8, 'capacidad' => 4, 'estado' => 'ocupada', 'pedidos_activos' => 1, 'ubicacion' => 'Interior']
        ];
    }

    /**
     * Obtiene estadisticas para el dashboard del mesero
     * @return array
     */
    private function obtenerEstadisticas(): array
    {
        $pedidos = $this->obtenerTodosPedidos();
        $mesas = $this->obtenerMesas();

        return [
            'pedidos_activos' => count($pedidos),
            'pedidos_listos' => count(array_filter($pedidos, fn($p) => $p['estado'] === 'listo')),
            'mesas_ocupadas' => count(array_filter($mesas, fn($m) => $m['estado'] === 'ocupada')),
            'mesas_disponibles' => count(array_filter($mesas, fn($m) => $m['estado'] === 'disponible'))
        ];
    }
}