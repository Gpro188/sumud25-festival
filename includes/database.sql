-- SUMUD'25 Arts Festival Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS sumud25_festival;
USE sumud25_festival;

-- Teams table
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    leader VARCHAR(100) NOT NULL,
    manager VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#3498db',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Team members table
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(50) DEFAULT 'Participant',
    category ENUM('BIDAYA', 'THANIYA') NOT NULL,
    chest_number VARCHAR(20),
    photo_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Competition categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Competition types table
CREATE TABLE competition_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- Programs table
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    type_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (type_id) REFERENCES competition_types(id)
);

-- Results table
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    team_id INT NOT NULL,
    winner_name VARCHAR(100) NOT NULL,
    position ENUM('1st', '2nd', '3rd') NOT NULL,
    grade ENUM('A', 'B') NOT NULL,
    points INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (team_id) REFERENCES teams(id)
);

-- Gallery table
CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image_path VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin users table
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Team Leaders table
CREATE TABLE team_leaders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    team_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name) VALUES ('BIDAYA'), ('THANIYA');

-- Insert default competition types
INSERT INTO competition_types (name) VALUES ('Individual'), ('Group'), ('General');

-- Insert default admin user (password: sumud25)
INSERT INTO admin_users (username, password_hash) VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample data for teams
INSERT INTO teams (name, leader, manager, color) VALUES 
('Team Alpha', 'Ahmed Ali', 'Fatima Khan', '#3498db'),
('Team Beta', 'Mohammed Saleh', 'Aisha Patel', '#e74c3c'),
('Team Gamma', 'Youssef Nasser', 'Layla Abbas', '#2ecc71');

-- Sample data for team members
INSERT INTO team_members (team_id, name, role, category, chest_number) VALUES 
(1, 'Ali Hassan', 'Participant', 'BIDAYA', 'B001'),
(1, 'Mariam Said', 'Participant', 'BIDAYA', 'B002'),
(2, 'Sarah Mohammed', 'Participant', 'THANIYA', 'T001'),
(2, 'Omar Farouk', 'Participant', 'THANIYA', 'T002'),
(3, 'Layla Mustafa', 'Participant', 'BIDAYA', 'G001'),
(3, 'Khalid Abbas', 'Participant', 'THANIYA', 'G002');

-- Sample data for team leaders (password: teamleader123)
INSERT INTO team_leaders (username, password_hash, full_name, team_id, is_active) VALUES 
('alpha_leader', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Ahmed Ali', 1, TRUE),
('beta_leader', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mohammed Saleh', 2, TRUE),
('gamma_leader', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Youssef Nasser', 3, TRUE);

-- Sample data for programs
INSERT INTO programs (name, category_id, type_id) VALUES 
('Quran Recitation', 1, 1),  -- BIDAYA, Individual
('Poetry Reading', 1, 1),     -- BIDAYA, Individual
('Group Dance', 2, 2),        -- THANIYA, Group
('Debate Competition', 2, 3), -- THANIYA, General
('Solo Singing', 1, 1),       -- BIDAYA, Individual
('Drama Performance', 2, 2);  -- THANIYA, Group

-- Sample data for results
INSERT INTO results (program_id, team_id, winner_name, position, grade, points) VALUES 
(1, 1, 'Ali Hassan', '1st', 'A', 10),
(2, 2, 'Sarah Mohammed', '2nd', 'B', 6);