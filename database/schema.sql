-- Parivahan Sewa Database Schema

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    address TEXT,
    aadhar_number VARCHAR(12),
    date_of_birth DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    account_status ENUM('active', 'inactive', 'suspended') DEFAULT 'active'
);

-- License Applications Table
CREATE TABLE IF NOT EXISTS license_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    license_type ENUM('learner', 'permanent', 'renewal', 'international') NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'in_process') DEFAULT 'pending',
    id_proof VARCHAR(255) NOT NULL,
    address_proof VARCHAR(255) NOT NULL,
    photo VARCHAR(255) NOT NULL,
    medical_certificate VARCHAR(255),
    test_date DATE NULL,
    test_result ENUM('pass', 'fail', 'pending') DEFAULT 'pending',
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Vehicle Registration Applications Table
CREATE TABLE IF NOT EXISTS vehicle_registrations (
    registration_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_type ENUM('two_wheeler', 'four_wheeler', 'commercial', 'other') NOT NULL,
    manufacturer VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    chassis_number VARCHAR(50) NOT NULL UNIQUE,
    engine_number VARCHAR(50) NOT NULL UNIQUE,
    purchase_date DATE NOT NULL,
    dealer_name VARCHAR(100) NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'in_process') DEFAULT 'pending',
    registration_number VARCHAR(20) UNIQUE,
    registration_date DATE NULL,
    registration_expiry DATE NULL,
    insurance_details VARCHAR(255) NOT NULL,
    pollution_certificate VARCHAR(255),
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Ownership Transfer Applications Table
CREATE TABLE IF NOT EXISTS ownership_transfers (
    transfer_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_registration_id INT NOT NULL,
    current_owner_id INT NOT NULL,
    new_owner_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'in_process') DEFAULT 'pending',
    transfer_reason TEXT,
    sale_agreement VARCHAR(255) NOT NULL,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (vehicle_registration_id) REFERENCES vehicle_registrations(registration_id),
    FOREIGN KEY (current_owner_id) REFERENCES users(user_id),
    FOREIGN KEY (new_owner_id) REFERENCES users(user_id)
);

-- Road Tax Payments Table
CREATE TABLE IF NOT EXISTS road_tax_payments (
    tax_payment_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_registration_id INT NOT NULL,
    user_id INT NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL,
    tax_period_start DATE NOT NULL,
    tax_period_end DATE NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_reference VARCHAR(100),
    receipt_number VARCHAR(50),
    remarks TEXT,
    FOREIGN KEY (vehicle_registration_id) REFERENCES vehicle_registrations(registration_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Pollution Certificate Applications Table
CREATE TABLE IF NOT EXISTS pollution_certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_registration_id INT NOT NULL,
    user_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    test_center VARCHAR(100) NOT NULL,
    test_date DATE NOT NULL,
    result ENUM('pass', 'fail') NOT NULL,
    certificate_number VARCHAR(50) UNIQUE,
    issue_date DATE,
    expiry_date DATE,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (vehicle_registration_id) REFERENCES vehicle_registrations(registration_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Transport Permit Applications Table
CREATE TABLE IF NOT EXISTS transport_permits (
    permit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_registration_id INT NOT NULL,
    permit_type ENUM('national', 'tourist', 'goods_carrier', 'other') NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'in_process') DEFAULT 'pending',
    permit_number VARCHAR(50) UNIQUE,
    issue_date DATE,
    expiry_date DATE,
    route_details TEXT,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (vehicle_registration_id) REFERENCES vehicle_registrations(registration_id)
);

-- Fitness Certificate Applications Table
CREATE TABLE IF NOT EXISTS fitness_certificates (
    fitness_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    vehicle_registration_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected', 'in_process') DEFAULT 'pending',
    inspection_date DATE,
    inspection_center VARCHAR(100),
    result ENUM('pass', 'fail', 'pending') DEFAULT 'pending',
    certificate_number VARCHAR(50) UNIQUE,
    issue_date DATE,
    expiry_date DATE,
    payment_status ENUM('pending', 'completed') DEFAULT 'pending',
    payment_amount DECIMAL(10, 2) NOT NULL,
    payment_date TIMESTAMP NULL,
    payment_reference VARCHAR(100),
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (vehicle_registration_id) REFERENCES vehicle_registrations(registration_id)
);

-- Payment Transactions Table
CREATE TABLE IF NOT EXISTS payment_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    service_type ENUM('license', 'registration', 'transfer', 'tax', 'pollution', 'permit', 'fitness', 'other') NOT NULL,
    reference_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('credit_card', 'debit_card', 'net_banking', 'upi', 'wallet', 'other') NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_reference VARCHAR(100),
    gateway_response TEXT,
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- User Documents Table
CREATE TABLE IF NOT EXISTS user_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type ENUM('id_proof', 'address_proof', 'photo', 'signature', 'medical_certificate', 'other') NOT NULL,
    document_path VARCHAR(255) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verification_date TIMESTAMP NULL,
    remarks TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('application_update', 'payment', 'document', 'reminder', 'other') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);