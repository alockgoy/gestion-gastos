<?php

namespace Models;

use Core\Database;
use Exception;

class Sesion {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crea una nueva sesión
     */
    public function create($idUsuario) {
        $token = generateToken(32);
        $expiraEn = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        $data = [
            'id_usuario' => $idUsuario,
            'token' => $token,
            'ip' => getClientIP(),
            'user_agent' => getUserAgent(),
            'expira_en' => $expiraEn
        ];
        
        $this->db->insert('sesiones', $data);
        
        return $token;
    }
    
    /**
     * Valida un token de sesión
     */
    public function validate($token) {
        $sql = "SELECT s.*, u.* 
                FROM sesiones s 
                INNER JOIN usuarios u ON s.id_usuario = u.id 
                WHERE s.token = :token 
                AND s.expira_en > NOW()";
        
        return $this->db->fetchOne($sql, ['token' => $token]);
    }
    
    /**
     * Renueva una sesión existente
     */
    public function renew($token) {
        $expiraEn = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        
        return $this->db->update(
            'sesiones',
            ['expira_en' => $expiraEn],
            'token = :token',
            ['token' => $token]
        );
    }
    
    /**
     * Elimina una sesión (logout)
     */
    public function delete($token) {
        return $this->db->delete('sesiones', 'token = :token', ['token' => $token]);
    }
    
    /**
     * Elimina todas las sesiones de un usuario
     */
    public function deleteAllByUser($idUsuario) {
        return $this->db->delete('sesiones', 'id_usuario = :id', ['id' => $idUsuario]);
    }
    
    /**
     * Elimina sesiones expiradas
     */
    public function deleteExpired() {
        return $this->db->delete('sesiones', 'expira_en < NOW()');
    }
    
    /**
     * Obtiene todas las sesiones activas de un usuario
     */
    public function getActiveByUser($idUsuario) {
        $sql = "SELECT * FROM sesiones 
                WHERE id_usuario = :id 
                AND expira_en > NOW() 
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, ['id' => $idUsuario]);
    }
}

class Codigo2FA {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Genera un código 2FA
     */
    public function generate($idUsuario) {
        // Eliminar códigos anteriores no usados
        $this->db->delete('codigos_2fa', 'id_usuario = :id AND usado = 0', ['id' => $idUsuario]);
        
        $codigo = generateNumericCode(TFA_CODE_LENGTH);
        $expiraEn = date('Y-m-d H:i:s', time() + TFA_CODE_EXPIRY);
        
        $this->db->insert('codigos_2fa', [
            'id_usuario' => $idUsuario,
            'codigo' => $codigo,
            'expira_en' => $expiraEn
        ]);
        
        return $codigo;
    }
    
    /**
     * Valida un código 2FA
     */
    public function validate($idUsuario, $codigo) {
        $sql = "SELECT * FROM codigos_2fa 
                WHERE id_usuario = :id_usuario 
                AND codigo = :codigo 
                AND usado = 0 
                AND expira_en > NOW()";
        
        $result = $this->db->fetchOne($sql, [
            'id_usuario' => $idUsuario,
            'codigo' => $codigo
        ]);
        
        if ($result) {
            // Marcar como usado
            $this->db->update('codigos_2fa', ['usado' => 1], 'id = :id', ['id' => $result['id']]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Elimina códigos expirados
     */
    public function deleteExpired() {
        return $this->db->delete('codigos_2fa', 'expira_en < NOW()');
    }
}

class TokenRecuperacion {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Genera un token de recuperación
     */
    public function generate($idUsuario) {
        // Eliminar tokens anteriores no usados
        $this->db->delete('tokens_recuperacion', 'id_usuario = :id AND usado = 0', ['id' => $idUsuario]);
        
        $token = generateToken(32);
        $expiraEn = date('Y-m-d H:i:s', time() + PASSWORD_RESET_TOKEN_EXPIRY);
        
        $this->db->insert('tokens_recuperacion', [
            'id_usuario' => $idUsuario,
            'token' => $token,
            'expira_en' => $expiraEn
        ]);
        
        return $token;
    }
    
    /**
     * Valida un token de recuperación
     */
    public function validate($token) {
        $sql = "SELECT * FROM tokens_recuperacion 
                WHERE token = :token 
                AND usado = 0 
                AND expira_en > NOW()";
        
        return $this->db->fetchOne($sql, ['token' => $token]);
    }
    
    /**
     * Marca un token como usado
     */
    public function markAsUsed($token) {
        return $this->db->update('tokens_recuperacion', ['usado' => 1], 'token = :token', ['token' => $token]);
    }
    
    /**
     * Elimina tokens expirados
     */
    public function deleteExpired() {
        return $this->db->delete('tokens_recuperacion', 'expira_en < NOW()');
    }
}

class TokenAPI {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Genera un token API
     */
    public function generate($idUsuario, $nombre = 'Bot Telegram') {
        $token = generateToken(32);
        
        $id = $this->db->insert('tokens_api', [
            'id_usuario' => $idUsuario,
            'token' => $token,
            'nombre' => $nombre
        ]);
        
        logAction($idUsuario, "ha generado un token API para '{$nombre}'");
        
        return $token;
    }
    
    /**
     * Valida un token API
     */
    public function validate($token) {
        $sql = "SELECT t.*, u.* 
                FROM tokens_api t 
                INNER JOIN usuarios u ON t.id_usuario = u.id 
                WHERE t.token = :token 
                AND t.activo = 1";
        
        $result = $this->db->fetchOne($sql, ['token' => $token]);
        
        if ($result) {
            // Actualizar último uso
            $this->db->update(
                'tokens_api',
                ['ultimo_uso' => date('Y-m-d H:i:s')],
                'token = :token',
                ['token' => $token]
            );
        }
        
        return $result;
    }
    
    /**
     * Desactiva un token API
     */
    public function deactivate($token) {
        return $this->db->update('tokens_api', ['activo' => 0], 'token = :token', ['token' => $token]);
    }
    
    /**
     * Obtiene todos los tokens de un usuario
     */
    public function getByUser($idUsuario) {
        $sql = "SELECT * FROM tokens_api 
                WHERE id_usuario = :id 
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql, ['id' => $idUsuario]);
    }
    
    /**
     * Elimina un token
     */
    public function delete($id, $idUsuario) {
        return $this->db->delete('tokens_api', 'id = :id AND id_usuario = :id_usuario', [
            'id' => $id,
            'id_usuario' => $idUsuario
        ]);
    }
}