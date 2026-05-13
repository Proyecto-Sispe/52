<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Usuario
 * Maneja autenticacion y operaciones de usuarios
 */
class UsuarioModel extends Model {
    protected string $table = 'Usuario';
    protected array $fillable = ['Correo_usu', 'Password', 'pkfk_Tipo_doc', 'pkfk_id_usuario'];
    
    /**
     * Autentica un usuario
     * @param string $correo Correo electronico
     * @param string $password Contrasena
     * @return array|null Datos del usuario o null
     */
    public function authenticate(string $correo, string $password): ?array {
        try {
            $sql = "SELECT u.*, p.Nom1_usu, p.Nom2_usu, p.Ape1_usu, p.Ape2_usu,
                           p.Telefono, phr.pkfk_idRol, r.Nom_rol
                    FROM Usuario u
                    JOIN Persona p ON u.pkfk_id_usuario = p.id_usuario 
                        AND u.pkfk_Tipo_doc = p.pkfk_Tipo_doc
                    JOIN Persona_has_Rol phr ON p.id_usuario = phr.pkfk_id_usuario 
                        AND p.pkfk_Tipo_doc = phr.pkfk_Tipo_doc
                    JOIN Rol r ON phr.pkfk_idRol = r.idRol
                    WHERE u.Correo_usu = :correo AND u.Password = :password
                    LIMIT 1";
            
            $result = $this->query($sql, [
                'correo' => $correo,
                'password' => $password
            ]);
            
            if (empty($result)) {
                return null;
            }
            
            return $result[0];
            
        } catch (Exception $e) {
            $this->addError("Error de autenticacion: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca usuario por correo
     * @param string $correo Correo electronico
     * @return array|null
     */
    public function findByEmail(string $correo): ?array {
        $sql = "SELECT u.*, p.Nom1_usu, p.Ape1_usu, phr.pkfk_idRol
                FROM Usuario u
                JOIN Persona p ON u.pkfk_id_usuario = p.id_usuario
                JOIN Persona_has_Rol phr ON p.id_usuario = phr.pkfk_id_usuario
                WHERE u.Correo_usu = :correo
                LIMIT 1";
        
        $result = $this->query($sql, ['correo' => $correo]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Crea un nuevo usuario con transaccion
     * @param array $personaData Datos de la persona
     * @param array $usuarioData Datos del usuario
     * @param int $rolId ID del rol
     * @return bool
     */
    public function createUsuario(array $personaData, array $usuarioData, int $rolId): bool {
        try {
            $this->beginTransaction();
            
            // Verificar si ya existe el correo
            if ($this->findByEmail($usuarioData['Correo_usu']) !== null) {
                throw new Exception("El correo ya esta registrado");
            }
            
            // Insertar persona
            $sqlPersona = "INSERT INTO Persona (pkfk_Tipo_doc, id_usuario, Nom1_usu, 
                          Nom2_usu, Ape1_usu, Ape2_usu, Telefono) 
                          VALUES (:tipoDoc, :idUsuario, :nom1, :nom2, :ape1, :ape2, :telefono)";
            
            $this->execute($sqlPersona, [
                'tipoDoc' => $personaData['pkfk_Tipo_doc'],
                'idUsuario' => $personaData['id_usuario'],
                'nom1' => $personaData['Nom1_usu'],
                'nom2' => $personaData['Nom2_usu'] ?? null,
                'ape1' => $personaData['Ape1_usu'],
                'ape2' => $personaData['Ape2_usu'] ?? null,
                'telefono' => $personaData['Telefono']
            ]);
            
            // Insertar usuario
            $sqlUsuario = "INSERT INTO Usuario (Correo_usu, Password, pkfk_Tipo_doc, pkfk_id_usuario) 
                          VALUES (:correo, :password, :tipoDoc, :idUsuario)";
            
            $this->execute($sqlUsuario, [
                'correo' => $usuarioData['Correo_usu'],
                'password' => $usuarioData['Password'],
                'tipoDoc' => $personaData['pkfk_Tipo_doc'],
                'idUsuario' => $personaData['id_usuario']
            ]);
            
            // Asignar rol
            $sqlRol = "INSERT INTO Persona_has_Rol (pkfk_Tipo_doc, pkfk_id_usuario, pkfk_idRol) 
                       VALUES (:tipoDoc, :idUsuario, :rolId)";
            
            $this->execute($sqlRol, [
                'tipoDoc' => $personaData['pkfk_Tipo_doc'],
                'idUsuario' => $personaData['id_usuario'],
                'rolId' => $rolId
            ]);
            
            // Insertar en tabla de rol especifica
            $tablasRol = [
                ROL_ADMIN => 'Admin',
                ROL_COCINERO => 'Cocinero',
                ROL_MESERO => 'Mesero',
                ROL_CLIENTE => 'Cliente'
            ];
            
            if (isset($tablasRol[$rolId])) {
                $sqlRolEspecifico = "INSERT INTO {$tablasRol[$rolId]} (pkfk_Tipo_doc, pkfk_id_usuario) 
                                    VALUES (:tipoDoc, :idUsuario)";
                $this->execute($sqlRolEspecifico, [
                    'tipoDoc' => $personaData['pkfk_Tipo_doc'],
                    'idUsuario' => $personaData['id_usuario']
                ]);
            }
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            $this->addError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza contrasena
     * @param int $tipoDoc Tipo de documento
     * @param int $idUsuario ID del usuario
     * @param string $nuevaPassword Nueva contrasena
     * @return bool
     */
    public function cambiarPassword(int $tipoDoc, int $idUsuario, string $nuevaPassword): bool {
        // Validar longitud minima
        if (strlen($nuevaPassword) < 6) {
            $this->addError("La contrasena debe tener al menos 6 caracteres");
            return false;
        }
        
        $sql = "UPDATE Usuario SET Password = :password 
                WHERE pkfk_Tipo_doc = :tipoDoc AND pkfk_id_usuario = :idUsuario";
        
        return $this->execute($sql, [
            'password' => $nuevaPassword,
            'tipoDoc' => $tipoDoc,
            'idUsuario' => $idUsuario
        ]);
    }
    
    /**
     * Verifica si un usuario tiene un rol especifico
     * @param int $tipoDoc Tipo de documento
     * @param int $idUsuario ID del usuario
     * @param int $rolId ID del rol a verificar
     * @return bool
     */
    public function hasRol(int $tipoDoc, int $idUsuario, int $rolId): bool {
        $sql = "SELECT 1 FROM Persona_has_Rol 
                WHERE pkfk_Tipo_doc = :tipoDoc 
                AND pkfk_id_usuario = :idUsuario 
                AND pkfk_idRol = :rolId
                LIMIT 1";
        
        $result = $this->query($sql, [
            'tipoDoc' => $tipoDoc,
            'idUsuario' => $idUsuario,
            'rolId' => $rolId
        ]);
        
        return !empty($result);
    }
    
    /**
     * Inicia sesion de usuario
     * @param array $userData Datos del usuario autenticado
     */
    public function login(array $userData): void {
        $_SESSION['user_id'] = $userData['pkfk_id_usuario'];
        $_SESSION['user'] = [
            'id' => $userData['pkfk_id_usuario'],
            'tipo_doc' => $userData['pkfk_Tipo_doc'],
            'nombre' => $userData['Nom1_usu'] . ' ' . $userData['Ape1_usu'],
            'correo' => $userData['Correo_usu'],
            'rol' => $userData['pkfk_idRol'],
            'rol_nombre' => $userData['Nom_rol']
        ];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * Cierra sesion
     */
    public function logout(): void {
        session_unset();
        session_destroy();
    }
    
    /**
     * Verifica si la sesion ha expirado
     * @return bool
     */
    public function isSessionExpired(): bool {
        if (!isset($_SESSION['login_time'])) {
            return true;
        }
        
        return (time() - $_SESSION['login_time']) > SESSION_LIFETIME;
    }
    
    /**
     * Obtiene usuarios por rol
     * @param int $rolId ID del rol
     * @return array
     */
    public function getByRol(int $rolId): array {
        $sql = "SELECT u.*, p.Nom1_usu, p.Ape1_usu, r.Nom_rol
                FROM Usuario u
                JOIN Persona p ON u.pkfk_id_usuario = p.id_usuario
                JOIN Persona_has_Rol phr ON p.id_usuario = phr.pkfk_id_usuario
                JOIN Rol r ON phr.pkfk_idRol = r.idRol
                WHERE phr.pkfk_idRol = :rolId
                ORDER BY p.Nom1_usu";
        
        return $this->query($sql, ['rolId' => $rolId]);
    }
    
    /**
     * Cuenta usuarios por rol
     * @return array
     */
    public function countByRol(): array {
        $sql = "SELECT r.Nom_rol, COUNT(u.pkfk_id_usuario) as total
                FROM Rol r
                LEFT JOIN Persona_has_Rol phr ON r.idRol = phr.pkfk_idRol
                LEFT JOIN Usuario u ON phr.pkfk_id_usuario = u.pkfk_id_usuario
                GROUP BY r.idRol, r.Nom_rol
                ORDER BY r.idRol";
        
        return $this->query($sql);
    }
}
?>
