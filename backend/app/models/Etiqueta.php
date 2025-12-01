<?php

namespace Models;

use Core\Database;
use Exception;

class Etiqueta {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Crea una nueva etiqueta
     */
    public function create($nombre) {
        if (empty($nombre)) {
            throw new Exception("El nombre de la etiqueta es obligatorio");
        }
        
        if ($this->existsByName($nombre)) {
            throw new Exception("Ya existe una etiqueta con ese nombre");
        }
        
        return $this->db->insert('etiquetas', ['nombre' => $nombre]);
    }
    
    /**
     * Obtiene una etiqueta por ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM etiquetas WHERE id = :id";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Obtiene todas las etiquetas
     */
    public function getAll() {
        $sql = "SELECT e.*, 
                COUNT(c.id) as cuentas_usando 
                FROM etiquetas e 
                LEFT JOIN cuentas c ON e.id = c.id_etiqueta 
                GROUP BY e.id, e.nombre 
                ORDER BY e.nombre ASC";
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Verifica si existe una etiqueta por nombre
     */
    public function existsByName($nombre) {
        $sql = "SELECT COUNT(*) FROM etiquetas WHERE nombre = :nombre";
        return $this->db->fetchColumn($sql, ['nombre' => $nombre]) > 0;
    }
    
    /**
     * Verifica si una etiqueta está en uso
     */
    public function isInUse($id) {
        $sql = "SELECT COUNT(*) FROM cuentas WHERE id_etiqueta = :id";
        return $this->db->fetchColumn($sql, ['id' => $id]) > 0;
    }
    
    /**
     * Actualiza una etiqueta
     */
    public function update($id, $nombre) {
        // Verificar que no esté en uso
        if ($this->isInUse($id)) {
            throw new Exception("No se puede modificar una etiqueta que está en uso");
        }
        
        if (empty($nombre)) {
            throw new Exception("El nombre de la etiqueta es obligatorio");
        }
        
        // Verificar que el nuevo nombre no exista
        $etiqueta = $this->findById($id);
        if ($etiqueta['nombre'] !== $nombre && $this->existsByName($nombre)) {
            throw new Exception("Ya existe una etiqueta con ese nombre");
        }
        
        return $this->db->update('etiquetas', ['nombre' => $nombre], 'id = :id', ['id' => $id]);
    }
    
    /**
     * Elimina una etiqueta
     */
    public function delete($id) {
        // Verificar que no esté en uso
        if ($this->isInUse($id)) {
            throw new Exception("No se puede eliminar una etiqueta que está en uso");
        }
        
        return $this->db->delete('etiquetas', 'id = :id', ['id' => $id]);
    }
    
    /**
     * Obtiene etiquetas no utilizadas
     */
    public function getUnused() {
        $sql = "SELECT e.* 
                FROM etiquetas e 
                LEFT JOIN cuentas c ON e.id = c.id_etiqueta 
                WHERE c.id IS NULL 
                ORDER BY e.nombre ASC";
        return $this->db->fetchAll($sql);
    }
}