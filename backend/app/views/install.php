<?php
// Procesar instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nombre_usuario = sanitize($_POST['nombre_usuario'] ?? '');
        $correo = sanitize($_POST['correo'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';
        
        // Validaciones
        if (empty($nombre_usuario) || empty($correo) || empty($contrasena)) {
            throw new Exception("Todos los campos son obligatorios");
        }
        
        if (!isValidEmail($correo)) {
            throw new Exception("El correo electrónico no es válido");
        }
        
        $passwordErrors = validatePassword($contrasena);
        if (!empty($passwordErrors)) {
            throw new Exception("La contraseña no cumple los requisitos: " . implode(', ', $passwordErrors));
        }
        
        // Ejecutar script SQL
        $sql = file_get_contents(BASE_PATH . '/database/init.sql');
        
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Ejecutar SQL en múltiples queries
        $queries = array_filter(explode(';', $sql), 'trim');
        foreach ($queries as $query) {
            if (!empty(trim($query))) {
                $conn->exec($query);
            }
        }
        
        // Crear usuario propietario
        $hashedPassword = hashPassword($contrasena);
        $db->insert('usuarios', [
            'nombre_usuario' => $nombre_usuario,
            'correo_electronico' => $correo,
            'contrasena' => $hashedPassword,
            'rol' => 'propietario',
            'autenticacion_2fa' => false
        ]);
        
        // Redirigir a la aplicación
        echo "<script>alert('Instalación completada exitosamente'); window.location.href = '" . BASE_URL . "';</script>";
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Gestión de Gastos</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .info-box {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .info-box p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            line-height: 1.5;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            border-left: 4px solid #f44;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #c33;
            font-size: 14px;
        }
        
        .github-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e1e8ed;
        }
        
        .github-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .github-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalación</h1>
        <p class="subtitle">Gestión de Gastos - Configuración inicial</p>
        
        <div class="info-box">
            <p>
                <strong>Bienvenido!</strong><br>
                Para comenzar, crea una cuenta de propietario que tendrá control total sobre la aplicación.
                Este usuario no podrá ser eliminado y tendrá todos los permisos administrativos.
            </p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error">
                ⚠️ <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de usuario</label>
                <input 
                    type="text" 
                    id="nombre_usuario" 
                    name="nombre_usuario" 
                    required 
                    maxlength="50"
                    placeholder="Ej: admin"
                    value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="correo">Correo electrónico</label>
                <input 
                    type="email" 
                    id="correo" 
                    name="correo" 
                    required 
                    maxlength="200"
                    placeholder="tu@email.com"
                    value="<?php echo htmlspecialchars($_POST['correo'] ?? ''); ?>"
                >
            </div>
            
            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input 
                    type="password" 
                    id="contrasena" 
                    name="contrasena" 
                    required
                    placeholder="Mínimo 8 caracteres"
                >
                <div class="password-requirements">
                    La contraseña debe tener al menos 8 caracteres, incluyendo:
                    mayúsculas, minúsculas y números.
                </div>
            </div>
            
            <button type="submit">Instalar aplicación</button>
        </form>
        
        <div class="github-link">
            <a href="<?php echo GITHUB_REPO_URL; ?>" target="_blank">
                Ver código fuente en GitHub →
            </a>
        </div>
    </div>
</body>
</html>