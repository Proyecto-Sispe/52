<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Categoria
 * Maneja todas las operaciones de base de datos relacionadas con categorias de productos
 */
class CategoriaModel extends Model
{
    protected $table = 'Categoria';
    protected $primaryKey = 'id_categoria';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_categoria',
        'nom_categoria'
    ];

    protected $validationRules = [
        'id_categoria' => 'required|numeric',
        'nom_categoria' => 'required|max_length[100]'
    ];

    /**
     * Obtiene todas las categorias
     * @return array
     */
    public function obtenerTodas(): array
    {
        try {
            $builder = $this->db->table('Categoria c');
            $builder->select('c.*, (SELECT COUNT(*) FROM Menu m WHERE m.pkfk_id_categoria = c.id_categoria) as total_productos');
            $builder->orderBy('c.nom_categoria', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener categorias: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene una categoria por ID
     * @param int $id
     * @return array|null
     */
    public function obtenerPorId(int $id): ?array
    {
        try {
            $resultado = $this->db->table('Categoria')
                ->where('id_categoria', $id)
                ->get()
                ->getRowArray();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener categoria: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea una nueva categoria
     * @param array $datos
     * @return bool
     */
    public function crearCategoria(array $datos): bool
    {
        try {
            return $this->db->table('Categoria')->insert([
                'id_categoria' => $datos['id_categoria'],
                'nom_categoria' => $datos['nom_categoria']
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error al crear categoria: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una categoria
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizarCategoria(int $id, array $datos): bool
    {
        try {
            return $this->db->table('Categoria')
                ->where('id_categoria', $id)
                ->update(['nom_categoria' => $datos['nom_categoria']]);

        } catch (Exception $e) {
            log_message('error', 'Error al actualizar categoria: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina una categoria
     * @param int $id
     * @return bool
     */
    public function eliminarCategoria(int $id): bool
    {
        try {
            // Verificar que no tenga productos
            $productos = $this->db->table('Menu')
                ->where('pkfk_id_categoria', $id)
                ->countAllResults();

            if ($productos > 0) {
                throw new Exception('No se puede eliminar una categoria con productos');
            }

            return $this->db->table('Categoria')
                ->where('id_categoria', $id)
                ->delete();

        } catch (Exception $e) {
            log_message('error', 'Error al eliminar categoria: ' . $e->getMessage());
            return false;
        }
    }
}