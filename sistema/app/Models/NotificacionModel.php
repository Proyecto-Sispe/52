<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Notificacion
 * Maneja todas las operaciones de base de datos relacionadas con notificaciones
 */
class NotificacionModel extends Model
{
    protected $table = 'Notificaciones';
    protected $primaryKey = 'id_notificacion';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'tipo',
        'mensaje',
        'id_mesa',
        'id_pedido',
        'leida',
        'fecha_creacion',
        'destinatario_rol'
    ];

    /**
     * Tipos de notificacion
     */
    public const TIPO_PEDIDO_LISTO = 'pedido_listo';
    public const TIPO_NUEVO_PEDIDO = 'nuevo_pedido';
    public const TIPO_PEDIDO_URGENTE = 'pedido_urgente';
    public const TIPO_MESA_LIBERADA = 'mesa_liberada';

    /**
     * Obtiene notificaciones para un rol
     * @param int $rolId
     * @param bool $soloNoLeidas
     * @return array
     */
    public function obtenerPorRol(int $rolId, bool $soloNoLeidas = true): array
    {
        try {
            $builder = $this->db->table('Notificaciones n');
            $builder->select('n.*, m.Ubicacion as mesa_ubicacion');
            $builder->join('Mesa m', 'm.id_Mesa = n.id_mesa', 'left');
            $builder->where('n.destinatario_rol', $rolId);

            if ($soloNoLeidas) {
                $builder->where('n.leida', 0);
            }

            $builder->orderBy('n.fecha_creacion', 'DESC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener notificaciones: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene notificaciones para meseros (rol 3)
     * @param bool $soloNoLeidas
     * @return array
     */
    public function obtenerParaMeseros(bool $soloNoLeidas = true): array
    {
        return $this->obtenerPorRol(3, $soloNoLeidas);
    }

    /**
     * Obtiene notificaciones para cocineros (rol 2)
     * @param bool $soloNoLeidas
     * @return array
     */
    public function obtenerParaCocineros(bool $soloNoLeidas = true): array
    {
        return $this->obtenerPorRol(2, $soloNoLeidas);
    }

    /**
     * Crea una notificacion
     * @param string $tipo
     * @param string $mensaje
     * @param int|null $mesaId
     * @param int|null $pedidoId
     * @param int|null $destinatarioRol
     * @return int|bool
     */
    public function crear(string $tipo, string $mensaje, ?int $mesaId = null, ?int $pedidoId = null, ?int $destinatarioRol = null)
    {
        try {
            $datosNotificacion = [
                'tipo' => $tipo,
                'mensaje' => $mensaje,
                'id_mesa' => $mesaId,
                'id_pedido' => $pedidoId,
                'leida' => 0,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'destinatario_rol' => $destinatarioRol
            ];

            $this->db->table('Notificaciones')->insert($datosNotificacion);

            return $this->db->insertID();

        } catch (Exception $e) {
            log_message('error', 'Error al crear notificacion: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crea notificacion de pedido listo
     * @param int $mesaId
     * @param int $pedidoId
     * @return int|bool
     */
    public function notificarPedidoListo(int $mesaId, int $pedidoId)
    {
        return $this->crear(
            self::TIPO_PEDIDO_LISTO,
            "Pedido listo en mesa $mesaId",
            $mesaId,
            $pedidoId,
            3 // Meseros
        );
    }

    /**
     * Crea notificacion de nuevo pedido
     * @param int $mesaId
     * @param int $pedidoId
     * @return int|bool
     */
    public function notificarNuevoPedido(int $mesaId, int $pedidoId)
    {
        return $this->crear(
            self::TIPO_NUEVO_PEDIDO,
            "Nuevo pedido en mesa $mesaId",
            $mesaId,
            $pedidoId,
            2 // Cocineros
        );
    }

    /**
     * Crea notificacion de pedido urgente
     * @param int $mesaId
     * @param int $pedidoId
     * @return int|bool
     */
    public function notificarPedidoUrgente(int $mesaId, int $pedidoId)
    {
        return $this->crear(
            self::TIPO_PEDIDO_URGENTE,
            "Pedido URGENTE en mesa $mesaId",
            $mesaId,
            $pedidoId,
            2 // Cocineros
        );
    }

    /**
     * Marca una notificacion como leida
     * @param int $id
     * @return bool
     */
    public function marcarLeida(int $id): bool
    {
        try {
            return $this->db->table('Notificaciones')
                ->where('id_notificacion', $id)
                ->update(['leida' => 1]);

        } catch (Exception $e) {
            log_message('error', 'Error al marcar notificacion como leida: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marca todas las notificaciones de un rol como leidas
     * @param int $rolId
     * @return bool
     */
    public function marcarTodasLeidas(int $rolId): bool
    {
        try {
            return $this->db->table('Notificaciones')
                ->where('destinatario_rol', $rolId)
                ->where('leida', 0)
                ->update(['leida' => 1]);

        } catch (Exception $e) {
            log_message('error', 'Error al marcar notificaciones como leidas: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cuenta notificaciones no leidas por rol
     * @param int $rolId
     * @return int
     */
    public function contarNoLeidas(int $rolId): int
    {
        try {
            return $this->db->table('Notificaciones')
                ->where('destinatario_rol', $rolId)
                ->where('leida', 0)
                ->countAllResults();

        } catch (Exception $e) {
            log_message('error', 'Error al contar notificaciones: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Elimina notificaciones antiguas
     * @param int $diasAntiguedad
     * @return bool
     */
    public function limpiarAntiguas(int $diasAntiguedad = 30): bool
    {
        try {
            $fechaLimite = date('Y-m-d H:i:s', strtotime("-$diasAntiguedad days"));

            return $this->db->table('Notificaciones')
                ->where('fecha_creacion <', $fechaLimite)
                ->where('leida', 1)
                ->delete();

        } catch (Exception $e) {
            log_message('error', 'Error al limpiar notificaciones: ' . $e->getMessage());
            return false;
        }
    }
}
