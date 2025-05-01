# Parivahan Sewa Website

## Setup Instructions

### Prerequisites
1. Install XAMPP (includes Apache, MySQL, PHP)
   - Download from: https://www.apachefriends.org/download.html
   - Install with default settings

### Database Setup
1. Start XAMPP Control Panel
2. Start Apache and MySQL services
3. Open phpMyAdmin (http://localhost/phpmyadmin)
4. Create a new database named `parivahan_db`
5. Import the database schema from `database/schema.sql`

### Website Setup
1. Copy the entire project folder to `C:\xampp\htdocs\parivahan`
2. Open your browser and navigate to `http://localhost/parivahan`
3. Register a new account to start using the services

## Features
- User Registration and Authentication
- License Application (Learner's, Permanent, Renewal, International)
- Vehicle Registration
- Payment Processing
- Notifications
- Document Management

## Services
- License Application
- Vehicle Registration
- Tax Payment
- Document Management

## Troubleshooting
- If you encounter any database connection issues, check the database credentials in `database/db_connect.php`
- Make sure the `uploads` directory has write permissions
- For file upload issues, check PHP configuration for maximum file upload size