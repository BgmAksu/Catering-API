<?php

namespace App\Controllers;

use App\Plugins\Di\Injectable;

class FacilityController extends Injectable
{
    /**
     * Sanitize a string value from client input.
     * Removes HTML tags and trims whitespace.
     */
    private function sanitizeString($input)
    {
        return trim(strip_tags((string)$input));
    }
    public function list()
    {
        $pdo = $this->db->getConnection();

        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;

        // Get total count
        $total = $pdo->query("SELECT COUNT(*) FROM facilities")->fetchColumn();

        // 1. step: Get paginated IDs
        $idSql = "SELECT id FROM facilities ORDER BY id LIMIT :limit OFFSET :offset";
        $idStmt = $pdo->prepare($idSql);
        $idStmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $idStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $idStmt->execute();
        $ids = $idStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!$ids) {
            $result = [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'facilities' => []
            ];
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }

        // 2. step: Fetch details for IDs
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);

        $facilities = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
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
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facilities[$fid]['tags'][] = $row['tag_name'];
            }
        }

        $result = [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'facilities' => array_values($facilities)
        ];

        header('Content-Type: application/json');
        echo json_encode($result);
    }


    public function detail($id)
    {
        $pdo = $this->db->getConnection();

        // Get facility, location, and tags by id
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

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

        if (!$facility) {
            http_response_code(404);
            echo json_encode(['error' => 'Facility not found']);
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($facility);
    }

    /**
     * Create a new facility, including location and tags, with input sanitation.
     */
    public function create()
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        // Sanitize all input fields
        $name = $this->sanitizeString($data['name'] ?? '');
        $location = array_map([$this, 'sanitizeString'], $data['location'] ?? []);
        $tags = array_map([$this, 'sanitizeString'], isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : []);

        // Basic validation
        if (
            empty($name) ||
            empty($location['city']) ||
            empty($location['address']) ||
            empty($location['zip_code']) ||
            empty($location['country_code']) ||
            empty($location['phone_number'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid input']);
            return;
        }

        // Insert location
        $stmt = $pdo->prepare("INSERT INTO locations (city, address, zip_code, country_code, phone_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $location['city'],
            $location['address'],
            $location['zip_code'],
            $location['country_code'],
            $location['phone_number'],
        ]);
        $locationId = $pdo->lastInsertId();

        // Insert facility
        $stmt = $pdo->prepare("INSERT INTO facilities (name, location_id) VALUES (?, ?)");
        $stmt->execute([$name, $locationId]);
        $facilityId = $pdo->lastInsertId();

        // Handle tags
        foreach ($tags as $tagName) {
            if ($tagName === '') continue;
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tagName]);
            $tagId = $stmt->fetchColumn();
            if (!$tagId) {
                $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt->execute([$tagName]);
                $tagId = $pdo->lastInsertId();
            }
            $stmt = $pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$facilityId, $tagId]);
        }

        http_response_code(201);
        echo json_encode(['id' => $facilityId]);
    }

    /**
     * Update an existing facility, location, and tags, with input sanitation.
     */
    public function update($id)
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        // Sanitize input fields
        $name = $this->sanitizeString($data['name'] ?? '');
        $location = array_map([$this, 'sanitizeString'], $data['location'] ?? []);
        $tags = array_map([$this, 'sanitizeString'], isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : []);

        $stmt = $pdo->prepare("SELECT location_id FROM facilities WHERE id = ?");
        $stmt->execute([$id]);
        $locationId = $stmt->fetchColumn();
        if (!$locationId) {
            http_response_code(404);
            echo json_encode(['error' => 'Facility not found']);
            return;
        }

        // Update location
        $stmt = $pdo->prepare("UPDATE locations SET city=?, address=?, zip_code=?, country_code=?, phone_number=? WHERE id=?");
        $stmt->execute([
            $location['city'],
            $location['address'],
            $location['zip_code'],
            $location['country_code'],
            $location['phone_number'],
            $locationId
        ]);

        // Update facility name
        $stmt = $pdo->prepare("UPDATE facilities SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);

        // Update tags
        $stmt = $pdo->prepare("DELETE FROM facility_tags WHERE facility_id=?");
        $stmt->execute([$id]);
        foreach ($tags as $tagName) {
            if ($tagName === '') continue;
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $stmt->execute([$tagName]);
            $tagId = $stmt->fetchColumn();
            if (!$tagId) {
                $stmt = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $stmt->execute([$tagName]);
                $tagId = $pdo->lastInsertId();
            }
            $stmt = $pdo->prepare("INSERT INTO facility_tags (facility_id, tag_id) VALUES (?, ?)");
            $stmt->execute([$id, $tagId]);
        }

        echo json_encode(['message' => 'Facility updated']);
    }

    public function delete($id)
    {
        $pdo = $this->db->getConnection();

        // Delete facility (CASCADE will delete facility_tags)
        $stmt = $pdo->prepare("DELETE FROM facilities WHERE id=?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Facility not found']);
            return;
        }

        echo json_encode(['message' => 'Facility deleted']);
    }

    public function search()
    {
        $pdo = $this->db->getConnection();

        // Parse query parameters
        $params = [
            'name' => isset($_GET['name']) ? $_GET['name'] : null,
            'tag' => isset($_GET['tag']) ? $_GET['tag'] : null,
            'city' => isset($_GET['city']) ? $_GET['city'] : null,
        ];

        $page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $offset = ($page - 1) * $limit;

        // Build dynamic WHERE clause
        $where = " WHERE 1=1 ";
        $queryParams = [];
        if ($params['name']) {
            $where .= " AND f.name LIKE :name";
            $queryParams[':name'] = '%' . $params['name'] . '%';
        }
        if ($params['tag']) {
            $where .= " AND t.name LIKE :tag";
            $queryParams[':tag'] = '%' . $params['tag'] . '%';
        }
        if ($params['city']) {
            $where .= " AND l.city LIKE :city";
            $queryParams[':city'] = '%' . $params['city'] . '%';
        }

        // Step 1: Get total count of matching facilities
        $countSql = "
        SELECT COUNT(DISTINCT f.id)
        FROM facilities f
        JOIN locations l ON f.location_id = l.id
        LEFT JOIN facility_tags ft ON f.id = ft.facility_id
        LEFT JOIN tags t ON ft.tag_id = t.id
        $where
    ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($queryParams);
        $total = $countStmt->fetchColumn();

        // Step 2: Get paginated facility IDs
        $idSql = "
        SELECT DISTINCT f.id
        FROM facilities f
        JOIN locations l ON f.location_id = l.id
        LEFT JOIN facility_tags ft ON f.id = ft.facility_id
        LEFT JOIN tags t ON ft.tag_id = t.id
        $where
        ORDER BY f.id
        LIMIT :limit OFFSET :offset
    ";
        $idStmt = $pdo->prepare($idSql);
        foreach ($queryParams as $key => $value) {
            $idStmt->bindValue($key, $value);
        }
        $idStmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $idStmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $idStmt->execute();
        $ids = $idStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!$ids) {
            $result = [
                'page' => $page,
                'limit' => $limit,
                'total' => (int)$total,
                'facilities' => []
            ];
            header('Content-Type: application/json');
            echo json_encode($result);
            return;
        }

        // Step 3: Fetch all facility details for these IDs
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
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids);

        // Map the results into the required structure
        $facilities = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
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
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facilities[$fid]['tags'][] = $row['tag_name'];
            }
        }

        $result = [
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'facilities' => array_values($facilities)
        ];

        header('Content-Type: application/json');
        echo json_encode($result);
    }

}
