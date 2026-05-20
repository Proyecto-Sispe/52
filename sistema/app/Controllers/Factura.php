<?php

namespace App\Controllers;

use App\Models\FacturaModel;
use App\Models\PedidoModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Exception;

/**
 * Controlador de Factura
 * Maneja todas las operaciones de facturas
 */
class Factura extends BaseController
{
    /**
     * Modelos
     */
    protected FacturaModel $facturaModel;
    protected PedidoModel $pedidoModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->facturaModel = new FacturaModel();
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
     * Verifica si el usuario tiene permiso
     */
    private function tienePermiso(): bool
    {
        $rol = session('rol') ?? '';
        return in_array($rol, ['admin', 'Administrador', 'mesero', 'Mesero']);
    }

    /**
     * Lista todas las facturas
     */
    public function index()
    {
        if (!$this->verificarSesion()) {
            return redirect()->to('/');
        }

        $datos = [
            'facturas' => $this->facturaModel->obtenerTodas(),
            'estadisticas' => $this->facturaModel->obtenerEstadisticas(),
            'metodos_pago' => $this->facturaModel->obtenerMetodosPagoDisponibles()
        ];

        return view('facturas', $datos);
    }

    /**
     * Formulario para crear factura
     */
    public function agregar()
    {
        if (!$this->verificarSesion() || !$this->tienePermiso()) {
            return redirect()->to('/dashboard');
        }

        // Obtener pedidos entregados sin factura
        $pedidosEntregados = $this->pedidoModel->obtenerPorEstado(PedidoModel::ESTADO_ENTREGADO);

        $datos = [
            'pedidos' => $pedidosEntregados,
            'metodos_pago' => $this->facturaModel->obtenerMetodosPagoDisponibles()
        ];

        return view('agregar_factura', $datos);
    }

    /**
     * Guarda una nueva factura
     */
    public function guardar()
    {
        try {
            if (!$this->verificarSesion() || !$this->tienePermiso()) {
                throw new Exception('No tienes permisos');
            }

            $pedidoId = $this->request->getPost('pedido_id');
            $metodoPagoId = $this->request->getPost('metodo_pago');

            if (empty($pedidoId)) {
                throw new Exception('Debe seleccionar un pedido');
            }

            // Obtener el total del pedido
            $total = $this->pedidoModel->calcularTotal($pedidoId);

            if ($total <= 0) {
                throw new Exception('El pedido no tiene productos');
            }

            // Crear la factura
            $facturaId = $this->facturaModel->crearFactura($pedidoId, $total);

            if (!$facturaId) {
                throw new Exception('Error al crear la factura');
            }

            // Registrar el pago
            if (!empty($metodoPagoId)) {
                $this->facturaModel->registrarPago($facturaId, $metodoPagoId, $total);
            }

            session()->setFlashdata('exito', 'Factura creada correctamente');
            return redirect()->to('/facturas');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
            return redirect()->to('/facturas/agregar');
        }
    }

    /**
     * Ver detalle de factura
     */
    public function ver(int $id)
    {
        if (!$this->verificarSesion()) {
            return redirect()->to('/');
        }

        $factura = $this->facturaModel->obtenerPorId($id);

        if ($factura === null) {
            throw new PageNotFoundException('Factura no encontrada');
        }

        $datos = ['factura' => $factura];

        return view('ver_factura', $datos);
    }

    /**
     * Imprime una factura
     */
    public function imprimir(int $id)
    {
        if (!$this->verificarSesion()) {
            return redirect()->to('/');
        }

        $factura = $this->facturaModel->obtenerPorId($id);

        if ($factura === null) {
            throw new PageNotFoundException('Factura no encontrada');
        }

        $datos = ['factura' => $factura];

        return view('imprimir_factura', $datos);
    }

    /**
     * Elimina una factura
     */
    public function eliminar(int $id)
    {
        try {
            if (!$this->verificarSesion() || session('rol') !== 'admin') {
                throw new Exception('No tienes permisos');
            }

            $resultado = $this->facturaModel->delete($id);

            if (!$resultado) {
                throw new Exception('Error al eliminar la factura');
            }

            session()->setFlashdata('exito', 'Factura eliminada correctamente');

        } catch (Exception $e) {
            session()->setFlashdata('error', $e->getMessage());
        }

        return redirect()->to('/facturas');
    }
}
