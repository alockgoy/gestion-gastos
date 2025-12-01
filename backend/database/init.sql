-- Base de datos: Gestión de Gastos
-- Versión corregida con todas las mejoras de seguridad

CREATE DATABASE IF NOT EXISTS gastos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gastos_db;

-- Tabla de Etiquetas (debe crearse primero por las FK)
CREATE TABLE IF NOT EXISTS etiquetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar etiquetas por defecto
INSERT INTO etiquetas (nombre) VALUES 
    ('Efectivo'),
    ('Paysafecard'),
    ('Paypal'),
    ('Santander'),
    ('BBVA'),
    ('CaixaBank'),
    ('Caja 6000'),
    ('ING'),
    ('Otro');

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    correo_electronico VARCHAR(200) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL, -- Almacenará hash bcrypt
    foto_perfil VARCHAR(255) DEFAULT NULL,
    autenticacion_2fa BOOLEAN DEFAULT FALSE,
    rol ENUM('propietario', 'administrador', 'usuario', 'solicita') DEFAULT 'usuario',
    ultimo_logueo TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre_usuario (nombre_usuario),
    INDEX idx_correo (correo_electronico),
    INDEX idx_rol (rol),
    INDEX idx_ultimo_logueo (ultimo_logueo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de códigos 2FA temporales
CREATE TABLE IF NOT EXISTS codigos_2fa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    codigo VARCHAR(6) NOT NULL,
    expira_en TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario_codigo (id_usuario, codigo),
    INDEX idx_expiracion (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de tokens de recuperación de contraseña
CREATE TABLE IF NOT EXISTS tokens_recuperacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expira_en TIMESTAMP NOT NULL,
    usado BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expiracion (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Cuentas
CREATE TABLE IF NOT EXISTS cuentas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('efectivo', 'bancaria') NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(500) DEFAULT NULL,
    balance DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    moneda VARCHAR(3) DEFAULT 'EUR',
    id_etiqueta INT DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#808080', -- Color en formato hexadecimal
    meta DECIMAL(15, 2) DEFAULT NULL,
    id_usuario INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (id_etiqueta) REFERENCES etiquetas(id) ON DELETE SET NULL,
    INDEX idx_usuario (id_usuario),
    INDEX idx_etiqueta (id_etiqueta),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Movimientos
CREATE TABLE IF NOT EXISTS movimientos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('ingreso', 'retirada') NOT NULL,
    id_cuenta INT NOT NULL,
    cantidad DECIMAL(15, 2) NOT NULL,
    notas VARCHAR(1000) DEFAULT NULL,
    adjunto VARCHAR(255) DEFAULT NULL, -- Ruta del archivo adjunto
    fecha_movimiento TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cuenta) REFERENCES cuentas(id) ON DELETE CASCADE,
    INDEX idx_cuenta (id_cuenta),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha_movimiento),
    INDEX idx_cantidad (cantidad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de Movimientos de página (auditoría)
CREATE TABLE IF NOT EXISTS movimientos_pagina (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    accion TEXT NOT NULL, -- "ha hecho" - descripción de la acción
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip VARCHAR(45) DEFAULT NULL, -- Soporte para IPv4 e IPv6
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (id_usuario),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones activas
CREATE TABLE IF NOT EXISTS sesiones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    expira_en TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (id_usuario),
    INDEX idx_expiracion (expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla para tokens API del bot de Telegram
CREATE TABLE IF NOT EXISTS tokens_api (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    nombre VARCHAR(100) DEFAULT 'Bot Telegram',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_uso TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger para actualizar balance de cuenta al insertar movimiento
DELIMITER //
CREATE TRIGGER actualizar_balance_insertar
AFTER INSERT ON movimientos
FOR EACH ROW
BEGIN
    IF NEW.tipo = 'ingreso' THEN
        UPDATE cuentas 
        SET balance = balance + NEW.cantidad 
        WHERE id = NEW.id_cuenta;
    ELSE
        UPDATE cuentas 
        SET balance = balance - NEW.cantidad 
        WHERE id = NEW.id_cuenta;
    END IF;
END//

-- Trigger para actualizar balance al modificar movimiento
CREATE TRIGGER actualizar_balance_modificar
AFTER UPDATE ON movimientos
FOR EACH ROW
BEGIN
    -- Revertir el movimiento anterior
    IF OLD.tipo = 'ingreso' THEN
        UPDATE cuentas 
        SET balance = balance - OLD.cantidad 
        WHERE id = OLD.id_cuenta;
    ELSE
        UPDATE cuentas 
        SET balance = balance + OLD.cantidad 
        WHERE id = OLD.id_cuenta;
    END IF;
    
    -- Aplicar el nuevo movimiento
    IF NEW.tipo = 'ingreso' THEN
        UPDATE cuentas 
        SET balance = balance + NEW.cantidad 
        WHERE id = NEW.id_cuenta;
    ELSE
        UPDATE cuentas 
        SET balance = balance - NEW.cantidad 
        WHERE id = NEW.id_cuenta;
    END IF;
END//

-- Trigger para actualizar balance al eliminar movimiento
CREATE TRIGGER actualizar_balance_eliminar
AFTER DELETE ON movimientos
FOR EACH ROW
BEGIN
    IF OLD.tipo = 'ingreso' THEN
        UPDATE cuentas 
        SET balance = balance - OLD.cantidad 
        WHERE id = OLD.id_cuenta;
    ELSE
        UPDATE cuentas 
        SET balance = balance + OLD.cantidad 
        WHERE id = OLD.id_cuenta;
    END IF;
END//

DELIMITER ;

-- Vista para obtener el balance total de un usuario
CREATE OR REPLACE VIEW vista_balance_total_usuarios AS
SELECT 
    u.id as id_usuario,
    u.nombre_usuario,
    COALESCE(SUM(c.balance), 0) as balance_total,
    COUNT(c.id) as total_cuentas
FROM usuarios u
LEFT JOIN cuentas c ON u.id = c.id_usuario
GROUP BY u.id, u.nombre_usuario;

-- Vista para verificar si etiquetas están en uso
CREATE OR REPLACE VIEW vista_etiquetas_en_uso AS
SELECT 
    e.id,
    e.nombre,
    COUNT(c.id) as cuentas_usando
FROM etiquetas e
LEFT JOIN cuentas c ON e.id = c.id_etiqueta
GROUP BY e.id, e.nombre;