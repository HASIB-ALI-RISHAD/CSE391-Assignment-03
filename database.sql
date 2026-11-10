-- ==========================================================================
-- CSE 391: Programming for the Internet — Assignment 03 (PHP & MySQL)
-- Student: Md. Hasib Ali Rishad | Student ID: 23101389 | BRAC University
-- Database Schema & Seed Data for the Car Workshop Appointment System
-- Note: Select your database in phpMyAdmin before importing this file.
--       (db.php also creates these tables automatically on first page load.)
-- ==========================================================================

-- Uncomment the next two lines only if importing via local MySQL CLI without selecting a database first:
-- CREATE DATABASE IF NOT EXISTS cse391_workshop;
-- USE cse391_workshop;

-- Table 1: users — registered client accounts (password stored as a hash)
CREATE TABLE IF NOT EXISTS users (
    user_id INT NOT NULL AUTO_INCREMENT,
    username VARCHAR(60) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
);

-- Table 2: mechanics — 5 senior mechanics, each handles max 4 cars per day
CREATE TABLE IF NOT EXISTS mechanics (
    mechanic_id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    daily_capacity INT NOT NULL DEFAULT 4,
    PRIMARY KEY (mechanic_id)
);

INSERT INTO mechanics (mechanic_id, name, specialty, phone, daily_capacity) VALUES
(1, 'Rafiqul Islam', 'Engine & Transmission', '01711223344', 4),
(2, 'Tanvir Ahmed', 'Electrical & ECU Diagnostics', '01711223355', 4),
(3, 'Kamrul Hasan', 'Brakes, Suspension & Steering', '01711223366', 4),
(4, 'Mahmudul Karim', 'Hybrid & Fuel Injection', '01711223377', 4),
(5, 'Sajjad Hossain', 'AC, Cooling & General Overhaul', '01711223388', 4)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    specialty = VALUES(specialty),
    phone = VALUES(phone),
    daily_capacity = VALUES(daily_capacity);

-- Table 3: appointments — client bookings linked to a mechanic, a date and the client account
CREATE TABLE IF NOT EXISTS appointments (
    appointment_id INT NOT NULL AUTO_INCREMENT,
    user_id INT NULL,
    client_name VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    car_license VARCHAR(50) NOT NULL,
    car_engine VARCHAR(50) NOT NULL,
    appointment_date DATE NOT NULL,
    mechanic_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id),
    FOREIGN KEY (mechanic_id) REFERENCES mechanics(mechanic_id)
);
