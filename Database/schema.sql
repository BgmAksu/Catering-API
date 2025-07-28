-- Database schema

CREATE TABLE locations (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           city VARCHAR(100) NOT NULL,
                           address VARCHAR(255) NOT NULL,
                           zip_code VARCHAR(20) NOT NULL,
                           country_code CHAR(2) NOT NULL,
                           phone_number VARCHAR(20) NOT NULL
);

CREATE TABLE tags (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE facilities (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            location_id INT NOT NULL,
                            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);

CREATE TABLE facility_tags (
                               facility_id INT NOT NULL,
                               tag_id INT NOT NULL,
                               PRIMARY KEY (facility_id, tag_id),
                               FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
                               FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

CREATE TABLE employees (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           facility_id INT NOT NULL,
                           name VARCHAR(100) NOT NULL,
                           email VARCHAR(100) NOT NULL,
                           phone VARCHAR(20) NOT NULL,
                           position VARCHAR(100) NOT NULL,
                           FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);