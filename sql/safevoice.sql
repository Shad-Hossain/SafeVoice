CREATE DATABASE IF NOT EXISTS safevoice CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE safevoice;

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id VARCHAR(20) NOT NULL UNIQUE,
    type        VARCHAR(50) NOT NULL,
    incident_date DATETIME,
    location    VARCHAR(255),
    description TEXT NOT NULL,
    is_anonymous TINYINT(1) DEFAULT 0,
    status      ENUM('Submitted', 'Under Review', 'Resolved', 'Rejected') DEFAULT 'Submitted',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
);

-- Sample data (optional - for testing)
INSERT INTO complaints (complaint_id, type, incident_date, location, description, is_anonymous, status) VALUES
('SV-2026-1001', 'harassment',     '2026-05-01 09:00:00', 'Mirpur, Dhaka',   'A CNG driver harassed me and demanded extra fare.', 0, 'Resolved'),
('SV-2026-1002', 'fare_overcharge','2026-05-03 14:30:00', 'Gulshan, Dhaka',  'Rickshaw puller charged double fare at night.', 1, 'Under Review'),
('SV-2026-1003', 'corruption',     '2026-05-05 11:00:00', 'Uttara, Dhaka',   'Traffic police asked for bribe without reason.', 0, 'Submitted');


CREATE TABLE sos_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    latitude VARCHAR(50),
    longitude VARCHAR(50),
    location_text TEXT,
    crime_type VARCHAR(100) DEFAULT NULL,
    description TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sos_evidence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sos_id INT NOT NULL,
    file_path TEXT,
    file_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);