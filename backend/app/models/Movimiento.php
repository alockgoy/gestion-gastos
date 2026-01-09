<?php

namespace Models;

use Core\Database;
use Exception;

class Movimiento
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crea un nuevo movimiento
     */
    public function create($data)
    {
        // Validaciones
        if (!isset($data['tipo']) || !in_array($data['tipo'], ['ingreso', 'retirada'])) {
            throw new Exception("El tipo debe ser 'ingreso' o 'retirada'");
        }

        if (!isset($data['id_cuenta']) || empty($data['id_cuenta'])) {
            throw new Exception("Debe especificar una cuenta");
        }

        if (!isset($data['cantidad']) || $data['cantidad'] <= 0) {
            throw new Exception("La cantidad debe ser mayor a 0");
        }

        // Verificar saldo suficiente para retiradas
        if ($data['tipo'] === 'retirada') {
            $cuenta = (new Cuenta())->findById($data['id_cuenta']);
            if (!$cuenta) {
                throw new Exception("La cuenta especificada no existe");
            }

            if ($cuenta['balance'] < $data['cantidad']) {
                throw new Exception("Saldo insuficiente. Disponible: " . formatMoney($cuenta['balance']) . " " . $cuenta['moneda']);
            }
        }

        // Si no se especifica fecha, usar la actual
        if (!isset($data['fecha_movimiento'])) {
            $data['fecha_movimiento'] = date('Y-m-d H:i:s');
        }

        // Manejar archivo adjunto si existe
        if (isset($data['archivo']) && !empty($data['archivo'])) {
            $data['adjunto'] = $this->uploadFile($data['archivo']);
            unset($data['archivo']);
        }

        // Insertar movimiento (el trigger actualizará el balance automáticamente)
        $id = $this->db->insert('movimientos', $data);

        // Obtener información de la cuenta para el log
        $cuenta = (new Cuenta())->findById($data['id_cuenta']);
        $tipoAccion = $data['tipo'] === 'ingreso' ? 'ingreso' : 'retirada';

        logAction(
            $cuenta['id_usuario'],
            "ha registrado un {$tipoAccion} de " . formatMoney($data['cantidad']) .
            " en la cuenta '{$cuenta['nombre']}'"
        );

        return $id;
    }

    /**
     * Obtiene un movimiento por ID
     */
    public function findById($id)
    {
        $sql = "SELECT m.*, c.nombre as cuenta_nombre, c.id_usuario 
                FROM movimientos m 
                INNER JOIN cuentas c ON m.id_cuenta = c.id 
                WHERE m.id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Obtiene movimientos de una cuenta
     */
    public function findByAccount($idCuenta, $filters = [])
    {
        $sql = "SELECT m.* FROM movimientos m WHERE m.id_cuenta = :id_cuenta";
        $params = ['id_cuenta' => $idCuenta];

        if (isset($filters['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        if (isset($filters['fecha_desde'])) {
            $sql .= " AND m.fecha_movimiento >= :fecha_desde";
            $params['fecha_desde'] = $filters['fecha_desde'];
        }

        if (isset($filters['fecha_hasta'])) {
            $sql .= " AND m.fecha_movimiento <= :fecha_hasta";
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }

        // Ordenamiento
        $orderBy = $filters['order_by'] ?? 'fecha_movimiento';
        $orderDir = $filters['order_dir'] ?? 'DESC';
        $sql .= " ORDER BY {$orderBy} {$orderDir}";

        // Paginación
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int) $filters['limit'];

            if (isset($filters['offset'])) {
                $sql .= " OFFSET :offset";
                $params['offset'] = (int) $filters['offset'];
            }
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtiene todos los movimientos de un usuario
     */
    public function findByUser($idUsuario, $filters = [])
    {
        $sql = "SELECT m.*, c.nombre as cuenta_nombre, c.color as cuenta_color 
            FROM movimientos m 
            INNER JOIN cuentas c ON m.id_cuenta = c.id 
            WHERE c.id_usuario = :id_usuario";

        $params = ['id_usuario' => $idUsuario];

        // Filtros
        if (isset($filters['id_cuenta'])) {
            $sql .= " AND m.id_cuenta = :id_cuenta";
            $params['id_cuenta'] = $filters['id_cuenta'];
        }

        if (isset($filters['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        if (isset($filters['fecha_desde'])) {
            $sql .= " AND m.fecha_movimiento >= :fecha_desde";
            $params['fecha_desde'] = $filters['fecha_desde'];
        }

        if (isset($filters['fecha_hasta'])) {
            $sql .= " AND m.fecha_movimiento <= :fecha_hasta";
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (isset($filters['cantidad_min'])) {
            $sql .= " AND m.cantidad >= :cantidad_min";
            $params['cantidad_min'] = $filters['cantidad_min'];
        }

        if (isset($filters['cantidad_max'])) {
            $sql .= " AND m.cantidad <= :cantidad_max";
            $params['cantidad_max'] = $filters['cantidad_max'];
        }

        // Ordenamiento
        if (isset($filters['order_by']) && !empty($filters['order_by'])) {
            $orderBy = $filters['order_by'];
            $orderDir = isset($filters['order_dir']) && strtoupper($filters['order_dir']) === 'ASC' ? 'ASC' : 'DESC';

            // Validar campos permitidos para evitar SQL injection
            $allowedFields = ['fecha_movimiento', 'cantidad', 'id'];
            if (in_array($orderBy, $allowedFields)) {
                $sql .= " ORDER BY m.{$orderBy} {$orderDir}";
            } else {
                // Si el campo no es válido, usar orden por defecto
                $sql .= " ORDER BY m.id DESC";
            }
        } else {
            // Orden por defecto: más reciente primero (por ID)
            $sql .= " ORDER BY m.id DESC";
        }

        // Paginación
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int) $filters['limit'];

            if (isset($filters['offset'])) {
                $sql .= " OFFSET :offset";
                $params['offset'] = (int) $filters['offset'];
            }
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Cuenta total de movimientos de un usuario
     */
    public function countByUser($idUsuario, $filters = [])
    {
        $sql = "SELECT COUNT(*) 
                FROM movimientos m 
                INNER JOIN cuentas c ON m.id_cuenta = c.id 
                WHERE c.id_usuario = :id_usuario";

        $params = ['id_usuario' => $idUsuario];

        if (isset($filters['id_cuenta'])) {
            $sql .= " AND m.id_cuenta = :id_cuenta";
            $params['id_cuenta'] = $filters['id_cuenta'];
        }

        if (isset($filters['tipo'])) {
            $sql .= " AND m.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        return $this->db->fetchColumn($sql, $params);
    }

    /**
     * Actualiza un movimiento
     */
    public function update($id, $data)
    {
        $movimiento = $this->findById($id);

        if (!$movimiento) {
            throw new Exception("Movimiento no encontrado");
        }

        // No permitir cambiar la cuenta
        unset($data['id_cuenta']);

        // Manejar archivo adjunto si existe
        if (isset($data['archivo']) && !empty($data['archivo'])) {
            // Eliminar archivo anterior si existe
            if ($movimiento['adjunto']) {
                $this->deleteFile($movimiento['adjunto']);
            }
            $data['adjunto'] = $this->uploadFile($data['archivo']);
            unset($data['archivo']);
        }

        $updated = $this->db->update('movimientos', $data, 'id = :id', ['id' => $id]);

        if ($updated) {
            $cuenta = (new Cuenta())->findById($movimiento['id_cuenta']);
            logAction(
                $cuenta['id_usuario'],
                "ha modificado un movimiento en la cuenta '{$cuenta['nombre']}'"
            );
        }

        return $updated;
    }

    /**
     * Elimina un movimiento
     */
    public function delete($id)
    {
        $movimiento = $this->findById($id);

        if (!$movimiento) {
            throw new Exception("Movimiento no encontrado");
        }

        // Eliminar archivo adjunto si existe
        if ($movimiento['adjunto']) {
            $this->deleteFile($movimiento['adjunto']);
        }

        $deleted = $this->db->delete('movimientos', 'id = :id', ['id' => $id]);

        if ($deleted) {
            $cuenta = (new Cuenta())->findById($movimiento['id_cuenta']);
            logAction(
                $cuenta['id_usuario'],
                "ha eliminado un movimiento de la cuenta '{$cuenta['nombre']}'"
            );
        }

        return $deleted;
    }

    /**
     * Sube un archivo adjunto
     */
    private function uploadFile($file)
    {
        // Validar que el archivo temporal existe
        if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            throw new Exception("Error al subir el archivo");
        }

        // Verificar si es un archivo subido por POST o creado manualmente (PUT)
        $isUploadedFile = is_uploaded_file($file['tmp_name']);

        // Si no es un archivo subido por HTTP, verificar que sea un archivo temporal válido
        if (!$isUploadedFile) {
            // Verificar que el archivo esté en el directorio temporal del sistema
            $tmpDir = sys_get_temp_dir();
            $realTmpPath = realpath($file['tmp_name']);
            $realTmpDir = realpath($tmpDir);

            if (!$realTmpPath || strpos($realTmpPath, $realTmpDir) !== 0) {
                throw new Exception("Error al subir el archivo");
            }
        }

        // Validar tamaño
        if (!isValidFileSize($file['size'])) {
            throw new Exception("El archivo no puede superar los 5 MB");
        }

        // Validar tipo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!isValidFileType($mimeType, $extension)) {
            throw new Exception("Solo se permiten archivos PDF e imágenes (JPG, PNG, GIF, WEBP)");
        }

        // Generar nombre único
        $filename = generateUniqueFilename($file['name']);
        $destination = UPLOADS_PATH . '/movements/' . $filename;

        // Crear directorio si no existe
        if (!file_exists(UPLOADS_PATH . '/movements')) {
            mkdir(UPLOADS_PATH . '/movements', 0755, true);
        }

        // Mover/copiar archivo según su origen
        if ($isUploadedFile) {
            // Archivo subido por POST - usar move_uploaded_file
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new Exception("Error al guardar el archivo");
            }
        } else {
            // Archivo temporal de PUT - usar rename o copy
            if (!rename($file['tmp_name'], $destination)) {
                // Si rename falla (diferente filesystem), intentar copy + unlink
                if (!copy($file['tmp_name'], $destination)) {
                    throw new Exception("Error al guardar el archivo");
                }
                @unlink($file['tmp_name']);
            }
        }

        return $filename;
    }

    /**
     * Elimina un archivo adjunto
     */
    private function deleteFile($filename)
    {
        $filepath = UPLOADS_PATH . '/movements/' . $filename;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /**
     * Exporta movimientos a CSV
     */
    public function exportToCSV($idUsuario, $filters = [])
    {
        $movimientos = $this->findByUser($idUsuario, $filters);

        $csv = "Fecha,Tipo,Cuenta,Cantidad,Notas\n";

        foreach ($movimientos as $mov) {
            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $mov['fecha_movimiento'],
                $mov['tipo'],
                $mov['cuenta_nombre'],
                $mov['cantidad'],
                str_replace('"', '""', $mov['notas'] ?? '')
            );
        }

        return $csv;
    }

    /**
     * Exporta movimientos a JSON con adjuntos en Base64 y metadata de cuentas
     * Formato completo para backup/restore entre usuarios
     * INCLUYE balance actual para preservar balances iniciales
     */
    public function exportToJSON($idUsuario, $filters = [])
    {
        $movimientos = $this->findByUser($idUsuario, $filters);
        $cuentaModel = new Cuenta();

        // Obtener todas las cuentas del usuario
        $todasCuentas = $cuentaModel->findByUser($idUsuario);

        // Preparar estructura del backup
        $backup = [
            'version' => '2.0',
            'fecha_exportacion' => date('Y-m-d H:i:s'),
            'usuario_id_original' => $idUsuario, // Para verificar duplicados
            'cuentas' => [],
            'movimientos' => []
        ];

        // Mapear cuentas con balance actual
        $cuentasMap = [];
        foreach ($todasCuentas as $cuenta) {
            $cuentasMap[$cuenta['id']] = [
                'nombre' => $cuenta['nombre'],
                'tipo' => $cuenta['tipo'],
                'moneda' => $cuenta['moneda'],
                'color' => $cuenta['color'],
                'descripcion' => $cuenta['descripcion'] ?? null,
                'meta' => $cuenta['meta'] ?? null,
                'balance_actual' => $cuenta['balance'] // NUEVO: Preservar balance
            ];

            $backup['cuentas'][] = $cuentasMap[$cuenta['id']];
        }

        // Mapear movimientos con nombre de cuenta en lugar de ID
        foreach ($movimientos as $mov) {
            $movimientoData = [
                'cuenta_nombre' => $mov['cuenta_nombre'],
                'tipo' => $mov['tipo'],
                'cantidad' => $mov['cantidad'],
                'fecha_movimiento' => $mov['fecha_movimiento'],
                'notas' => $mov['notas'] ?? null,
            ];

            // Procesar adjunto si existe
            if (!empty($mov['adjunto'])) {
                $filepath = UPLOADS_PATH . '/movements/' . $mov['adjunto'];
                if (file_exists($filepath)) {
                    $fileContent = file_get_contents($filepath);
                    $base64 = base64_encode($fileContent);

                    // Detectar MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $filepath);
                    finfo_close($finfo);

                    $movimientoData['adjunto_base64'] = "data:{$mimeType};base64,{$base64}";
                    $movimientoData['adjunto_nombre'] = basename($mov['adjunto']);
                }
            }

            $backup['movimientos'][] = $movimientoData;
        }

        return json_encode($backup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Importa movimientos desde JSON con creación automática de cuentas
     * Detecta y previene duplicados si se importa en el mismo usuario
     * RESTAURA balance inicial si no hay movimientos que lo justifiquen
     */
    public function importFromJSON($idUsuario, $jsonData)
    {
        $data = json_decode($jsonData, true);

        if (!is_array($data)) {
            throw new Exception("Formato JSON inválido");
        }

        // Verificar si es un backup antiguo (sin estructura de cuentas)
        $esFormatoNuevo = isset($data['version']) && isset($data['cuentas']) && isset($data['movimientos']);

        if (!$esFormatoNuevo) {
            // Compatibilidad con formato antiguo
            return $this->importFromJSONLegacy($idUsuario, $data);
        }

        // VALIDACIÓN: Detectar si se está intentando importar en el mismo usuario
        if (isset($data['usuario_id_original']) && $data['usuario_id_original'] == $idUsuario) {
            throw new Exception("No puedes importar un backup en la misma cuenta donde fue creado. Esto causaría duplicados.");
        }

        $cuentaModel = new Cuenta();
        $mapeoNombres = []; // nombre_cuenta => id_cuenta_nueva
        $cuentasCreadas = [];
        $cuentasExistentes = [];
        $balancesOriginales = []; // nombre_cuenta => balance_original
        $imported = 0;
        $errors = [];

        // PASO 1: Procesar cuentas - crear o mapear a existentes
        foreach ($data['cuentas'] as $cuentaData) {
            $nombreCuenta = $cuentaData['nombre'];
            $balanceOriginal = floatval($cuentaData['balance_actual'] ?? 0);
            $balancesOriginales[$nombreCuenta] = $balanceOriginal;

            // Buscar si ya existe una cuenta con ese nombre
            $cuentaExistente = $cuentaModel->findByNameAndUser($nombreCuenta, $idUsuario);

            if ($cuentaExistente) {
                // Mapear a cuenta existente
                $mapeoNombres[$nombreCuenta] = $cuentaExistente['id'];
                $cuentasExistentes[] = $nombreCuenta;
            } else {
                // Crear nueva cuenta (con balance 0, se actualizará con movimientos)
                try {
                    $nuevaId = $cuentaModel->create([
                        'id_usuario' => $idUsuario,
                        'nombre' => $cuentaData['nombre'],
                        'tipo' => $cuentaData['tipo'],
                        'moneda' => $cuentaData['moneda'] ?? 'EUR',
                        'color' => $cuentaData['color'] ?? '#3B82F6',
                        'descripcion' => $cuentaData['descripcion'] ?? null,
                        'meta' => $cuentaData['meta'] ?? null,
                        'balance' => 0 // Iniciar en 0, se ajustará después
                    ]);

                    $mapeoNombres[$nombreCuenta] = $nuevaId;
                    $cuentasCreadas[] = $nombreCuenta;

                } catch (Exception $e) {
                    $errors[] = "Error al crear cuenta '{$nombreCuenta}': " . $e->getMessage();
                }
            }
        }

        // PASO 2: Importar movimientos usando el mapeo de nombres
        foreach ($data['movimientos'] as $index => $mov) {
            try {
                $nombreCuenta = $mov['cuenta_nombre'];

                // Verificar que la cuenta exista en el mapeo
                if (!isset($mapeoNombres[$nombreCuenta])) {
                    throw new Exception("La cuenta '{$nombreCuenta}' no pudo ser creada o mapeada");
                }

                $dataToInsert = [
                    'id_cuenta' => $mapeoNombres[$nombreCuenta],
                    'tipo' => $mov['tipo'],
                    'cantidad' => $mov['cantidad'],
                    'notas' => $mov['notas'] ?? null,
                    'fecha_movimiento' => $mov['fecha_movimiento'] ?? date('Y-m-d H:i:s')
                ];

                // Procesar adjunto si existe en Base64
                if (!empty($mov['adjunto_base64'])) {
                    try {
                        // Extraer MIME type y datos
                        if (preg_match('/^data:([^;]+);base64,(.+)$/', $mov['adjunto_base64'], $matches)) {
                            $mimeType = $matches[1];
                            $base64Data = $matches[2];
                            $fileContent = base64_decode($base64Data);

                            if ($fileContent === false) {
                                throw new Exception("Error al decodificar adjunto");
                            }

                            // Determinar extensión
                            $extension = '';
                            switch ($mimeType) {
                                case 'application/pdf':
                                    $extension = 'pdf';
                                    break;
                                case 'image/jpeg':
                                    $extension = 'jpg';
                                    break;
                                case 'image/png':
                                    $extension = 'png';
                                    break;
                                case 'image/gif':
                                    $extension = 'gif';
                                    break;
                                case 'image/webp':
                                    $extension = 'webp';
                                    break;
                                default:
                                    throw new Exception("Tipo de archivo no soportado: {$mimeType}");
                            }

                            // Generar nombre único
                            $originalName = $mov['adjunto_nombre'] ?? "archivo.{$extension}";
                            $filename = uniqid() . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
                            $filepath = UPLOADS_PATH . '/movements/' . $filename;

                            // Guardar archivo
                            if (file_put_contents($filepath, $fileContent) === false) {
                                throw new Exception("Error al guardar adjunto");
                            }

                            $dataToInsert['adjunto'] = $filename;
                        }
                    } catch (Exception $e) {
                        // Si falla el adjunto, importar sin él pero registrar error
                        $errors[] = "Movimiento #" . ($index + 1) . " - Advertencia: " . $e->getMessage() . " (movimiento importado sin adjunto)";
                    }
                }

                $this->create($dataToInsert);
                $imported++;

            } catch (Exception $e) {
                $errors[] = "Movimiento #" . ($index + 1) . ": " . $e->getMessage();
            }
        }

        // PASO 3: AJUSTAR BALANCES INICIALES
        // Para cada cuenta creada, verificar si el balance calculado coincide con el original
        foreach ($cuentasCreadas as $nombreCuenta) {
            $idCuenta = $mapeoNombres[$nombreCuenta];
            $balanceOriginal = $balancesOriginales[$nombreCuenta];

            // Obtener balance actual calculado por los triggers
            $balanceActual = floatval($cuentaModel->getBalance($idCuenta));

            // Si hay diferencia, crear movimiento de "Balance Inicial"
            $diferencia = $balanceOriginal - $balanceActual;

            if (abs($diferencia) > 0.01) { // Tolerancia de 1 céntimo
                try {
                    $tipo = $diferencia > 0 ? 'ingreso' : 'retirada';
                    $cantidad = abs($diferencia);

                    $this->create([
                        'tipo' => $tipo,
                        'id_cuenta' => $idCuenta,
                        'cantidad' => $cantidad,
                        'notas' => '💼 Balance inicial (restaurado desde backup)',
                        'fecha_movimiento' => $data['fecha_exportacion'] ?? date('Y-m-d H:i:s')
                    ]);

                    $imported++; // Contar el movimiento de balance inicial

                } catch (Exception $e) {
                    $errors[] = "Error al ajustar balance inicial de '{$nombreCuenta}': " . $e->getMessage();
                }
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors,
            'cuentas_creadas' => $cuentasCreadas,
            'cuentas_existentes' => $cuentasExistentes
        ];
    }

    /**
     * Importación legacy (formato antiguo sin estructura de cuentas)
     * Mantenido para retrocompatibilidad
     */
    private function importFromJSONLegacy($idUsuario, $data)
    {
        $cuentaModel = new Cuenta();
        $imported = 0;
        $errors = [];

        foreach ($data as $index => $mov) {
            try {
                // Validar que la cuenta pertenezca al usuario
                if (!isset($mov['id_cuenta']) || !$cuentaModel->belongsToUser($mov['id_cuenta'], $idUsuario)) {
                    throw new Exception("La cuenta no pertenece al usuario");
                }

                $dataToInsert = [
                    'tipo' => $mov['tipo'],
                    'id_cuenta' => $mov['id_cuenta'],
                    'cantidad' => $mov['cantidad'],
                    'notas' => $mov['notas'] ?? null,
                    'fecha_movimiento' => $mov['fecha_movimiento'] ?? date('Y-m-d H:i:s')
                ];

                // Procesar adjunto si existe
                if (!empty($mov['adjunto_base64'])) {
                    try {
                        if (preg_match('/^data:([^;]+);base64,(.+)$/', $mov['adjunto_base64'], $matches)) {
                            $mimeType = $matches[1];
                            $base64Data = $matches[2];
                            $fileContent = base64_decode($base64Data);

                            if ($fileContent === false) {
                                throw new Exception("Error al decodificar adjunto");
                            }

                            $extension = '';
                            switch ($mimeType) {
                                case 'application/pdf':
                                    $extension = 'pdf';
                                    break;
                                case 'image/jpeg':
                                    $extension = 'jpg';
                                    break;
                                case 'image/png':
                                    $extension = 'png';
                                    break;
                                case 'image/gif':
                                    $extension = 'gif';
                                    break;
                                case 'image/webp':
                                    $extension = 'webp';
                                    break;
                                default:
                                    throw new Exception("Tipo de archivo no soportado");
                            }

                            $originalName = $mov['adjunto_nombre'] ?? "archivo.{$extension}";
                            $filename = uniqid() . '_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
                            $filepath = UPLOADS_PATH . '/movements/' . $filename;

                            if (file_put_contents($filepath, $fileContent) === false) {
                                throw new Exception("Error al guardar adjunto");
                            }

                            $dataToInsert['adjunto'] = $filename;
                        }
                    } catch (Exception $e) {
                        $errors[] = "Línea " . ($index + 1) . " - Advertencia: " . $e->getMessage();
                    }
                }

                $this->create($dataToInsert);
                $imported++;

            } catch (Exception $e) {
                $errors[] = "Línea " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Importa movimientos desde CSV
     */
    public function importFromCSV($idUsuario, $csvContent)
    {
        $lines = explode("\n", trim($csvContent));

        // Saltar la primera línea (cabecera)
        array_shift($lines);

        if (empty($lines)) {
            throw new Exception("El archivo CSV está vacío");
        }

        $imported = 0;
        $errors = [];
        $cuentaModel = new Cuenta();

        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            try {
                // Parsear CSV manualmente para manejar comillas
                $data = str_getcsv($line);

                if (count($data) < 4) {
                    throw new Exception("Formato inválido");
                }

                list($fecha, $tipo, $cuentaNombre, $cantidad, $notas) = array_pad($data, 5, '');

                // Validar tipo
                if (!in_array($tipo, ['ingreso', 'retirada'])) {
                    throw new Exception("Tipo debe ser 'ingreso' o 'retirada'");
                }

                // Buscar cuenta por nombre
                $cuentas = $cuentaModel->findByUser($idUsuario);
                $cuenta = null;
                foreach ($cuentas as $c) {
                    if ($c['nombre'] === $cuentaNombre) {
                        $cuenta = $c;
                        break;
                    }
                }

                if (!$cuenta) {
                    throw new Exception("Cuenta '$cuentaNombre' no encontrada");
                }

                // Validar cantidad
                if (!is_numeric($cantidad) || $cantidad <= 0) {
                    throw new Exception("Cantidad inválida");
                }

                $this->create([
                    'tipo' => $tipo,
                    'id_cuenta' => $cuenta['id'],
                    'cantidad' => $cantidad,
                    'notas' => $notas ?: null,
                    'fecha_movimiento' => $fecha
                ]);

                $imported++;
            } catch (Exception $e) {
                $errors[] = "Línea " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        return [
            'imported' => $imported,
            'errors' => $errors
        ];
    }

    /**
     * Obtiene estadísticas de movimientos de un usuario
     */
    public function getUserStats($idUsuario, $filters = [])
    {
        $sql = "SELECT 
                    COUNT(*) as total_movimientos,
                    COUNT(CASE WHEN m.tipo = 'ingreso' THEN 1 END) as total_ingresos,
                    COUNT(CASE WHEN m.tipo = 'retirada' THEN 1 END) as total_retiradas,
                    COALESCE(SUM(CASE WHEN m.tipo = 'ingreso' THEN m.cantidad ELSE 0 END), 0) as suma_ingresos,
                    COALESCE(SUM(CASE WHEN m.tipo = 'retirada' THEN m.cantidad ELSE 0 END), 0) as suma_retiradas,
                    COALESCE(AVG(CASE WHEN m.tipo = 'ingreso' THEN m.cantidad END), 0) as promedio_ingresos,
                    COALESCE(AVG(CASE WHEN m.tipo = 'retirada' THEN m.cantidad END), 0) as promedio_retiradas,
                    MAX(m.cantidad) as movimiento_mayor,
                    MIN(m.cantidad) as movimiento_menor
                FROM movimientos m 
                INNER JOIN cuentas c ON m.id_cuenta = c.id 
                WHERE c.id_usuario = :id_usuario";

        $params = ['id_usuario' => $idUsuario];

        if (isset($filters['fecha_desde'])) {
            $sql .= " AND m.fecha_movimiento >= :fecha_desde";
            $params['fecha_desde'] = $filters['fecha_desde'];
        }

        if (isset($filters['fecha_hasta'])) {
            $sql .= " AND m.fecha_movimiento <= :fecha_hasta";
            $params['fecha_hasta'] = $filters['fecha_hasta'];
        }

        return $this->db->fetchOne($sql, $params);
    }
}