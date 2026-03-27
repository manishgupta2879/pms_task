CREATE DATABASE php_auth;
USE php_auth;
CREATE TABLE users (
id INT AUTO_INCREMENT PRIMARY KEY,
username VARCHAR(100),
email VARCHAR(150),
password VARCHAR(255),
role ENUM('superadmin','staff')
);
INSERT INTO users(username,email,password,role) VALUES('admin','admin@test.com',MD5('123456'),'superadmin');