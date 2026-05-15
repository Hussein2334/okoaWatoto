# OkoaWatoto - Child Protection System

[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue.svg)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 📋 Overview

**OkoaWatoto** is a comprehensive web-based Child Protection System designed to help track, manage, and reunite missing and found children in Tanzania. The system provides a centralized platform for reporting missing children, managing cases, and coordinating with law enforcement agencies.

### 🎯 Mission

To provide a reliable, efficient, and user-friendly platform that helps protect children and reunite them with their families through technology and community collaboration.

---

## ✨ Features

### 🏠 Public Features
- **Homepage** - Overview of the system with statistics and recent cases
- **Children Registry** - Browse all missing, found, and reunited children
- **Report Missing Child** - Submit detailed reports about missing children
- **Report Found Child** - Submit reports about found children
- **Child Details** - View detailed information about each case
- **User Registration** - Create an account to access additional features

### 👑 Admin Features
- **Admin Dashboard** - Overview of system statistics and metrics
- **Case Management** - View, edit, update, and delete cases
- **User Management** - Add, edit, delete, and manage user roles
- **Reports & Analytics** - View charts and statistics about cases
- **System Logs** - Track all system activities and user actions
- **Settings** - Manage profile, password, and system settings

### 🔒 Security Features
- **Password Hashing** - Secure password storage using bcrypt
- **Session Management** - Secure session handling
- **Role-Based Access** - Admin, Staff, and User roles
- **Activity Logging** - Track all user actions
- **CSRF Protection** - Form submission security
- **XSS Prevention** - Input sanitization

### 📊 Statistics & Reporting
- Real-time statistics dashboard
- Monthly case trends charts
- Gender distribution analysis
- Age group analysis
- Geographic distribution
- Top reporters tracking
- Export reports to CSV

### 🗺️ Geographic Features
- Regions and districts of Tanzania
- Police stations database
- Location-based case tracking

---

## 🛠️ Technology Stack

| Technology | Version | Purpose |
|------------|---------|---------|
| **PHP** | 8.2+ | Backend logic |
| **MySQL/MariaDB** | 5.7+/10.4+ | Database |
| **HTML5** | - | Structure |
| **Tailwind CSS** | 3.x | Styling |
| **JavaScript** | ES6 | Frontend interactivity |
| **Chart.js** | 4.x | Charts and graphs |
| **Leaflet.js** | 1.9+ | Maps |
| **SweetAlert2** | 11.x | Beautiful alerts |

---

## 📁 Project Structure
okoaWatoto/
├── admin/ # Admin panel files
│ ├── includes/ # Admin includes
│ │ ├── admin-header.php
│ │ ├── admin-sidebar.php
│ │ └── admin-footer.php
│ ├── dashboard.php # Admin dashboard
│ ├── cases.php # Case management
│ ├── users.php # User management
│ ├── reports.php # Reports & analytics
│ ├── logs.php # System logs
│ ├── settings.php # System settings
│ └── edit-case.php # Edit case details
├── assets/ # Static assets
│ ├── css/
│ ├── js/
│ └── uploads/ # Uploaded photos
├── config/ # Configuration
│ └── database.php # Database connection
├── includes/ # Public includes
│ ├── header.php
│ ├── footer.php
│ └── functions.php
├── ajax/ # AJAX handlers
│ └── get_districts.php
├── index.php # Homepage
├── children.php # Children registry
├── child-details.php # Child details page
├── report-missing.php # Report missing child
├── report-found.php # Report found child
├── login.php # User login
├── register.php # User registration
├── logout.php # User logout
└── sql/ # Database files
└── database.sql # Database schema




---

## 💾 Database Schema

### Core Tables

| Table | Description |
|-------|-------------|
| `users` | System users (admin, staff, regular) |
| `children_reports` | Missing children reports |
| `found_reports` | Found children reports |
| `regions` | Tanzania regions |
| `districts` | Districts within regions |
| `wards` | Wards within districts |
| `police_stations` | Police stations information |
| `system_logs` | Activity logs |
| `system_stats` | System statistics |
| `donations` | Donation records |
| `emergency_contacts` | Emergency contact numbers |

### Key Relationships
