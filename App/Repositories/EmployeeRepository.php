<?php

namespace App\Repositories;

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
     * @param $facilityId
     * @param $limit
     * @param $cursor
     * @return mixed
     */
    public function getPaginatedByFacility($facilityId, $limit, $cursor): mixed
    {
        $sql = "SELECT id, name, email, phone, position FROM employees WHERE facility_id = ? AND id > ? ORDER BY id LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $facilityId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $facilityId
     * @return mixed
     */
    public function getByFacility($facilityId): mixed
    {
        $sql = "SELECT id, name, email, phone, position FROM employees WHERE facility_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$facilityId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function getById($id): mixed
    {
        $stmt = $this->pdo->prepare("SELECT id, facility_id, name, email, phone, position FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $facilityId
     * @param $emp
     * @return mixed
     */
    public function create($facilityId, $emp): mixed
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
     * @param $id
     * @param $emp
     * @return mixed
     */
    public function update($id, $emp): mixed
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
     * @param $id
     * @return mixed
     */
    public function delete($id): mixed
    {
        $stmt = $this->pdo->prepare("DELETE FROM employees WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }
}