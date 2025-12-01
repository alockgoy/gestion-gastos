<?php

/**
 * Script de tareas programadas
 * Ejecutar mensualmente: 0 0 1 * * php /var/www/html/scripts/cron.php
 */

require_once dirname(__DIR__) . '/config/config.php';

use Models\Usuario;
use Models\Sesion;
use Models\Codigo2FA;
use Models\TokenRecuperacion;

echo "[" . date('Y-m-d H:i:s') . "] Iniciando tareas programadas...\n";

// ============================================
// 1. Gestión de usuarios inactivos
// ============================================

echo "Verificando usuarios inactivos...\n";

$usuarioModel = new Usuario();
$inactiveUsers = $usuarioModel->getInactiveUsers();

foreach ($inactiveUsers as $user) {
    switch ($user['status']) {
        case 'delete':
            // Usuario inactivo por 2+ años: eliminar
            echo "Eliminando usuario inactivo: {$user['nombre_usuario']}\n";
            
            try {
                $usuarioModel->delete($user['id']);
                echo "  ✓ Usuario eliminado\n";
            } catch (Exception $e) {
                echo "  ✗ Error al eliminar: {$e->getMessage()}\n";
            }
            break;
            
        case 'warning':
            // Usuario inactivo por 1.5+ años: enviar advertencia
            echo "Enviando advertencia a: {$user['correo_electronico']}\n";
            
            $diasInactivo = floor((time() - strtotime($user['ultimo_logueo'])) / 86400);
            $diasRestantes = 730 - $diasInactivo; // 2 años = 730 días
            
            $subject = "⚠️ Tu cuenta será eliminada en {$diasRestantes} días";
            $body = "
                <h2>Advertencia de cuenta inactiva</h2>
                <p>Hola {$user['nombre_usuario']},</p>
                <p>No has iniciado sesión en <strong>{$diasInactivo} días</strong>.</p>
                <p>Si no accedes a tu cuenta en los próximos <strong>{$diasRestantes} días</strong>, 
                será eliminada automáticamente junto con todos tus datos.</p>
                <p><a href='" . BASE_URL . "'>Iniciar sesión ahora</a></p>
                <p>Si deseas mantener tu cuenta, simplemente inicia sesión.</p>
            ";
            
            if (sendEmail($user['correo_electronico'], $subject, $body)) {
                echo "  ✓ Correo enviado\n";
            } else {
                echo "  ✗ Error al enviar correo\n";
            }
            break;
            
        case 'reminder':
            // Usuario inactivo por 1+ año: enviar recordatorio
            echo "Enviando recordatorio a: {$user['correo_electronico']}\n";
            
            $diasInactivo = floor((time() - strtotime($user['ultimo_logueo'])) / 86400);
            
            $subject = "Recordatorio: No has usado tu cuenta en mucho tiempo";
            $body = "
                <h2>Te extrañamos!</h2>
                <p>Hola {$user['nombre_usuario']},</p>
                <p>No has iniciado sesión en <strong>{$diasInactivo} días</strong>.</p>
                <p>Queremos recordarte que tu cuenta sigue activa y lista para usar.</p>
                <p><a href='" . BASE_URL . "'>Iniciar sesión</a></p>
                <p><small>Nota: Las cuentas inactivas por más de 2 años son eliminadas automáticamente.</small></p>
            ";
            
            if (sendEmail($user['correo_electronico'], $subject, $body)) {
                echo "  ✓ Correo enviado\n";
            } else {
                echo "  ✗ Error al enviar correo\n";
            }
            break;
    }
}

echo "Usuarios inactivos procesados: " . count($inactiveUsers) . "\n\n";

// ============================================
// 2. Limpiar sesiones expiradas
// ============================================

echo "Limpiando sesiones expiradas...\n";

$sesionModel = new Sesion();
$deletedSessions = $sesionModel->deleteExpired();

echo "Sesiones eliminadas: {$deletedSessions}\n\n";

// ============================================
// 3. Limpiar códigos 2FA expirados
// ============================================

echo "Limpiando códigos 2FA expirados...\n";

$codigo2FAModel = new Codigo2FA();
$deleted2FA = $codigo2FAModel->deleteExpired();

echo "Códigos 2FA eliminados: {$deleted2FA}\n\n";

// ============================================
// 4. Limpiar tokens de recuperación expirados
// ============================================

echo "Limpiando tokens de recuperación expirados...\n";

$tokenRecuperacionModel = new TokenRecuperacion();
$deletedTokens = $tokenRecuperacionModel->deleteExpired();

echo "Tokens eliminados: {$deletedTokens}\n\n";

// ============================================
// Resumen final
// ============================================

echo "[" . date('Y-m-d H:i:s') . "] Tareas completadas exitosamente\n";
echo "================================================\n";
echo "Resumen:\n";
echo "  - Usuarios procesados: " . count($inactiveUsers) . "\n";
echo "  - Sesiones limpiadas: {$deletedSessions}\n";
echo "  - Códigos 2FA limpiados: {$deleted2FA}\n";
echo "  - Tokens limpiados: {$deletedTokens}\n";
echo "================================================\n";