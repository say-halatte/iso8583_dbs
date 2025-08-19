
-- Structure de base de données MySQL
-- database.sql
CREATE DATABASE IF NOT EXISTS iso8583_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE iso8583_db;

DROP TABLE IF EXISTS iso_messages;

CREATE TABLE iso_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mti VARCHAR(4) NOT NULL COMMENT 'Message Type Indicator (Field 0)',
    pan TEXT NOT NULL COMMENT 'Primary Account Number - Encrypted (Field 2)',
    processing_code VARCHAR(6) NOT NULL COMMENT 'Processing Code (Field 3)',
    amount BIGINT NOT NULL COMMENT 'Transaction Amount (Field 4)',
    transaction_time VARCHAR(6) NOT NULL COMMENT 'Transaction Time HHmmss (Field 12)',
    transaction_date VARCHAR(4) NOT NULL COMMENT 'Transaction Date MMDD (Field 13)',
    rrn VARCHAR(12) NOT NULL COMMENT 'Retrieval Reference Number (Field 37)',
    response_code VARCHAR(2) DEFAULT NULL COMMENT 'Response Code (Field 39)',
    terminal_id VARCHAR(16) NOT NULL COMMENT 'Terminal ID (Field 41)',
    currency VARCHAR(3) NOT NULL COMMENT 'Currency Code (Field 49)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date d\'ajout en BD',
    
    INDEX idx_mti (mti),
    INDEX idx_terminal_id (terminal_id),
    INDEX idx_rrn (rrn),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour gérer les tokens d'authentification (optionnel pour une gestion plus avancée)
CREATE TABLE api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    username VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_token (token),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer des tokens d'exemple
INSERT INTO api_tokens (token, user_id, username, expires_at) VALUES
('bearer_token_example_123456789', 1, 'admin', DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('api_key_iso8583_secure_2024', 2, 'service', DATE_ADD(NOW(), INTERVAL 6 MONTH));
