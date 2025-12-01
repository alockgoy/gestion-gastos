<?php

require_once '../config/config.php';

use Core\Database;

// Verificar si la base de datos existe y está instalada
$db = Database::getInstance();

if (!$db->tableExists('usuarios')) {
    // Mostrar página de instalación
    require_once '../app/views/install.php';
    exit;
}

// Si la BD existe, servir el frontend de React
// (En producción, esto estaría manejado por el servidor web)
header('Location: ' . BASE_URL);
exit;