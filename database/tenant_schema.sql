-- ============================================
-- TENANT DATABASE SCHEMA
-- ============================================


-- ============================================
-- 1. USERS
-- ============================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(500) NOT NULL,          -- AES encrypted at rest
    email VARCHAR(500) NOT NULL,         -- AES encrypted at rest
    email_hash CHAR(64) NOT NULL UNIQUE, -- HMAC-SHA256(email), used for lookups
    password VARCHAR(255) NOT NULL,      -- bcrypt hash
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
-- 5. PATIENTS
-- ============================================

CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NULL,

    patient_name VARCHAR(500) NOT NULL, -- AES encrypted at rest
    email VARCHAR(500),                 -- AES encrypted at rest
    mobile VARCHAR(255) NOT NULL,       -- AES encrypted at rest
    date_of_birth VARCHAR(255),         -- AES encrypted at rest
    gender VARCHAR(255),                -- AES encrypted at rest
    address VARCHAR(255),               -- AES encrypted at rest
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
-- 6. APPOINTMENTS
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
-- 7. MEDICINES
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

INSERT INTO medicines (name, description, stock_quantity, created_at, updated_at) VALUES
('Paracetamol 500mg', 'Used for fever and mild pain', 100, NOW(), NOW()),
('Paracetamol 650mg', 'Used for fever and pain relief', 120, NOW(), NOW()),
('Ibuprofen 200mg', 'Used for pain and inflammation', 80, NOW(), NOW()),
('Ibuprofen 400mg', 'Used for moderate pain and inflammation', 90, NOW(), NOW()),
('Amoxicillin 500mg', 'Antibiotic for bacterial infections', 75, NOW(), NOW()),
('Azithromycin 500mg', 'Antibiotic for bacterial infections', 60, NOW(), NOW()),
('Cetirizine 10mg', 'Used for allergy symptoms', 100, NOW(), NOW()),
('Loratadine 10mg', 'Used for allergy relief', 80, NOW(), NOW()),
('Omeprazole 20mg', 'Used for acidity and acid reflux', 100, NOW(), NOW()),
('Pantoprazole 40mg', 'Used for acidity and gastric problems', 90, NOW(), NOW()),
('Rabeprazole 20mg', 'Used for acid reflux and ulcers', 70, NOW(), NOW()),
('Esomeprazole 40mg', 'Used for acid reflux', 85, NOW(), NOW()),
('Metformin 500mg', 'Used to control blood sugar', 100, NOW(), NOW()),
('Metformin 850mg', 'Used to manage type 2 diabetes', 80, NOW(), NOW()),
('Glimepiride 1mg', 'Used to control blood sugar', 60, NOW(), NOW()),
('Amlodipine 5mg', 'Used for high blood pressure', 100, NOW(), NOW()),
('Amlodipine 10mg', 'Used for high blood pressure', 80, NOW(), NOW()),
('Losartan 50mg', 'Used to treat high blood pressure', 75, NOW(), NOW()),
('Losartan 100mg', 'Used to manage hypertension', 60, NOW(), NOW()),
('Atenolol 50mg', 'Used for blood pressure and heart conditions', 70, NOW(), NOW()),
('Atorvastatin 10mg', 'Used to reduce cholesterol', 90, NOW(), NOW()),
('Atorvastatin 20mg', 'Used to manage high cholesterol', 80, NOW(), NOW()),
('Rosuvastatin 10mg', 'Used to reduce cholesterol', 75, NOW(), NOW()),
('Aspirin 75mg', 'Used for cardiovascular protection', 100, NOW(), NOW()),
('Clopidogrel 75mg', 'Used to prevent blood clots', 70, NOW(), NOW()),
('Diclofenac 50mg', 'Used for pain and inflammation', 85, NOW(), NOW()),
('Naproxen 250mg', 'Used for pain and inflammation', 65, NOW(), NOW()),
('Montelukast 10mg', 'Used for allergy and respiratory symptoms', 75, NOW(), NOW()),
('Levocetirizine 5mg', 'Used for allergy symptoms', 90, NOW(), NOW()),
('Dextromethorphan Syrup', 'Used for dry cough', 50, NOW(), NOW()),
('Ambroxol Syrup', 'Used to loosen mucus and relieve cough', 55, NOW(), NOW()),
('Salbutamol Inhaler', 'Used for breathing difficulties', 40, NOW(), NOW()),
('Budesonide Inhaler', 'Used for respiratory conditions', 35, NOW(), NOW()),
('Ondansetron 4mg', 'Used to prevent nausea and vomiting', 60, NOW(), NOW()),
('Domperidone 10mg', 'Used for nausea and gastric symptoms', 70, NOW(), NOW()),
('ORS Sachet', 'Used to prevent dehydration', 150, NOW(), NOW()),
('Calcium 500mg', 'Calcium supplement', 100, NOW(), NOW()),
('Vitamin D3 60000IU', 'Vitamin D supplement', 80, NOW(), NOW()),
('Vitamin B Complex', 'Vitamin B supplement', 90, NOW(), NOW()),
('Iron 100mg', 'Iron supplement', 70, NOW(), NOW()),
('Folic Acid 5mg', 'Used as folic acid supplement', 100, NOW(), NOW()),
('Multivitamin Tablet', 'General vitamin supplement', 120, NOW(), NOW()),
('Hydrocortisone Cream', 'Used for skin inflammation', 45, NOW(), NOW()),
('Clotrimazole Cream', 'Used for fungal skin infections', 50, NOW(), NOW()),
('Mupirocin Ointment', 'Used for bacterial skin infections', 40, NOW(), NOW()),
('Calamine Lotion', 'Used for skin irritation', 60, NOW(), NOW()),
('Antacid Suspension', 'Used for acidity and heartburn', 70, NOW(), NOW()),
('Loperamide 2mg', 'Used for short-term diarrhea control', 50, NOW(), NOW()),
('Bisacodyl 5mg', 'Used for constipation relief', 55, NOW(), NOW()),
('Dicyclomine 10mg', 'Used for abdominal cramps', 65, NOW(), NOW());

-- ============================================
-- 8. PRESCRIPTIONS
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
-- 9. PRESCRIPTION ITEMS
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
-- 10. HOSPITAL BILLING
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
-- 11. STAFF
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
-- 12. APPOINTMENT NOTES
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

-- ============================================
-- 14. STAFF CHAT MESSAGES (user-to-user)
-- ============================================

CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,

    sender_user_id INT NOT NULL,
    receiver_user_id INT NOT NULL,

    message TEXT NOT NULL,

    -- Soft delete columns
    deleted_for_sender TINYINT(1) NOT NULL DEFAULT 0,
    deleted_for_receiver TINYINT(1) NOT NULL DEFAULT 0,
    is_deleted_for_everyone TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sender_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (receiver_user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    INDEX idx_chat_pair (sender_user_id, receiver_user_id),
    INDEX idx_chat_created (created_at)
);