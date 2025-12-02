<?php
// filepath: backend/app/core/CorsMiddleware.php

namespace Core;

class CorsMiddleware {
    public static function handle() {
        // Permitir origen específico o todos (*)
        $allowedOrigins = ['http://localhost:3000', 'http://192.168.1.122:3000'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            header("Access-Control-Allow-Origin: http://localhost:3000");
        }
        
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
        
        // Si es una petición OPTIONS (preflight), responder y terminar
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
}