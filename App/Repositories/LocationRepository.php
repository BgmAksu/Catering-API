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

    /**
     * Partially update a location with only provided fields.
     * @param int   $locationId
     * @param array $patch keys: city,address,zip_code,country_code,phone_number
     * @return int affected rows
     */
    public function updatePartial(int $locationId, array $patch): int
    {
        if (empty($patch)) {
            return 0;
        }
        $fields = [];
        $values = [];
        foreach (['city','address','zip_code','country_code','phone_number'] as $k) {
            if (array_key_exists($k, $patch)) {
                $fields[] = "$k = ?";
                $values[] = $patch[$k];
            }
        }
        if (empty($fields)) {
            return 0;
        }
        $values[] = $locationId;
        $sql = "UPDATE locations SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
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
        $stmt->bindValue(1, $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        // Check if location is used by any facility
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM facilities WHERE location_id = ?");
        $stmt->execute([$id]);
        $usedCount = $stmt->fetchColumn();

        if ($usedCount > 0) {
            return 0;
        } else {
            $stmt = $this->pdo->prepare("DELETE FROM locations WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->rowCount();
        }
    }
}