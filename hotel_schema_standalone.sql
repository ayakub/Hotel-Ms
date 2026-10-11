-- ============================================================
-- Hotel Reservation Management System
-- University Semester Final Project - CSE331 DBMS
-- Full Schema + Seed Data (Normalized to 3NF)
-- ============================================================

CREATE DATABASE IF NOT EXISTS hotel_reservation;
USE hotel_reservation;

CREATE TABLE Staff (
    StaffID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(60) NOT NULL,
    Position ENUM('Manager','Receptionist','Housekeeping') NOT NULL,
    Phone VARCHAR(20),
    Salary DECIMAL(10,2),
    HireDate DATE
);

CREATE TABLE Users (
    UserID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(50) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Role ENUM('Admin','Receptionist','Housekeeping') NOT NULL,
    StaffID INT NULL,
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID) ON DELETE SET NULL
);

CREATE TABLE Guest (
    GuestID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(60) NOT NULL,
    Phone VARCHAR(20),
    Email VARCHAR(60),
    Address VARCHAR(150),
    NID VARCHAR(30),
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE RoomType (
    RoomTypeID INT AUTO_INCREMENT PRIMARY KEY,
    TypeName VARCHAR(30) NOT NULL,
    PricePerNight DECIMAL(10,2) NOT NULL,
    Capacity INT NOT NULL,
    Description VARCHAR(200)
);

CREATE TABLE Room (
    RoomID INT AUTO_INCREMENT PRIMARY KEY,
    RoomNumber VARCHAR(10) NOT NULL UNIQUE,
    FloorNo INT,
    Status ENUM('Available','Reserved','Occupied','Cleaning','Maintenance') DEFAULT 'Available',
    RoomTypeID INT,
    FOREIGN KEY (RoomTypeID) REFERENCES RoomType(RoomTypeID)
);

CREATE TABLE Booking (
    BookingID INT AUTO_INCREMENT PRIMARY KEY,
    GuestID INT,
    StaffID INT,
    BookingDate DATE DEFAULT (CURRENT_DATE),
    CheckInDate DATE NOT NULL,
    CheckOutDate DATE NOT NULL,
    ActualCheckIn DATETIME NULL,
    ActualCheckOut DATETIME NULL,
    Status ENUM('Pending','Confirmed','Cancelled','Checked-In','Checked-Out') DEFAULT 'Pending',
    FOREIGN KEY (GuestID) REFERENCES Guest(GuestID),
    FOREIGN KEY (StaffID) REFERENCES Staff(StaffID)
);

CREATE TABLE BookingRoom (
    BookingID INT,
    RoomID INT,
    PricePerNight DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (BookingID, RoomID),
    FOREIGN KEY (BookingID) REFERENCES Booking(BookingID) ON DELETE CASCADE,
    FOREIGN KEY (RoomID) REFERENCES Room(RoomID)
);

CREATE TABLE Payment (
    PaymentID INT AUTO_INCREMENT PRIMARY KEY,
    BookingID INT,
    Amount DECIMAL(10,2) NOT NULL,
    PaymentDate DATE DEFAULT (CURRENT_DATE),
    PaymentMethod ENUM('Cash','Card','Mobile Banking') NOT NULL,
    PaymentStatus ENUM('Pending','Partial','Paid','Refunded') DEFAULT 'Paid',
    TransactionID VARCHAR(50),
    FOREIGN KEY (BookingID) REFERENCES Booking(BookingID) ON DELETE CASCADE
);

CREATE TABLE Service (
    ServiceID INT AUTO_INCREMENT PRIMARY KEY,
    ServiceName VARCHAR(50) NOT NULL,
    Price DECIMAL(10,2) NOT NULL
);

CREATE TABLE BookingService (
    BookingID INT,
    ServiceID INT,
    Quantity INT DEFAULT 1,
    PRIMARY KEY (BookingID, ServiceID),
    FOREIGN KEY (BookingID) REFERENCES Booking(BookingID) ON DELETE CASCADE,
    FOREIGN KEY (ServiceID) REFERENCES Service(ServiceID)
);

-- ============================================================
-- SEED DATA
-- ============================================================

INSERT INTO Staff (Name, Position, Phone, Salary, HireDate) VALUES
('Kamal Hossain', 'Manager', '01710000001', 45000, '2022-01-10'),
('Rafiq Ahmed', 'Receptionist', '01710000002', 25000, '2023-03-15'),
('Sultana Begum', 'Housekeeping', '01710000003', 18000, '2023-06-01');

-- Passwords set by database/generate_password.php -> then UPDATE manually.
-- Default plain-text password used for all seed accounts: password123
INSERT INTO Users (Username, PasswordHash, Role, StaffID) VALUES
('admin', 'REPLACE_WITH_HASH', 'Admin', 1),
('rafiq', 'REPLACE_WITH_HASH', 'Receptionist', 2),
('sultana', 'REPLACE_WITH_HASH', 'Housekeeping', 3);

INSERT INTO RoomType (TypeName, PricePerNight, Capacity, Description) VALUES
('Single', 1500, 1, 'One single bed, city view'),
('Double', 2500, 2, 'Two single beds or one double bed'),
('Deluxe', 4000, 2, 'Spacious room with premium amenities'),
('Suite', 7000, 4, 'Living area + bedroom, luxury suite'),
('Family', 5500, 5, 'Two bedrooms, suitable for families');

INSERT INTO Room (RoomNumber, FloorNo, Status, RoomTypeID) VALUES
('101', 1, 'Available', 1), ('102', 1, 'Available', 1),
('201', 2, 'Available', 2), ('202', 2, 'Available', 2),
('301', 3, 'Available', 3), ('302', 3, 'Available', 3),
('401', 4, 'Available', 4),
('501', 5, 'Available', 5);

INSERT INTO Guest (Name, Phone, Email, Address, NID) VALUES
('Tanvir Islam', '01810000001', 'tanvir@example.com', 'Dhaka', '1234567890'),
('Nadia Karim', '01810000002', 'nadia@example.com', 'Chattogram', '1234567891');

INSERT INTO Service (ServiceName, Price) VALUES
('Breakfast', 300),
('Laundry', 200),
('Room Service', 250),
('Extra Bed', 500),
('Airport Pickup', 800);
