CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50) NULL, -- Optional middle name
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL, -- Store hashed passwords
    phone_number VARCHAR(20) NULL, -- Optional phone number
    post_code VARCHAR(20) NULL, -- Optional postcode
    city VARCHAR(100) NOT NULL, -- City must be provided
    country VARCHAR(100) NOT NULL -- Country must be provided
);