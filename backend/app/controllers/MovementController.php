<?php

namespace Controllers;

use Models\Movimiento;
use Models\Cuenta;

class MovementController
{
    private $movimientoModel;
    private $cuentaModel;

    public function __construct()
    {
        $this->movimientoModel = new Movimiento();
        $this->cuentaModel = new Cuenta();
    }

    /**
     * Crear nuevo movimiento
     */
    public function create($userId)
    {
        try {
            $data = $_POST;

            // Validaciones
            if (empty($data['tipo']) || !in_array($data['tipo'], ['ingreso', 'retirada'])) {
                jsonError("El tipo debe ser 'ingreso' o 'retirada'", 400);
            }

            if (empty($data['id_cuenta'])) {
                jsonError("Debe especificar una cuenta", 400);
            }

            if (empty($data['cantidad']) || $data['cantidad'] <= 0) {
                jsonError("La cantidad debe ser mayor a 0", 400);
            }

            // Verificar que la cuenta pertenezca al usuario
            if (!$this->cuentaModel->belongsToUser($data['id_cuenta'], $userId)) {
                jsonError("No tienes permiso para agregar movimientos a esta cuenta", 403);
            }

            // Validar notas si se proporcionan
            if (isset($data['notas']) && strlen($data['notas']) > 1000) {
                jsonError("Las notas no pueden superar los 1000 caracteres", 400);
            }

            // Preparar datos
            $movimientoData = [
                'tipo' => $data['tipo'],
                'id_cuenta' => $data['id_cuenta'],
                'cantidad' => floatval($data['cantidad']),
                'notas' => isset($data['notas']) ? sanitize($data['notas']) : null,
                'fecha_movimiento' => $data['fecha_movimiento'] ?? date('Y-m-d H:i:s')
            ];

            // Manejar archivo adjunto
            if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                $movimientoData['archivo'] = $_FILES['adjunto'];
            }

            $movimientoId = $this->movimientoModel->create($movimientoData);

            jsonSuccess("Movimiento registrado exitosamente", ['movimiento_id' => $movimientoId], 201);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Obtener todos los movimientos del usuario
     */
    public function getAll($userId)
    {
        try {
            $filters = [];

            // Filtros disponibles
            if (isset($_GET['id_cuenta'])) {
                $filters['id_cuenta'] = $_GET['id_cuenta'];
            }

            if (isset($_GET['tipo'])) {
                $filters['tipo'] = $_GET['tipo'];
            }

            if (isset($_GET['fecha_desde'])) {
                $filters['fecha_desde'] = $_GET['fecha_desde'];
            }

            if (isset($_GET['fecha_hasta'])) {
                $filters['fecha_hasta'] = $_GET['fecha_hasta'];
            }

            if (isset($_GET['cantidad_min'])) {
                $filters['cantidad_min'] = $_GET['cantidad_min'];
            }

            if (isset($_GET['cantidad_max'])) {
                $filters['cantidad_max'] = $_GET['cantidad_max'];
            }

            // Ordenamiento
            if (isset($_GET['order_by'])) {
                $filters['order_by'] = $_GET['order_by'];
                $filters['order_dir'] = $_GET['order_dir'] ?? 'DESC';
            }

            // Paginación
            if (isset($_GET['limit'])) {
                $filters['limit'] = intval($_GET['limit']);
                $filters['offset'] = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
            }

            $movimientos = $this->movimientoModel->findByUser($userId, $filters);
            $total = $this->movimientoModel->countByUser($userId, $filters);

            jsonSuccess("Movimientos obtenidos", [
                'movimientos' => $movimientos,
                'total' => $total
            ]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Obtener un movimiento específico
     */
    public function getOne($userId, $movimientoId)
    {
        try {
            $movimiento = $this->movimientoModel->findById($movimientoId);

            if (!$movimiento) {
                jsonError("Movimiento no encontrado", 404);
            }

            // Verificar que pertenezca al usuario
            if ($movimiento['id_usuario'] != $userId) {
                jsonError("No tienes permiso para acceder a este movimiento", 403);
            }

            jsonSuccess("Movimiento obtenido", ['movimiento' => $movimiento]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Actualizar movimiento
     */
    public function update($userId, $movimientoId)
    {
        try {
            $movimiento = $this->movimientoModel->findById($movimientoId);

            if (!$movimiento) {
                jsonError("Movimiento no encontrado", 404);
            }

            // Verificar que pertenezca al usuario
            if ($movimiento['id_usuario'] != $userId) {
                jsonError("No tienes permiso para modificar este movimiento", 403);
            }

            // Para peticiones PUT con multipart/form-data, necesitamos una solución especial
            // Primero intentar con $_POST (puede funcionar en algunas configuraciones)
            $data = $_POST;

            // Si $_POST está vacío, intentar parsear el input
            if (empty($data)) {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

                if (strpos($contentType, 'multipart/form-data') !== false) {
                    // Para multipart/form-data en PUT, necesitamos parsear manualmente
                    $data = $this->parseMultipartFormData();
                } else {
                    // JSON o application/x-www-form-urlencoded
                    $rawData = file_get_contents('php://input');
                    if (!empty($rawData)) {
                        // Intentar como JSON primero
                        $jsonData = json_decode($rawData, true);
                        if ($jsonData !== null) {
                            $data = $jsonData;
                        } else {
                            // Si no es JSON, intentar como form-urlencoded
                            parse_str($rawData, $data);
                        }
                    }
                }
            }

            // DEBUG: Registrar qué datos llegaron
            error_log("UPDATE MOVEMENT - Data received: " . print_r($data, true));
            error_log("UPDATE MOVEMENT - FILES: " . print_r($_FILES, true));

            // Validar campos si se proporcionan
            if (isset($data['tipo']) && !in_array($data['tipo'], ['ingreso', 'retirada'])) {
                jsonError("El tipo debe ser 'ingreso' o 'retirada'", 400);
            }

            // Solo validar cantidad si está presente, no vacía y no es null
            if (isset($data['cantidad']) && $data['cantidad'] !== '' && $data['cantidad'] !== null) {
                if (floatval($data['cantidad']) <= 0) {
                    jsonError("La cantidad debe ser mayor a 0", 400);
                }
            }

            if (isset($data['notas']) && strlen($data['notas']) > 1000) {
                jsonError("Las notas no pueden superar los 1000 caracteres", 400);
            }

            // Preparar datos de actualización
            $updateData = [];
            $allowedFields = ['tipo', 'cantidad', 'notas', 'fecha_movimiento', 'id_cuenta'];

            foreach ($allowedFields as $field) {
                // Solo incluir si existe y no está vacío
                if (isset($data[$field]) && $data[$field] !== '' && $data[$field] !== null) {
                    if ($field === 'notas') {
                        $updateData[$field] = sanitize($data[$field]);
                    } elseif ($field === 'cantidad') {
                        $updateData[$field] = floatval($data[$field]);
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }

            // Manejar archivo adjunto
            if (isset($_FILES['adjunto']) && $_FILES['adjunto']['error'] === UPLOAD_ERR_OK) {
                $updateData['archivo'] = $_FILES['adjunto'];
            }

            // DEBUG
            error_log("UPDATE MOVEMENT - Update data: " . print_r($updateData, true));

            if (empty($updateData)) {
                jsonError("No hay datos para actualizar", 400);
            }

            $this->movimientoModel->update($movimientoId, $updateData);

            jsonSuccess("Movimiento actualizado exitosamente");

        } catch (\Exception $e) {
            error_log("UPDATE MOVEMENT - Error: " . $e->getMessage());
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

        // Obtener el boundary del Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        preg_match('/boundary=(.*)$/', $contentType, $matches);

        if (!isset($matches[1])) {
            return $data;
        }

        $boundary = $matches[1];

        // Dividir por boundary (usar explode en lugar de preg_split para manejar datos binarios)
        $parts = explode('--' . $boundary, $input);

        // Remover primer y último elemento
        array_shift($parts);
        array_pop($parts);

        foreach ($parts as $part) {
            // Saltar partes vacías
            if (trim($part) === '--' || empty(trim($part, "\r\n-"))) {
                continue;
            }

            // Separar headers del contenido
            $parts_split = preg_split("/\r?\n\r?\n/", $part, 2);

            if (count($parts_split) < 2) {
                continue;
            }

            list($headers_block, $content) = $parts_split;

            // Extraer nombre del campo
            if (preg_match('/name="([^"]*)"/', $headers_block, $nameMatches)) {
                $name = $nameMatches[1];

                // Verificar si es un archivo
                if (preg_match('/filename="([^"]*)"/', $headers_block, $fileMatches)) {
                    $filename = $fileMatches[1];

                    if (!empty($filename)) {
                        // Es un archivo - extraer tipo MIME
                        $mimeType = 'application/octet-stream';
                        if (preg_match('/Content-Type:\s*(.*)$/mi', $headers_block, $typeMatches)) {
                            $mimeType = trim($typeMatches[1]);
                        }

                        // Limpiar el contenido (quitar \r\n del final)
                        $content = substr($content, 0, -2); // Remover \r\n final

                        // Crear archivo temporal
                        $tmpPath = tempnam(sys_get_temp_dir(), 'php_upload_');

                        // Escribir contenido binario al archivo
                        $written = file_put_contents($tmpPath, $content, LOCK_EX);

                        if ($written !== false && file_exists($tmpPath)) {
                            // Simular estructura de $_FILES
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
                    // Es un campo normal
                    $data[$name] = rtrim($content, "\r\n");
                }
            }
        }

        return $data;
    }

    /**
     * Eliminar movimiento
     */
    public function delete($userId, $movimientoId)
    {
        try {
            $movimiento = $this->movimientoModel->findById($movimientoId);

            if (!$movimiento) {
                jsonError("Movimiento no encontrado", 404);
            }

            // Verificar que pertenezca al usuario
            if ($movimiento['id_usuario'] != $userId) {
                jsonError("No tienes permiso para eliminar este movimiento", 403);
            }

            $this->movimientoModel->delete($movimientoId);

            jsonSuccess("Movimiento eliminado exitosamente");

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function getStats($userId)
    {
        try {
            $filters = [];

            if (isset($_GET['fecha_desde'])) {
                $filters['fecha_desde'] = $_GET['fecha_desde'];
            }

            if (isset($_GET['fecha_hasta'])) {
                $filters['fecha_hasta'] = $_GET['fecha_hasta'];
            }

            $stats = $this->movimientoModel->getUserStats($userId, $filters);

            jsonSuccess("Estadísticas obtenidas", ['stats' => $stats]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Exportar movimientos a CSV
     */
    public function exportCSV($userId)
    {
        try {
            $filters = [];

            if (isset($_GET['id_cuenta'])) {
                $filters['id_cuenta'] = $_GET['id_cuenta'];
            }

            if (isset($_GET['tipo'])) {
                $filters['tipo'] = $_GET['tipo'];
            }

            if (isset($_GET['fecha_desde'])) {
                $filters['fecha_desde'] = $_GET['fecha_desde'];
            }

            if (isset($_GET['fecha_hasta'])) {
                $filters['fecha_hasta'] = $_GET['fecha_hasta'];
            }

            $csv = $this->movimientoModel->exportToCSV($userId, $filters);

            // Configurar headers para descarga
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="movimientos_' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo "\xEF\xBB\xBF"; // BOM para UTF-8
            echo $csv;
            exit;

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Exportar movimientos a JSON
     */
    public function exportJSON($userId)
    {
        try {
            $filters = [];

            if (isset($_GET['id_cuenta'])) {
                $filters['id_cuenta'] = $_GET['id_cuenta'];
            }

            if (isset($_GET['tipo'])) {
                $filters['tipo'] = $_GET['tipo'];
            }

            if (isset($_GET['fecha_desde'])) {
                $filters['fecha_desde'] = $_GET['fecha_desde'];
            }

            if (isset($_GET['fecha_hasta'])) {
                $filters['fecha_hasta'] = $_GET['fecha_hasta'];
            }

            $json = $this->movimientoModel->exportToJSON($userId, $filters);

            // Configurar headers para descarga
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="movimientos_' . date('Y-m-d') . '.json"');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $json;
            exit;

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Importar movimientos desde JSON
     */
    public function importJSON($userId)
    {
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                jsonError("Error al subir el archivo", 400);
            }

            $file = $_FILES['file'];

            // Validar que sea JSON (por tipo MIME o extensión)
            $allowedMimeTypes = ['application/json', 'text/plain', ''];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file['type'], $allowedMimeTypes) && $fileExtension !== 'json') {
                jsonError("El archivo debe ser JSON", 400);
            }

            $jsonContent = file_get_contents($file['tmp_name']);

            $result = $this->movimientoModel->importFromJSON($userId, $jsonContent);

            if ($result['imported'] === 0 && !empty($result['errors'])) {
                jsonError("No se pudo importar ningún movimiento", 400, $result['errors']);
            }

            logAction($userId, "ha importado {$result['imported']} movimientos");

            jsonSuccess("Importación completada", [
                'imported' => $result['imported'],
                'errors' => $result['errors']
            ]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Importar movimientos desde CSV
     */
    public function importCSV($userId)
    {
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                jsonError("Error al subir el archivo", 400);
            }

            $file = $_FILES['file'];

            // Validar que sea CSV (por tipo MIME o extensión)
            $allowedMimeTypes = ['text/csv', 'text/plain', 'application/csv', ''];
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file['type'], $allowedMimeTypes) && $fileExtension !== 'csv') {
                jsonError("El archivo debe ser CSV", 400);
            }

            $csvContent = file_get_contents($file['tmp_name']);

            $result = $this->movimientoModel->importFromCSV($userId, $csvContent);

            if ($result['imported'] === 0 && !empty($result['errors'])) {
                jsonError("No se pudo importar ningún movimiento", 400, $result['errors']);
            }

            logAction($userId, "ha importado {$result['imported']} movimientos desde CSV");

            jsonSuccess("Importación completada", [
                'imported' => $result['imported'],
                'errors' => $result['errors']
            ]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
}