<?php

namespace App\Repositories;

use App\Models\Employee;

/**
 * Employee Table related DB operations
 */
class EmployeeRepository
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
     * Cursor-based pagination (limit+1) for employees of a facility.
     * @param int $facilityId
     * @param int $limit
     * @param int $cursor
     * @return array
     */
    public function getPaginatedByFacility(int $facilityId, int $limit, int $cursor = 0): array
    {
        $limitPlusOne = $limit + 1;

        $sql = "
        SELECT e.*
        FROM employees e
        WHERE e.facility_id = :fid
          AND e.id >= :cursor
        ORDER BY e.id ASC
        LIMIT :limit_plus_one
    ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':fid', $facilityId, \PDO::PARAM_INT);
        $stmt->bindValue(':cursor', $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(':limit_plus_one', $limitPlusOne, \PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $limit;
        $nextCursor = null;
        if ($hasMore) {
            $nextCursor = (int)$rows[$limit]['id']; // first id of the next page
            $rows = array_slice($rows, 0, $limit);
        }

        return [$rows, $nextCursor];
    }

    /**
     * Typed variant that returns Employee models alongside next cursor.
     * @param int $facilityId
     * @param int $limit
     * @param int $cursor
     * @return array
     */
    public function getPaginatedByFacilityModels(int $facilityId, int $limit, int $cursor = 0): array
    {
        [$rows, $nextCursor] = $this->getPaginatedByFacility($facilityId, $limit, $cursor);
        $models = [];
        foreach ($rows as $row) {
            $models[] = Employee::fromArray($row);
        }
        return [$models, $nextCursor];
    }

    /**
     * @param int $facilityId
     * @return mixed
     */
    public function getByFacility(int $facilityId): mixed
    {
        $sql = "SELECT id, name, email, phone, position FROM employees WHERE facility_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$facilityId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Typed variant for fetching employees by facility id.
     * @param int $facilityId
     * @return Employee[]
     */
    public function getByFacilityModels(int $facilityId): array
    {
        $rows = $this->getByFacility($facilityId); // reuse existing method
        $models = [];
        foreach ((array)$rows as $row) {
            if (is_array($row)) {
                $models[] = Employee::fromArray($row);
            }
        }
        return $models;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function getById(int $id): mixed
    {
        $stmt = $this->pdo->prepare("SELECT id, facility_id, name, email, phone, position FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Typed fetch by employee id.
     * @param int $id
     * @return Employee|null
     */
    public function getByIdModel(int $id): ?Employee
    {
        $row = $this->getById($id);

        if (!$row) {
            return null;
        }
        return Employee::fromArray($row);
    }

    /**
     * @param int $facilityId
     * @param array $emp
     * @return mixed
     */
    public function create(int $facilityId, array $emp): mixed
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO employees (facility_id, name, email, phone, position) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $facilityId, $emp['name'], $emp['email'], $emp['phone'], $emp['position']
        ]);

        return $this->pdo->lastInsertId();
    }

    /**
     * @param int $id
     * @param array $emp
     * @return mixed
     */
    public function update(int $id, array $emp): mixed
    {
        $stmt = $this->pdo->prepare(
            "UPDATE employees SET name=?, email=?, phone=?, position=? WHERE id=?"
        );
        $stmt->execute([
            $emp['name'], $emp['email'], $emp['phone'], $emp['position'], $id
        ]);
        return $stmt->rowCount();
    }

    /**
     * Partially update an employee with only provided fields.
     * @param int   $employeeId
     * @param array $patch keys: name, email, phone_number, title
     * @return int affected rows
     */
    public function updatePartial(int $employeeId, array $patch): int
    {
        if (empty($patch)) {
            return 0;
        }
        $fields = [];
        $values = [];

        foreach (['name','email','phone_number','title'] as $k) {
            if (array_key_exists($k, $patch)) {
                $fields[] = "$k = ?";
                $values[] = $patch[$k];
            }
        }

        if (empty($fields)) {
            return 0;
        }

        $values[] = $employeeId;
        $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return $stmt->rowCount();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function delete(int $id): mixed
    {
        $stmt = $this->pdo->prepare("DELETE FROM employees WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}