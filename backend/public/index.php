<?php

require_once '../config/config.php';

use Core\Database;

// Verificar si la base de datos existe y está instalada
$db = Database::getInstance();

try {
    // Verificar si existe la tabla usuarios
    if (!$db->tableExists('usuarios')) {
        // Si no existe la tabla, mostrar instalación
        require_once '../app/views/install.php';
        exit;
    }
    
    // Verificar si existe un usuario propietario
    $sql = "SELECT COUNT(*) as count FROM usuarios WHERE rol = 'propietario'";
    $result = $db->fetchOne($sql);
    
    if ($result['count'] == 0) {
        // No hay usuario propietario, mostrar instalación
        require_once '../app/views/install.php';
        exit;
    }
    
} catch (Exception $e) {
    // Error al conectar, mostrar instalación
    require_once '../app/views/install.php';
    exit;
}

// Si hay usuario propietario, redirigir al frontend
header('Location: ' . BASE_URL);
exit;