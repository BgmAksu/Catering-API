<?php

namespace App\Repositories;

class LocationRepository
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function create($location)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO locations (city, address, zip_code, country_code, phone_number) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $location['city'], $location['address'], $location['zip_code'],
            $location['country_code'], $location['phone_number']
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($locationId, $location)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE locations SET city=?, address=?, zip_code=?, country_code=?, phone_number=? WHERE id=?"
        );
        $stmt->execute([
            $location['city'], $location['address'], $location['zip_code'],
            $location['country_code'], $location['phone_number'], $locationId
        ]);
        return $stmt->rowCount();
    }

    public function getById($id)
    {
        $stmt = $this->pdo->prepare("SELECT id, city, address, zip_code, country_code, phone_number FROM locations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPaginated($limit, $cursor)
    {
        $sql = "SELECT id, city, address, zip_code, country_code, phone_number FROM locations WHERE id > ? ORDER BY id LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$cursor, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM locations WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}