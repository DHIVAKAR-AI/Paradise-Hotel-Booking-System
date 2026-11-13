CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer') NOT NULL DEFAULT 'customer'
);
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    status ENUM('available', 'booked') DEFAULT 'available'
);
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    room_id INT,
    check_in DATE,
    check_out DATE,
    total DECIMAL(10, 2),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);
CREATE TABLE billing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT,
    total_amount DECIMAL(10, 2),
    billing_date DATE,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE
);
CREATE TABLE payment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_id INT,
    payment_mode VARCHAR(50),
    payment_date DATE,
    FOREIGN KEY (billing_id) REFERENCES billing(id) ON DELETE CASCADE
);
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    q1 VARCHAR(100),
    q2 VARCHAR(100),
    q3 VARCHAR(100),
    q4 VARCHAR(100),
    q5 VARCHAR(100),
    q6 VARCHAR(100),
    q7 VARCHAR(100),
    q8 VARCHAR(100),
    q9 VARCHAR(100),
    q10 VARCHAR(100),
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id)
);



-- Insert default admin
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@hotel.com', MD5('admin123'), 'admin');

-- Insert default rooms
INSERT INTO rooms (room_number, price, status) VALUES
('101', 1000.00, 'available'),
('102', 1200.00, 'available'),
('103', 1500.00, 'available'),
('104', 1000.00, 'available'),
('105', 1800.00, 'available'),
('106', 1100.00, 'available'),
('107', 1400.00, 'available'),
('108', 2000.00, 'available'),
('109', 1600.00, 'available'),
('110', 1700.00, 'available'),
('111', 1500, 'available'),
('112', 1600, 'available'),
('113', 1700, 'available'),
('114', 1800, 'available'),
('115', 1900, 'available'),
('116', 2000, 'available'),
('117', 2100, 'available'),
('118', 2200, 'available'),
('119', 2300, 'available'),
('120', 2400, 'available'),
('121', 2500, 'available'),
('122', 2600, 'available'),
('123', 2700, 'available'),
('124', 2800, 'available'),
('125', 2900, 'available'),
('126', 3000, 'available'),
('127', 3100, 'available'),
('128', 3200, 'available'),
('129', 3300, 'available'),
('130', 3400, 'available'),
('131', 3500, 'available'),
('132', 3600, 'available'),
('133', 3700, 'available'),
('134', 3800, 'available'),
('135', 3900, 'available'),
('136', 4000, 'available'),
('137', 4100, 'available'),
('138', 4200, 'available'),
('139', 4300, 'available'),
('140', 4400, 'available');