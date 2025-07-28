-- Example data

INSERT INTO locations (city, address, zip_code, country_code, phone_number) VALUES
                                                                                ('Amsterdam', 'Keizersgracht 123', '1015CJ', 'NL', '+31201234567'),
                                                                                ('Rotterdam', 'Coolsingel 45', '3012AA', 'NL', '+31101001000');

INSERT INTO tags (name) VALUES
                            ('Vegetarian'),
                            ('Vegan'),
                            ('Halal'),
                            ('Organic');

INSERT INTO facilities (name, location_id) VALUES
                                               ('Green Bites', 1),
                                               ('Urban Eats', 2);

INSERT INTO facility_tags (facility_id, tag_id) VALUES
                                                    (1, 1),
                                                    (1, 4),
                                                    (2, 2),
                                                    (2, 3);

INSERT INTO employees (facility_id, name, email, phone, position) VALUES
                                                                      (1, 'Begum Aksu', 'aksu@greenbites.com', '+31201112222', 'Chef'),
                                                                      (1, 'Begum Yilmaz', 'yilmaz@greenbites.com', '+31201113333', 'Waiter'),
                                                                      (2, 'John Max', 'john@urbaneats.com', '+31101114444', 'Manager'),
                                                                      (2, 'Max Gela', 'gela@urbaneats.com', '+31101115555', 'HR');
