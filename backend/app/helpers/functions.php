<?php

/**
 * Funciones auxiliares globales
 */

/**
 * Sanitiza una cadena de texto
 */
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida un correo electrónico
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Genera un hash seguro de contraseña usando bcrypt
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
}

/**
 * Verifica una contraseña contra un hash
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Genera un token seguro aleatorio
 */
function generateToken($length = 32)
{
    return bin2hex(random_bytes($length));
}

/**
 * Genera un código numérico aleatorio
 */
function generateNumericCode($length = 6)
{
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= random_int(0, 9);
    }
    return $code;
}

/**
 * Obtiene la IP del cliente
 */
function getClientIP()
{
    $ip = '';

    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    }

    // Validar IP
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }

    return '0.0.0.0';
}

/**
 * Obtiene el User Agent del cliente
 */
function getUserAgent()
{
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Redirecciona a una URL
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Devuelve una respuesta JSON
 */
function jsonResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Devuelve un error JSON
 */
function jsonError($message, $statusCode = 400, $errors = [])
{
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $statusCode);
}

/**
 * Devuelve éxito JSON
 */
function jsonSuccess($message, $data = [], $statusCode = 200)
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

/**
 * Valida que un archivo sea del tipo permitido
 */
function isValidFileType($mimeType, $extension)
{
    return in_array($mimeType, ALLOWED_FILE_TYPES) &&
        in_array(strtolower($extension), ALLOWED_FILE_EXTENSIONS);
}

/**
 * Valida el tamaño del archivo
 */
function isValidFileSize($size)
{
    return $size <= MAX_FILE_SIZE;
}

/**
 * Genera un nombre de archivo único
 */
function generateUniqueFilename($originalName)
{
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid('file_', true) . '.' . $extension;
}

/**
 * Formatea una cantidad de dinero
 */
function formatMoney($amount, $currency = 'EUR')
{
    return number_format($amount, 2, ',', '.') . ' ' . $currency;
}

/**
 * Formatea una fecha
 */
function formatDate($date, $format = 'Y-m-d H:i:s')
{
    if (empty($date))
        return '';

    try {
        // Si la fecha ya está en formato correcto de la BD
        $datetime = new DateTime($date);
        // Asegurar que se interpreta en la zona horaria configurada
        $datetime->setTimezone(new DateTimeZone('Europe/Madrid'));

        return $datetime->format($format);
    } catch (Exception $e) {
        return $date; // Devolver original si hay error
    }
}

/**
 * Calcula la diferencia de tiempo desde una fecha
 */
function timeAgo($date)
{
    $datetime = new DateTime($date);
    $now = new DateTime();
    $interval = $now->diff($datetime);

    if ($interval->y > 0) {
        return $interval->y . ' año' . ($interval->y > 1 ? 's' : '');
    } elseif ($interval->m > 0) {
        return $interval->m . ' mes' . ($interval->m > 1 ? 'es' : '');
    } elseif ($interval->d > 0) {
        return $interval->d . ' día' . ($interval->d > 1 ? 's' : '');
    } elseif ($interval->h > 0) {
        return $interval->h . ' hora' . ($interval->h > 1 ? 's' : '');
    } elseif ($interval->i > 0) {
        return $interval->i . ' minuto' . ($interval->i > 1 ? 's' : '');
    } else {
        return 'hace unos segundos';
    }
}

/**
 * Valida un token CSRF
 */
function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Genera un token CSRF
 */
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(32);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida una contraseña
 */
function validatePassword($password)
{
    $errors = [];

    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        $errors[] = "La contraseña debe tener al menos " . PASSWORD_MIN_LENGTH . " caracteres";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra mayúscula";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra minúscula";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un número";
    }

    return $errors;
}

/**
 * Registra una acción en el historial de movimientos de página
 */
function logAction($idUsuario, $accion)
{
    try {
        $db = \Core\Database::getInstance();
        $ip = getClientIP();
        $fecha = date('Y-m-d H:i:s'); // Usar la zona horaria de PHP (Europe/Madrid)

        $db->insert('movimientos_pagina', [
            'id_usuario' => $idUsuario,
            'accion' => $accion,
            'ip' => $ip,
            'fecha' => $fecha  
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Error en logAction: " . $e->getMessage());
        return false;
    }
}

/**
 * Envía un email usando SMTP
 */
function sendEmail($to, $subject, $body, $isHtml = true)
{
    require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/SMTP.php';
    require_once BASE_PATH . '/vendor/phpmailer/phpmailer/src/Exception.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Configuración del servidor
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        // Remitente y destinatario
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Contenido
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body = $body;

        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Error al enviar email: " . $mail->ErrorInfo);
        }
        return false;
    }
}