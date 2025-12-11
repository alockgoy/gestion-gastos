<?php

namespace Controllers;

use Models\Usuario;
use Models\Sesion;
use Models\TokenAPI;

class UserController
{
    private $usuarioModel;
    private $sesionModel;
    private $tokenAPIModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
        $this->sesionModel = new Sesion();
        $this->tokenAPIModel = new TokenAPI();
    }

    /**
     * Obtener perfil del usuario actual
     */
    public function getProfile($userId)
    {
        try {
            $user = $this->usuarioModel->findById($userId);

            if (!$user) {
                jsonError("Usuario no encontrado", 404);
            }

            // Obtener balance total
            $balanceTotal = $this->usuarioModel->getTotalBalance($userId);

            // No devolver la contraseña
            unset($user['contrasena']);
            $user['balance_total'] = $balanceTotal;

            jsonSuccess("Perfil obtenido", ['user' => $user]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Actualizar perfil
     */
    public function updateProfile()
    {
        $userId = $GLOBALS['current_user']['id'];

        try {
            // Para PUT con multipart/form-data, parsear manualmente
            $data = $_POST;

            if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'PUT') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

                if (strpos($contentType, 'multipart/form-data') !== false) {
                    $data = $this->parseMultipartFormData();
                } else {
                    $data = json_decode(file_get_contents('php://input'), true) ?? [];
                }
            }

            $updateData = [];

            // Procesar nombre de usuario si se proporciona
            if (isset($data['nombre_usuario']) && !empty($data['nombre_usuario'])) {
                $nombreUsuario = sanitize($data['nombre_usuario']);

                if (strlen($nombreUsuario) < 3) {
                    jsonError("El nombre de usuario debe tener al menos 3 caracteres", 400);
                }

                // Verificar que el nombre de usuario no esté en uso
                $existingUser = $this->userModel->findByUsername($nombreUsuario);
                if ($existingUser && $existingUser['id'] != $userId) {
                    jsonError("El nombre de usuario ya está en uso", 409);
                }

                $updateData['nombre_usuario'] = $nombreUsuario;
            }

            // Procesar foto de perfil
            if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
                $updateData['foto_perfil'] = $_FILES['foto_perfil'];
            }

            if (empty($updateData)) {
                jsonError("No hay datos para actualizar", 400);
            }

            $this->userModel->updateProfile($userId, $updateData);

            // Obtener datos actualizados del usuario
            $user = $this->userModel->findById($userId);

            jsonSuccess("Perfil actualizado exitosamente", [
                'user' => [
                    'id' => $user['id'],
                    'nombre_usuario' => $user['nombre_usuario'],
                    'correo_electronico' => $user['correo_electronico'],
                    'rol' => $user['rol'],
                    'foto_perfil' => $user['foto_perfil'],
                    'autenticacion_2fa' => (int) $user['autenticacion_2fa']
                ]
            ]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Parsea multipart/form-data para peticiones PUT
     */
    private function parseMultipartFormData()
    {
        $data = [];
        $input = file_get_contents('php://input');

        if (empty($input)) {
            return $data;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        preg_match('/boundary=(.*)$/', $contentType, $matches);

        if (!isset($matches[1])) {
            return $data;
        }

        $boundary = $matches[1];
        $parts = explode('--' . $boundary, $input);
        array_shift($parts);
        array_pop($parts);

        foreach ($parts as $part) {
            if (trim($part) === '--' || empty(trim($part, "\r\n-"))) {
                continue;
            }

            $parts_split = preg_split("/\r?\n\r?\n/", $part, 2);

            if (count($parts_split) < 2) {
                continue;
            }

            list($headers_block, $content) = $parts_split;

            if (preg_match('/name="([^"]*)"/', $headers_block, $nameMatches)) {
                $name = $nameMatches[1];

                if (preg_match('/filename="([^"]*)"/', $headers_block, $fileMatches)) {
                    $filename = $fileMatches[1];

                    if (!empty($filename)) {
                        $mimeType = 'application/octet-stream';
                        if (preg_match('/Content-Type:\s*(.*)$/mi', $headers_block, $typeMatches)) {
                            $mimeType = trim($typeMatches[1]);
                        }

                        $content = substr($content, 0, -2);
                        $tmpPath = tempnam(sys_get_temp_dir(), 'php_upload_');
                        file_put_contents($tmpPath, $content, LOCK_EX);

                        if (file_exists($tmpPath)) {
                            $_FILES[$name] = [
                                'name' => $filename,
                                'type' => $mimeType,
                                'tmp_name' => $tmpPath,
                                'error' => UPLOAD_ERR_OK,
                                'size' => filesize($tmpPath)
                            ];
                        }
                    }
                } else {
                    $data[$name] = rtrim($content, "\r\n");
                }
            }
        }

        return $data;
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword($userId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['contrasena_actual']) || empty($data['contrasena_nueva'])) {
                jsonError("Todos los campos son obligatorios", 400);
            }

            // Verificar contraseña actual
            $user = $this->usuarioModel->findById($userId);
            if (!verifyPassword($data['contrasena_actual'], $user['contrasena'])) {
                jsonError("La contraseña actual es incorrecta", 401);
            }

            // Validar nueva contraseña
            $passwordErrors = validatePassword($data['contrasena_nueva']);
            if (!empty($passwordErrors)) {
                jsonError("La nueva contraseña no cumple los requisitos", 400, $passwordErrors);
            }

            // Actualizar contraseña
            $this->usuarioModel->update($userId, [
                'contrasena' => $data['contrasena_nueva']
            ]);

            // Cerrar todas las sesiones excepto la actual
            $currentToken = str_replace('Bearer ', '', getallheaders()['Authorization'] ?? '');
            $sessions = $this->sesionModel->getActiveByUser($userId);

            foreach ($sessions as $session) {
                if ($session['token'] !== $currentToken) {
                    $this->sesionModel->delete($session['token']);
                }
            }

            logAction($userId, 'ha cambiado su contraseña');

            jsonSuccess("Contraseña actualizada exitosamente");

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Subir foto de perfil
     */
    public function uploadProfilePhoto($userId)
    {
        try {
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                jsonError("Error al subir el archivo", 400);
            }

            $file = $_FILES['foto'];

            // Validar que sea una imagen
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, $allowedTypes)) {
                jsonError("Solo se permiten imágenes (JPG, PNG, GIF, WEBP)", 400);
            }

            // Validar tamaño (máximo 2MB para fotos de perfil)
            if ($file['size'] > 2097152) {
                jsonError("La imagen no puede superar los 2 MB", 400);
            }

            // Eliminar foto anterior si existe
            $user = $this->usuarioModel->findById($userId);
            if ($user['foto_perfil']) {
                $oldPhoto = UPLOADS_PATH . '/profiles/' . $user['foto_perfil'];
                if (file_exists($oldPhoto)) {
                    unlink($oldPhoto);
                }
            }

            // Guardar nueva foto
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
            $destination = UPLOADS_PATH . '/profiles/' . $filename;

            // Crear directorio si no existe
            if (!file_exists(UPLOADS_PATH . '/profiles')) {
                mkdir(UPLOADS_PATH . '/profiles', 0755, true);
            }

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                jsonError("Error al guardar la imagen", 500);
            }

            // Actualizar base de datos
            $this->usuarioModel->update($userId, ['foto_perfil' => $filename]);

            logAction($userId, 'ha actualizado su foto de perfil');

            jsonSuccess("Foto de perfil actualizada", ['filename' => $filename]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Activar/Desactivar 2FA
     */
    public function toggle2FA($userId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['enable'])) {
                jsonError("Debe especificar si activar o desactivar", 400);
            }

            $enable = (bool) $data['enable'];

            // Si va a activar, verificar contraseña
            /* if ($enable) {
                if (empty($data['contrasena'])) {
                    jsonError("Debe confirmar su contraseña", 400);
                }

                $user = $this->usuarioModel->findById($userId);
                if (!verifyPassword($data['contrasena'], $user['contrasena'])) {
                    jsonError("Contraseña incorrecta", 401);
                }
            } */

            $this->usuarioModel->toggle2FA($userId, $enable);

            $message = $enable ? "Verificación en 2 pasos activada" : "Verificación en 2 pasos desactivada";
            jsonSuccess($message);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Solicitar ser administrador
     */
    public function requestAdmin($userId)
    {
        try {
            $this->usuarioModel->requestAdmin($userId);
            jsonSuccess("Solicitud enviada exitosamente");

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Eliminar cuenta
     */
    public function deleteAccount($userId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Verificar contraseña
            if (empty($data['contrasena'])) {
                jsonError("Debe confirmar su contraseña", 400);
            }

            $user = $this->usuarioModel->findById($userId);
            if (!verifyPassword($data['contrasena'], $user['contrasena'])) {
                jsonError("Contraseña incorrecta", 401);
            }

            // Verificar confirmaciones
            if (empty($data['confirmacion1']) || empty($data['confirmacion2'])) {
                jsonError("Debe confirmar la eliminación dos veces", 400);
            }

            if ($data['confirmacion1'] !== 'ELIMINAR' || $data['confirmacion2'] !== 'ELIMINAR') {
                jsonError("Las confirmaciones deben ser exactamente 'ELIMINAR'", 400);
            }

            // Eliminar usuario (cascada eliminará todo)
            $this->usuarioModel->delete($userId);

            jsonSuccess("Cuenta eliminada exitosamente");

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Obtener sesiones activas
     */
    public function getActiveSessions($userId)
    {
        try {
            $sessions = $this->sesionModel->getActiveByUser($userId);

            // Formatear sesiones
            $formattedSessions = array_map(function ($session) {
                return [
                    'ip' => $session['ip'],
                    'user_agent' => $session['user_agent'],
                    'created_at' => $session['created_at'],
                    'expira_en' => $session['expira_en']
                ];
            }, $sessions);

            jsonSuccess("Sesiones obtenidas", ['sessions' => $formattedSessions]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Generar token API
     */
    public function generateAPIToken($userId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $nombre = $data['nombre'] ?? 'Bot Telegram';

            $token = $this->tokenAPIModel->generate($userId, $nombre);

            jsonSuccess("Token generado exitosamente", ['token' => $token]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Listar tokens API
     */
    public function listAPITokens($userId)
    {
        try {
            $tokens = $this->tokenAPIModel->getByUser($userId);

            // Ocultar parte del token por seguridad
            $formattedTokens = array_map(function ($token) {
                return [
                    'id' => $token['id'],
                    'nombre' => $token['nombre'],
                    'token_preview' => substr($token['token'], 0, 8) . '...' . substr($token['token'], -8),
                    'activo' => $token['activo'],
                    'ultimo_uso' => $token['ultimo_uso'],
                    'created_at' => $token['created_at']
                ];
            }, $tokens);

            jsonSuccess("Tokens obtenidos", ['tokens' => $formattedTokens]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Eliminar token API
     */
    public function deleteAPIToken($userId)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['token_id'])) {
                jsonError("ID del token no proporcionado", 400);
            }

            $this->tokenAPIModel->delete($data['token_id'], $userId);

            logAction($userId, 'ha eliminado un token API');

            jsonSuccess("Token eliminado exitosamente");

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
}