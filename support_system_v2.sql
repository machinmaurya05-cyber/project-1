<DOCUMENT filename="support_system_v2.sql">
CREATE DATABASE IF NOT EXISTS support_system;
USE support_system;
DROP TABLE IF EXISTS ticket_activity_log;
DROP TABLE IF EXISTS ticket_assign;
DROP TABLE IF EXISTS ticket_comments;
DROP TABLE IF EXISTS tickets;
DROP TABLE IF EXISTS users;
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
name VARCHAR(100),
email VARCHAR(150),
mobile VARCHAR(20) UNIQUE,
password VARCHAR(255),
role ENUM('customer','it','admin','support') DEFAULT 'customer',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE tickets (
id INT AUTO_INCREMENT PRIMARY KEY,
ticket_no VARCHAR(50),
user_id INT,
category VARCHAR(100),
title VARCHAR(255),
description TEXT,
image VARCHAR(255),
priority ENUM('Normal','High','Critical') DEFAULT 'Normal',
status ENUM('Open','Assigned','In Progress','Waiting','Resolved','Closed') DEFAULT 'Open',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE ticket_comments (
id INT AUTO_INCREMENT PRIMARY KEY,
ticket_id INT,
user_id INT,
message TEXT,
attachment VARCHAR(255),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE ticket_assign (
id INT AUTO_INCREMENT PRIMARY KEY,
ticket_id INT,
it_user_id INT,
assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE ticket_activity_log (
id INT AUTO_INCREMENT PRIMARY KEY,
ticket_id INT,
action VARCHAR(255),
done_by INT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO users(name,email,mobile,password,role) VALUES
('Main Admin','admin@company.com','9999999991',MD5('admin123'),'admin'),
('Admin 2','admin2@company.com','9999999992',MD5('admin123'),'admin'),
('IT Member 1','it1@company.com','9999999993',MD5('it123'),'it'),
('IT Member 2','it2@company.com','9999999994',MD5('it123'),'it'),
('Support Team','support@company.com','9999999995',MD5('support123'),'support'),
('Customer Demo','user@company.com','9999999996',MD5('user123'),'customer');
</DOCUMENT>