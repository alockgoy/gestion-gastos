<?php
// Configuración principal de la aplicación

// Cargar autoloader de Composer PRIMERO
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Mostrar errores en desarrollo (cambiar a false en producción)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuración de zona horaria
date_default_timezone_set('Europe/Madrid');

// Configuración de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'gastos_db');
define('DB_USER', getenv('DB_USER') ?: 'gastos_user');
define('DB_PASS', getenv('DB_PASS') ?: 'gastos_pass');
define('DB_CHARSET', 'utf8mb4');

// Configuración SMTP
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: '');
define('SMTP_FROM_NAME', 'Gestión de Gastos');

// Rutas de la aplicación
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('VIEWS_PATH', BASE_PATH . '/app/views');

// URLs
define('BASE_URL', getenv('APP_URL') ?: 'http://localhost:3000');
define('API_URL', getenv('API_URL') ?: 'http://localhost:8080/api');

// Configuración de sesión
define('SESSION_NAME', 'GASTOS_SESSION');
define('SESSION_LIFETIME', 7200); // 2 horas

// Configuración de cookies
define('COOKIE_DOMAIN', '');
define('COOKIE_PATH', '/');
define('COOKIE_SECURE', false); // true en producción con HTTPS
define('COOKIE_HTTPONLY', true);

// Configuración de seguridad
define('PASSWORD_MIN_LENGTH', 8);
define('BCRYPT_COST', 12); // Coste del algoritmo bcrypt

// Configuración de 2FA
define('TFA_CODE_LENGTH', 6);
define('TFA_CODE_EXPIRY', 300); // 5 minutos en segundos

// Configuración de recuperación de contraseña
define('PASSWORD_RESET_TOKEN_EXPIRY', 3600); // 1 hora en segundos

// Configuración de archivos adjuntos
define('MAX_FILE_SIZE', 5242880); // 5 MB en bytes
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf']);
define('ALLOWED_FILE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf']);

// Configuración de API
define('API_TOKEN_LENGTH', 64);

// Repositorio de GitHub
define('GITHUB_REPO_URL', getenv('GITHUB_REPO_URL') ?: 'https://github.com/tu-usuario/gestion-gastos');

// Autoload de clases
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Funciones auxiliares
require_once BASE_PATH . '/app/helpers/functions.php';