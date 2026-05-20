<?php

namespace App\Controllers;

use App\Models\MesaModel;
use App\Models\PedidoModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Mesa
 * Maneja todas las operaciones CRUD de mesas
 */
class Mesa extends BaseController
{
    /**
     * Modelos
     */
    protected MesaModel $mesaModel;
    protected PedidoModel $pedidoModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->mesaModel = new MesaModel();
        $this->pedidoModel = new PedidoModel();
    }

    /**
     * Verifica si el usuario tiene sesion activa
     */
    private function verificarSesion(): bool
    {
        return session('logueado') === true;
    }

    /**
     * Verifica si el usuario es admin o mesero
     */
    private function tienePermiso(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['admin', 'Administrador', 'mesero', 'Mesero']);
    }

    /**
     * Lista todas las mesas (vista de tarjetas)
     */
    public function index()
    {
        if (!$this->verificarSesion()) {
            return redirect()->to('/');
        }

        $datos = [
            'mesas' => $this->mesaModel->obtenerTodas(),
            'estadisticas' => $this->mesaModel->obtenerEstadisticas()
        ];

        return view('mesas', $datos);
    }

    /**
     * Vista de gestion de mesas (tabla administrativa)
     */
    public function gestion()
    {
        if (!$this->verificarSesion() || !$this->tienePermiso()) {
            session()->setFlashdata('error', 'No tienes permisos');
            return redirect()->to('/dashboard');
        }

        $datos = [
            'mesas' => $this->mesaModel->obtenerTodas(),
            'estadisticas' => $this->mesaModel->obtenerEstadisticas()
        ];

        return view('gestion_mesas', $datos);
    }

    /**
     * Formulario para agregar mesa
     */
    public function agregar()
    {
        if (!$this->verificarSesion() || !$this->tienePermiso()) {
            return redirect()->to('/dashboard');
        }

        return view('agregar_mesa');
    }

    /**
     * Guarda una nueva mesa
     */
    public function guardar()
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $datos = [
                'id_Mesa' => $this->request->getPost('id_mesa'),
                'Capacidad' => $this->request->getPost('capacidad'),
                'Ubicacion' => $this->request->getPost('ubicacion'),
                'Estado' => MesaModel::ESTADO_DISPONIBLE
            ];

            if (empty($datos['id_Mesa']) || empty($datos['Capacidad']) || empty($datos['Ubicacion'])) {
                throw new Exception('Todos los campos son obligatorios');
            }

            $resultado = $this->mesaModel->crearMesa($datos);

            if (!$resultado) {
                throw new Exception('Error al crear la mesa');
            }

            session()->setFlashdata('exito', 'Mesa creada correctamente');
            return redirect()->to('/mesas/gestion');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/mesas/agregar');
        }
    }

    /**
     * Formulario para editar mesa
     */
    public function editar(int $id)
    {
        if (!$this->verificarSesion() || !$this->tienePermiso()) {
            return redirect()->to('/dashboard');
        }

        $mesa = $this->mesaModel->obtenerPorId($id);

        if ($mesa === null) {
            throw new PageNotFoundException('Mesa no encontrada');
        }

        $datos = ['mesa' => $mesa];

        return view('editar_mesa', $datos);
    }

    /**
     * Actualiza una mesa
     */
    public function actualizar(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $datos = [
                'Capacidad' => $this->request->getPost('capacidad'),
                'Ubicacion' => $this->request->getPost('ubicacion'),
                'Estado' => $this->request->getPost('estado')
            ];

            $resultado = $this->mesaModel->actualizarMesa($id, $datos);

            if (!$resultado) {
                throw new Exception('Error al actualizar la mesa');
            }

            session()->setFlashdata('exito', 'Mesa actualizada correctamente');
            return redirect()->to('/mesas/gestion');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/mesas/editar/' . $id);
        }
    }

    /**
     * Elimina una mesa
     */
    public function eliminar(int $id)
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $resultado = $this->mesaModel->eliminarMesa($id);

            if (!$resultado) {
                throw new Exception('Error al eliminar la mesa');
            }

            session()->setFlashdata('exito', 'Mesa eliminada correctamente');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
        }

        return redirect()->to('/mesas/gestion');
    }

    /**
     * Cambia el estado de una mesa
     */
    public function cambiarEstado(int $id, string $estado)
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $estadosValidos = [
                'disponible' => MesaModel::ESTADO_DISPONIBLE,
                'ocupada' => MesaModel::ESTADO_OCUPADA,
                'reservada' => MesaModel::ESTADO_RESERVADA
            ];

            if (!isset($estadosValidos[$estado])) {
                throw new Exception('Estado no valido');
            }

            $resultado = $this->mesaModel->cambiarEstado($id, $estadosValidos[$estado]);

            if (!$resultado) {
                throw new Exception('Error al cambiar estado');
            }

            session()->setFlashdata('exito', 'Estado de mesa actualizado');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
        }

        return redirect()->to('/mesas/gestion');
    }

    /**
     * API: Obtiene todas las mesas
     */
    public function apiMesas()
    {
        try {
            if (!$this->verificarSesion()) {
                return $this->response->setJSON([
                    'error' => true,
                    'mensaje' => 'No autorizado'
                ])->setStatusCode(401);
            }

            $mesas = $this->mesaModel->obtenerTodas();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $mesas,
                'total' => count($mesas)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * API: Obtiene mesas disponibles
     */
    public function apiDisponibles()
    {
        try {
            $mesas = $this->mesaModel->obtenerDisponibles();

            return $this->response->setJSON([
                'error' => false,
                'datos' => $mesas,
                'total' => count($mesas)
            ]);

        } catch (Exception $e) {
            return $this->response->setJSON([
                'error' => true,
                'mensaje' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}