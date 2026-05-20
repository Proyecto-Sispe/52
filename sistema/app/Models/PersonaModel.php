<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Persona
 * Maneja todas las operaciones de base de datos relacionadas con personas/usuarios
 */
class PersonaModel extends Model
{
    protected $table = 'Persona';
    protected $primaryKey = 'id_usuario';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_usuario',
        'pkfk_Tipo_doc',
        'Nom1_usu',
        'Nom2_usu',
        'Ape1_usu',
        'Ape2_usu',
        'Telefono',
        'Correo_usu',
        'Password',
        'estado'
    ];

    protected $validationRules = [
        'id_usuario' => 'required|numeric',
        'pkfk_Tipo_doc' => 'required|numeric',
        'Nom1_usu' => 'required|min_length[2]|max_length[20]',
        'Ape1_usu' => 'required|min_length[2]|max_length[20]',
        'Correo_usu' => 'permit_empty|valid_email|max_length[45]'
    ];

    protected $validationMessages = [
        'id_usuario' => [
            'required' => 'El numero de documento es obligatorio',
            'numeric' => 'El numero de documento debe ser numerico'
        ],
        'Nom1_usu' => [
            'required' => 'El primer nombre es obligatorio',
            'min_length' => 'El nombre debe tener al menos 2 caracteres'
        ],
        'Ape1_usu' => [
            'required' => 'El primer apellido es obligatorio'
        ]
    ];

    /**
     * Obtiene todas las personas con sus roles
     * @param array $filtros
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        try {
            $builder = $this->db->table('Persona p');
            $builder->select('p.*, td.tipo_doc, r.Nom_rol as rol');
            $builder->join('Tipo_doc td', 'td.id_doc = p.pkfk_Tipo_doc', 'left');
            $builder->join('Persona_has_Rol phr', 'phr.pkfk_id_usuario = p.id_usuario AND phr.pkfk_Tipo_doc = p.pkfk_Tipo_doc', 'left');
            $builder->join('Rol r', 'r.idRol = phr.pkfk_idRol', 'left');

            if (isset($filtros['rol']) && !empty($filtros['rol'])) {
                $builder->where('r.Nom_rol', $filtros['rol']);
            }

            if (isset($filtros['estado'])) {
                $builder->where('p.estado', $filtros['estado']);
            }

            if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
                $builder->groupStart()
                    ->like('p.Nom1_usu', $filtros['busqueda'])
                    ->orLike('p.Ape1_usu', $filtros['busqueda'])
                    ->orLike('p.Correo_usu', $filtros['busqueda'])
                    ->orLike('p.id_usuario', $filtros['busqueda'])
                    ->groupEnd();
            }

            $builder->orderBy('p.Nom1_usu', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener personas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca persona por correo electronico
     * @param string $correo
     * @return array|null
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        try {
            if (empty($correo)) {
                return null;
            }

            $builder = $this->db->table('Persona p');
            $builder->select('p.*, r.Nom_rol as rol, r.idRol');
            $builder->join('Persona_has_Rol phr', 'phr.pkfk_id_usuario = p.id_usuario AND phr.pkfk_Tipo_doc = p.pkfk_Tipo_doc', 'left');
            $builder->join('Rol r', 'r.idRol = phr.pkfk_idRol', 'left');
            $builder->where('p.Correo_usu', $correo);
            $builder->where('p.estado', 1);

            $resultado = $builder->get()->getRowArray();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al buscar persona por correo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca persona por ID
     * @param int $id
     * @param int $tipoDoc
     * @return array|null
     */
    public function buscarPorId(int $id, int $tipoDoc = 1): ?array
    {
        try {
            $builder = $this->db->table('Persona p');
            $builder->select('p.*, td.tipo_doc, r.Nom_rol as rol, r.idRol');
            $builder->join('Tipo_doc td', 'td.id_doc = p.pkfk_Tipo_doc', 'left');
            $builder->join('Persona_has_Rol phr', 'phr.pkfk_id_usuario = p.id_usuario AND phr.pkfk_Tipo_doc = p.pkfk_Tipo_doc', 'left');
            $builder->join('Rol r', 'r.idRol = phr.pkfk_idRol', 'left');
            $builder->where('p.id_usuario', $id);
            $builder->where('p.pkfk_Tipo_doc', $tipoDoc);

            $resultado = $builder->get()->getRowArray();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al buscar persona por ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca personas por rol
     * @param string $rol
     * @return array
     */
    public function buscarPorRol(string $rol): array
    {
        try {
            $builder = $this->db->table('Persona p');
            $builder->select('p.*, r.Nom_rol as rol');
            $builder->join('Persona_has_Rol phr', 'phr.pkfk_id_usuario = p.id_usuario AND phr.pkfk_Tipo_doc = p.pkfk_Tipo_doc');
            $builder->join('Rol r', 'r.idRol = phr.pkfk_idRol');
            $builder->where('r.Nom_rol', $rol);
            $builder->where('p.estado', 1);
            $builder->orderBy('p.Nom1_usu', 'ASC');

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al buscar personas por rol: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene meseros activos
     * @return array
     */
    public function obtenerMeseros(): array
    {
        return $this->buscarPorRol('Mesero');
    }

    /**
     * Obtiene cocineros activos
     * @return array
     */
    public function obtenerCocineros(): array
    {
        return $this->buscarPorRol('Cocinero');
    }

    /**
     * Crea una nueva persona con rol
     * @param array $datos
     * @param int $idRol
     * @return bool
     */
    public function crearPersona(array $datos, int $idRol = 4): bool
    {
        try {
            $this->db->transStart();

            // Insertar persona
            $datosPersona = [
                'id_usuario' => $datos['id_usuario'],
                'pkfk_Tipo_doc' => $datos['pkfk_Tipo_doc'] ?? 1,
                'Nom1_usu' => $datos['Nom1_usu'],
                'Nom2_usu' => $datos['Nom2_usu'] ?? null,
                'Ape1_usu' => $datos['Ape1_usu'],
                'Ape2_usu' => $datos['Ape2_usu'] ?? null,
                'Telefono' => $datos['Telefono'],
                'Correo_usu' => $datos['Correo_usu'] ?? null,
                'Password' => isset($datos['Password']) ? password_hash($datos['Password'], PASSWORD_DEFAULT) : null,
                'estado' => 1
            ];

            $this->db->table('Persona')->insert($datosPersona);

            // Insertar relacion persona-rol
            $datosRol = [
                'pkfk_Tipo_doc' => $datos['pkfk_Tipo_doc'] ?? 1,
                'pkfk_id_usuario' => $datos['id_usuario'],
                'pkfk_idRol' => $idRol
            ];

            $this->db->table('Persona_has_Rol')->insert($datosRol);

            $this->db->transComplete();

            return $this->db->transStatus();

        } catch (Exception $e) {
            log_message('error', 'Error al crear persona: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza una persona
     * @param int $id
     * @param int $tipoDoc
     * @param array $datos
     * @return bool
     */
    public function actualizarPersona(int $id, int $tipoDoc, array $datos): bool
    {
        try {
            $datosActualizar = [];

            $camposPermitidos = ['Nom1_usu', 'Nom2_usu', 'Ape1_usu', 'Ape2_usu', 'Telefono', 'Correo_usu', 'estado'];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $datosActualizar[$campo] = $datos[$campo];
                }
            }

            if (isset($datos['Password']) && !empty($datos['Password'])) {
                $datosActualizar['Password'] = password_hash($datos['Password'], PASSWORD_DEFAULT);
            }

            if (empty($datosActualizar)) {
                return true;
            }

            return $this->db->table('Persona')
                ->where('id_usuario', $id)
                ->where('pkfk_Tipo_doc', $tipoDoc)
                ->update($datosActualizar);

        } catch (Exception $e) {
            log_message('error', 'Error al actualizar persona: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Autentica una persona
     * @param string $correo
     * @param string $password
     * @return array|null
     */
    public function autenticar(string $correo, string $password): ?array
    {
        try {
            if (empty($correo) || empty($password)) {
                return null;
            }

            $persona = $this->buscarPorCorreo($correo);

            if ($persona === null) {
                return null;
            }

            if ($persona['estado'] != 1) {
                return null;
            }

            // Verificar password (puede ser hash o texto plano para datos de prueba)
            if (password_verify($password, $persona['Password']) || $persona['Password'] === $password) {
                unset($persona['Password']);
                return $persona;
            }

            return null;

        } catch (Exception $e) {
            log_message('error', 'Error en autenticacion: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtiene nombre completo de persona
     * @param array $persona
     * @return string
     */
    public function getNombreCompleto(array $persona): string
    {
        $nombre = $persona['Nom1_usu'] ?? '';
        if (!empty($persona['Nom2_usu'])) {
            $nombre .= ' ' . $persona['Nom2_usu'];
        }
        $nombre .= ' ' . ($persona['Ape1_usu'] ?? '');
        if (!empty($persona['Ape2_usu'])) {
            $nombre .= ' ' . $persona['Ape2_usu'];
        }
        return trim($nombre);
    }
}
