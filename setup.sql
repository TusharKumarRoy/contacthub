
<<<<<<< HEAD
=======

-- ========================================
-- TABLE 1: USERS
-- ========================================
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (email LIKE '%@%')
);

-- ========================================
-- TABLE 2: CONTACTS
-- ========================================
CREATE TABLE contacts (
    contact_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    company VARCHAR(100),
    notes TEXT,
    is_favorite BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
<<<<<<< HEAD
=======
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CHECK (email LIKE '%@%' OR email IS NULL)
);

-- ========================================
-- TABLE 3: CONTACT_GROUPS (renamed from groups)
-- ========================================
CREATE TABLE contact_groups_table (
    group_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    group_name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ========================================
-- TABLE 4: CONTACT_GROUP_MEMBERS
-- ========================================
CREATE TABLE contact_group_members (
    contact_id INT NOT NULL,
    group_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (contact_id, group_id),
    FOREIGN KEY (contact_id) REFERENCES contacts(contact_id) ON DELETE CASCADE,
    FOREIGN KEY (group_id) REFERENCES contact_groups_table(group_id) ON DELETE CASCADE
);

-- ========================================
-- INSERT SAMPLE DATA
-- ========================================
<<<<<<< HEAD
=======

-- Users (password: password123)
INSERT INTO users (username, email, password) VALUES
('admin', 'admin@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('john', 'john@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Contacts
INSERT INTO contacts (user_id, first_name, last_name, email, phone, company, is_favorite) VALUES
(2, 'Alice', 'Johnson', 'alice@company.com', '123-456-7890', 'Tech Corp', 1),
(2, 'Bob', 'Smith', 'bob@company.com', '123-456-7891', 'Finance Inc', 0),
(2, 'Carol', 'Davis', 'carol@company.com', '123-456-7892', 'Tech Corp', 1),
(2, 'David', 'Wilson', 'david@company.com', '123-456-7893', 'Health Plus', 0),
(2, 'Emma', 'Brown', 'emma@company.com', '123-456-7894', 'Tech Corp', 0);

-- Groups
INSERT INTO contact_groups_table (user_id, group_name) VALUES
(2, 'Family'),
(2, 'Work'),
(2, 'Friends');

-- Assign contacts to groups
INSERT INTO contact_group_members (contact_id, group_id) VALUES
(1, 2), -- Alice in Work
(2, 2), -- Bob in Work
(3, 3), -- Carol in Friends
(4, 1), -- David in Family
(5, 2); -- Emma in Work

SELECT 'Setup Complete!' AS Status;
>>>>>>> 473ac1af8f281af4159fef28908c9c79dd496b95
