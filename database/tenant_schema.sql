-- ============================================
-- TENANT DATABASE SCHEMA
-- ============================================


-- ============================================
-- 1. USERS
-- ============================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ============================================
-- 2. ROLES
-- ============================================

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES
('Admin'),
('Provider'),
('Nurse'),
('Patient'),
('Pharmacist');


-- ============================================
-- 3. USER ROLES
-- ============================================

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,

    PRIMARY KEY (user_id, role_id),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE
);


-- ============================================
-- 4. REFRESH TOKENS
-- ============================================

CREATE TABLE refresh_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    revoked BOOLEAN DEFAULT FALSE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- ============================================
-- 5. CSRF TOKENS
-- ============================================

CREATE TABLE csrf_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);


-- ============================================
-- 6. PATIENTS
-- ============================================

CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    patient_name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    mobile VARCHAR(20) NOT NULL,

    date_of_birth DATE,
    gender VARCHAR(20),
    address TEXT,
    medical_data TEXT,

    deleted_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);


-- ============================================
-- 7. APPOINTMENTS
-- ============================================

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,

    patient_id INT NOT NULL,
    provider_id INT NULL,

    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,

   status VARCHAR(30) NOT NULL DEFAULT 'Scheduled',

    reason TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id)
        REFERENCES patients(id),

    FOREIGN KEY (provider_id)
        REFERENCES users(id)
        ON DELETE SET NULL
);


-- ============================================
-- 8. MEDICINES
-- ============================================

CREATE TABLE medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(150) NOT NULL,
    description TEXT,

    stock_quantity INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- ============================================
-- 9. PRESCRIPTIONS
-- ============================================

CREATE TABLE prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,

    patient_id INT NOT NULL,
    provider_id INT NOT NULL,
    appointment_id INT NULL,

    notes TEXT,

    status VARCHAR(30) NOT NULL DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id)
        REFERENCES patients(id),

    FOREIGN KEY (provider_id)
        REFERENCES users(id),

    FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON DELETE SET NULL
);


-- ============================================
-- 10. PRESCRIPTION ITEMS
-- ============================================

CREATE TABLE prescription_items (
    id INT AUTO_INCREMENT PRIMARY KEY,

    prescription_id INT NOT NULL,
    medicine_id INT NOT NULL,

    dosage VARCHAR(100),
    frequency VARCHAR(100),
    duration VARCHAR(100),

    quantity INT DEFAULT 1,

    FOREIGN KEY (prescription_id)
        REFERENCES prescriptions(id)
        ON DELETE CASCADE,

    FOREIGN KEY (medicine_id)
        REFERENCES medicines(id)
);


-- ============================================
-- 11. HOSPITAL BILLING
-- ============================================

CREATE TABLE billing (
    id INT AUTO_INCREMENT PRIMARY KEY,

    patient_id INT NOT NULL,
    appointment_id INT NULL,

    invoice_number VARCHAR(100) NOT NULL UNIQUE,

    amount DECIMAL(10,2) NOT NULL,

    payment_status VARCHAR(30) NOT NULL DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (patient_id)
        REFERENCES patients(id),

    FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON DELETE SET NULL
);


-- ============================================
-- 12. STAFF
-- ============================================

CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    role_id INT NOT NULL,

    status VARCHAR(20) NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
);


-- ============================================
-- 13. APPOINTMENT NOTES
-- ============================================

CREATE TABLE appointment_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    appointment_id INT NOT NULL,
    user_id INT NOT NULL,

    note TEXT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (appointment_id)
        REFERENCES appointments(id)
        ON DELETE CASCADE,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);