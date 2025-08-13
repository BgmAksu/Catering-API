<?php

namespace App\Repositories;

use App\Models\Facility;

/**
 * Facility related DB operations
 */
class FacilityRepository
{
    /**
     * @var
     */
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Cursor-based pagination for facilities with optional filters.
     * Single query using GROUP BY + GROUP_CONCAT to avoid N+1.
     *
     * @param int   $limit  number of facilities to return (page size)
     * @param int   $cursor return facilities with id > $cursor
     * @param array $filters ['name'=>string|null, 'city'=>string|null, 'tag'=>string|null]
     * @return array [array<int,array> $facilities, int|null $nextCursor]
     */
    public function getPaginated(int $limit, int $cursor, array $filters = []): array
    {
        $limitPlusOne = $limit + 1;

        $where = ["f.id >= :cursor"];
        $params = [':cursor' => $cursor];

        if (!empty($filters['name'])) {
            $where[] = "f.name LIKE :name";
            $params[':name'] = '%' . $filters['name'] . '%';
        }
        if (!empty($filters['city'])) {
            $where[] = "l.city LIKE :city";
            $params[':city'] = '%' . $filters['city'] . '%';
        }

        // When tag filter is provided, only facilities having at least one matching tag are returned.
        // Tag filter via EXISTS (do NOT filter the LEFT JOIN used for tags_csv)
        $existsTagSql = '';
        if (!empty($filters['tag'])) {
            $existsTagSql = " AND EXISTS (
            SELECT 1
            FROM facility_tags ftf
            JOIN tags tf ON tf.id = ftf.tag_id
            WHERE ftf.facility_id = f.id
              AND tf.name LIKE :tag
        )";
            $params[':tag'] = '%' . $filters['tag'] . '%';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where) . $existsTagSql;

        $sql = "
        SELECT
            f.id AS facility_id,
            f.name AS facility_name,
            f.creation_date,
            l.city, l.address, l.zip_code, l.country_code, l.phone_number,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ',') AS tags_csv
        FROM facilities f
        JOIN locations l ON l.id = f.location_id

        -- LEFT JOIN used to collect ALL tags of each facility
        LEFT JOIN facility_tags ft ON ft.facility_id = f.id
        LEFT JOIN tags t ON t.id = ft.tag_id

        $whereSql
        GROUP BY f.id
        ORDER BY f.id ASC
        LIMIT :limit_plus_one
    ";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit_plus_one', $limitPlusOne, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Determine next cursor using limit+1 rule
        $hasMore = count($rows) > $limit;
        if ($hasMore) {
            $nextCursor = (int)$rows[$limit]['facility_id']; // the first id of the next page
            // Trim to requested page size
            $rows = array_slice($rows, 0, $limit);
        } else {
            $nextCursor = null;
        }

        // Map rows to response structure
        $facilities = [];
        foreach ($rows as $row) {
            $tags = [];
            if (!empty($row['tags_csv'])) {
                $tags = array_values(array_filter(array_map('trim', explode(',', $row['tags_csv']))));
            }
            $facilities[] = [
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
                'tags' => $tags,
            ];
        }

        return [$facilities, $nextCursor];
    }

    /**
     * Typed variant of getPaginated() that returns Facility models.
     *
     * @param int   $limit
     * @param int   $cursor
     * @param array $filters
     * @return array{0: Facility[], 1: int|null} [facilities, nextCursor]
     */
    public function getPaginatedModels(int $limit, int $cursor = 0, array $filters = []): array
    {
        [$rows, $next] = $this->getPaginated($limit, $cursor, $filters);
        $models = [];
        foreach ($rows as $row) {
            $models[] = Facility::fromArray($row);
        }
        return [$models, $next];
    }

    /**
     * @param $id
     * @return array
     */
    public function getById($id): array
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
        return $facility ?: [];
    }

    /**
     * Typed variant of single fetch that returns a Facility model or null.
     *
     * @param int $id
     * @return Facility|null
     */
    public function getByIdModel(int $id): ?Facility
    {
        $arr = $this->getById($id);
        if (!$arr) {
            return null;
        }
        return Facility::fromArray($arr);
    }

    /**
     * @param $facilityId
     * @return mixed
     */
    public function getLocationId($facilityId): mixed
    {
        $stmt = $this->pdo->prepare("SELECT location_id FROM facilities WHERE id = ?");
        $stmt->execute([$facilityId]);
        return $stmt->fetchColumn();
    }

    /**
     * @param $name
     * @param $locationId
     * @return mixed
     */
    public function create($name, $locationId): mixed
    {
        $stmt = $this->pdo->prepare("INSERT INTO facilities (name, location_id) VALUES (?, ?)");
        $stmt->execute([$name, $locationId]);
        return $this->pdo->lastInsertId();
    }

    /**
     * @param $id
     * @param $name
     * @return mixed
     */
    public function update($id, $name): mixed
    {
        $stmt = $this->pdo->prepare("UPDATE facilities SET name=? WHERE id=?");
        $stmt->execute([$name, $id]);
        return $stmt->rowCount();
    }

    /**
     * @param $id
     * @return mixed
     */
    public function delete($id): mixed
    {
        $stmt = $this->pdo->prepare("DELETE FROM facilities WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

}