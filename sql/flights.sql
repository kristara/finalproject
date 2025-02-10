CREATE TABLE flights (
    flight_id INT AUTO_INCREMENT PRIMARY KEY,
    origin VARCHAR(100),
    destination VARCHAR(100),
    departure_time DATETIME,
    arrival_time DATETIME,
    flight_duration TIME, -- Added flight time (HH:MM:SS)
    price DECIMAL(10,2),
    seats_available INT
);