-- ============================================================
-- BASE DE DATOS: Hotel Bellavista
-- Sistema de Gestión Hotelera
-- Versión mejorada desde hotelms.sql
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `hotelbellavista` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotelbellavista`;

-- ============================================================
-- TABLA: id_card_type (tipos de documento de identidad)
-- ============================================================
CREATE TABLE `id_card_type` (
  `id_card_type_id` INT(10) NOT NULL AUTO_INCREMENT,
  `id_card_type`    VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_card_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `id_card_type` VALUES
(1, 'DNI'),
(2, 'Pasaporte'),
(3, 'NIE'),
(4, 'Permiso de Conducir');

-- ============================================================
-- TABLA: room_type (tipos de habitación)
-- ============================================================
CREATE TABLE `room_type` (
  `room_type_id`   INT(10) NOT NULL AUTO_INCREMENT,
  `room_type_name` VARCHAR(100) NOT NULL,
  `description`    TEXT DEFAULT NULL,
  `base_price`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `capacity`       INT(5) NOT NULL DEFAULT 1,
  PRIMARY KEY (`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_type` VALUES
(1, 'Individual', 'Habitación para una persona con cama de 90cm', 55.00, 1),
(2, 'Doble', 'Habitación para dos personas con cama de matrimonio', 85.00, 2),
(3, 'Suite', 'Suite de lujo con salón y vistas al mar', 175.00, 2),
(4, 'Suite Familiar', 'Suite amplia para familias con hasta 4 personas', 220.00, 4);

-- ============================================================
-- TABLA: room (habitaciones)
-- ============================================================
CREATE TABLE `room` (
  `room_id`         INT(10) NOT NULL AUTO_INCREMENT,
  `room_type_id`    INT(10) NOT NULL,
  `room_no`         VARCHAR(10) NOT NULL,
  `floor`           INT(5) NOT NULL DEFAULT 1,
  `status`          TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=disponible, 0=mantenimiento',
  `check_in_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=ocupada, 0=libre',
  `description`     TEXT DEFAULT NULL,
  PRIMARY KEY (`room_id`),
  UNIQUE KEY `room_no` (`room_no`),
  FOREIGN KEY (`room_type_id`) REFERENCES `room_type`(`room_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room` VALUES
(1,  1, '101', 1, 1, 0, 'Habitación individual con vistas al jardín'),
(2,  2, '102', 1, 1, 0, 'Habitación doble con terraza'),
(3,  2, '103', 1, 1, 0, 'Habitación doble interior tranquila'),
(4,  1, '104', 1, 1, 0, 'Habitación individual con baño completo'),
(5,  2, '201', 2, 1, 0, 'Habitación doble con vistas al mar'),
(6,  2, '202', 2, 1, 1, 'Habitación doble con balcón'),
(7,  3, '203', 2, 1, 0, 'Suite con salón y vistas panorámicas'),
(8,  1, '301', 3, 1, 0, 'Habitación individual en planta alta'),
(9,  4, '302', 3, 1, 0, 'Suite familiar con dos habitaciones'),
(10, 3, '303', 3, 1, 0, 'Suite de lujo con jacuzzi');

-- ============================================================
-- TABLA: room_image (imágenes de habitaciones) — NUEVA
-- ============================================================
CREATE TABLE `room_image` (
  `image_id`   INT(10) NOT NULL AUTO_INCREMENT,
  `room_id`    INT(10) NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `alt_text`   VARCHAR(200) DEFAULT NULL,
  `is_main`    TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=imagen principal',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  FOREIGN KEY (`room_id`) REFERENCES `room`(`room_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `room_image` VALUES
(1, 7, 'assets/images/rooms/suite1.jpg', 'Suite con vistas al mar', 1, NOW()),
(2, 9, 'assets/images/rooms/suite_familiar.jpg', 'Suite familiar amplia', 1, NOW()),
(3, 5, 'assets/images/rooms/doble_mar.jpg', 'Doble con vistas al mar', 1, NOW()),
(4, 2, 'assets/images/rooms/doble_terraza.jpg', 'Doble con terraza', 1, NOW()),
(5, 10,'assets/images/rooms/suite_lujo.jpg', 'Suite de lujo con jacuzzi', 1, NOW());

-- ============================================================
-- TABLA: customer (clientes)
-- ============================================================
CREATE TABLE `customer` (
  `customer_id`    INT(10) NOT NULL AUTO_INCREMENT,
  `customer_name`  VARCHAR(100) NOT NULL,
  `email`          VARCHAR(100) NOT NULL,
  `contact_no`     VARCHAR(20) NOT NULL,
  `id_card_type_id` INT(10) NOT NULL,
  `id_card_no`     VARCHAR(30) NOT NULL,
  `address`        VARCHAR(200) NOT NULL,
  `nationality`    VARCHAR(100) DEFAULT 'Española',
  `birth_date`     DATE DEFAULT NULL,
  `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`),
  FOREIGN KEY (`id_card_type_id`) REFERENCES `id_card_type`(`id_card_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer` VALUES
(1, 'María García López',    'maria.garcia@email.com',   '612345678', 1, '12345678A', 'Calle Mayor 15, Madrid',       'Española',  '1985-03-12', NOW()),
(2, 'Carlos Martínez Ruiz',  'carlos.m@email.com',       '623456789', 1, '87654321B', 'Av. Diagonal 42, Barcelona',   'Española',  '1990-07-25', NOW()),
(3, 'Sophie Dubois',         'sophie.d@email.com',       '634567890', 2, 'FR12345678','15 Rue de la Paix, París',     'Francesa',  '1988-11-03', NOW()),
(4, 'Antonio Fernández',     'antonio.f@email.com',      '645678901', 1, '11223344C', 'Calle Sierpes 8, Sevilla',     'Española',  '1975-05-18', NOW()),
(5, 'Laura Sánchez Pérez',   'laura.sp@email.com',       '656789012', 1, '44332211D', 'Gran Vía 100, Madrid',         'Española',  '1995-09-30', NOW());

-- ============================================================
-- TABLA: booking (reservas) — MEJORADA
-- ============================================================
CREATE TABLE `booking` (
  `booking_id`     INT(10) NOT NULL AUTO_INCREMENT,
  `customer_id`    INT(10) NOT NULL,
  `room_id`        INT(10) NOT NULL,
  `booking_date`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `check_in`       DATE NOT NULL,
  `check_out`      DATE NOT NULL,
  `num_nights`     INT(5) NOT NULL DEFAULT 1,
  `total_price`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `remaining_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pendiente, 1=pagado',
  `notes`          TEXT DEFAULT NULL,
  PRIMARY KEY (`booking_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer`(`customer_id`),
  FOREIGN KEY (`room_id`) REFERENCES `room`(`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `booking` VALUES
(1, 1, 5, NOW(), '2025-06-10', '2025-06-13', 3, 255.00, 0.00,    1, 'Llegada tardía, avisar recepción'),
(2, 2, 7, NOW(), '2025-06-15', '2025-06-18', 3, 525.00, 525.00,  0, NULL),
(3, 3, 2, NOW(), '2025-07-01', '2025-07-05', 4, 340.00, 170.00,  0, 'Solicita cuna para bebé'),
(4, 5, 1, NOW(), '2025-07-10', '2025-07-11', 1, 55.00,  0.00,    1, NULL);

-- ============================================================
-- TABLA: service (servicios adicionales) — NUEVA
-- ============================================================
CREATE TABLE `service` (
  `service_id`   INT(10) NOT NULL AUTO_INCREMENT,
  `service_name` VARCHAR(100) NOT NULL,
  `description`  TEXT DEFAULT NULL,
  `price`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `active`       TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `service` VALUES
(1, 'Desayuno',         'Desayuno buffet incluido',          15.00, 1),
(2, 'Media Pensión',    'Desayuno y cena incluidos',         35.00, 1),
(3, 'Pensión Completa', 'Desayuno, comida y cena',           55.00, 1),
(4, 'Parking',          'Plaza de aparcamiento cubierto',    10.00, 1),
(5, 'Spa',              'Acceso ilimitado al spa y piscina', 25.00, 1),
(6, 'Transfer',         'Traslado al aeropuerto',            40.00, 1);

-- ============================================================
-- TABLA: booking_service (servicios por reserva) — NUEVA
-- ============================================================
CREATE TABLE `booking_service` (
  `id`         INT(10) NOT NULL AUTO_INCREMENT,
  `booking_id` INT(10) NOT NULL,
  `service_id` INT(10) NOT NULL,
  `quantity`   INT(5) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`booking_id`) REFERENCES `booking`(`booking_id`) ON DELETE CASCADE,
  FOREIGN KEY (`service_id`) REFERENCES `service`(`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `booking_service` VALUES (1, 1, 1, 2), (2, 2, 5, 2), (3, 3, 1, 1);

-- ============================================================
-- TABLA: review (valoraciones) — NUEVA
-- ============================================================
CREATE TABLE `review` (
  `review_id`   INT(10) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(10) NOT NULL,
  `booking_id`  INT(10) NOT NULL,
  `rating`      TINYINT(1) NOT NULL DEFAULT 5 COMMENT '1 a 5 estrellas',
  `comment`     TEXT DEFAULT NULL,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `visible`     TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`review_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer`(`customer_id`),
  FOREIGN KEY (`booking_id`) REFERENCES `booking`(`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `review` VALUES
(1, 1, 1, 5, 'Hotel increíble, vistas espectaculares y personal muy atento. Repetiremos sin duda.', NOW(), 1),
(2, 4, 4, 4, 'Muy buena ubicación y habitaciones cómodas. El desayuno podría mejorar.', NOW(), 1),
(3, 3, 3, 5, 'Parfait! Meilleur hôtel de la côte. Le spa est extraordinaire.', NOW(), 1);

-- ============================================================
-- TABLA: shift (turnos de trabajo)
-- ============================================================
CREATE TABLE `shift` (
  `shift_id`   INT(10) NOT NULL AUTO_INCREMENT,
  `shift_name` VARCHAR(100) NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time`   TIME NOT NULL,
  PRIMARY KEY (`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `shift` VALUES
(1, 'Turno Mañana',  '07:00:00', '15:00:00'),
(2, 'Turno Tarde',   '15:00:00', '23:00:00'),
(3, 'Turno Noche',   '23:00:00', '07:00:00'),
(4, 'Turno Partido', '09:00:00', '13:00:00');

-- ============================================================
-- TABLA: staff_type (tipo de personal)
-- ============================================================
CREATE TABLE `staff_type` (
  `staff_type_id`   INT(10) NOT NULL AUTO_INCREMENT,
  `staff_type_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`staff_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff_type` VALUES
(1, 'Recepcionista'),
(2, 'Limpieza'),
(3, 'Mantenimiento'),
(4, 'Restaurante'),
(5, 'Dirección');

-- ============================================================
-- TABLA: staff (empleados)
-- ============================================================
CREATE TABLE `staff` (
  `staff_id`      INT(10) NOT NULL AUTO_INCREMENT,
  `staff_type_id` INT(10) NOT NULL,
  `staff_name`    VARCHAR(100) NOT NULL,
  `email`         VARCHAR(100) DEFAULT NULL,
  `contact_no`    VARCHAR(20) DEFAULT NULL,
  `shift_id`      INT(10) NOT NULL,
  `salary`        DECIMAL(10,2) DEFAULT NULL,
  `hire_date`     DATE DEFAULT NULL,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`staff_id`),
  FOREIGN KEY (`staff_type_id`) REFERENCES `staff_type`(`staff_type_id`),
  FOREIGN KEY (`shift_id`) REFERENCES `shift`(`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `staff` VALUES
(1, 1, 'Ana Rodríguez',     'ana.r@bellavista.com',   '611000001', 1, 1800.00, '2020-01-15', 1),
(2, 1, 'Pedro Gómez',       'pedro.g@bellavista.com', '611000002', 2, 1800.00, '2019-06-01', 1),
(3, 2, 'Isabel Torres',     'isabel.t@bellavista.com','611000003', 1, 1500.00, '2021-03-10', 1),
(4, 2, 'Miguel Herrera',    'miguel.h@bellavista.com','611000004', 2, 1500.00, '2021-03-10', 1),
(5, 3, 'Juan Morales',      'juan.m@bellavista.com',  '611000005', 4, 1600.00, '2018-11-20', 1),
(6, 4, 'Carmen Jiménez',    'carmen.j@bellavista.com','611000006', 1, 1700.00, '2022-01-05', 1),
(7, 5, 'Roberto Castillo',  'roberto.c@bellavista.com','611000007',1, 3000.00, '2015-05-01', 1);

-- ============================================================
-- TABLA: emp_history (historial de turnos)
-- ============================================================
CREATE TABLE `emp_history` (
  `id`         INT(11) NOT NULL AUTO_INCREMENT,
  `emp_id`     INT(11) NOT NULL,
  `shift_id`   INT(11) NOT NULL,
  `from_date`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `to_date`    TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`emp_id`)   REFERENCES `staff`(`staff_id`),
  FOREIGN KEY (`shift_id`) REFERENCES `shift`(`shift_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLA: complaint (quejas y reclamaciones)
-- ============================================================
CREATE TABLE `complaint` (
  `id`               INT(11) NOT NULL AUTO_INCREMENT,
  `customer_id`      INT(10) DEFAULT NULL,
  `complainant_name` VARCHAR(100) NOT NULL,
  `complaint_type`   VARCHAR(100) NOT NULL,
  `complaint`        TEXT NOT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `resolve_status`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pendiente, 1=resuelto',
  `resolve_date`     TIMESTAMP NULL DEFAULT NULL,
  `budget`           DECIMAL(10,2) DEFAULT NULL,
  `response`         TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer`(`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `complaint` VALUES
(1, 1, 'María García López', 'Aire acondicionado', 'El aire acondicionado de la habitación 101 hace mucho ruido por las noches.', NOW(), 1, NOW(), 0.00, 'Se ha revisado y limpiado el equipo. Problema solucionado.'),
(2, NULL, 'Visitante anónimo', 'Limpieza', 'El pasillo de la segunda planta necesita más atención en la limpieza.', NOW(), 0, NULL, NULL, NULL);

-- ============================================================
-- TABLA: user (usuarios del sistema)
-- ============================================================
CREATE TABLE `user` (
  `user_id`    INT(10) NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(50) NOT NULL,
  `password`   VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt',
  `role`       ENUM('admin','recepcionista','limpieza') NOT NULL DEFAULT 'recepcionista',
  `staff_id`   INT(10) DEFAULT NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  FOREIGN KEY (`staff_id`) REFERENCES `staff`(`staff_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contraseñas: admin123 y recep123 (hash bcrypt)
INSERT INTO `user` VALUES
(1, 'admin',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin',          7, 1, NOW()),
(2, 'recepcion',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'recepcionista',  1, 1, NOW());

COMMIT;

-- ============================================================
-- TABLA: customer_user (cuentas de clientes para la web)
-- ============================================================
CREATE TABLE IF NOT EXISTS `customer_user` (
  `id`          INT(10) NOT NULL AUTO_INCREMENT,
  `customer_id` INT(10) DEFAULT NULL,
  `name`        VARCHAR(100) NOT NULL,
  `email`       VARCHAR(100) NOT NULL,
  `password`    VARCHAR(255) NOT NULL,
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  FOREIGN KEY (`customer_id`) REFERENCES `customer`(`customer_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
