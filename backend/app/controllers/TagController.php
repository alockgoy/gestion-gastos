<?php

// ============================================
// TagController.php
// ============================================

namespace Controllers;

use Models\Etiqueta;

class TagController
{
    private $etiquetaModel;

    public function __construct()
    {
        $this->etiquetaModel = new Etiqueta();
    }

    /**
     * Obtener todas las etiquetas
     */
    public function getAll()
    {
        try {
            $etiquetas = $this->etiquetaModel->getAll();

            jsonSuccess("Etiquetas obtenidas", ['etiquetas' => $etiquetas]);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Crear nueva etiqueta (solo propietario)
     */
    public function create($userId)
    {
        try {
            $user = (new \Models\Usuario())->findById($userId);

            if ($user['rol'] !== 'propietario') {
                jsonError("Solo el propietario puede crear etiquetas", 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre'])) {
                jsonError("El nombre de la etiqueta es obligatorio", 400);
            }

            if (strlen($data['nombre']) > 100) {
                jsonError("El nombre no puede tener más de 100 caracteres", 400);
            }

            $etiquetaId = $this->etiquetaModel->create(sanitize($data['nombre']));

            logAction($userId, "ha creado la etiqueta '{$data['nombre']}'");

            jsonSuccess("Etiqueta creada exitosamente", ['etiqueta_id' => $etiquetaId], 201);

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Actualizar etiqueta (solo propietario, solo si no está en uso)
     */
    public function update($userId, $etiquetaId)
    {
        try {
            $user = (new \Models\Usuario())->findById($userId);

            if ($user['rol'] !== 'propietario') {
                jsonError("Solo el propietario puede modificar etiquetas", 403);
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['nombre'])) {
                jsonError("El nombre de la etiqueta es obligatorio", 400);
            }

            if (strlen($data['nombre']) > 100) {
                jsonError("El nombre no puede tener más de 100 caracteres", 400);
            }

            $etiqueta = $this->etiquetaModel->findById($etiquetaId);

            if (!$etiqueta) {
                jsonError("Etiqueta no encontrada", 404);
            }

            $updated = $this->etiquetaModel->update($etiquetaId, sanitize($data['nombre']));

            if ($updated === 0 && $etiqueta['nombre'] !== sanitize($data['nombre'])) {
                jsonError("No se pudo actualizar la etiqueta", 500);
            }

            logAction($userId, "ha modificado la etiqueta '{$etiqueta['nombre']}' a '{$data['nombre']}'");

            jsonSuccess("Etiqueta actualizada exitosamente");

        } catch (\Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * Eliminar etiqueta (solo propietario, solo si no está en uso)
     */
    public function delete($userId, $etiquetaId)
    {
        try {
            error_log("=== DELETE ETIQUETA ===");
            error_log("User ID: " . $userId);
            error_log("Etiqueta ID: " . $etiquetaId);

            $user = (new \Models\Usuario())->findById($userId);
            error_log("User rol: " . $user['rol']);

            if ($user['rol'] !== 'propietario') {
                jsonError("Solo el propietario puede eliminar etiquetas", 403);
            }

            $etiqueta = $this->etiquetaModel->findById($etiquetaId);
            error_log("Etiqueta encontrada: " . json_encode($etiqueta));

            if (!$etiqueta) {
                jsonError("Etiqueta no encontrada", 404);
            }

            $deleted = $this->etiquetaModel->delete($etiquetaId);
            error_log("Deleted result: " . $deleted);

            if ($deleted === 0) {
                error_log("ERROR: No se eliminó ninguna fila");
                jsonError("No se pudo eliminar la etiqueta", 500);
            }

            logAction($userId, "ha eliminado la etiqueta '{$etiqueta['nombre']}'");

            jsonSuccess("Etiqueta eliminada exitosamente");

        } catch (\Exception $e) {
            error_log("EXCEPTION: " . $e->getMessage());
            jsonError($e->getMessage(), 400);
        }
    }
}