<?php

namespace Core;

class Router {
    private $routes = [];
    private $middlewares = [];
    
    /**
     * Registra una ruta GET
     */
    public function get($path, $callback, $middlewares = []) {
        $this->addRoute('GET', $path, $callback, $middlewares);
    }
    
    /**
     * Registra una ruta POST
     */
    public function post($path, $callback, $middlewares = []) {
        $this->addRoute('POST', $path, $callback, $middlewares);
    }
    
    /**
     * Registra una ruta PUT
     */
    public function put($path, $callback, $middlewares = []) {
        $this->addRoute('PUT', $path, $callback, $middlewares);
    }
    
    /**
     * Registra una ruta DELETE
     */
    public function delete($path, $callback, $middlewares = []) {
        $this->addRoute('DELETE', $path, $callback, $middlewares);
    }
    
    /**
     * Añade una ruta al router
     */
    private function addRoute($method, $path, $callback, $middlewares) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback,
            'middlewares' => $middlewares
        ];
    }
    
    /**
     * Registra un middleware global
     */
    public function middleware($middleware) {
        $this->middlewares[] = $middleware;
    }
    
    /**
     * Ejecuta el router
     */
    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Strip /api prefix from the request URI to match defined routes
        if (strpos($requestUri, '/api') === 0) {
            $requestUri = substr($requestUri, 4);
        }
        
        // Handle empty path after stripping prefix
        if ($requestUri === '' || $requestUri === false) {
            $requestUri = '/';
        }
        
        // Manejar métodos PUT y DELETE desde _method
        if ($requestMethod === 'POST' && isset($_POST['_method'])) {
            $requestMethod = strtoupper($_POST['_method']);
        }
        
        // Ejecutar middlewares globales
        foreach ($this->middlewares as $middleware) {
            $middlewareInstance = new $middleware();
            $middlewareInstance->handle();
        }
        
        // Buscar ruta coincidente
        foreach ($this->routes as $route) {
            $pattern = $this->buildPattern($route['path']);
            
            if ($route['method'] === $requestMethod && preg_match($pattern, $requestUri, $matches)) {
                // Ejecutar middlewares de la ruta
                foreach ($route['middlewares'] as $middleware) {
                    $middlewareInstance = new $middleware();
                    $middlewareInstance->handle();
                }
                
                // Extraer parámetros
                array_shift($matches); // Remover match completo
                
                // Ejecutar callback
                if (is_callable($route['callback'])) {
                    call_user_func_array($route['callback'], $matches);
                } elseif (is_array($route['callback'])) {
                    $controller = new $route['callback'][0]();
                    $method = $route['callback'][1];
                    call_user_func_array([$controller, $method], $matches);
                }
                
                return;
            }
        }
        
        // Ruta no encontrada
        http_response_code(404);
        jsonError("Ruta no encontrada", 404);
    }
    
    /**
     * Construye un patrón regex desde una ruta
     */
    private function buildPattern($path) {
        // Convertir parámetros (:param) a grupos regex
        $pattern = preg_replace('/\/:([^\/]+)/', '/(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}

class Middleware {
    /**
     * Método a implementar por middlewares específicos
     */
    public function handle() {
        // Implementar en subclases
    }
    
    /**
     * Obtiene el usuario autenticado desde el token
     */
    protected function getAuthenticatedUser() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        
        if (empty($token)) {
            return null;
        }
        
        $sesionModel = new \Models\Sesion();
        $session = $sesionModel->validate($token);
        
        return $session;
    }
}

class AuthMiddleware extends Middleware {
    /**
     * Verifica que el usuario esté autenticado
     */
    public function handle() {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            jsonError("No autenticado", 401);
        }
        
        // Guardar usuario en variable global para uso en controladores
        $GLOBALS['current_user'] = $user;
    }
}

class AdminMiddleware extends Middleware {
    /**
     * Verifica que el usuario sea admin o propietario
     */
    public function handle() {
        $user = $this->getAuthenticatedUser();
        
        if (!$user || !in_array($user['rol'], ['administrador', 'propietario'])) {
            jsonError("Requiere permisos de administrador", 403);
        }
        
        $GLOBALS['current_user'] = $user;
    }
}

class OwnerMiddleware extends Middleware {
    /**
     * Verifica que el usuario sea el propietario
     */
    public function handle() {
        $user = $this->getAuthenticatedUser();
        
        if (!$user || $user['rol'] !== 'propietario') {
            jsonError("Requiere permisos de propietario", 403);
        }
        
        $GLOBALS['current_user'] = $user;
    }
}

class CorsMiddleware extends Middleware {
    /**
     * Maneja CORS para permitir peticiones desde el frontend
     */
    public function handle() {
        // Permitir orígenes específicos
        $allowedOrigins = [
            'http://localhost:3000',
            'http://192.168.1.122:3000',
            'http://127.0.0.1:3000'
        ];
        
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } else {
            header("Access-Control-Allow-Origin: http://localhost:3000");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
        
        // Responder a preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}