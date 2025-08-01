-- LOCATIONS TABLE
CREATE TABLE IF NOT EXISTS locations (
                                         id INT AUTO_INCREMENT PRIMARY KEY,
                                         city VARCHAR(100) NOT NULL,
    address VARCHAR(255) NOT NULL,
    zip_code VARCHAR(20) NOT NULL,
    country_code CHAR(2) NOT NULL,
    phone_number VARCHAR(20) NOT NULL
    );


-- FACILITIES TABLE (ONE-TO-ONE with locations via location_id)
CREATE TABLE IF NOT EXISTS facilities (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            creation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            location_id INT UNIQUE,  -- UNIQUE for one-to-one relationship!
                            FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
);


-- TAGS TABLE
CREATE TABLE IF NOT EXISTS tags (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      name VARCHAR(100) NOT NULL UNIQUE
);

-- FACILITY_TAGS TABLE (MANY-TO-MANY for facilities and tags)
CREATE TABLE IF NOT EXISTS facility_tags (
                               facility_id INT NOT NULL,
                               tag_id INT NOT NULL,
                               PRIMARY KEY (facility_id, tag_id),
                               FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE,
                               FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- EMPLOYEES TABLE (MANY-TO-ONE to facilities)
CREATE TABLE IF NOT EXISTS employees (
                           id INT AUTO_INCREMENT PRIMARY KEY,
                           facility_id INT NOT NULL,
                           name VARCHAR(100) NOT NULL,
                           email VARCHAR(100) NOT NULL,
                           phone VARCHAR(20) NOT NULL,
                           position VARCHAR(100) NOT NULL,
                           FOREIGN KEY (facility_id) REFERENCES facilities(id) ON DELETE CASCADE
);

-- Example data

INSERT INTO locations (city, address, zip_code, country_code, phone_number) VALUES
                                                                                ('Amsterdam', 'Keizersgracht 123', '1015CJ', 'NL', '+31201234567'),
                                                                                ('Rotterdam', 'Coolsingel 45', '3012AA', 'NL', '+31101001000'),
                                                                                ('Nijmegen', 'Heyendaal 12', '6511AG', 'NL', '+31202223333');

INSERT INTO tags (name) VALUES
                            ('Vegetarian'),
                            ('Vegan'),
                            ('Sea Food'),
                            ('Gluten Free'),
                            ('Organic');

INSERT INTO facilities (name, location_id) VALUES
                                               ('Green Bites', 1),
                                               ('Sea Side Foods', 2),
                                               ('Urban Eats', 3);

INSERT INTO facility_tags (facility_id, tag_id) VALUES
                                                    (1, 1),
                                                    (1, 2),
                                                    (1, 5),
                                                    (2, 3),
                                                    (2, 5),
                                                    (3, 4),
                                                    (3, 5);

INSERT INTO employees (facility_id, name, email, phone, position) VALUES
                                                                      (1, 'Begum Aksu', 'aksu@greenbites.com', '+31201112222', 'Chef'),
                                                                      (1, 'Begum Yilmaz', 'yilmaz@greenbites.com', '+31201113333', 'Waiter'),
                                                                      (2, 'John Max', 'john@seaside.com', '+31101114444', 'Manager'),
                                                                      (2, 'Max Gela', 'gela@seaside.com', '+31101115555', 'HR'),
                                                                      (3, 'Ali Veli', 'john@urbaneats.com', '+31101114466', 'Chef'),
                                                                      (3, 'Veli Ali', 'gela@urbaneats.com', '+31101115566', 'HR');