CREATE DATABASE IF NOT EXISTS toilet_cleanliness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE toilet_cleanliness;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS toilets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  building VARCHAR(120) NOT NULL,
  floor_label VARCHAR(40) NOT NULL,
  status ENUM('available','attention','closed') NOT NULL DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_toilets (
  user_id INT UNSIGNED NOT NULL,
  toilet_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, toilet_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (toilet_id) REFERENCES toilets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS toilet_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  toilet_id INT UNSIGNED NOT NULL,
  check_in_at DATETIME NOT NULL,
  check_in_comment TEXT NOT NULL,
  check_out_at DATETIME NULL,
  check_out_comment TEXT NULL,
  status ENUM('active','completed') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (toilet_id) REFERENCES toilets(id),
  INDEX idx_history (toilet_id, check_in_at),
  INDEX idx_active (user_id, toilet_id, status)
);

CREATE TABLE IF NOT EXISTS session_photos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id BIGINT UNSIGNED NOT NULL,
  phase ENUM('before','after') NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES toilet_sessions(id) ON DELETE CASCADE,
  INDEX idx_session_phase (session_id, phase)
);

CREATE TABLE IF NOT EXISTS login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(160) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  succeeded TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_time (email, attempted_at)
);

CREATE TABLE IF NOT EXISTS password_resets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  token VARCHAR(64) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
