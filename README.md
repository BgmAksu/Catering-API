# Catering API

A modern, Docker-ready PHP RESTful API backend project.

---

## 🚀 Quick Start (with Docker)

### Requirements
- PHP Version: 8.4
- Before starting services, run this command in project root folder: ``composer install``
- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)
- [Postman](https://www.postman.com/) 

---

### 1️⃣ Start All Services

Open a terminal in your project folder:
```bash
docker-compose up --build
```
The following services will start:

    app – PHP 8.x + Apache (API container)

    db – MySQL 8 (main production/dev database)

    testdb – MySQL 8 (isolated test database for automated tests)

On first start, the schema and sample data are loaded automatically.

### 2️⃣ API Access

All endpoints are available at: http://localhost:8080/api

Example, to list all facilities: ``GET`` http://localhost:8080/api/facilities

### 3️⃣ Authorization

All API requests require the following Bearer token:

``
Authorization: 
    Bearer 97e01d39b8d5a12883bc3b776f21f6c4ac7732e4e9d96a0e87a3b5c0b15a79f4
``

The default token can be found in ``config.php``.

### 4️⃣ Example API Requests
List all facilities (with cursor pagination)

``GET`` http://localhost:8080/api/facilities?limit=10&cursor=0

For more endpoints, see ``/routes/routes.php`` and the sample Postman collection.

### 5️⃣ Running Tests Inside Docker in Root Project Folder

Run ``docker-compose exec app bash``

#### Inside the container:
``export DB_HOST=testdb``

``export DB_DATABASE=testdb``

``export DB_USERNAME=testuser``

``export DB_PASSWORD=testpass``

``./vendor/bin/phpunit``

### 6️⃣ Database Information
Main DB (db):

    Host: db

    Database: dtt-catering

    User: root

    Password: root

Test DB (testdb):

    Host: testdb

    Database: testdb

    User: testuser

    Password: testpass

The schema and sample data are created automatically from
```/docker/mysql/init.sql``` and ```/docker/mysql/schema.sql```.

### 7️⃣ Main API Endpoints

    /api/facilities – Facility CRUD

    /api/locations – Location CRUD

    /api/tags – Tag CRUD

    /api/employees – Employee CRUD

Features:

    Cursor-based pagination (limit and cursor parameters)

    Search & filtering (by name, city, tag, etc.)

    Input validation and sanitization for all endpoints

    Authorization

### 8️⃣ Developer Information

Entry point: ``public/index.php``

Configuration: ``config/``

Architecture: ``DI, Repository, DTO, Exception, Middleware``

Tests: Located in the ``tests/`` folder, run with PHPUnit

Other documents (hour_log sheet, postman collection etc.) in ``otherDocs/`` folder

### 9️⃣ Useful Commands

Start all services:

    docker-compose up --build

Clean up all containers and data:

    docker-compose down -v

View logs:

    docker-compose logs -f app

Open a shell in the app container:

    docker-compose exec app bash
