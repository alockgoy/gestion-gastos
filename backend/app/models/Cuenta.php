<?php

namespace Models;

use Core\Database;
use Exception;

class Cuenta
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Crea una nueva cuenta
     */
    public function create($data)
    {
        // Validaciones
        if (empty($data['nombre'])) {
            throw new Exception("El nombre de la cuenta es obligatorio");
        }

        if (!isset($data['tipo']) || !in_array($data['tipo'], ['efectivo', 'bancaria'])) {
            throw new Exception("El tipo de cuenta debe ser 'efectivo' o 'bancaria'");
        }

        if (!isset($data['balance'])) {
            $data['balance'] = 0;
        }

        if (!isset($data['moneda'])) {
            $data['moneda'] = 'EUR';
        }

        if (!isset($data['color']) || empty($data['color'])) {
            $data['color'] = '#808080';
        }

        // Insertar cuenta
        $id = $this->db->insert('cuentas', $data);

        // Registrar acción
        logAction($data['id_usuario'], "ha creado la cuenta '{$data['nombre']}'");

        return $id;
    }

    /**
     * Obtiene una cuenta por ID
     */
    public function findById($id)
    {
        $sql = "SELECT c.*, e.nombre as etiqueta_nombre 
                FROM cuentas c 
                LEFT JOIN etiquetas e ON c.id_etiqueta = e.id 
                WHERE c.id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Obtiene todas las cuentas de un usuario
     */
    public function findByUser($idUsuario, $filters = [])
    {
        $sql = "SELECT c.*, e.nombre as etiqueta_nombre 
                FROM cuentas c 
                LEFT JOIN etiquetas e ON c.id_etiqueta = e.id 
                WHERE c.id_usuario = :id_usuario";

        $params = ['id_usuario' => $idUsuario];

        if (isset($filters['tipo'])) {
            $sql .= " AND c.tipo = :tipo";
            $params['tipo'] = $filters['tipo'];
        }

        if (isset($filters['id_etiqueta'])) {
            $sql .= " AND c.id_etiqueta = :id_etiqueta";
            $params['id_etiqueta'] = $filters['id_etiqueta'];
        }

        $sql .= " ORDER BY c.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Actualiza una cuenta
     */
    public function update($id, $data)
    {
        $cuenta = $this->findById($id);

        if (!$cuenta) {
            throw new Exception("Cuenta no encontrada");
        }

        // No permitir cambiar el balance directamente
        unset($data['balance']);
        unset($data['id_usuario']);

        $updated = $this->db->update('cuentas', $data, 'id = :id', ['id' => $id]);

        if ($updated) {
            logAction($cuenta['id_usuario'], "ha modificado la cuenta '{$cuenta['nombre']}'");
        }

        return $updated;
    }

    /**
     * Elimina una cuenta
     */
    public function delete($id)
    {
        $cuenta = $this->findById($id);

        if (!$cuenta) {
            throw new Exception("Cuenta no encontrada");
        }

        // Verificar si tiene movimientos
        $sql = "SELECT COUNT(*) FROM movimientos WHERE id_cuenta = :id";
        $hasMovements = $this->db->fetchColumn($sql, ['id' => $id]) > 0;

        if ($hasMovements) {
            throw new Exception("No se puede eliminar una cuenta con movimientos. Elimina primero los movimientos.");
        }

        $deleted = $this->db->delete('cuentas', 'id = :id', ['id' => $id]);

        if ($deleted) {
            logAction($cuenta['id_usuario'], "ha eliminado la cuenta '{$cuenta['nombre']}'");
        }

        return $deleted;
    }

    /**
     * Verifica si el usuario es dueño de la cuenta
     */
    public function belongsToUser($idCuenta, $idUsuario)
    {
        $sql = "SELECT COUNT(*) FROM cuentas WHERE id = :id AND id_usuario = :id_usuario";
        return $this->db->fetchColumn($sql, ['id' => $idCuenta, 'id_usuario' => $idUsuario]) > 0;
    }

    /**
     * Busca una cuenta por nombre y usuario
     */
    public function findByNameAndUser($nombre, $idUsuario)
    {
        $sql = "SELECT * FROM cuentas WHERE nombre = :nombre AND id_usuario = :id_usuario LIMIT 1";
        return $this->db->fetchOne($sql, ['nombre' => $nombre, 'id_usuario' => $idUsuario]);
    }

    /**
     * Obtiene el balance actual de una cuenta
     */
    public function getBalance($id)
    {
        $sql = "SELECT balance FROM cuentas WHERE id = :id";
        return $this->db->fetchColumn($sql, ['id' => $id]);
    }

    /**
     * Obtiene el progreso de la meta
     */
    public function getGoalProgress($id)
    {
        $cuenta = $this->findById($id);

        if (!$cuenta || !$cuenta['meta']) {
            return null;
        }

        $balance = floatval($cuenta['balance']);
        $meta = floatval($cuenta['meta']);

        if ($balance >= $meta) {
            return [
                'alcanzada' => true,
                'porcentaje' => 100,
                'faltante' => 0,
                'mensaje' => 'Meta alcanzada'
            ];
        }

        $faltante = $meta - $balance;
        $porcentaje = ($balance / $meta) * 100;

        return [
            'alcanzada' => false,
            'porcentaje' => round($porcentaje, 2),
            'faltante' => $faltante,
            'mensaje' => 'Faltan ' . formatMoney($faltante) . ' para alcanzar la meta'
        ];
    }

    /**
     * Obtiene estadísticas de una cuenta
     */
    public function getStats($id)
    {
        $sql = "SELECT 
                    COUNT(*) as total_movimientos,
                    COUNT(CASE WHEN tipo = 'ingreso' THEN 1 END) as total_ingresos,
                    COUNT(CASE WHEN tipo = 'retirada' THEN 1 END) as total_retiradas,
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN cantidad ELSE 0 END), 0) as suma_ingresos,
                    COALESCE(SUM(CASE WHEN tipo = 'retirada' THEN cantidad ELSE 0 END), 0) as suma_retiradas
                FROM movimientos 
                WHERE id_cuenta = :id";

        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    /**
     * Obtiene resumen de cuentas de un usuario
     */
    public function getUserSummary($idUsuario)
    {
        $sql = "SELECT 
                    COUNT(*) as total_cuentas,
                    COUNT(CASE WHEN tipo = 'efectivo' THEN 1 END) as cuentas_efectivo,
                    COUNT(CASE WHEN tipo = 'bancaria' THEN 1 END) as cuentas_bancarias,
                    COALESCE(SUM(balance), 0) as balance_total,
                    COALESCE(AVG(balance), 0) as balance_promedio,
                    MAX(balance) as balance_maximo,
                    MIN(balance) as balance_minimo
                FROM cuentas 
                WHERE id_usuario = :id_usuario";

        return $this->db->fetchOne($sql, ['id_usuario' => $idUsuario]);
    }

    /**
     * Busca cuentas por nombre
     */
    public function search($idUsuario, $query)
    {
        $sql = "SELECT c.*, e.nombre as etiqueta_nombre 
                FROM cuentas c 
                LEFT JOIN etiquetas e ON c.id_etiqueta = e.id 
                WHERE c.id_usuario = :id_usuario 
                AND (c.nombre LIKE :query OR c.descripcion LIKE :query)
                ORDER BY c.nombre ASC";

        return $this->db->fetchAll($sql, [
            'id_usuario' => $idUsuario,
            'query' => '%' . $query . '%'
        ]);
    }
}