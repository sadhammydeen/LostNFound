-- Campus Lost & Found Portal Database Schema & Seed Data
CREATE DATABASE IF NOT EXISTS lostfound_db;
USE lostfound_db;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','admin') DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Items Table
CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('lost','found') NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  category VARCHAR(50),
  location VARCHAR(150),
  date_reported DATE,
  image_path VARCHAR(255),
  status ENUM('open','claimed','resolved') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 3. Claims Table
CREATE TABLE IF NOT EXISTS claims (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  claimant_id INT NOT NULL,
  message TEXT,
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES items(id),
  FOREIGN KEY (claimant_id) REFERENCES users(id)
);

-- -------------------------------------------------------------
-- Seed Initial Demo Users (Password for all accounts is 'password123')
-- Hash generated via password_hash('password123', PASSWORD_DEFAULT)
-- -------------------------------------------------------------
INSERT INTO users (id, name, email, password, role) VALUES
(1, 'Admin Portal Manager', 'admin@college.edu', '$2y$10$QjBhvG98i6gK0U4iE45L7.Wc9gWd2a84.p0l0n1t4sF7L6.r4p2yq', 'admin'),
(2, 'Alex Rivera', 'alex@college.edu', '$2y$10$QjBhvG98i6gK0U4iE45L7.Wc9gWd2a84.p0l0n1t4sF7L6.r4p2yq', 'student'),
(3, 'Sarah Connor', 'sarah@college.edu', '$2y$10$QjBhvG98i6gK0U4iE45L7.Wc9gWd2a84.p0l0n1t4sF7L6.r4p2yq', 'student'),
(4, 'David Miller', 'david@college.edu', '$2y$10$QjBhvG98i6gK0U4iE45L7.Wc9gWd2a84.p0l0n1t4sF7L6.r4p2yq', 'student')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- -------------------------------------------------------------
-- Seed Demo Items
-- -------------------------------------------------------------
INSERT INTO items (id, user_id, type, title, description, category, location, date_reported, image_path, status) VALUES
(1, 2, 'lost', 'Matte Black Apple MacBook Air (M2)', 'Lost around the quiet study zone. Has an octocat sticker on the lid and a faint scratch near the trackpad.', 'Electronics', 'Central Library 3rd Floor', '2026-08-28', NULL, 'open'),
(2, 3, 'found', 'Stainless Steel Hydro Flask (Blue 32oz)', 'Found resting on the wooden bleachers after football practice. Has small college crest engraved on cap.', 'Bottles & Utensils', 'Athletic Complex & Stadium', '2026-08-29', NULL, 'open'),
(3, 4, 'found', 'Brown Leather Fossil Bi-Fold Wallet', 'Contains college ID card (initials S.C.) and room key card inside. Safely handed to department reception.', 'Bags & Wallets', 'Main Campus Dining Cafeteria', '2026-08-30', NULL, 'claimed'),
(4, 2, 'lost', 'Casio FX-991EX Scientific Calculator', 'Left behind after calculus midterm test in Lecture Hall B.', 'Books & Stationery', 'Engineering Block - Room 204', '2026-08-25', NULL, 'resolved'),
(5, 3, 'lost', 'Silver Set of 3 Locker & Dorm Keys', 'Attached to a red woven paracord keychain with a mini brass flashlight.', 'Keys', 'Student Activity Center Plaza', '2026-08-30', NULL, 'open')
ON DUPLICATE KEY UPDATE title=VALUES(title);

-- -------------------------------------------------------------
-- Seed Demo Claims
-- -------------------------------------------------------------
INSERT INTO claims (id, item_id, claimant_id, message, status) VALUES
(1, 3, 3, 'Hello, that wallet belongs to me! My student ID has name Sarah Connor with photo, and the room key has dorm code #204.', 'pending'),
(2, 4, 2, 'Thank you! The calculator has my student roll number written in black marker inside the back plastic battery cover.', 'approved')
ON DUPLICATE KEY UPDATE message=VALUES(message);
