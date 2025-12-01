<?php

namespace Models;

use Core\Database;
use Exception;

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crea un nuevo usuario
     */
    public function create($data) {
        // Validar que el nombre de usuario no exista
        if ($this->existsByUsername($data['nombre_usuario'])) {
            throw new Exception("El nombre de usuario ya existe");
        }
        
        // Validar que el correo no exista
        if ($this->existsByEmail($data['correo_electronico'])) {
            throw new Exception("El correo electrónico ya está registrado");
        }
        
        // Hashear la contraseña
        $data['contrasena'] = hashPassword($data['contrasena']);
        
        // Insertar usuario
        $id = $this->db->insert('usuarios', $data);
        
        // Registrar acción
        logAction($id, 'ha creado su cuenta');
        
        return $id;
    }
    
    /**
     * Obtiene un usuario por ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM usuarios WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Obtiene un usuario por nombre de usuario
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM usuarios WHERE nombre_usuario = :username";
        return $this->db->fetchOne($sql, ['username' => $username]);
    }
    
    /**
     * Obtiene un usuario por correo electrónico
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM usuarios WHERE correo_electronico = :email";
        return $this->db->fetchOne($sql, ['email' => $email]);
    }
    
    /**
     * Verifica si existe un usuario por nombre de usuario
     */
    public function existsByUsername($username) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE nombre_usuario = :username";
        return $this->db->fetchColumn($sql, ['username' => $username]) > 0;
    }
    
    /**
     * Verifica si existe un usuario por correo electrónico
     */
    public function existsByEmail($email) {
        $sql = "SELECT COUNT(*) FROM usuarios WHERE correo_electronico = :email";
        return $this->db->fetchColumn($sql, ['email' => $email]) > 0;
    }
    
    /**
     * Actualiza un usuario
     */
    public function update($id, $data) {
        // Si se va a cambiar el nombre de usuario, verificar que no exista
        if (isset($data['nombre_usuario'])) {
            $user = $this->findById($id);
            if ($user['nombre_usuario'] !== $data['nombre_usuario'] && 
                $this->existsByUsername($data['nombre_usuario'])) {
                throw new Exception("El nombre de usuario ya existe");
            }
        }
        
        // Si se va a cambiar el correo, verificar que no exista
        if (isset($data['correo_electronico'])) {
            $user = $this->findById($id);
            if ($user['correo_electronico'] !== $data['correo_electronico'] && 
                $this->existsByEmail($data['correo_electronico'])) {
                throw new Exception("El correo electrónico ya está registrado");
            }
        }
        
        // Si se va a cambiar la contraseña, hashearla
        if (isset($data['contrasena'])) {
            $data['contrasena'] = hashPassword($data['contrasena']);
        }
        
        return $this->db->update('usuarios', $data, 'id = :id', ['id' => $id]);
    }
    
    /**
     * Elimina un usuario y todos sus datos
     */
    public function delete($id) {
        // Verificar que no sea el propietario
        $user = $this->findById($id);
        if ($user['rol'] === 'propietario') {
            throw new Exception("No se puede eliminar al propietario");
        }
        
        // La eliminación en cascada se encarga del resto
        return $this->db->delete('usuarios', 'id = :id', ['id' => $id]);
    }
    
    /**
     * Obtiene todos los usuarios
     */
    public function getAll($filters = []) {
        $sql = "SELECT id, nombre_usuario, correo_electronico, rol, ultimo_logueo, 
                       created_at FROM usuarios";
        $where = [];
        $params = [];
        
        if (isset($filters['rol'])) {
            $where[] = "rol = :rol";
            $params['rol'] = $filters['rol'];
        }
        
        if (isset($filters['search'])) {
            $where[] = "(nombre_usuario LIKE :search OR correo_electronico LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Actualiza el último logueo
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE usuarios SET ultimo_logueo = NOW() WHERE id = :id";
        return $this->db->query($sql, ['id' => $id]);
    }
    
    /**
     * Cambia el rol de un usuario
     */
    public function changeRole($id, $newRole) {
        $user = $this->findById($id);
        
        // No se puede cambiar el rol del propietario
        if ($user['rol'] === 'propietario') {
            throw new Exception("No se puede cambiar el rol del propietario");
        }
        
        // No se puede asignar el rol de propietario
        if ($newRole === 'propietario') {
            throw new Exception("No se puede asignar el rol de propietario");
        }
        
        return $this->db->update('usuarios', ['rol' => $newRole], 'id = :id', ['id' => $id]);
    }
    
    /**
     * Solicita ser administrador
     */
    public function requestAdmin($id) {
        $user = $this->findById($id);
        
        if ($user['rol'] === 'solicita') {
            throw new Exception("Ya has solicitado ser administrador");
        }
        
        if (in_array($user['rol'], ['administrador', 'propietario'])) {
            throw new Exception("Ya eres administrador");
        }
        
        $this->db->update('usuarios', ['rol' => 'solicita'], 'id = :id', ['id' => $id]);
        logAction($id, 'ha solicitado ser administrador');
        
        return true;
    }
    
    /**
     * Obtiene el balance total de un usuario
     */
    public function getTotalBalance($id) {
        $sql = "SELECT COALESCE(SUM(balance), 0) as total FROM cuentas WHERE id_usuario = :id";
        return $this->db->fetchColumn($sql, ['id' => $id]);
    }
    
    /**
     * Verifica las credenciales de un usuario
     */
    public function verifyCredentials($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            return false;
        }
        
        if (!verifyPassword($password, $user['contrasena'])) {
            return false;
        }
        
        return $user;
    }
    
    /**
     * Activa o desactiva 2FA
     */
    public function toggle2FA($id, $enable) {
        $this->db->update('usuarios', ['autenticacion_2fa' => $enable], 'id = :id', ['id' => $id]);
        
        $action = $enable ? 'ha activado' : 'ha desactivado';
        logAction($id, $action . ' la verificación en 2 pasos');
        
        return true;
    }
    
    /**
     * Obtiene usuarios inactivos
     */
    public function getInactiveUsers() {
        $oneYear = date('Y-m-d H:i:s', strtotime('-1 year'));
        $oneYearSixMonths = date('Y-m-d H:i:s', strtotime('-18 months'));
        $twoYears = date('Y-m-d H:i:s', strtotime('-2 years'));
        
        $sql = "SELECT *, 
                CASE 
                    WHEN ultimo_logueo < :two_years THEN 'delete'
                    WHEN ultimo_logueo < :one_year_six_months THEN 'warning'
                    WHEN ultimo_logueo < :one_year THEN 'reminder'
                    ELSE 'active'
                END as status
                FROM usuarios 
                WHERE rol != 'propietario' 
                AND ultimo_logueo IS NOT NULL
                HAVING status != 'active'";
        
        return $this->db->fetchAll($sql, [
            'one_year' => $oneYear,
            'one_year_six_months' => $oneYearSixMonths,
            'two_years' => $twoYears
        ]);
    }
}