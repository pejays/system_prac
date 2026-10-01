-- ============================================================
-- Campus Equipment Borrowing System
-- Database Schema + Seed Data
-- ============================================================

CREATE DATABASE IF NOT EXISTS system_prac
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE system_prac;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('student','faculty','staff') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: equipment
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS equipment (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    category            VARCHAR(50)  NOT NULL,
    quantity_total      INT NOT NULL DEFAULT 0,
    quantity_available  INT NOT NULL DEFAULT 0,
    condition_status    ENUM('good','damaged','maintenance') NOT NULL DEFAULT 'good',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ------------------------------------------------------------
-- Table: borrow_requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS borrow_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    equipment_id    INT NOT NULL,
    quantity        INT NOT NULL,
    purpose         TEXT NOT NULL,
    date_needed     DATE NOT NULL,
    status          ENUM('pending','approved','rejected','returned') NOT NULL DEFAULT 'pending',
    requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_by     INT NULL,
    returned_at     DATETIME NULL,
    FOREIGN KEY (user_id)      REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by)  REFERENCES users(id)     ON DELETE SET NULL
);

-- ============================================================
-- SEED DATA
-- ============================================================

-- All passwords are: password123
INSERT INTO users (full_name, email, password, role) VALUES
('Juan Dela Cruz',    'student@dnsc.edu.ph', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBVenK1uFqXb4kIu', 'student'),
('Prof. Maria Santos','faculty@dnsc.edu.ph', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBVenK1uFqXb4kIu', 'faculty'),
('Admin Staff',       'staff@dnsc.edu.ph',   '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1HlCS4bZJ18JuywdBVenK1uFqXb4kIu', 'staff');

INSERT INTO equipment (name, category, quantity_total, quantity_available, condition_status) VALUES
('Epson Projector',     'AV Equipment', 5, 5, 'good'),
('HDMI Cable 3m',       'Cable',       10,10, 'good'),
('Wireless Microphone', 'Audio',        4, 4, 'good'),
('Extension Cord 5m',   'Power',        8, 8, 'good'),
('Laptop (Dell)',       'Computer',     3, 3, 'good'),
('Document Camera',     'AV Equipment', 2, 2, 'maintenance');