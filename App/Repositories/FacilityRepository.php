<?php

namespace App\Repositories;

class FacilityRepository
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }
    public function getPaginated($limit, $cursor, $filters = [])
    {
        // Build WHERE clause for filters (name, tag, city)
        $where = " WHERE f.id > :cursor";
        $params = [':cursor' => $cursor];

        if (!empty($filters['name'])) {
            $where .= " AND f.name LIKE :name";
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['city'])) {
            $where .= " AND l.city LIKE :city";
            $params[':city'] = '%' . $filters['city'] . '%';
        }

        // Get IDs
        $idSql = "
            SELECT f.id
            FROM facilities f
            JOIN locations l ON f.location_id = l.id
            $where
            ORDER BY f.id
            LIMIT :limit
        ";
        $idStmt = $this->pdo->prepare($idSql);
        foreach ($params as $key => $value) {
            $idStmt->bindValue($key, $value);
        }
        $idStmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $idStmt->execute();
        $ids = $idStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!$ids) {
            return [[], [], $cursor];
        }

        // Fetch all details for those IDs
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "
            SELECT 
                f.id as facility_id,
                f.name as facility_name,
                f.creation_date,
                l.city, l.address, l.zip_code, l.country_code, l.phone_number,
                t.id as tag_id,
                t.name as tag_name
            FROM facilities f
            JOIN locations l ON f.location_id = l.id
            LEFT JOIN facility_tags ft ON f.id = ft.facility_id
            LEFT JOIN tags t ON ft.tag_id = t.id
            WHERE f.id IN ($in)
            ORDER BY f.id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);

        $facilities = [];
        $maxId = $cursor;
        foreach ($stmt as $row) {
            $fid = $row['facility_id'];
            if (!isset($facilities[$fid])) {
                $facilities[$fid] = [
                    'id' => (int)$row['facility_id'],
                    'name' => $row['facility_name'],
                    'creation_date' => $row['creation_date'],
                    'location' => [
                        'city' => $row['city'],
                        'address' => $row['address'],
                        'zip_code' => $row['zip_code'],
                        'country_code' => $row['country_code'],
                        'phone_number' => $row['phone_number'],
                    ],
                    'tags' => []
                ];
                if ($row['facility_id'] > $maxId) {
                    $maxId = $row['facility_id'];
                }
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facilities[$fid]['tags'][] = $row['tag_name'];
            }
        }
        return [$facilities, $maxId];
    }

    public function getById($id)
    {
        $sql = "
            SELECT 
                f.id as facility_id,
                f.name as facility_name,
                f.creation_date,
                l.city, l.address, l.zip_code, l.country_code, l.phone_number,
                t.id as tag_id,
                t.name as tag_name
            FROM facilities f
            JOIN locations l ON f.location_id = l.id
            LEFT JOIN facility_tags ft ON f.id = ft.facility_id
            LEFT JOIN tags t ON ft.tag_id = t.id
            WHERE f.id = :id
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $facility = null;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (!$facility) {
                $facility = [
                    'id' => (int)$row['facility_id'],
                    'name' => $row['facility_name'],
                    'creation_date' => $row['creation_date'],
                    'location' => [
                        'city' => $row['city'],
                        'address' => $row['address'],
                        'zip_code' => $row['zip_code'],
                        'country_code' => $row['country_code'],
                        'phone_number' => $row['phone_number'],
                    ],
                    'tags' => []
                ];
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facility['tags'][] = $row['tag_name'];
            }
        }
        return $facility;
    }

    public function getLocationId($facilityId)
    {
        $stmt = $this->pdo->prepare("SELECT location_id FROM facilities WHERE id = ?");
        $stmt->execute([$facilityId]);
        return $stmt->fetchColumn();
    }

    public function create($name, $locationId)
    {
        $stmt = $this->pdo->prepare("INSERT INTO facilities (name, location_id) VALUES (?, ?)");
        $stmt->execute([$name, $locationId]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name)
    {
        $stmt = $this->pdo->prepare("UPDATE facilities SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM facilities WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

}