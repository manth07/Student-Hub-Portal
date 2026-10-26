-- ==========================================================
-- StudentHub Portal Database Schema & Clean Seed Data
-- Course: ITUE203 - Web Development Frameworks (Semester 3)
-- Charotar University of Science and Technology (CHARUSAT)
-- ==========================================================

DROP DATABASE IF EXISTS studenthub_db;
CREATE DATABASE studenthub_db;
USE studenthub_db;

-- 1. Users Table (Authentication & RBAC)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Students Table (Academic Directory & CRUD)
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mobile VARCHAR(15) NOT NULL,
    course VARCHAR(50) NOT NULL,
    year INT NOT NULL,
    gender VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Events Table (Campus Activities & Poster Uploads)
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    poster_image VARCHAR(255) DEFAULT 'default_poster.svg',
    status ENUM('Open', 'Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Event Registrations Table (Foreign Keys)
CREATE TABLE registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    event_id INT NOT NULL,
    role VARCHAR(50) DEFAULT 'Participant',
    notes VARCHAR(255) DEFAULT '',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reg_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Audit Logs Table (Admin Security & Event Tracking)
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_email VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL,
    details TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================================
-- SEED DATA: EXACT 2 LOGIN ACCOUNTS
-- Admin:   manthanvasoya07@gmail.com / 011207@man (lowercase m)
-- Student: 25ce136@charusat.edu.in  / 011207@Man (uppercase M)
-- ==========================================================
INSERT INTO users (full_name, email, mobile, password_hash, role) VALUES
('Manthan Vasoya', 'manthanvasoya07@gmail.com', '9876543210', '$2y$10$gE0MFaCdqJ7P2vHNKqZV4.iBBjxsHF8Qt9vVzRaYw7X2vPvN9yKSW', 'admin'),
('Manthan Student', '25ce136@charusat.edu.in', '9825012345', '$2y$10$NKFiSligJxSwM9xuTJoere5ghkGv6gL/9.PwZvBcLgt0beFdOAvEW', 'student');

-- Seed Students: 25CE136 (Primary student account) + 20 other campus students
INSERT INTO students (enrollment_no, name, email, mobile, course, year, gender) VALUES
('25CE136', 'Manthan Student', '25ce136@charusat.edu.in', '9825012345', 'B.Tech CE', 2, 'Male'),
('24CE001', 'Aarav Sharma', 'aarav.sharma@charusat.edu.in', '9825100001', 'B.Tech CE', 2, 'Male'),
('24CE002', 'Diya Patel', 'diya.patel@charusat.edu.in', '9825100002', 'B.Tech CE', 2, 'Female'),
('24CE003', 'Rohan Patel', 'rohan.patel@charusat.edu.in', '9825100003', 'B.Tech CE', 2, 'Male'),
('24CE004', 'Ananya Desai', 'ananya.desai@charusat.edu.in', '9825100004', 'B.Tech CE', 3, 'Female'),
('24CE005', 'Kavya Shah', 'kavya.shah@charusat.edu.in', '9825100005', 'B.Tech CE', 1, 'Female'),
('24IT001', 'Meet Joshi', 'meet.joshi@charusat.edu.in', '9825100006', 'B.Tech IT', 2, 'Male'),
('24IT002', 'Pooja Dave', 'pooja.dave@charusat.edu.in', '9825100007', 'B.Tech IT', 3, 'Female'),
('24IT003', 'Yash Mehta', 'yash.mehta@charusat.edu.in', '9825100008', 'B.Tech IT', 2, 'Male'),
('24IT004', 'Tanvi Bhatt', 'tanvi.bhatt@charusat.edu.in', '9825100009', 'B.Tech IT', 4, 'Female'),
('24IT005', 'Harsh Trivedi', 'harsh.trivedi@charusat.edu.in', '9825100010', 'B.Tech IT', 1, 'Male'),
('24CSE001', 'Ishaan Vora', 'ishaan.vora@charusat.edu.in', '9825100011', 'B.Tech CSE', 2, 'Male'),
('24CSE002', 'Rhea Soni', 'rhea.soni@charusat.edu.in', '9825100012', 'B.Tech CSE', 3, 'Female'),
('24CSE003', 'Manan Pandya', 'manan.pandya@charusat.edu.in', '9825100013', 'B.Tech CSE', 2, 'Male'),
('24CSE004', 'Khushi Prajapati', 'khushi.p@charusat.edu.in', '9825100014', 'B.Tech CSE', 1, 'Female'),
('24CSE005', 'Devansh Shah', 'devansh.s@charusat.edu.in', '9825100015', 'B.Tech CSE', 4, 'Male'),
('24BCA001', 'Aryan Parmar', 'aryan.parmar@charusat.edu.in', '9825100016', 'BCA', 2, 'Male'),
('24BCA002', 'Sneha Solanki', 'sneha.solanki@charusat.edu.in', '9825100017', 'BCA', 1, 'Female'),
('24BCA003', 'Jaydeep Rathod', 'jaydeep.r@charusat.edu.in', '9825100018', 'BCA', 3, 'Male'),
('24BCA004', 'Bhavya Barot', 'bhavya.b@charusat.edu.in', '9825100019', 'BCA', 2, 'Female'),
('24BCA005', 'Karan Makwana', 'karan.m@charusat.edu.in', '9825100020', 'BCA', 3, 'Male');

-- Seed 10 Campus Events
INSERT INTO events (title, category, event_date, venue, description, poster_image, status) VALUES
('HackCHARUSAT 2026', 'Technical', '2026-11-15', 'DEPSTAR Auditorium & Labs', '36-hour flagship national level hackathon focusing on AI, IoT, Web3 and Sustainable Engineering solutions.', 'default_poster.svg', 'Open'),
('CodeStorm Competitive Coding', 'Technical', '2026-11-20', 'CSPIT Computer Center Lab 3', 'Intense 3-round algorithmic programming contest in C++, Java, and Python with cash prizes and certificates.', 'default_poster.svg', 'Open'),
('Spoural Annual Cultural Fest', 'Cultural', '2026-12-05', 'University Central Lawns', 'Grand cultural festival featuring dance, music, drama, fashion parade, and vibrant stage performances.', 'default_poster.svg', 'Open'),
('Full Stack Web Dev Bootcamp', 'Workshop', '2026-11-10', 'Seminar Hall 2, CSPIT', 'Hands-on training session covering HTML5, CSS3, JavaScript, PHP, MySQL, and REST APIs for second-year students.', 'default_poster.svg', 'Open'),
('Inter-Department Cricket Cup', 'Sports', '2026-12-12', 'CHARUSAT Sports Complex Ground', 'T20 inter-departmental tournament for B.Tech CE, IT, CSE, and BCA student teams.', 'default_poster.svg', 'Open'),
('AI & Machine Learning Seminar', 'Workshop', '2026-11-28', 'Auditorium Hall A', 'Guest talk by industry experts on Generative AI, LLMs, and real-world deployment challenges.', 'default_poster.svg', 'Open'),
('RoboWars Championship', 'Technical', '2026-12-01', 'Mechanical Workshop Arena', 'Wired and wireless combat robotics competition testing design, strength, and agility.', 'default_poster.svg', 'Open'),
('Rangoli & Fine Arts Exhibition', 'Cultural', '2026-11-18', 'Student Activity Center', 'Creative expression contest covering traditional rangoli, canvas painting, and digital art.', 'default_poster.svg', 'Open'),
('Badminton Singles & Doubles League', 'Sports', '2026-12-18', 'Indoor Sports Complex', 'Annual university badminton tournament for men and women across all engineering batches.', 'default_poster.svg', 'Open'),
('Cyber Security & Ethical Hacking', 'Workshop', '2026-12-22', 'Online via Microsoft Teams', 'Practical workshop on network security, web penetration testing, and secure coding fundamentals.', 'default_poster.svg', 'Open');

-- Seed 10+ Event Registrations
INSERT INTO registrations (student_id, event_id, role, notes, registered_at) VALUES
(1, 1, 'Coordinator', 'Lead student coordinator', '2026-10-20 10:15:00'),
(1, 2, 'Participant', 'Algorithms track', '2026-10-20 10:30:00'),
(2, 1, 'Participant', 'Web track team lead', '2026-10-20 11:00:00'),
(3, 2, 'Volunteer', 'Lab assistance desk', '2026-10-21 09:15:00'),
(4, 3, 'Participant', 'Drama club entry', '2026-10-21 14:00:00'),
(5, 4, 'Participant', 'Attending both days', '2026-10-21 15:45:00'),
(6, 4, 'Volunteer', 'Attendance manager', '2026-10-22 09:20:00'),
(7, 5, 'Participant', 'Department bowler', '2026-10-22 11:10:00'),
(8, 6, 'Speaker', 'Student Lightning talk', '2026-10-22 16:30:00'),
(9, 7, 'Participant', 'Robotics club arena entry', '2026-10-23 08:45:00'),
(10, 2, 'Participant', 'Python team', '2026-10-23 09:00:00'),
(11, 3, 'Volunteer', 'Stage lights crew', '2026-10-23 10:00:00');

-- Seed Audit Logs
INSERT INTO audit_logs (user_email, action, details) VALUES
('system@charusat.edu.in', 'SYSTEM_INIT', 'Database studenthub_db successfully created and seeded with admin and student credentials.'),
('manthanvasoya07@gmail.com', 'ADMIN_SETUP', 'Super Admin account provisioned for Manthan Vasoya.'),
('manthanvasoya07@gmail.com', 'EVENT_PUBLISH', 'Published new campus event: HackCHARUSAT 2026.'),
('25ce136@charusat.edu.in', 'REGISTRATION', 'Student Manthan Student (25CE136) registered for HackCHARUSAT 2026.'),
('25ce136@charusat.edu.in', 'PROFILE_CHECK', 'Verified enrollment 25CE136 in student directory.');
