-- ============================================
-- SafeVoice Complete Database Schema
-- ============================================

CREATE DATABASE IF NOT EXISTS safevoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE safevoice;

-- ── Users ────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    email            VARCHAR(150) NOT NULL UNIQUE,
    password         VARCHAR(255) NOT NULL,
    phone            VARCHAR(20),
    division         VARCHAR(100),
    complaints_count INT DEFAULT 0,
    is_verified      TINYINT(1) DEFAULT 0,
    created_at       DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ── Complaints ───────────────────────────────
CREATE TABLE IF NOT EXISTS complaints (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id     VARCHAR(20)  NOT NULL UNIQUE,
    user_id          INT          NOT NULL,
    type             VARCHAR(50)  NOT NULL,
    incident_date    DATETIME,
    location         VARCHAR(255),
    description      TEXT         NOT NULL,
    is_anonymous     TINYINT(1)   DEFAULT 0,
    status           ENUM('Submitted','Under Review','Resolved','Rejected') DEFAULT 'Submitted',
    admin_message    TEXT,
    submitted_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── Complaint Evidence ───────────────────────
CREATE TABLE IF NOT EXISTS complaint_evidence (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20)  NOT NULL,
    file_path    VARCHAR(500) NOT NULL,
    file_name    VARCHAR(255) NOT NULL,
    uploaded_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE
);

-- ── SOS Alerts ───────────────────────────────
CREATE TABLE IF NOT EXISTS sos_alerts (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    latitude      VARCHAR(50),
    longitude     VARCHAR(50),
    location_text TEXT,
    crime_type    VARCHAR(100) DEFAULT NULL,
    description   TEXT,
    status        VARCHAR(20)  DEFAULT 'active',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ── SOS Evidence ─────────────────────────────
CREATE TABLE IF NOT EXISTS sos_evidence (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    sos_id      INT NOT NULL,
    file_path   TEXT,
    file_type   VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── Test User (password: 1234) ───────────────
-- password hash is for "1234"
INSERT IGNORE INTO users (id, name, email, password, is_verified) VALUES
(1, 'Test User', 'user@safevoice.com', '$2y$10$e0NRp/8E3PA1FvXFl7F0p.5YQ8XtGlRzKvBXuGl3TZWl.BvTnXKKu', 1);

-- ── Sample Complaints ────────────────────────
INSERT IGNORE INTO complaints (complaint_id, user_id, type, incident_date, location, description, is_anonymous, status) VALUES
('SV-2026-1001', 1, 'harassment',      '2026-05-01 09:00:00', 'Mirpur, Dhaka',  'A CNG driver harassed me and demanded extra fare.', 0, 'Resolved'),
('SV-2026-1002', 1, 'fare_overcharge', '2026-05-03 14:30:00', 'Gulshan, Dhaka', 'Rickshaw puller charged double fare at night.',     1, 'Under Review'),
('SV-2026-1003', 1, 'corruption',      '2026-05-05 11:00:00', 'Uttara, Dhaka',  'Traffic police asked for bribe without reason.',    0, 'Submitted');