CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    birth_date DATE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    language VARCHAR(5) DEFAULT 'ru',
    status ENUM('active','banned') DEFAULT 'active',
    total_wins DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE social_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider VARCHAR(50) NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_social (provider, provider_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE balances (
    user_id INT PRIMARY KEY,
    balance DECIMAL(12,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending','paid','failed') DEFAULT 'pending',
    provider VARCHAR(50) NOT NULL,
    reference VARCHAR(64),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    status ENUM('pending','on_hold','approved','rejected') DEFAULT 'pending',
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_withdrawal_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE bonuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    claimed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE promo_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    amount DECIMAL(12,2) NOT NULL,
    max_uses INT DEFAULT 1,
    used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE game_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    slot VARCHAR(100) NOT NULL,
    bet DECIMAL(12,2) NOT NULL,
    win DECIMAL(12,2) NOT NULL,
    meta JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_game_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    rtp DECIMAL(4,2) NOT NULL,
    volatility VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    subject VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','moderator','finance') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff_users(id) ON DELETE CASCADE
);

CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NULL,
    action VARCHAR(255) NOT NULL,
    meta JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE antifraud_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event VARCHAR(100) NOT NULL,
    detail TEXT,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_risk (
    user_id INT PRIMARY KEY,
    risk_score INT DEFAULT 0,
    flags VARCHAR(255) DEFAULT 'none',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    ip VARCHAR(45),
    success TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL
);

INSERT INTO settings (name, value) VALUES
('terms', 'Правила использования и условия виртуальной валюты.'),
('privacy', 'Политика конфиденциальности.');

INSERT INTO staff_users (email, password_hash, role) VALUES
('admin@kasino.local', '$2y$12$UrP0yDk6/P62gYE3vAqfbecDkebaAutmKbie3U1yGPz6xups6elX2', 'admin');

INSERT INTO staff_permissions (staff_id, permission) VALUES
(1, 'users'),
(1, 'staff'),
(1, 'slots'),
(1, 'balances'),
(1, 'deposits'),
(1, 'withdrawals'),
(1, 'antifraud'),
(1, 'audit'),
(1, 'bonuses'),
(1, 'promo'),
(1, 'notifications'),
(1, 'cms'),
(1, 'settings');

INSERT INTO slots (slug, name, rtp, volatility) VALUES
('aurora-cascade', 'Aurora Cascade', 96.20, 'high'),
('cosmic-cluster', 'Cosmic Cluster', 95.70, 'medium'),
('dragon-sticky', 'Dragon Sticky Wilds', 94.90, 'high'),
('sky-titans', 'Sky Titans', 96.40, 'high'),
('sugar-bloom', 'Sugar Bloom', 96.10, 'medium'),
('zenith-gems', 'Zenith Gems', 96.30, 'high'),
('orbit-jewels', 'Orbit Jewels', 95.90, 'medium');
