<?php

// Headers CORS - APLICAR ANTES DE CUALQUIER COSA
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/config.php';

// Eliminar prefijo /api de la URI
$_SERVER['REQUEST_URI'] = preg_replace('#^/api#', '', $_SERVER['REQUEST_URI']);

// Cargar autoloader de Composer
if (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use Core\Router;
use Core\AuthMiddleware;
use Core\AdminMiddleware;
use Core\OwnerMiddleware;
use Core\CorsMiddleware;

use Controllers\AuthController;
use Controllers\UserController;
use Controllers\AccountController;
use Controllers\MovementController;
use Controllers\AdminController;
use Controllers\TagController;

$router = new Router();

// Middleware global CORS
$router->middleware(CorsMiddleware::class);

// ============================================
// Rutas públicas (sin autenticación)
// ============================================

// Autenticación
$router->post('/auth/register', [AuthController::class, 'register']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/verify-2fa', [AuthController::class, 'verify2FA']);
$router->post('/auth/request-password-reset', [AuthController::class, 'requestPasswordReset']);
$router->post('/auth/reset-password', [AuthController::class, 'resetPassword']);

// Etiquetas (públicas para mostrar en formularios)
$router->get('/tags', [TagController::class, 'getAll']);

// ============================================
// Rutas protegidas (requieren autenticación)
// ============================================

// Validar sesión
$router->get('/auth/validate', [AuthController::class, 'validateSession'], [AuthMiddleware::class]);
$router->post('/auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// Usuario
$router->get('/user/profile', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->getProfile($userId);
}, [AuthMiddleware::class]);

$router->put('/user/profile', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->updateProfile($userId);
}, [AuthMiddleware::class]);

$router->put('/user/change-password', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->changePassword($userId);
}, [AuthMiddleware::class]);

$router->post('/user/profile-photo', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->uploadProfilePhoto($userId);
}, [AuthMiddleware::class]);

$router->put('/user/toggle-2fa', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->toggle2FA($userId);
}, [AuthMiddleware::class]);

$router->post('/user/request-admin', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->requestAdmin($userId);
}, [AuthMiddleware::class]);

$router->delete('/user/account', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->deleteAccount($userId);
}, [AuthMiddleware::class]);

$router->get('/user/sessions', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->getActiveSessions($userId);
}, [AuthMiddleware::class]);

// Tokens API
$router->post('/user/api-tokens', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->generateAPIToken($userId);
}, [AuthMiddleware::class]);

$router->get('/user/api-tokens', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->listAPITokens($userId);
}, [AuthMiddleware::class]);

$router->delete('/user/api-tokens', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new UserController();
    $controller->deleteAPIToken($userId);
}, [AuthMiddleware::class]);

// Cuentas
$router->post('/accounts', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->create($userId);
}, [AuthMiddleware::class]);

$router->get('/accounts', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->getAll($userId);
}, [AuthMiddleware::class]);

$router->get('/accounts/summary', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->getSummary($userId);
}, [AuthMiddleware::class]);

$router->get('/accounts/search', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->search($userId);
}, [AuthMiddleware::class]);

$router->get('/accounts/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->getOne($userId, $id);
}, [AuthMiddleware::class]);

$router->put('/accounts/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->update($userId, $id);
}, [AuthMiddleware::class]);

$router->delete('/accounts/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AccountController();
    $controller->delete($userId, $id);
}, [AuthMiddleware::class]);

// Movimientos
$router->post('/movements', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->create($userId);
}, [AuthMiddleware::class]);

$router->get('/movements', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->getAll($userId);
}, [AuthMiddleware::class]);

$router->get('/movements/stats', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->getStats($userId);
}, [AuthMiddleware::class]);

$router->get('/movements/export/csv', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->exportCSV($userId);
}, [AuthMiddleware::class]);

$router->get('/movements/export/json', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->exportJSON($userId);
}, [AuthMiddleware::class]);

$router->post('/movements/import', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->importJSON($userId);
}, [AuthMiddleware::class]);

$router->get('/movements/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->getOne($userId, $id);
}, [AuthMiddleware::class]);

$router->put('/movements/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->update($userId, $id);
}, [AuthMiddleware::class]);

$router->delete('/movements/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new MovementController();
    $controller->delete($userId, $id);
}, [AuthMiddleware::class]);

// ============================================
// Rutas de administración
// ============================================

// Gestión de usuarios (admin y propietario)
$router->get('/admin/users', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->getUsers($userId);
}, [AdminMiddleware::class]);

$router->put('/admin/users/role', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->changeUserRole($userId);
}, [AdminMiddleware::class]);

$router->put('/admin/users/update', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->updateUser($userId);
}, [AdminMiddleware::class]);

$router->delete('/admin/users', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->deleteUser($userId);
}, [AdminMiddleware::class]);

$router->delete('/admin/movements', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->deleteUserMovement($userId);
}, [AdminMiddleware::class]);

// Solo propietario
$router->get('/admin/activity-log', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->getActivityLog($userId);
}, [OwnerMiddleware::class]);

$router->get('/admin/stats', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new AdminController();
    $controller->getStats($userId);
}, [OwnerMiddleware::class]);

// Gestión de etiquetas (solo propietario)
$router->post('/admin/tags', function() {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new TagController();
    $controller->create($userId);
}, [OwnerMiddleware::class]);

$router->put('/admin/tags/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new TagController();
    $controller->update($userId, $id);
}, [OwnerMiddleware::class]);

$router->delete('/admin/tags/:id', function($id) {
    $userId = $GLOBALS['current_user']['id'];
    $controller = new TagController();
    $controller->delete($userId, $id);
}, [OwnerMiddleware::class]);

// Ejecutar router
$router->run();