# StudentHub Portal — College Campus Web Portal

> **Course:** Web Development Frameworks (ITUE203) — Semester 3 (2026–27)  
> **Institution:** Charotar University of Science and Technology (CHARUSAT)  
> **Department:** Department of Computer Engineering, CSPIT  
> **Student Name:** Manthan Hareshbhai Vasoya  
> **Enrollment No:** 25CE136  

---

## 1. Project Overview

**StudentHub Portal** is a full-stack, modular campus management web application engineered for college students and faculty coordinators. The portal provides a centralized platform for academic directory browsing, campus event registration, real-time feedback logging, and administrative CRUD operations.

The codebase strictly follows a clean, beginner-friendly **procedural PHP and Vanilla JavaScript** architecture tailored for a Semester-3 Indian B.Tech computer engineering curriculum:
* **Separation of Presentation & Logic:** Primary user-facing pages are semantic `.html` files, while backend processors, CRUD handlers, and JSON APIs reside inside `php/`.
* **Zero Bloat / No Heavy Frameworks:** No Composer dependencies, namespaces, OOP classes, or Node bundlers—everything runs natively on XAMPP (Apache + MariaDB/MySQL).
* **Role-Based Access Control (RBAC):** Distinct interfaces, navigation links, and administrative controls for Students and Administrators.
* **Accessible & Responsive:** Pure CSS Flexbox and Grid layouts responsive across desktop, tablet, and mobile displays with persistent Light/Dark mode.

---

## 2. Default Examination Accounts & Credentials

The database comes pre-seeded with two verified authentication accounts and sample data:

| Role | Name | Email Address | Password | Permissions |
| :--- | :--- | :--- | :--- | :--- |
| **Administrator** | Manthan Vasoya | `manthanvasoya07@gmail.com` | `011207@man` | Full access to Admin Panel, Student CRUD, Event CRUD, Audit Logs, and CSV exports |
| **Student** | Manthan Student | `25ce136@charusat.edu.in` | `011207@Man` | Access to Student Dashboard, Directory, Event Catalog, Feedback, and Contact Support |

---

## 3. Directory & File Structure

```text
C:\xampp\htdocs\studenthub\
├── index.html                  # Home page (Hero, Image Slider, Announcement Banner, Highlights)
├── about.html                  # About portal, institutional overview, technology stack
├── events.html                 # Dynamic campus events catalog loaded via Fetch API + Modal dialog
├── faq.html                    # Collapsible FAQ accordion loaded asynchronously from JSON
├── contact.html                # Helpdesk contact form submitting POST to php/contact_process.php
├── feedback.html               # Student feedback form submitting POST to php/feedback_process.php
├── register.html               # Registration form with live regex validation & password strength meter
├── login.html                  # Standalone authentication screen with Remember-Me and error handling
├── dashboard.html              # Student dashboard with session status and quick portal actions
├── profile.html                # Academic student directory with branch/year filtering
├── admin.html                  # Administrative landing page routing to backend management modules
├── ajax_crud.html              # Asynchronous Event Registration & Live JSON API Manager (Practical 13)
├── README.md                   # Project documentation, practical mapping, and local setup guide
├── .gitignore                  # Git exclusion rules for reports, submissions, and temporary scripts
│
├── css/
│   ├── style.css               # Core design tokens, Flexbox navigation, CSS Grid, cards, themes
│   └── responsive.css          # Media queries for Tablet (<=992px) and Mobile (<=576px) breakpoints
│
├── js/
│   ├── script.js               # Global UI controller: client auth guard, theme toggle, slider, modal
│   ├── validation.js           # Client-side Regular Expression (Regex) validation & password meter
│   ├── fetch_data.js           # Fetch API pipeline for JSON events, FAQ accordion, and student directory
│   └── ajax_crud.js            # Asynchronous Fetch API CRUD controller with live KPI metric updates
│
├── data/
│   ├── events.json             # Structured JSON catalog of campus hackathons, fests, and workshops
│   ├── students.json           # Seeded student directory records for client-side rendering
│   ├── faqs.json               # Frequently asked questions for accordion rendering
│   ├── contacts.json           # Stored contact inquiries appended by php/contact_process.php
│   └── feedback.csv            # Tabular feedback records written using PHP fputcsv()
│
├── php/
│   ├── db_connect.php          # Procedural MySQLi connection establishment and charset configuration
│   ├── auth_check.php          # Server-side authentication guard, 15-minute inactivity timeout & RBAC
│   ├── session_status.php      # Procedural JSON API endpoint for client-side session authentication state
│   ├── login_process.php       # Password verification (bcrypt), session fixation prevention & cookie setup
│   ├── logout.php              # Session destruction, cookie invalidation, and audit log recording
│   ├── register_process.php    # Server-side duplicate email check, bcrypt hashing & dual table insert
│   ├── contact_process.php     # Contact form sanitizer and JSON file persistence handler
│   ├── feedback_process.php    # Feedback form sanitizer and CSV file append processor
│   ├── students_crud.php       # Practical 11: Student Master CRUD with dynamic LIKE search & CSV export
│   ├── events_crud.php         # Practical 12: Event CRUD with multipart/form-data poster upload (max 2MB)
│   ├── api.php                 # Practical 13: Lightweight RESTful JSON API for AJAX event registrations
│   └── admin_dashboard.php     # Practical 14: Admin Control Panel with SQL COUNT(*) KPIs & audit_logs
│
├── sql/
│   └── studenthub_db.sql       # Normalized MySQL schema (5 tables, primary/foreign keys, seed records)
│
└── uploads/
    ├── default_poster.svg      # Fallback vector poster placeholder for campus events
    └── .gitkeep                # Directory preservation marker for uploaded event posters
```

---

## 4. ITUE203 Practical Implementation Mapping

| Practical | Syllabus Topic | Key Files & Modules | Features Implemented |
| :---: | :--- | :--- | :--- |
| **P1** | Sitemap, Wireframing & Project Initiation | `docs/sitemap_and_wireframes.html`, `about.html` | Visual sitemap hierarchy tree, low-fidelity wireframes, and portal workflow. |
| **P2** | Semantic HTML5 Page Structure | `index.html`, `about.html`, `events.html`, etc. | Semantic tags (`<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<footer>`, `<aside>`). |
| **P3** | Responsive Design (Grid, Flexbox, Media Queries) | `css/style.css`, `css/responsive.css` | 1D Flexbox navbar, 2D CSS Grid (`.grid-3`, `.grid-4`), responsive breakpoints (`<=576px`, `<=992px`). |
| **P4** | DOM Manipulation, Events & Interactivity | `js/script.js` | Persistent theme toggle (`localStorage`), hero carousel, collapsible accordion, modal dialog. |
| **P5** | Frontend Form Validation & Password Meter | `register.html`, `js/validation.js` | Client-side Regex patterns for name, email, mobile, password; live color-coded strength meter. |
| **P6** | Fetch API, External JSON, Search & Filter | `js/fetch_data.js`, `data/events.json` | Asynchronous data fetching, multi-field search (`LIKE`), category dropdown filter, pagination. |
| **P7** | PHP Form Processing & File Storage (CSV/JSON) | `php/contact_process.php`, `php/feedback_process.php` | Server-side sanitization (`htmlspecialchars`), JSON append, CSV logging using `fputcsv()`. |
| **P8** | Relational MySQL Schema & ER Model | `sql/studenthub_db.sql`, `php/db_connect.php` | 3NF database schema, primary/foreign keys (`ON DELETE CASCADE`), UTF-8 procedural `mysqli`. |
| **P9** | Secure Registration & Bcrypt Hashing | `php/register_process.php` | Parameterized duplicate check (`SELECT id`), `password_hash()`, dual insertion into `users` & `students`. |
| **P10** | Secure Login, Sessions, RBAC & Timeout | `php/login_process.php`, `php/auth_check.php` | `password_verify()`, `session_regenerate_id()`, 15-minute inactivity timeout, Remember-Me cookie. |
| **P11** | Student Management Master CRUD | `php/students_crud.php` | Server-side CRUD, dynamic `LIKE` search, course filtering, deletion confirmation, CSV export. |
| **P12** | Event CRUD & File Upload Validation | `php/events_crud.php` | `multipart/form-data`, MIME verification (`getimagesize`), 2MB limit, unique filename generation. |
| **P13** | Procedural PHP JSON API & AJAX CRUD | `php/api.php`, `ajax_crud.html`, `js/ajax_crud.js` | REST JSON API, non-reloading event enrollment via `fetch()`, dynamic KPI cards, JSON inspector. |
| **P14** | Admin Dashboard & Security Audit Trail | `php/admin_dashboard.php` | SQL `COUNT(*)` KPI cards, 3-table `JOIN` for recent registrations, system `audit_logs` table. |
| **P15** | Final Integration, Testing & Deployment | Full Project & `README.md` | Multi-tier security perimeter, 100% linted PHP files, XAMPP local deployment, Git repository. |

---

## 5. Local Setup & Installation Guide

### Prerequisites
1. **XAMPP Server** (Apache HTTP Server + MariaDB/MySQL + PHP 8.0 or higher).
2. Modern Web Browser (Google Chrome, Microsoft Edge, or Mozilla Firefox).

### Step-by-Step Installation
1. **Clone or Copy Repository:**
   Place the project folder directly inside your XAMPP web root directory:
   ```text
   C:\xampp\htdocs\studenthub\
   ```
2. **Start Apache & MySQL:**
   Open the **XAMPP Control Panel** and click **Start** next to **Apache** and **MySQL**.
3. **Import Database Schema (`studenthub_db`):**
   * Open your browser and navigate to `http://localhost/phpmyadmin/`.
   * Click the **Import** tab in the top navigation bar.
   * Choose the SQL file located at `C:\xampp\htdocs\studenthub\sql\studenthub_db.sql`.
   * Click **Go** to create and seed the database.
4. **Access the Portal in Your Browser:**
   Open:
   ```text
   http://localhost/studenthub/
   ```
   *If you are not yet authenticated, the portal's client-side and server-side authentication guards will automatically route you to `login.html`.*

---

## 6. Key Security & Architecture Features

* **Bcrypt Password Cryptography:** Passwords are never stored in plain text. PHP's `password_hash(..., PASSWORD_DEFAULT)` applies salted bcrypt hashes (`$2y$10$...`) resistant to rainbow table lookups.
* **SQL Injection Immunity:** All database mutations (inserts, updates, deletes) utilize MySQLi parameterised prepared statements (`mysqli_prepare` and `mysqli_stmt_bind_param`).
* **Cross-Site Scripting (XSS) Sanitization:** All form inputs undergo `trim()`, `stripslashes()`, and `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
* **Inactivity Session Expiration:** User sessions expire automatically after 15 minutes of inactivity (`$_SESSION['last_activity']`), protecting public lab computers.
* **Role-Based Access Control (RBAC):** Server-side `check_admin()` functions reject unauthorized student requests to administrative scripts and redirect with an `access_denied` parameter.

---

## 7. Author & Academic Submission

* **Student Name:** Manthan Hareshbhai Vasoya
* **Student ID / Enrollment No:** 25CE136
* **Academic Year:** 2026–27 (Semester 3, B.Tech Computer Engineering)
* **Subject:** ITUE203 — Web Development Frameworks
* **Institution:** Chandubhai S. Patel Institute of Technology (CSPIT), CHARUSAT University
