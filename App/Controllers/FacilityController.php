<?php

namespace App\Controllers;

use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;

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

    /**
     * List all facilities with cursor-based pagination, including location, tags, and employees.
     * GET /api/facilities?limit=10&cursor=0
     */
    public function list()
    {
        $pdo = $this->db->getConnection();

        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

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
            WHERE f.id > :cursor
            ORDER BY f.id
            LIMIT :limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cursor', $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $facilities = [];
        $maxId = $cursor;
        $ids = [];
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
                    'tags' => [],
                    'employees' => []
                ];
                $ids[] = $fid;
                if ($row['facility_id'] > $maxId) {
                    $maxId = $row['facility_id'];
                }
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facilities[$fid]['tags'][] = $row['tag_name'];
            }
        }

        // Fetch employees for these facilities
        if (count($ids)) {
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $employeeSql = "SELECT * FROM employees WHERE facility_id IN ($in)";
            $employeeStmt = $pdo->prepare($employeeSql);
            $employeeStmt->execute($ids);
            $employeesByFacility = [];
            while ($row = $employeeStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fid = $row['facility_id'];
                unset($row['facility_id']);
                $employeesByFacility[$fid][] = $row;
            }
            foreach ($facilities as $fid => &$fac) {
                $fac['employees'] = $employeesByFacility[$fid] ?? [];
            }
        }

        $nextCursor = count($facilities) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'facilities' => array_values($facilities)
        ]))->send();
    }

    /**
     * Search facilities with cursor-based pagination.
     * GET /api/facilities/search?name=...&tag=...&city=...&limit=10&cursor=0
     */
    public function search()
    {
        $pdo = $this->db->getConnection();

        $params = [
            'name' => isset($_GET['name']) ? $_GET['name'] : null,
            'tag' => isset($_GET['tag']) ? $_GET['tag'] : null,
            'city' => isset($_GET['city']) ? $_GET['city'] : null,
        ];

        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

        $where = " WHERE f.id > :cursor";
        $queryParams = [':cursor' => $cursor];
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
            $where
            ORDER BY f.id
            LIMIT :limit
        ";
        $stmt = $pdo->prepare($sql);
        foreach ($queryParams as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $facilities = [];
        $maxId = $cursor;
        $ids = [];
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
                    'tags' => [],
                    'employees' => []
                ];
                $ids[] = $fid;
                if ($row['facility_id'] > $maxId) {
                    $maxId = $row['facility_id'];
                }
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facilities[$fid]['tags'][] = $row['tag_name'];
            }
        }

        if (count($ids)) {
            $in = str_repeat('?,', count($ids) - 1) . '?';
            $employeeSql = "SELECT * FROM employees WHERE facility_id IN ($in)";
            $employeeStmt = $pdo->prepare($employeeSql);
            $employeeStmt->execute($ids);
            $employeesByFacility = [];
            while ($row = $employeeStmt->fetch(\PDO::FETCH_ASSOC)) {
                $fid = $row['facility_id'];
                unset($row['facility_id']);
                $employeesByFacility[$fid][] = $row;
            }
            foreach ($facilities as $fid => &$fac) {
                $fac['employees'] = $employeesByFacility[$fid] ?? [];
            }
        }

        $nextCursor = count($facilities) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'facilities' => array_values($facilities)
        ]))->send();
    }

    /**
     * Get detail of a facility with location, tags, and employees.
     */
    public function detail($id)
    {
        $pdo = $this->db->getConnection();
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
                    'tags' => [],
                    'employees' => []
                ];
            }
            if ($row['tag_id'] && $row['tag_name']) {
                $facility['tags'][] = $row['tag_name'];
            }
        }

        if (!$facility) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        $employeeSql = "SELECT id, name, email, phone, position FROM employees WHERE facility_id = ?";
        $employeeStmt = $pdo->prepare($employeeSql);
        $employeeStmt->execute([$id]);
        $facility['employees'] = $employeeStmt->fetchAll(\PDO::FETCH_ASSOC);

        (new Ok($facility))->send();
    }

    /**
     * Create a new facility, location, tags, and employees.
     */
    public function create()
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $this->sanitizeString($data['name'] ?? '');
        $location = array_map([$this, 'sanitizeString'], $data['location'] ?? []);
        $tags = array_map([$this, 'sanitizeString'], isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : []);
        $employees = isset($data['employees']) && is_array($data['employees']) ? $data['employees'] : [];

        if (
            empty($name) ||
            empty($location['city']) ||
            empty($location['address']) ||
            empty($location['zip_code']) ||
            empty($location['country_code']) ||
            empty($location['phone_number'])
        ) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $stmt = $pdo->prepare("INSERT INTO locations (city, address, zip_code, country_code, phone_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $location['city'],
            $location['address'],
            $location['zip_code'],
            $location['country_code'],
            $location['phone_number'],
        ]);
        $locationId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO facilities (name, location_id) VALUES (?, ?)");
        $stmt->execute([$name, $locationId]);
        $facilityId = $pdo->lastInsertId();

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

        foreach ($employees as $emp) {
            $empName = $this->sanitizeString($emp['name'] ?? '');
            $empEmail = $this->sanitizeString($emp['email'] ?? '');
            $empPhone = $this->sanitizeString($emp['phone'] ?? '');
            $empPosition = $this->sanitizeString($emp['position'] ?? '');
            if ($empName && $empEmail && $empPhone && $empPosition) {
                $stmt = $pdo->prepare("INSERT INTO employees (facility_id, name, email, phone, position) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$facilityId, $empName, $empEmail, $empPhone, $empPosition]);
            }
        }

        (new Created(['id' => $facilityId]))->send();
    }

    /**
     * Update a facility, location, tags, and employees.
     */
    public function update($id)
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        $name = $this->sanitizeString($data['name'] ?? '');
        $location = array_map([$this, 'sanitizeString'], $data['location'] ?? []);
        $tags = array_map([$this, 'sanitizeString'], isset($data['tags']) && is_array($data['tags']) ? $data['tags'] : []);
        $employees = isset($data['employees']) && is_array($data['employees']) ? $data['employees'] : [];

        $stmt = $pdo->prepare("SELECT location_id FROM facilities WHERE id = ?");
        $stmt->execute([$id]);
        $locationId = $stmt->fetchColumn();
        if (!$locationId) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        $stmt = $pdo->prepare("UPDATE locations SET city=?, address=?, zip_code=?, country_code=?, phone_number=? WHERE id=?");
        $stmt->execute([
            $location['city'],
            $location['address'],
            $location['zip_code'],
            $location['country_code'],
            $location['phone_number'],
            $locationId
        ]);

        $stmt = $pdo->prepare("UPDATE facilities SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);

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

        $stmt = $pdo->prepare("DELETE FROM employees WHERE facility_id=?");
        $stmt->execute([$id]);
        foreach ($employees as $emp) {
            $empName = $this->sanitizeString($emp['name'] ?? '');
            $empEmail = $this->sanitizeString($emp['email'] ?? '');
            $empPhone = $this->sanitizeString($emp['phone'] ?? '');
            $empPosition = $this->sanitizeString($emp['position'] ?? '');
            if ($empName && $empEmail && $empPhone && $empPosition) {
                $stmt = $pdo->prepare("INSERT INTO employees (facility_id, name, email, phone, position) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$id, $empName, $empEmail, $empPhone, $empPosition]);
            }
        }

        (new Ok(['message' => 'Facility updated']))->send();
    }

    /**
     * Delete a facility (CASCADE deletes tags and employees).
     */
    public function delete($id)
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM facilities WHERE id=?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            throw new NotFound(['error' => 'Facility not found']);
        }

        (new NoContent())->send();
    }
}
