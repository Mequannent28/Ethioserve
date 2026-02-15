-- EthioServe Platform Database Schema

CREATE DATABASE IF NOT EXISTS ethioserve;
USE ethioserve;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    role ENUM('admin', 'hotel', 'broker', 'transport', 'customer') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hotels Table
CREATE TABLE IF NOT EXISTS hotels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    cuisine_type VARCHAR(100),
    opening_hours VARCHAR(100),
    rating DECIMAL(2,1) DEFAULT 0.0,
    min_order DECIMAL(10,2) DEFAULT 0.0,
    delivery_time VARCHAR(50),
    image_url VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Menu Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL
);

-- Menu Items Table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    hotel_id INT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'preparing', 'on_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    hotel_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    booking_type ENUM('room', 'table', 'hall') NOT NULL,
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
);

-- Brokers Table
CREATE TABLE IF NOT EXISTS brokers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    referral_code VARCHAR(20) UNIQUE,
    bio TEXT,
    total_earnings DECIMAL(10,2) DEFAULT 0.0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Referrals Table
CREATE TABLE IF NOT EXISTS referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    broker_id INT,
    order_id INT,
    commission_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (broker_id) REFERENCES brokers(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- ==================== TRANSPORT SYSTEM ====================

-- Transport Companies Table (Golden Bus, Walya Bus, Gion Bus, Geda Bus, Awash Bus)
CREATE TABLE IF NOT EXISTS transport_companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    company_name VARCHAR(100) NOT NULL,
    description TEXT,
    logo_url VARCHAR(255),
    phone VARCHAR(20),
    email VARCHAR(100),
    address VARCHAR(255),
    rating DECIMAL(2,1) DEFAULT 0.0,
    total_buses INT DEFAULT 0,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bus Types
CREATE TABLE IF NOT EXISTS bus_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(255),
    seat_layout VARCHAR(50) DEFAULT '2-2'
);

-- Buses Table
CREATE TABLE IF NOT EXISTS buses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    bus_type_id INT,
    bus_number VARCHAR(20) NOT NULL UNIQUE,
    plate_number VARCHAR(20),
    total_seats INT DEFAULT 50,
    amenities TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (company_id) REFERENCES transport_companies(id) ON DELETE CASCADE,
    FOREIGN KEY (bus_type_id) REFERENCES bus_types(id) ON DELETE SET NULL
);

-- Routes Table (e.g., Addis Ababa to Hawassa)
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(10,2),
    estimated_hours DECIMAL(4,1),
    base_price DECIMAL(10,2) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (company_id) REFERENCES transport_companies(id) ON DELETE CASCADE
);

-- Schedules Table (Daily departures)
CREATE TABLE IF NOT EXISTS schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bus_id INT,
    route_id INT,
    departure_time TIME NOT NULL,
    arrival_time TIME,
    price DECIMAL(10,2) NOT NULL,
    operating_days VARCHAR(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bus_id) REFERENCES buses(id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
);

-- Bus Bookings Table
CREATE TABLE IF NOT EXISTS bus_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_reference VARCHAR(20) UNIQUE,
    customer_id INT,
    schedule_id INT,
    travel_date DATE NOT NULL,
    seat_numbers VARCHAR(100),
    num_passengers INT DEFAULT 1,
    passenger_names TEXT,
    passenger_phones TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    pickup_point VARCHAR(255),
    dropoff_point VARCHAR(255),
    payment_method VARCHAR(50),
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES schedules(id) ON DELETE CASCADE
);

-- Seat Bookings Table (Individual seats)
CREATE TABLE IF NOT EXISTS seat_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT,
    seat_number VARCHAR(10) NOT NULL,
    passenger_name VARCHAR(100),
    passenger_phone VARCHAR(20),
    price DECIMAL(10,2),
    FOREIGN KEY (booking_id) REFERENCES bus_bookings(id) ON DELETE CASCADE
);

-- ==================== END TRANSPORT SYSTEM ====================

-- Listings Table (For House Rent, Car Rent, etc.)
CREATE TABLE IF NOT EXISTS listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('house_rent', 'car_rent', 'bus_ticket', 'home_service') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    location VARCHAR(255),
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    area_sqm INT DEFAULT 0,
    features TEXT,
    contact_phone VARCHAR(20),
    contact_name VARCHAR(100),
    status ENUM('available', 'taken', 'pending') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Rental Requests Table
CREATE TABLE IF NOT EXISTS rental_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT,
    customer_id INT,
    customer_name VARCHAR(100),
    customer_phone VARCHAR(20),
    customer_email VARCHAR(100),
    message TEXT,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Flights Table
CREATE TABLE IF NOT EXISTS flights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    airline VARCHAR(100) NOT NULL,
    flight_number VARCHAR(20) UNIQUE,
    origin VARCHAR(100) DEFAULT 'Addis Ababa (ADD)',
    destination VARCHAR(100) NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    available_seats INT DEFAULT 50,
    status ENUM('scheduled', 'delayed', 'cancelled', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Flight Bookings Table
CREATE TABLE IF NOT EXISTS flight_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    flight_id INT,
    passenger_name VARCHAR(100),
    passport_number VARCHAR(50),
    pnr_code VARCHAR(10) UNIQUE,
    trip_type ENUM('one_way', 'round_trip') DEFAULT 'one_way',
    seat_number VARCHAR(10),
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
);

-- ==================== SEED DATA ====================

-- Categories
INSERT INTO categories (name) VALUES ('Breakfast'), ('Lunch'), ('Dinner'), ('Drinks'), ('Desserts');

-- Bus Types
INSERT INTO bus_types (name, description, seat_layout) VALUES 
('Standard', 'Regular bus with 2-2 seating', '2-2'),
('VIP', 'Luxury bus with extra legroom', '2-1'),
('Sleeper', 'Overnight bus with beds', '1-1'),
('Mini Bus', 'Smaller bus for short routes', '2-2');

-- Admin
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@ethioserve.com', 'System Admin', 'admin');

-- Hotel Owners
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('hilton_owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hilton@ethioserve.com', 'Hilton Addis', 'hotel'),
       ('sheraton_owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'sheraton@ethioserve.com', 'Sheraton Addis', 'hotel');

-- Hotels
INSERT INTO hotels (user_id, name, description, location, cuisine_type, opening_hours, rating, min_order, delivery_time, image_url, status)
VALUES 
(2, 'Hilton Addis Ababa', 'Classic luxury with authentic Ethiopian hospitality.', 'Menelik II Avenue, Addis Ababa', 'Ethiopian & International', '24/7', 4.8, 500.00, '30-45 min', 'https://images.unsplash.com/photo-1541014741259-df529411b96a?auto=format&fit=crop&w=1200&q=80', 'approved'),
(3, 'Sheraton Addis', 'State-of-the-art sanctuary in the heart of Ethiopia.', 'Taitu Street, Addis Ababa', 'Fine Dining', '06:00 AM - 11:00 PM', 4.9, 800.00, '40-60 min', 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=1200&q=80', 'approved');

-- Menu Items
INSERT INTO menu_items (hotel_id, category_id, name, description, price, image_url)
VALUES 
(1, 1, 'Injera with Firfir', 'Spicy beef firfir served with fresh injera.', 350.00, 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=300&q=80'),
(1, 2, 'Beyaynetu', 'Large assortment of vegan stews on injera.', 450.00, 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=300&q=80'),
(2, 2, 'Special Kitfo', 'Minced beef seasoned with mitmita and kibbeh.', 950.00, 'https://images.unsplash.com/photo-1541014741259-df529411b96a?auto=format&fit=crop&w=300&q=80'),
(2, 4, 'Buna (Coffee)', 'Traditional Ethiopian coffee ceremony style.', 150.00, 'https://images.unsplash.com/photo-1547825407-2d060104b7f8?auto=format&fit=crop&w=300&q=80');

-- Broker
INSERT INTO users (username, password, email, full_name, role) 
VALUES ('broker1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'broker1@ethioserve.com', 'Abebe Bikila', 'broker');

INSERT INTO brokers (user_id, referral_code, bio)
VALUES (4, 'ETHIO678', 'Connecting you to the best hotels in Addis.');

-- ==================== TRANSPORT SEED DATA ====================

-- Transport Company Owners
INSERT INTO users (username, password, email, full_name, role) 
VALUES 
('golden_bus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'golden@ethioserve.com', 'Golden Bus', 'transport'),
('walya_bus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'walya@ethioserve.com', 'Walya Bus', 'transport'),
('gion_bus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gion@ethioserve.com', 'Gion Bus', 'transport'),
('geda_bus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'geda@ethioserve.com', 'Geda Bus', 'transport'),
('awash_bus', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'awash@ethioserve.com', 'Awash Bus', 'transport');

-- Transport Companies
INSERT INTO transport_companies (user_id, company_name, description, phone, email, address, rating, total_buses, status)
VALUES 
(5, 'Golden Bus', 'Premium intercity bus service with modern fleet. Comfortable seats, AC, and entertainment.', '+251911000001', 'golden@ethioserve.com', 'Lamberet Bus Station, Addis Ababa', 4.5, 25, 'approved'),
(6, 'Walya Bus', 'Reliable and affordable transport across Ethiopia. Known for punctuality.', '+251911000002', 'walya@ethioserve.com', 'Meskel Square Terminal, Addis Ababa', 4.3, 30, 'approved'),
(7, 'Gion Bus', 'Luxury travel experience with VIP and sleeper options.', '+251911000003', 'gion@ethioserve.com', 'Bole International Airport Area', 4.7, 20, 'approved'),
(8, 'Geda Bus', 'Budget-friendly travel with extensive route network.', '+251911000004', 'geda@ethioserve.com', 'Mercato Bus Terminal', 4.1, 35, 'approved'),
(9, 'Awash Bus', 'Connecting major cities with comfortable standard buses.', '+251911000005', 'awash@ethioserve.com', 'Kazanchis Bus Station', 4.2, 28, 'approved');

-- Buses
INSERT INTO buses (company_id, bus_type_id, bus_number, plate_number, total_seats, amenities, is_active)
VALUES 
(1, 2, 'GB-001', 'AA-1234-A1', 45, 'AC,WiFi,USB Charging,Reclining Seats', TRUE),
(1, 1, 'GB-002', 'AA-1235-A1', 50, 'AC,Reclining Seats', TRUE),
(2, 1, 'WB-001', 'AA-2345-B1', 50, 'AC,Reclining Seats', TRUE),
(2, 2, 'WB-002', 'AA-2346-B1', 40, 'AC,WiFi,USB Charging', TRUE),
(3, 2, 'GN-001', 'AA-3456-C1', 35, 'AC,WiFi,USB Charging,Entertainment,Luxury Seats', TRUE),
(3, 3, 'GN-002', 'AA-3457-C1', 24, 'AC,WiFi,Beds,Privacy Curtains', TRUE),
(4, 1, 'GD-001', 'AA-4567-D1', 55, 'AC,Fans', TRUE),
(4, 1, 'GD-002', 'AA-4568-D1', 55, 'AC,Fans', TRUE),
(5, 1, 'AW-001', 'AA-5678-E1', 50, 'AC,Reclining Seats', TRUE),
(5, 2, 'AW-002', 'AA-5679-E1', 42, 'AC,WiFi,USB Charging', TRUE);

-- Routes
INSERT INTO routes (company_id, origin, destination, distance_km, estimated_hours, base_price, is_active)
VALUES 
(1, 'Addis Ababa', 'Hawassa', 275, 5.0, 650.00, TRUE),
(1, 'Addis Ababa', 'Bahir Dar', 565, 10.0, 950.00, TRUE),
(1, 'Addis Ababa', 'Dire Dawa', 515, 9.0, 900.00, TRUE),
(2, 'Addis Ababa', 'Hawassa', 275, 5.0, 550.00, TRUE),
(2, 'Addis Ababa', 'Jimma', 352, 6.0, 700.00, TRUE),
(2, 'Addis Ababa', 'Gondar', 738, 12.0, 1100.00, TRUE),
(3, 'Addis Ababa', 'Hawassa', 275, 5.0, 850.00, TRUE),
(3, 'Addis Ababa', 'Mekelle', 783, 12.0, 1200.00, TRUE),
(4, 'Addis Ababa', 'Hawassa', 275, 5.0, 450.00, TRUE),
(4, 'Addis Ababa', 'Adama', 99, 2.0, 250.00, TRUE),
(4, 'Addis Ababa', 'Bahir Dar', 565, 10.0, 750.00, TRUE),
(5, 'Addis Ababa', 'Hawassa', 275, 5.0, 500.00, TRUE),
(5, 'Addis Ababa', 'Dire Dawa', 515, 9.0, 800.00, TRUE);

-- Schedules
INSERT INTO schedules (bus_id, route_id, departure_time, arrival_time, price, operating_days, is_active)
VALUES 
(1, 1, '06:00:00', '11:00:00', 750.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(1, 1, '14:00:00', '19:00:00', 750.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(2, 2, '20:00:00', '06:00:00', 1100.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(3, 4, '07:00:00', '12:00:00', 600.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(3, 4, '15:00:00', '20:00:00', 600.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(4, 5, '08:00:00', '14:00:00', 800.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(5, 7, '06:30:00', '11:30:00', 950.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(6, 8, '18:00:00', '06:00:00', 1400.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(7, 9, '05:00:00', '10:00:00', 500.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(7, 9, '12:00:00', '17:00:00', 500.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(8, 10, '06:00:00', '08:00:00', 300.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(8, 10, '10:00:00', '12:00:00', 300.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(8, 10, '14:00:00', '16:00:00', 300.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(9, 12, '07:00:00', '12:00:00', 550.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(9, 12, '16:00:00', '21:00:00', 550.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE),
(10, 13, '06:00:00', '15:00:00', 900.00, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE);

-- Flights
INSERT INTO flights (airline, flight_number, destination, departure_time, arrival_time, price) VALUES 
('Ethiopian Airlines', 'ET302', 'Nairobi (NBO)', DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL '2 2' DAY_HOUR), 8500.00),
('Ethiopian Airlines', 'ET500', 'Washington D.C. (IAD)', DATE_ADD(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL '3 14' DAY_HOUR), 45000.00),
('Emirates', 'EK723', 'Dubai (DXB)', DATE_ADD(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL '1 4' DAY_HOUR), 22000.00),
('Ethiopian Airlines', 'ET700', 'London (LHR)', DATE_ADD(NOW(), INTERVAL 5 DAY), DATE_ADD(NOW(), INTERVAL '5 8' DAY_HOUR), 35000.00);

-- Listings
INSERT INTO listings (user_id, type, title, description, price, location, image_url, video_url, bedrooms, bathrooms, area_sqm, features, contact_phone, contact_name)
VALUES 
(4, 'house_rent', 'Luxury Villa in Bole', 'Stunning 4-bedroom villa with private garden, modern kitchen, marble floors, and 24/7 security. Perfect for families looking for premium living in the heart of Bole.', 55000.00, 'Bole, Addis Ababa', 'https://images.unsplash.com/photo-1580587767526-cf3660a9dd38?auto=format&fit=crop&w=800&q=80', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 4, 5, 250, 'Garden,Parking,Security,Swimming Pool', '+251911223344', 'Abebe Kebede'),
(4, 'house_rent', 'Modern 2BR Apartment CMC', 'Newly built apartment with open-plan living, balcony views, elevator access, and underground parking. Walking distance to shopping centers.', 22000.00, 'CMC, Addis Ababa', 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80', NULL, 2, 2, 120, 'Elevator,Parking,Balcony', '+251922334455', 'Meron Tadesse'),
(4, 'house_rent', 'Cozy Studio near Meskel Square', 'Furnished studio apartment ideal for young professionals. Includes Wi-Fi, water heater, and a small kitchenette.', 12000.00, 'Meskel Square, Addis Ababa', 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=80', NULL, 1, 1, 45, 'Furnished,Wi-Fi,Water Heater', '+251933445566', 'Sara Hailu'),
(4, 'house_rent', 'Penthouse with City View', 'Luxurious penthouse on the 12th floor with panoramic city views, 3 bedrooms, walk-in closets, and a private rooftop terrace.', 85000.00, 'Kazanchis, Addis Ababa', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 3, 3, 200, 'Rooftop Terrace,City View,Walk-in Closet,Gym', '+251944556677', 'Daniel Girma'),
(4, 'house_rent', 'Family Home in Ayat', 'Spacious family home with a large compound, servant quarters, and ample parking. Quiet neighborhood near schools.', 35000.00, 'Ayat, Addis Ababa', 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?auto=format&fit=crop&w=800&q=80', NULL, 3, 2, 180, 'Compound,Servant Quarter,Parking,Near Schools', '+251955667788', 'Tigist Mulugeta'),
(4, 'car_rent', 'Toyota Land Cruiser V8', 'Full options Land Cruiser, perfect for field trips and long-distance travel. Leather interior, AC, GPS navigation.', 3500.00, 'Meskel Square, Addis Ababa', 'https://images.unsplash.com/photo-1533473359331-0135ef1b58bf?auto=format&fit=crop&w=800&q=80', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 0, 0, 'GPS,Leather Seats,AC,4WD', '+251911001100', 'Yonas Motors'),
(4, 'car_rent', 'Toyota Corolla 2023', 'Brand new Corolla with automatic transmission, fuel efficient and comfortable for city driving.', 1800.00, 'Bole, Addis Ababa', 'https://images.unsplash.com/photo-1590362891991-f776e747a588?auto=format&fit=crop&w=800&q=80', NULL, 0, 0, 0, 'Automatic,AC,Bluetooth,USB', '+251922112233', 'Star Rent'),
(4, 'car_rent', 'Hyundai Santa Fe', 'SUV perfect for family outings and weekend getaways. Spacious interior with 7 seats.', 2500.00, 'Piassa, Addis Ababa', 'https://images.unsplash.com/photo-1619767886558-efdc259cde1a?auto=format&fit=crop&w=800&q=80', NULL, 0, 0, 0, '7 Seats,AC,Bluetooth,Cruise Control', '+251933223344', 'Addis Car Hire'),
(4, 'car_rent', 'Mercedes-Benz E-Class', 'Premium luxury sedan for business meetings, weddings, and VIP transport. Chauffeur available.', 5000.00, 'Kazanchis, Addis Ababa', 'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?auto=format&fit=crop&w=800&q=80', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 0, 0, 0, 'Luxury,Chauffeur Available,Leather,Premium Sound', '+251944334455', 'Royal Motors');

