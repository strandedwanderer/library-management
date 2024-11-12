CREATE TABLE publisher (
    publisher_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    year_of_publication YEAR
);

CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    author VARCHAR(255),
    publication_date DATE,
    isbn VARCHAR(20),
    category VARCHAR(50),
    available BOOLEAN DEFAULT TRUE,
    publisher_id INT,
    FOREIGN KEY (publisher_id) REFERENCES publisher (publisher_id)
);

CREATE TABLE user (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    fname VARCHAR(255),
    lname VARCHAR(255),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    book_bank_access BOOLEAN DEFAULT FALSE
);

CREATE TABLE admtb (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    fname VARCHAR(255),
    lname VARCHAR(255),
    phone VARCHAR(20)
    password
);

CREATE TABLE record (
    record_id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT,
    user_id INT,
    borrow_date DATE,
    due_date DATE,
    return_date DATE,
    FOREIGN KEY (book_id) REFERENCES books(book_id),
    FOREIGN KEY (user_id) REFERENCES user(user_id)
);