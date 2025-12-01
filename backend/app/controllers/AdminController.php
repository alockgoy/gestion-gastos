<?php
// ============================================
// AdminController.php
// ============================================

namespace Controllers;

use Models\Usuario;
use Models\Movimiento;
use Core\Database;

class AdminController {
    private $usuarioModel;
    private $movimientoModel;
    private $db;
    
    public function __construct() {
        $this->usuarioModel = new Usuario();
        $this->movimientoModel = new Movimiento();
        $this->db = Database::getInstance();
    }
    
    /**
     * Obtener lista de usuarios (solo admin/propietario)
     */
    public function getUsers($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if (!in_array($currentUser['rol'], ['administrador', 'propietario'])) {
                jsonError("No tienes permisos para esta acción", 403);
            }
            
            $filters = [];
            
            if (isset($_GET['rol'])) {
                $filters['rol'] = $_GET['rol'];
            }
            
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            $users = $this->usuarioModel->getAll($filters);
            
            // Si no es propietario, filtrar al propietario
            if ($currentUser['rol'] !== 'propietario') {
                $users = array_filter($users, function($user) {
                    return $user['rol'] !== 'propietario';
                });
            }
            
            jsonSuccess("Usuarios obtenidos", ['users' => array_values($users)]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Cambiar rol de usuario (solo propietario puede crear admins)
     */
    public function changeUserRole($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if (!in_array($currentUser['rol'], ['administrador', 'propietario'])) {
                jsonError("No tienes permisos para esta acción", 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['user_id']) || empty($data['new_role'])) {
                jsonError("Datos incompletos", 400);
            }
            
            $targetUser = $this->usuarioModel->findById($data['user_id']);
            
            // Solo propietario puede dar rol de administrador
            if ($data['new_role'] === 'administrador' && $currentUser['rol'] !== 'propietario') {
                jsonError("Solo el propietario puede conceder rol de administrador", 403);
            }
            
            // Admin no puede modificar al propietario
            if ($targetUser['rol'] === 'propietario' && $currentUser['rol'] !== 'propietario') {
                jsonError("No puedes modificar al propietario", 403);
            }
            
            $this->usuarioModel->changeRole($data['user_id'], $data['new_role']);
            
            logAction($currentUserId, "ha cambiado el rol del usuario '{$targetUser['nombre_usuario']}' a '{$data['new_role']}'");
            
            jsonSuccess("Rol actualizado exitosamente");
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Eliminar usuario (no puede eliminar propietario)
     */
    public function deleteUser($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if (!in_array($currentUser['rol'], ['administrador', 'propietario'])) {
                jsonError("No tienes permisos para esta acción", 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['user_id'])) {
                jsonError("ID de usuario no proporcionado", 400);
            }
            
            $targetUser = $this->usuarioModel->findById($data['user_id']);
            
            // Admin no puede eliminar al propietario
            if ($targetUser['rol'] === 'propietario') {
                jsonError("No se puede eliminar al propietario", 403);
            }
            
            // Admin no puede eliminar a otro admin o al propietario
            if ($currentUser['rol'] === 'administrador' && in_array($targetUser['rol'], ['administrador', 'propietario'])) {
                jsonError("No tienes permisos para eliminar este usuario", 403);
            }
            
            $this->usuarioModel->delete($data['user_id']);
            
            logAction($currentUserId, "ha eliminado al usuario '{$targetUser['nombre_usuario']}'");
            
            jsonSuccess("Usuario eliminado exitosamente");
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Modificar datos de usuario
     */
    public function updateUser($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if (!in_array($currentUser['rol'], ['administrador', 'propietario'])) {
                jsonError("No tienes permisos para esta acción", 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['user_id'])) {
                jsonError("ID de usuario no proporcionado", 400);
            }
            
            $targetUser = $this->usuarioModel->findById($data['user_id']);
            
            // Admin no puede modificar al propietario
            if ($targetUser['rol'] === 'propietario' && $currentUser['rol'] !== 'propietario') {
                jsonError("No puedes modificar al propietario", 403);
            }
            
            // Preparar datos de actualización
            $updateData = [];
            $allowedFields = ['nombre_usuario', 'correo_electronico', 'autenticacion_2fa'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateData[$field] = $data[$field];
                }
            }
            
            if (empty($updateData)) {
                jsonError("No hay datos para actualizar", 400);
            }
            
            $this->usuarioModel->update($data['user_id'], $updateData);
            
            logAction($currentUserId, "ha modificado datos del usuario '{$targetUser['nombre_usuario']}'");
            
            jsonSuccess("Usuario actualizado exitosamente");
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Eliminar movimientos de un usuario (sin modificar)
     */
    public function deleteUserMovement($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if (!in_array($currentUser['rol'], ['administrador', 'propietario'])) {
                jsonError("No tienes permisos para esta acción", 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data['movement_id'])) {
                jsonError("ID de movimiento no proporcionado", 400);
            }
            
            $movimiento = $this->movimientoModel->findById($data['movement_id']);
            
            if (!$movimiento) {
                jsonError("Movimiento no encontrado", 404);
            }
            
            // Admin no puede eliminar movimientos del propietario
            $targetUser = $this->usuarioModel->findById($movimiento['id_usuario']);
            if ($targetUser['rol'] === 'propietario' && $currentUser['rol'] !== 'propietario') {
                jsonError("No puedes eliminar movimientos del propietario", 403);
            }
            
            $this->movimientoModel->delete($data['movement_id']);
            
            logAction($currentUserId, "ha eliminado un movimiento del usuario '{$targetUser['nombre_usuario']}'");
            
            jsonSuccess("Movimiento eliminado exitosamente");
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Obtener historial de acciones (solo propietario)
     */
    public function getActivityLog($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if ($currentUser['rol'] !== 'propietario') {
                jsonError("Solo el propietario puede ver el historial", 403);
            }
            
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
            $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            
            $sql = "SELECT mp.*, u.nombre_usuario 
                    FROM movimientos_pagina mp 
                    INNER JOIN usuarios u ON mp.id_usuario = u.id 
                    ORDER BY mp.fecha DESC 
                    LIMIT :limit OFFSET :offset";
            
            $logs = $this->db->fetchAll($sql, [
                'limit' => $limit,
                'offset' => $offset
            ]);
            
            // Contar total
            $total = $this->db->fetchColumn("SELECT COUNT(*) FROM movimientos_pagina");
            
            jsonSuccess("Historial obtenido", [
                'logs' => $logs,
                'total' => $total
            ]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Obtener estadísticas generales (solo propietario)
     */
    public function getStats($currentUserId) {
        try {
            $currentUser = $this->usuarioModel->findById($currentUserId);
            
            if ($currentUser['rol'] !== 'propietario') {
                jsonError("Solo el propietario puede ver estadísticas generales", 403);
            }
            
            $stats = [
                'total_usuarios' => $this->db->fetchColumn("SELECT COUNT(*) FROM usuarios"),
                'usuarios_activos' => $this->db->fetchColumn("SELECT COUNT(*) FROM usuarios WHERE ultimo_logueo > DATE_SUB(NOW(), INTERVAL 30 DAY)"),
                'total_cuentas' => $this->db->fetchColumn("SELECT COUNT(*) FROM cuentas"),
                'total_movimientos' => $this->db->fetchColumn("SELECT COUNT(*) FROM movimientos"),
                'usuarios_con_2fa' => $this->db->fetchColumn("SELECT COUNT(*) FROM usuarios WHERE autenticacion_2fa = 1")
            ];
            
            jsonSuccess("Estadísticas obtenidas", ['stats' => $stats]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
}