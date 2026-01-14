CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nickname VARCHAR(50) NOT NULL UNIQUE,
    birth_date DATE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    email_verification_code VARCHAR(64) NULL,
    email_verification_expires_at TIMESTAMP NULL,
    password_reset_code VARCHAR(64) NULL,
    password_reset_expires_at TIMESTAMP NULL,
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

CREATE TABLE cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(120) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    price DECIMAL(12,2) NOT NULL,
    accent_color VARCHAR(20) DEFAULT '#f9b233',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE case_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    label VARCHAR(120) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    weight INT NOT NULL DEFAULT 1,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

CREATE TABLE case_open_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    case_id INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    win DECIMAL(12,2) NOT NULL,
    meta JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (case_id) REFERENCES cases(id) ON DELETE CASCADE
);

INSERT INTO cases (slug, name, description, price, accent_color, is_active) VALUES
    ('crimson-spark', 'Crimson Spark', 'Быстрый кейс с частыми малыми выплатами.', 99, '#ff6b6b', 1),
    ('neon-drift', 'Neon Drift', 'Сбалансированный набор с редким ускорением.', 249, '#6c63ff', 1),
    ('pulse-vault', 'Pulse Vault', 'Ставка на редкие всплески и высокий потенциал.', 499, '#00d4ff', 1),
    ('aurora-core', 'Aurora Core', 'Холодный спектр призов с редкими пиками.', 799, '#7efcff', 1),
    ('ember-crown', 'Ember Crown', 'Горячий кейс с высокой дисперсией.', 1299, '#f9b233', 1),
    ('titan-omega', 'Titan Omega', 'Только для смелых: редкие, но мощные награды.', 1999, '#8b6bff', 1);

INSERT INTO case_items (case_id, label, amount, weight) VALUES
    ((SELECT id FROM cases WHERE slug = 'crimson-spark'), 'Сигнал 30₽', 30, 35),
    ((SELECT id FROM cases WHERE slug = 'crimson-spark'), 'Импульс 50₽', 50, 25),
    ((SELECT id FROM cases WHERE slug = 'crimson-spark'), 'Заряд 80₽', 80, 18),
    ((SELECT id FROM cases WHERE slug = 'crimson-spark'), 'Вспышка 120₽', 120, 12),
    ((SELECT id FROM cases WHERE slug = 'crimson-spark'), 'Искра 200₽', 200, 7),
    ((SELECT id FROM cases WHERE slug = 'crimson-spark'), 'Прорыв 500₽', 500, 3),
    ((SELECT id FROM cases WHERE slug = 'neon-drift'), 'Бонус 100₽', 100, 30),
    ((SELECT id FROM cases WHERE slug = 'neon-drift'), 'Бонус 150₽', 150, 24),
    ((SELECT id FROM cases WHERE slug = 'neon-drift'), 'Бонус 200₽', 200, 20),
    ((SELECT id FROM cases WHERE slug = 'neon-drift'), 'Бонус 300₽', 300, 14),
    ((SELECT id FROM cases WHERE slug = 'neon-drift'), 'Бонус 450₽', 450, 8),
    ((SELECT id FROM cases WHERE slug = 'neon-drift'), 'Бонус 800₽', 800, 4),
    ((SELECT id FROM cases WHERE slug = 'pulse-vault'), 'Кристалл 150₽', 150, 28),
    ((SELECT id FROM cases WHERE slug = 'pulse-vault'), 'Кристалл 250₽', 250, 22),
    ((SELECT id FROM cases WHERE slug = 'pulse-vault'), 'Кристалл 350₽', 350, 18),
    ((SELECT id FROM cases WHERE slug = 'pulse-vault'), 'Кристалл 600₽', 600, 16),
    ((SELECT id FROM cases WHERE slug = 'pulse-vault'), 'Кристалл 1200₽', 1200, 10),
    ((SELECT id FROM cases WHERE slug = 'pulse-vault'), 'Кристалл 2500₽', 2500, 6),
    ((SELECT id FROM cases WHERE slug = 'aurora-core'), 'Север 200₽', 200, 26),
    ((SELECT id FROM cases WHERE slug = 'aurora-core'), 'Север 400₽', 400, 22),
    ((SELECT id FROM cases WHERE slug = 'aurora-core'), 'Север 600₽', 600, 18),
    ((SELECT id FROM cases WHERE slug = 'aurora-core'), 'Север 900₽', 900, 16),
    ((SELECT id FROM cases WHERE slug = 'aurora-core'), 'Север 1600₽', 1600, 12),
    ((SELECT id FROM cases WHERE slug = 'aurora-core'), 'Север 3200₽', 3200, 6),
    ((SELECT id FROM cases WHERE slug = 'ember-crown'), 'Жар 300₽', 300, 26),
    ((SELECT id FROM cases WHERE slug = 'ember-crown'), 'Жар 550₽', 550, 22),
    ((SELECT id FROM cases WHERE slug = 'ember-crown'), 'Жар 900₽', 900, 18),
    ((SELECT id FROM cases WHERE slug = 'ember-crown'), 'Жар 1500₽', 1500, 16),
    ((SELECT id FROM cases WHERE slug = 'ember-crown'), 'Жар 2600₽', 2600, 12),
    ((SELECT id FROM cases WHERE slug = 'ember-crown'), 'Жар 5200₽', 5200, 6),
    ((SELECT id FROM cases WHERE slug = 'titan-omega'), 'Омега 400₽', 400, 24),
    ((SELECT id FROM cases WHERE slug = 'titan-omega'), 'Омега 800₽', 800, 20),
    ((SELECT id FROM cases WHERE slug = 'titan-omega'), 'Омега 1400₽', 1400, 18),
    ((SELECT id FROM cases WHERE slug = 'titan-omega'), 'Омега 2400₽', 2400, 16),
    ((SELECT id FROM cases WHERE slug = 'titan-omega'), 'Омега 4200₽', 4200, 14),
    ((SELECT id FROM cases WHERE slug = 'titan-omega'), 'Омега 8500₽', 8500, 8);

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
    reply_message TEXT,
    replied_at TIMESTAMP NULL,
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

CREATE TABLE missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    type VARCHAR(40) NOT NULL,
    target_value DECIMAL(12, 2) NOT NULL DEFAULT 0,
    reward_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    reward_type VARCHAR(20) NOT NULL DEFAULT 'balance',
    period VARCHAR(20) NOT NULL DEFAULT 'daily',
    starts_at DATETIME NULL,
    ends_at DATETIME NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE user_missions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mission_id INT NOT NULL,
    progress DECIMAL(12, 2) NOT NULL DEFAULT 0,
    period_key VARCHAR(20) NOT NULL DEFAULT 'all',
    completed_at DATETIME NULL,
    claimed_at DATETIME NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_mission (user_id, mission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
);

CREATE TABLE tournaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(140) NOT NULL,
    description TEXT NOT NULL,
    game_type VARCHAR(20) NOT NULL DEFAULT 'slots',
    metric VARCHAR(20) NOT NULL DEFAULT 'win',
    entry_fee DECIMAL(12, 2) NOT NULL DEFAULT 0,
    prize_pool DECIMAL(12, 2) NOT NULL DEFAULT 0,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE tournament_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tournament_id INT NOT NULL,
    user_id INT NOT NULL,
    points DECIMAL(14, 2) NOT NULL DEFAULT 0,
    best_win DECIMAL(12, 2) NOT NULL DEFAULT 0,
    spins INT NOT NULL DEFAULT 0,
    reward_amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    reward_claimed_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tournament_entry (tournament_id, user_id),
    FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT NOT NULL
);

INSERT INTO settings (name, value) VALUES
('terms', 'Используя Kasino Lux, вы подтверждаете, что вам исполнилось 18 лет и вы соглашаетесь с правилами сервиса. Аккаунт предназначен только для личного использования, передача доступа третьим лицам запрещена. Все бонусы и промокоды имеют условия отыгрыша, указанные при активации. Запрещены мошенничество, мультиаккаунты и любые попытки манипуляции игровым процессом. Вывод средств осуществляется на банковскую карту или через СБП по номеру телефона, при необходимости администрация может запросить подтверждение личности. Администрация вправе приостанавливать операции и менять условия программы лояльности, уведомляя пользователей через раздел уведомлений.'),
('privacy', 'Kasino Lux собирает минимально необходимую информацию: email, никнейм, дату рождения, историю транзакций, игровые события, IP-адрес и данные устройства для безопасности и антифрода. Эти данные используются для предоставления сервиса, начисления бонусов, обработки платежей, а также для поддержки пользователей. Мы не продаем персональные данные и не передаем их третьим лицам, кроме случаев, необходимых для обработки платежей и соблюдения закона. Данные хранятся в защищенных системах, доступ ограничен уполномоченными сотрудниками. Вы можете запросить исправление или удаление данных через службу поддержки. Используя сайт, вы соглашаетесь с использованием cookies для сохранения сессии и персонализации.'),
('hero_title', 'Премиальный неон-казино клуб'),
('hero_subtitle', 'Играй в топовые слоты, получай бонусы и наслаждайся атмосферой роскоши.'),
('bonus_daily_text', 'Забирайте награду каждые 24 часа.'),
('bonus_welcome_text', 'До 500₽ + вейджер 20x.'),
('bonus_cashback_text', 'Возврат до 5% каждую неделю.'),
('missions_title', 'Миссии и квесты'),
('missions_subtitle', 'Выполняйте задания и получайте награды каждый день.'),
('mission_daily_spin', 'Дневные спины'),
('mission_daily_spin_desc', 'Сделайте серию спинов и заберите награду.'),
('mission_weekly_slots', 'Недельный слот-рывок'),
('mission_weekly_slots_desc', 'Проведите 20 спинов в любых слотах.'),
('mission_minigame', 'Мини-турнир'),
('mission_minigame_desc', 'Сыграйте 5 мини-игр без перерыва.'),
('referral_title', 'Реферальная программа'),
('referral_reward', 'Пригласите друзей и получайте бонусы'),
('referral_desc', 'За каждого друга — +100₽ и 10 фриспинов.'),
('promotions_title', 'Акции и турниры'),
('promo_vip_title', 'VIP-турнир недели'),
('promo_vip_desc', 'Наберите максимум выигрышей и делите фонд.'),
('promo_streak_title', 'Серия побед'),
('promo_streak_desc', 'Соберите 10 выигрышных спинов подряд.'),
('promo_codes_title', 'Промокоды недели'),
('promo_codes_desc', 'Активируйте промокоды для бонусов.'),
('promo_codes_list', 'LUX100, NEON50, WEEKEND');

INSERT INTO missions (name, description, type, target_value, reward_amount, period, starts_at, ends_at) VALUES
('Утренний спринт', 'Сделайте 12 спинов в слотах за день.', 'slots_spins', 12, 60, 'daily', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('Гонка по ставкам', 'Наберите 5000₽ общей ставки за неделю.', 'slots_bet', 5000, 300, 'weekly', NOW(), DATE_ADD(NOW(), INTERVAL 45 DAY)),
('Мини-игровой марафон', 'Сыграйте 6 мини-игр в день.', 'minigame_plays', 6, 140, 'daily', NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('Вин-рывок', 'Соберите 1500₽ выигрыша в слотах за неделю.', 'slots_win', 1500, 420, 'weekly', NOW(), DATE_ADD(NOW(), INTERVAL 45 DAY)),
('Ставка на удачу', 'Выиграйте 600₽ в мини-играх за неделю.', 'minigame_win', 600, 220, 'weekly', NOW(), DATE_ADD(NOW(), INTERVAL 45 DAY));

INSERT INTO tournaments (name, description, game_type, metric, entry_fee, prize_pool, starts_at, ends_at) VALUES
('Небесный спринт', 'Лучшие выигрыши в слотах за неделю. Считаем суммарный выигрыш.', 'slots', 'win', 0, 15000, NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY)),
('Золотой оборот', 'Турнир по обороту ставок в слотах. Больше ставок — выше место.', 'slots', 'bet', 50, 20000, DATE_ADD(NOW(), INTERVAL -1 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY)),
('Лига мини-игр', 'Побеждает тот, кто сыграет больше мини-игр.', 'minigames', 'spins', 0, 8000, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY));

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
(1, 'support'),
(1, 'cms'),
(1, 'settings'),
(1, 'missions'),
(1, 'tournaments');

INSERT INTO slots (slug, name, rtp, volatility) VALUES
('aurora-cascade', 'Aurora Cascade', 96.20, 'high'),
('cosmic-cluster', 'Cosmic Cluster', 95.70, 'medium'),
('dragon-sticky', 'Dragon Sticky Wilds', 94.90, 'high'),
('sky-titans', 'Sky Titans', 96.40, 'high'),
('sugar-bloom', 'Sugar Bloom', 96.10, 'medium'),
('zenith-gems', 'Zenith Gems', 96.30, 'high'),
('orbit-jewels', 'Orbit Jewels', 95.90, 'medium'),
('ember-eclipse', 'Ember Eclipse', 96.10, 'high');
