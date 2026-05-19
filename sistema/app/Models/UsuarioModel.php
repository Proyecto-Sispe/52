<?php

namespace App\Models;

use CodeIgniter\Model;
use Exception;

/**
 * Modelo de Usuario
 * Maneja todas las operaciones de base de datos relacionadas con usuarios
 */
class UsuarioModel extends Model
{
    /**
     * Nombre de la tabla
     * @var string
     */
    protected $table = 'usuarios';

    /**
     * Clave primaria
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Usar timestamps automaticos
     * @var bool
     */
    protected $useTimestamps = true;

    /**
     * Campos de fecha
     * @var string
     */
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Campos permitidos para insercion/actualizacion masiva
     * @var array
     */
    protected $allowedFields = [
        'nombre',
        'correo',
        'password',
        'rol',
        'activo',
        'ultimo_acceso'
    ];

    /**
     * Tipo de retorno
     * @var string
     */
    protected $returnType = 'array';

    /**
     * Reglas de validacion
     * @var array
     */
    protected $validationRules = [
        'nombre' => 'required|min_length[3]|max_length[100]',
        'correo' => 'required|valid_email|max_length[150]',
        'password' => 'required|min_length[6]',
        'rol' => 'required|in_list[admin,mesero,cocinero,cliente,mesa,aprendiz]'
    ];

    /**
     * Mensajes de validacion personalizados
     * @var array
     */
    protected $validationMessages = [
        'nombre' => [
            'required' => 'El nombre es obligatorio',
            'min_length' => 'El nombre debe tener al menos 3 caracteres',
            'max_length' => 'El nombre no puede exceder 100 caracteres'
        ],
        'correo' => [
            'required' => 'El correo es obligatorio',
            'valid_email' => 'El formato del correo no es válido',
            'max_length' => 'El correo no puede exceder 150 caracteres'
        ],
        'password' => [
            'required' => 'La contraseña es obligatoria',
            'min_length' => 'La contraseña debe tener al menos 6 caracteres'
        ],
        'rol' => [
            'required' => 'El rol es obligatorio',
            'in_list' => 'El rol seleccionado no es válido'
        ]
    ];

    /**
     * Roles disponibles en el sistema
     * @var array
     */
    private const ROLES = [
        'admin' => ['nombre' => 'Administrador', 'nivel' => 1],
        'mesero' => ['nombre' => 'Mesero', 'nivel' => 2],
        'cocinero' => ['nombre' => 'Cocinero', 'nivel' => 2],
        'cliente' => ['nombre' => 'Cliente', 'nivel' => 3],
        'mesa' => ['nombre' => 'Mesa', 'nivel' => 3],
        'aprendiz' => ['nombre' => 'Aprendiz', 'nivel' => 4]
    ];

    // ==========================================
    // OPERACIONES CRUD BASICAS
    // ==========================================

    /**
     * Obtiene todos los usuarios con filtros opcionales
     * @param array $filtros
     * @return array
     */
    public function obtenerTodos(array $filtros = []): array
    {
        try {
            $builder = $this->builder();

            // Condicion: aplicar filtro de rol si existe
            if (isset($filtros['rol']) && !empty($filtros['rol'])) {
                $builder->where('rol', $filtros['rol']);
            }

            // Condicion: aplicar filtro de activo
            if (isset($filtros['activo'])) {
                $builder->where('activo', $filtros['activo']);
            }

            // Condicion: aplicar busqueda por nombre o correo
            if (isset($filtros['busqueda']) && !empty($filtros['busqueda'])) {
                $builder->groupStart()
                    ->like('nombre', $filtros['busqueda'])
                    ->orLike('correo', $filtros['busqueda'])
                    ->groupEnd();
            }

            // Ordenar
            $ordenCampo = $filtros['orden_campo'] ?? 'id';
            $ordenDir = $filtros['orden_dir'] ?? 'ASC';
            $builder->orderBy($ordenCampo, $ordenDir);

            // Limitar resultados si se especifica
            if (isset($filtros['limite']) && $filtros['limite'] > 0) {
                $offset = $filtros['offset'] ?? 0;
                $builder->limit($filtros['limite'], $offset);
            }

            return $builder->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener usuarios: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca usuario por correo electronico
     * @param string $correo
     * @return array|null
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        try {
            // Condicion: validar que no este vacio
            if (empty($correo)) {
                return null;
            }

            $resultado = $this->where('correo', $correo)->first();

            return $resultado ?: null;

        } catch (Exception $e) {
            log_message('error', 'Error al buscar usuario por correo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Busca usuarios por rol
     * @param string $rol
     * @return array
     */
    public function buscarPorRol(string $rol): array
    {
        try {
            // Condicion: validar rol
            if (!$this->validarRol($rol)) {
                throw new Exception("Rol '$rol' no es válido");
            }

            return $this->where('rol', $rol)
                ->orderBy('nombre', 'ASC')
                ->findAll();

        } catch (Exception $e) {
            log_message('error', 'Error al buscar usuarios por rol: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Crea un nuevo usuario con validaciones
     * @param array $datos
     * @return int|bool ID del usuario creado o false
     */
    public function crearUsuario(array $datos)
    {
        try {
            // Validar datos requeridos
            $camposRequeridos = ['nombre', 'correo', 'password', 'rol'];
            
            // Bucle para verificar campos requeridos
            foreach ($camposRequeridos as $campo) {
                if (!isset($datos[$campo]) || empty($datos[$campo])) {
                    throw new Exception("El campo '$campo' es requerido");
                }
            }

            // Condicion: verificar correo duplicado
            if ($this->buscarPorCorreo($datos['correo']) !== null) {
                throw new Exception('El correo ya está registrado');
            }

            // Condicion: validar rol
            if (!$this->validarRol($datos['rol'])) {
                throw new Exception('Rol no válido');
            }

            // Preparar datos
            $datosInsertar = [
                'nombre' => $this->sanitizarTexto($datos['nombre']),
                'correo' => strtolower(trim($datos['correo'])),
                'password' => $this->hashearPassword($datos['password']),
                'rol' => $datos['rol'],
                'activo' => $datos['activo'] ?? 1
            ];

            // Insertar
            $id = $this->insert($datosInsertar);

            if ($id === false) {
                throw new Exception('Error al insertar usuario');
            }

            return $id;

        } catch (Exception $e) {
            log_message('error', 'Error al crear usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza usuario existente
     * @param int $id
     * @param array $datos
     * @return bool
     */
    public function actualizarUsuario(int $id, array $datos): bool
    {
        try {
            // Condicion: validar ID
            if ($id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            // Condicion: verificar que existe
            $usuario = $this->find($id);
            if ($usuario === null) {
                throw new Exception('Usuario no encontrado');
            }

            // Preparar datos a actualizar
            $datosActualizar = [];

            // Bucle para procesar campos actualizables
            $camposPermitidos = ['nombre', 'correo', 'rol', 'activo'];
            foreach ($camposPermitidos as $campo) {
                if (isset($datos[$campo])) {
                    $datosActualizar[$campo] = $datos[$campo];
                }
            }

            // Condicion: si hay nuevo password, hashearlo
            if (isset($datos['password']) && !empty($datos['password'])) {
                $datosActualizar['password'] = $this->hashearPassword($datos['password']);
            }

            // Condicion: si no hay nada que actualizar
            if (empty($datosActualizar)) {
                return true;
            }

            return $this->update($id, $datosActualizar);

        } catch (Exception $e) {
            log_message('error', 'Error al actualizar usuario: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina usuario (soft delete o hard delete)
     * @param int $id
     * @param bool $softDelete
     * @return bool
     */
    public function eliminarUsuario(int $id, bool $softDelete = true): bool
    {
        try {
            // Condicion: validar ID
            if ($id <= 0) {
                throw new Exception('ID de usuario inválido');
            }

            // Condicion: verificar que existe
            $usuario = $this->find($id);
            if ($usuario === null) {
                throw new Exception('Usuario no encontrado');
            }

            if ($softDelete) {
                // Soft delete: marcar como inactivo
                return $this->update($id, ['activo' => 0]);
            } else {
                // Hard delete: eliminar registro
                return $this->delete($id);
            }

        } catch (Exception $e) {
            log_message('error', 'Error al eliminar usuario: ' . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // AUTENTICACION
    // ==========================================

    /**
     * Autentica un usuario
     * @param string $correo
     * @param string $password
     * @return array|null Usuario autenticado o null
     */
    public function autenticar(string $correo, string $password): ?array
    {
        try {
            // Condicion: validar parametros
            if (empty($correo) || empty($password)) {
                return null;
            }

            // Buscar usuario
            $usuario = $this->buscarPorCorreo($correo);

            // Condicion: verificar existencia
            if ($usuario === null) {
                return null;
            }

            // Condicion: verificar si esta activo
            if (isset($usuario['activo']) && $usuario['activo'] == 0) {
                throw new Exception('Usuario inactivo');
            }

            // Condicion: verificar password
            if (!$this->verificarPassword($password, $usuario['password'])) {
                return null;
            }

            // Actualizar ultimo acceso
            $this->update($usuario['id'], ['ultimo_acceso' => date('Y-m-d H:i:s')]);

            // Remover password del resultado
            unset($usuario['password']);

            return $usuario;

        } catch (Exception $e) {
            log_message('error', 'Error en autenticación: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cambia la contraseña de un usuario
     * @param int $id
     * @param string $passwordActual
     * @param string $passwordNuevo
     * @return bool
     */
    public function cambiarPassword(int $id, string $passwordActual, string $passwordNuevo): bool
    {
        try {
            // Condicion: validar ID
            if ($id <= 0) {
                throw new Exception('ID inválido');
            }

            // Obtener usuario
            $usuario = $this->find($id);

            // Condicion: verificar existencia
            if ($usuario === null) {
                throw new Exception('Usuario no encontrado');
            }

            // Condicion: verificar password actual
            if (!$this->verificarPassword($passwordActual, $usuario['password'])) {
                throw new Exception('Contraseña actual incorrecta');
            }

            // Condicion: validar nuevo password
            if (strlen($passwordNuevo) < 6) {
                throw new Exception('La nueva contraseña debe tener al menos 6 caracteres');
            }

            // Actualizar
            return $this->update($id, [
                'password' => $this->hashearPassword($passwordNuevo)
            ]);

        } catch (Exception $e) {
            log_message('error', 'Error al cambiar password: ' . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // FUNCIONES AUXILIARES (Operaciones)
    // ==========================================

    /**
     * Hashea una contraseña
     * @param string $password
     * @return string
     */
    private function hashearPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }

    /**
     * Verifica si una contraseña coincide con su hash
     * @param string $password
     * @param string $hash
     * @return bool
     */
    private function verificarPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Valida si un rol es valido
     * @param string $rol
     * @return bool
     */
    public function validarRol(string $rol): bool
    {
        return array_key_exists($rol, self::ROLES);
    }

    /**
     * Obtiene informacion de un rol
     * @param string $rol
     * @return array|null
     */
    public function obtenerInfoRol(string $rol): ?array
    {
        return self::ROLES[$rol] ?? null;
    }

    /**
     * Obtiene todos los roles disponibles
     * @return array
     */
    public function obtenerRoles(): array
    {
        return self::ROLES;
    }

    /**
     * Sanitiza texto de entrada
     * @param string $texto
     * @return string
     */
    private function sanitizarTexto(string $texto): string
    {
        return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
    }

    // ==========================================
    // ESTADISTICAS Y REPORTES
    // ==========================================

    /**
     * Cuenta usuarios por rol
     * @return array
     */
    public function contarPorRol(): array
    {
        try {
            $resultado = [];

            // Bucle para contar cada rol
            foreach (array_keys(self::ROLES) as $rol) {
                $count = $this->where('rol', $rol)->countAllResults(false);
                $resultado[$rol] = [
                    'nombre' => self::ROLES[$rol]['nombre'],
                    'cantidad' => $count
                ];
            }

            return $resultado;

        } catch (Exception $e) {
            log_message('error', 'Error al contar usuarios por rol: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene usuarios registrados en un rango de fechas
     * @param string $fechaInicio
     * @param string $fechaFin
     * @return array
     */
    public function obtenerPorRangoFechas(string $fechaInicio, string $fechaFin): array
    {
        try {
            return $this->where('created_at >=', $fechaInicio)
                ->where('created_at <=', $fechaFin)
                ->orderBy('created_at', 'DESC')
                ->findAll();

        } catch (Exception $e) {
            log_message('error', 'Error al obtener usuarios por rango: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadisticas generales de usuarios
     * @return array
     */
    public function obtenerEstadisticas(): array
    {
        try {
            $stats = [
                'total' => $this->countAll(),
                'activos' => $this->where('activo', 1)->countAllResults(false),
                'inactivos' => $this->where('activo', 0)->countAllResults(false),
                'por_rol' => $this->contarPorRol(),
                'registros_hoy' => $this->where('DATE(created_at)', date('Y-m-d'))
                    ->countAllResults(false),
                'registros_mes' => $this->where('MONTH(created_at)', date('m'))
                    ->where('YEAR(created_at)', date('Y'))
                    ->countAllResults(false)
            ];

            return $stats;

        } catch (Exception $e) {
            log_message('error', 'Error al obtener estadísticas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca usuarios con multiples criterios
     * @param array $criterios
     * @return array
     */
    public function busquedaAvanzada(array $criterios): array
    {
        try {
            $builder = $this->builder();
            $hayFiltros = false;

            // Bucle para aplicar cada criterio
            foreach ($criterios as $campo => $valor) {
                if (empty($valor)) {
                    continue;
                }

                switch ($campo) {
                    case 'nombre':
                        $builder->like('nombre', $valor);
                        $hayFiltros = true;
                        break;

                    case 'correo':
                        $builder->like('correo', $valor);
                        $hayFiltros = true;
                        break;

                    case 'rol':
                        // Condicion: puede ser un rol o array de roles
                        if (is_array($valor)) {
                            $builder->whereIn('rol', $valor);
                        } else {
                            $builder->where('rol', $valor);
                        }
                        $hayFiltros = true;
                        break;

                    case 'activo':
                        $builder->where('activo', (int)$valor);
                        $hayFiltros = true;
                        break;

                    case 'fecha_desde':
                        $builder->where('created_at >=', $valor);
                        $hayFiltros = true;
                        break;

                    case 'fecha_hasta':
                        $builder->where('created_at <=', $valor);
                        $hayFiltros = true;
                        break;
                }
            }

            // Condicion: si no hay filtros, retornar vacio
            if (!$hayFiltros) {
                return [];
            }

            return $builder->orderBy('nombre', 'ASC')->get()->getResultArray();

        } catch (Exception $e) {
            log_message('error', 'Error en búsqueda avanzada: ' . $e->getMessage());
            return [];
        }
    }
}
