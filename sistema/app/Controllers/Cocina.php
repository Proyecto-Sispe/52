<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Cocina
 * Maneja la vista y operaciones del cocinero
 */
class Cocina extends BaseController
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
     * Verifica si el usuario es cocinero o admin
     * @return bool
     */
    private function esCocinero(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['cocinero', 'admin']);
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

        // Datos de ejemplo (en produccion vendrian de la BD)
        $datos = [
            'pedidos_pendientes' => $this->obtenerPedidosPorEstado('pendiente'),
            'pedidos_preparacion' => $this->obtenerPedidosPorEstado('preparacion'),
            'pedidos_listos' => $this->obtenerPedidosPorEstado('listo'),
            'estadisticas' => $this->obtenerEstadisticas()
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

            // En produccion: actualizar en BD
            // $this->pedidoModel->update($id, ['estado' => 'listo']);

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

            $estadosValidos = ['pendiente', 'preparacion', 'listo', 'entregado'];
            if (!in_array($estado, $estadosValidos)) {
                throw new Exception('Estado no valido');
            }

            // En produccion: actualizar en BD
            // $this->pedidoModel->update($id, ['estado' => $estado]);

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

            $estado = $this->request->getGet('estado') ?? 'todos';

            $pedidos = [
                'pendientes' => $this->obtenerPedidosPorEstado('pendiente'),
                'preparacion' => $this->obtenerPedidosPorEstado('preparacion'),
                'listos' => $this->obtenerPedidosPorEstado('listo')
            ];

            return $this->response->setJSON([
                'error' => false,
                'datos' => $pedidos,
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
     * Obtiene pedidos por estado (datos de ejemplo)
     * @param string $estado
     * @return array
     */
    private function obtenerPedidosPorEstado(string $estado): array
    {
        // En produccion: obtener de la BD
        $pedidosEjemplo = [
            [
                'id' => 1,
                'mesa' => 3,
                'productos' => [
                    ['nombre' => 'Hamburguesa Clasica', 'cantidad' => 2, 'observacion' => 'Sin cebolla'],
                    ['nombre' => 'Papas Fritas', 'cantidad' => 2, 'observacion' => '']
                ],
                'estado' => 'pendiente',
                'hora_pedido' => date('H:i', strtotime('-15 minutes')),
                'tiempo_transcurrido' => 15,
                'urgente' => false
            ],
            [
                'id' => 2,
                'mesa' => 5,
                'productos' => [
                    ['nombre' => 'Pizza Pepperoni', 'cantidad' => 1, 'observacion' => 'Extra queso'],
                    ['nombre' => 'Gaseosa', 'cantidad' => 2, 'observacion' => '']
                ],
                'estado' => 'preparacion',
                'hora_pedido' => date('H:i', strtotime('-25 minutes')),
                'tiempo_transcurrido' => 25,
                'urgente' => true
            ],
            [
                'id' => 3,
                'mesa' => 1,
                'productos' => [
                    ['nombre' => 'Ensalada Caesar', 'cantidad' => 1, 'observacion' => 'Sin anchoas']
                ],
                'estado' => 'listo',
                'hora_pedido' => date('H:i', strtotime('-10 minutes')),
                'tiempo_transcurrido' => 10,
                'urgente' => false
            ]
        ];

        // Filtrar por estado
        return array_filter($pedidosEjemplo, function($p) use ($estado) {
            return $p['estado'] === $estado;
        });
    }

    /**
     * Obtiene estadisticas para el dashboard del cocinero
     * @return array
     */
    private function obtenerEstadisticas(): array
    {
        return [
            'pendientes' => count($this->obtenerPedidosPorEstado('pendiente')),
            'preparacion' => count($this->obtenerPedidosPorEstado('preparacion')),
            'listos' => count($this->obtenerPedidosPorEstado('listo')),
            'urgentes' => 1
        ];
    }
}
