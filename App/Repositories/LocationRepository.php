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
    }
}