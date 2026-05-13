<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Model.php';

/**
 * Modelo de Persona
 * Maneja operaciones CRUD para la tabla Persona
 */
class PersonaModel extends Model {
    protected string $table = 'Persona';
    protected string $primaryKey = 'id_usuario';
    protected array $fillable = [
        'pkfk_Tipo_doc', 
        'id_usuario', 
        'Nom1_usu', 
        'Nom2_usu', 
        'Ape1_usu', 
        'Ape2_usu', 
        'Telefono'
    ];
    
    /**
     * Obtiene todas las personas con su tipo de documento y rol
     * Usa JOIN para obtener datos relacionados
     * @return array
     */
    public function getAllWithDetails(): array {
        $sql = "SELECT p.*, td.tipo_doc, r.Nom_rol, u.Correo_usu
                FROM Persona p
                LEFT JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
                LEFT JOIN Persona_has_Rol phr ON p.id_usuario = phr.pkfk_id_usuario 
                    AND p.pkfk_Tipo_doc = phr.pkfk_Tipo_doc
                LEFT JOIN Rol r ON phr.pkfk_idRol = r.idRol
                LEFT JOIN Usuario u ON p.id_usuario = u.pkfk_id_usuario 
                    AND p.pkfk_Tipo_doc = u.pkfk_Tipo_doc
                ORDER BY p.Nom1_usu ASC";
        
        return $this->query($sql);
    }
    
    /**
     * Busca persona por documento completo (tipo + numero)
     * @param int $tipoDoc ID del tipo de documento
     * @param int $idUsuario Numero de documento
     * @return array|null
     */
    public function findByDocument(int $tipoDoc, int $idUsuario): ?array {
        $sql = "SELECT p.*, td.tipo_doc
                FROM Persona p
                JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
                WHERE p.pkfk_Tipo_doc = :tipoDoc AND p.id_usuario = :idUsuario";
        
        $result = $this->query($sql, [
            'tipoDoc' => $tipoDoc,
            'idUsuario' => $idUsuario
        ]);
        
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Busca personas por nombre (parcial)
     * @param string $nombre Termino de busqueda
     * @return array
     */
    public function searchByName(string $nombre): array {
        $sql = "SELECT p.*, td.tipo_doc, r.Nom_rol
                FROM Persona p
                LEFT JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
                LEFT JOIN Persona_has_Rol phr ON p.id_usuario = phr.pkfk_id_usuario
                LEFT JOIN Rol r ON phr.pkfk_idRol = r.idRol
                WHERE CONCAT(p.Nom1_usu, ' ', COALESCE(p.Nom2_usu, ''), ' ', 
                      p.Ape1_usu, ' ', COALESCE(p.Ape2_usu, '')) LIKE :nombre
                ORDER BY p.Nom1_usu ASC";
        
        return $this->query($sql, ['nombre' => "%{$nombre}%"]);
    }
    
    /**
     * Filtra personas por rol
     * @param int $rolId ID del rol
     * @return array
     */
    public function getByRol(int $rolId): array {
        $sql = "SELECT p.*, td.tipo_doc, r.Nom_rol
                FROM Persona p
                JOIN Tipo_doc td ON p.pkfk_Tipo_doc = td.id_doc
                JOIN Persona_has_Rol phr ON p.id_usuario = phr.pkfk_id_usuario 
                    AND p.pkfk_Tipo_doc = phr.pkfk_Tipo_doc
                JOIN Rol r ON phr.pkfk_idRol = r.idRol
                WHERE phr.pkfk_idRol = :rolId
                ORDER BY p.Nom1_usu ASC";
        
        return $this->query($sql, ['rolId' => $rolId]);
    }
    
    /**
     * Crea persona con rol asignado (transaccion)
     * @param array $data Datos de la persona
     * @param int $rolId ID del rol
     * @return bool
     */
    public function createWithRol(array $data, int $rolId): bool {
        try {
            $this->beginTransaction();
            
            // Insertar persona
            $sqlPersona = "INSERT INTO Persona (pkfk_Tipo_doc, id_usuario, Nom1_usu, 
                          Nom2_usu, Ape1_usu, Ape2_usu, Telefono) 
                          VALUES (:tipoDoc, :idUsuario, :nom1, :nom2, :ape1, :ape2, :telefono)";
            
            $this->execute($sqlPersona, [
                'tipoDoc' => $data['pkfk_Tipo_doc'],
                'idUsuario' => $data['id_usuario'],
                'nom1' => $data['Nom1_usu'],
                'nom2' => $data['Nom2_usu'] ?? null,
                'ape1' => $data['Ape1_usu'],
                'ape2' => $data['Ape2_usu'] ?? null,
                'telefono' => $data['Telefono']
            ]);
            
            // Asignar rol
            $sqlRol = "INSERT INTO Persona_has_Rol (pkfk_Tipo_doc, pkfk_id_usuario, pkfk_idRol) 
                       VALUES (:tipoDoc, :idUsuario, :rolId)";
            
            $this->execute($sqlRol, [
                'tipoDoc' => $data['pkfk_Tipo_doc'],
                'idUsuario' => $data['id_usuario'],
                'rolId' => $rolId
            ]);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            $this->addError("Error al crear persona: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualiza persona por documento
     * @param int $tipoDoc Tipo de documento
     * @param int $idUsuario ID del usuario
     * @param array $data Datos a actualizar
     * @return bool
     */
    public function updateByDocument(int $tipoDoc, int $idUsuario, array $data): bool {
        $sql = "UPDATE Persona SET 
                Nom1_usu = :nom1, Nom2_usu = :nom2, 
                Ape1_usu = :ape1, Ape2_usu = :ape2, Telefono = :telefono
                WHERE pkfk_Tipo_doc = :tipoDoc AND id_usuario = :idUsuario";
        
        return $this->execute($sql, [
            'nom1' => $data['Nom1_usu'],
            'nom2' => $data['Nom2_usu'] ?? null,
            'ape1' => $data['Ape1_usu'],
            'ape2' => $data['Ape2_usu'] ?? null,
            'telefono' => $data['Telefono'],
            'tipoDoc' => $tipoDoc,
            'idUsuario' => $idUsuario
        ]);
    }
    
    /**
     * Elimina persona y sus relaciones (transaccion)
     * @param int $tipoDoc Tipo de documento
     * @param int $idUsuario ID del usuario
     * @return bool
     */
    public function deleteWithRelations(int $tipoDoc, int $idUsuario): bool {
        try {
            $this->beginTransaction();
            
            // Eliminar de tablas relacionadas en orden
            $tables = ['Usuario', 'Admin', 'Cocinero', 'Mesero', 'Cliente', 'Persona_has_Rol'];
            
            foreach ($tables as $table) {
                $sql = "DELETE FROM {$table} 
                        WHERE pkfk_Tipo_doc = :tipoDoc AND pkfk_id_usuario = :idUsuario";
                $this->execute($sql, [
                    'tipoDoc' => $tipoDoc,
                    'idUsuario' => $idUsuario
                ]);
            }
            
            // Finalmente eliminar persona
            $sql = "DELETE FROM Persona 
                    WHERE pkfk_Tipo_doc = :tipoDoc AND id_usuario = :idUsuario";
            $this->execute($sql, [
                'tipoDoc' => $tipoDoc,
                'idUsuario' => $idUsuario
            ]);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollback();
            $this->addError("Error al eliminar persona: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene nombre completo formateado
     * @param array $persona Datos de la persona
     * @return string
     */
    public static function getFullName(array $persona): string {
        $nombres = [$persona['Nom1_usu']];
        
        if (!empty($persona['Nom2_usu'])) {
            $nombres[] = $persona['Nom2_usu'];
        }
        
        $nombres[] = $persona['Ape1_usu'];
        
        if (!empty($persona['Ape2_usu'])) {
            $nombres[] = $persona['Ape2_usu'];
        }
        
        return implode(' ', $nombres);
    }
    
    /**
     * Cuenta personas por rol
     * @return array Conteo por cada rol
     */
    public function countByRol(): array {
        $sql = "SELECT r.Nom_rol, COUNT(*) as total
                FROM Persona_has_Rol phr
                JOIN Rol r ON phr.pkfk_idRol = r.idRol
                GROUP BY r.idRol, r.Nom_rol";
        
        return $this->query($sql);
    }
}
?>
