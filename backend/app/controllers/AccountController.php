<?php

namespace Controllers;

use Models\Cuenta;
use Models\Etiqueta;

class AccountController {
    private $cuentaModel;
    private $etiquetaModel;
    
    public function __construct() {
        $this->cuentaModel = new Cuenta();
        $this->etiquetaModel = new Etiqueta();
    }
    
    /**
     * Crear nueva cuenta
     */
    public function create($userId) {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validaciones
            if (empty($data['nombre'])) {
                jsonError("El nombre de la cuenta es obligatorio", 400);
            }
            
            if (empty($data['tipo']) || !in_array($data['tipo'], ['efectivo', 'bancaria'])) {
                jsonError("El tipo debe ser 'efectivo' o 'bancaria'", 400);
            }
            
            if (strlen($data['nombre']) > 100) {
                jsonError("El nombre no puede tener más de 100 caracteres", 400);
            }
            
            if (isset($data['descripcion']) && strlen($data['descripcion']) > 500) {
                jsonError("La descripción no puede tener más de 500 caracteres", 400);
            }
            
            // Validar etiqueta si se proporciona
            if (isset($data['id_etiqueta']) && !empty($data['id_etiqueta'])) {
                $etiqueta = $this->etiquetaModel->findById($data['id_etiqueta']);
                if (!$etiqueta) {
                    jsonError("La etiqueta seleccionada no existe", 400);
                }
            }
            
            // Preparar datos
            $cuentaData = [
                'tipo' => $data['tipo'],
                'nombre' => sanitize($data['nombre']),
                'descripcion' => isset($data['descripcion']) ? sanitize($data['descripcion']) : null,
                'balance' => $data['balance'] ?? 0,
                'moneda' => 'EUR',
                'id_etiqueta' => $data['id_etiqueta'] ?? null,
                'color' => $data['color'] ?? '#808080',
                'meta' => $data['meta'] ?? null,
                'id_usuario' => $userId
            ];
            
            $cuentaId = $this->cuentaModel->create($cuentaData);
            
            jsonSuccess("Cuenta creada exitosamente", ['cuenta_id' => $cuentaId], 201);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Obtener todas las cuentas del usuario
     */
    public function getAll($userId) {
        try {
            $filters = [];
            
            if (isset($_GET['tipo'])) {
                $filters['tipo'] = $_GET['tipo'];
            }
            
            if (isset($_GET['id_etiqueta'])) {
                $filters['id_etiqueta'] = $_GET['id_etiqueta'];
            }
            
            $cuentas = $this->cuentaModel->findByUser($userId, $filters);
            
            // Añadir progreso de meta a cada cuenta
            foreach ($cuentas as &$cuenta) {
                if ($cuenta['meta']) {
                    $cuenta['progreso_meta'] = $this->cuentaModel->getGoalProgress($cuenta['id']);
                }
            }
            
            jsonSuccess("Cuentas obtenidas", ['cuentas' => $cuentas]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Obtener una cuenta específica
     */
    public function getOne($userId, $cuentaId) {
        try {
            $cuenta = $this->cuentaModel->findById($cuentaId);
            
            if (!$cuenta) {
                jsonError("Cuenta no encontrada", 404);
            }
            
            // Verificar que la cuenta pertenezca al usuario
            if (!$this->cuentaModel->belongsToUser($cuentaId, $userId)) {
                jsonError("No tienes permiso para acceder a esta cuenta", 403);
            }
            
            // Obtener estadísticas
            $cuenta['estadisticas'] = $this->cuentaModel->getStats($cuentaId);
            
            // Obtener progreso de meta
            if ($cuenta['meta']) {
                $cuenta['progreso_meta'] = $this->cuentaModel->getGoalProgress($cuentaId);
            }
            
            jsonSuccess("Cuenta obtenida", ['cuenta' => $cuenta]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Actualizar cuenta
     */
    public function update($userId, $cuentaId) {
        try {
            // Verificar que la cuenta pertenezca al usuario
            if (!$this->cuentaModel->belongsToUser($cuentaId, $userId)) {
                jsonError("No tienes permiso para modificar esta cuenta", 403);
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar campos si se proporcionan
            if (isset($data['nombre']) && strlen($data['nombre']) > 100) {
                jsonError("El nombre no puede tener más de 100 caracteres", 400);
            }
            
            if (isset($data['descripcion']) && strlen($data['descripcion']) > 500) {
                jsonError("La descripción no puede tener más de 500 caracteres", 400);
            }
            
            if (isset($data['tipo']) && !in_array($data['tipo'], ['efectivo', 'bancaria'])) {
                jsonError("El tipo debe ser 'efectivo' o 'bancaria'", 400);
            }
            
            // Validar etiqueta si se proporciona
            if (isset($data['id_etiqueta']) && !empty($data['id_etiqueta'])) {
                $etiqueta = $this->etiquetaModel->findById($data['id_etiqueta']);
                if (!$etiqueta) {
                    jsonError("La etiqueta seleccionada no existe", 400);
                }
            }
            
            // Sanitizar datos textuales
            $updateData = [];
            $allowedFields = ['tipo', 'nombre', 'descripcion', 'id_etiqueta', 'color', 'meta'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    if (in_array($field, ['nombre', 'descripcion'])) {
                        $updateData[$field] = sanitize($data[$field]);
                    } else {
                        $updateData[$field] = $data[$field];
                    }
                }
            }
            
            if (empty($updateData)) {
                jsonError("No hay datos para actualizar", 400);
            }
            
            $this->cuentaModel->update($cuentaId, $updateData);
            
            jsonSuccess("Cuenta actualizada exitosamente");
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Eliminar cuenta
     */
    public function delete($userId, $cuentaId) {
        try {
            // Verificar que la cuenta pertenezca al usuario
            if (!$this->cuentaModel->belongsToUser($cuentaId, $userId)) {
                jsonError("No tienes permiso para eliminar esta cuenta", 403);
            }
            
            $this->cuentaModel->delete($cuentaId);
            
            jsonSuccess("Cuenta eliminada exitosamente");
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Obtener resumen de cuentas
     */
    public function getSummary($userId) {
        try {
            $summary = $this->cuentaModel->getUserSummary($userId);
            
            jsonSuccess("Resumen obtenido", ['summary' => $summary]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
    
    /**
     * Buscar cuentas
     */
    public function search($userId) {
        try {
            $query = $_GET['q'] ?? '';
            
            if (empty($query)) {
                jsonError("Debe proporcionar un término de búsqueda", 400);
            }
            
            $cuentas = $this->cuentaModel->search($userId, $query);
            
            jsonSuccess("Búsqueda completada", ['cuentas' => $cuentas]);
            
        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
}