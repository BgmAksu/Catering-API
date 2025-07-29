<?php

namespace App\Repositories;

class EmployeeRepository
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getByFacility($facilityId)
    {
        $sql = "SELECT id, name, email, phone, position FROM employees WHERE facility_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$facilityId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create($facilityId, $emp)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO employees (facility_id, name, email, phone, position) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $facilityId, $emp['name'], $emp['email'], $emp['phone'], $emp['position']
        ]);
    }

    public function deleteAllByFacility($facilityId)
    {
        $stmt = $this->pdo->prepare("DELETE FROM employees WHERE facility_id = ?");
        $stmt->execute([$facilityId]);
    }
}