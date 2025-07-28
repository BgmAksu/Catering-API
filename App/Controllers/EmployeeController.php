<?php

namespace App\Controllers;

use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;

class EmployeeController extends Injectable
{
    /**
     * Sanitize string input from the client.
     */
    private function sanitizeString($input)
    {
        return trim(strip_tags((string)$input));
    }

    // List all employees for a facility with cursor-based pagination
    // Usage: /api/facilities/{fid}/employees?limit=10&cursor=0
    public function list($facilityId)
    {
        $pdo = $this->db->getConnection();

        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0 ? (int)$_GET['limit'] : 20;
        $cursor = isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;

        $sql = "SELECT id, name, email, phone, position FROM employees WHERE facility_id = ? AND id > ? ORDER BY id LIMIT ?";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(1, $facilityId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $cursor, \PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $employees = [];
        $maxId = $cursor;
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $employees[] = $row;
            if ($row['id'] > $maxId) {
                $maxId = $row['id'];
            }
        }

        $nextCursor = count($employees) ? $maxId : null;

        $result = [
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'employees' => $employees
        ];

        (new Ok($result))->send();
    }

    // Get single employee detail
    public function detail($id)
    {
        $pdo = $this->db->getConnection();
        $sql = "SELECT id, facility_id, name, email, phone, position FROM employees WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $employee = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$employee) {
            throw new NotFound(['error' => 'Employee not found']);
        }

        (new Ok($employee))->send();
    }

    // Add new employee to a facility
    public function create($facilityId)
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        $empName = $this->sanitizeString($data['name'] ?? '');
        $empEmail = $this->sanitizeString($data['email'] ?? '');
        $empPhone = $this->sanitizeString($data['phone'] ?? '');
        $empPosition = $this->sanitizeString($data['position'] ?? '');

        if ($empName && $empEmail && $empPhone && $empPosition) {
            $stmt = $pdo->prepare("INSERT INTO employees (facility_id, name, email, phone, position) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$facilityId, $empName, $empEmail, $empPhone, $empPosition]);
            (new Created(['id' => $pdo->lastInsertId()]))->send();
        } else {
            throw new BadRequest(['error' => 'Invalid input']);
        }
    }

    // Update an employee
    public function update($employeeId)
    {
        $pdo = $this->db->getConnection();
        $data = json_decode(file_get_contents('php://input'), true);

        $empName = $this->sanitizeString($data['name'] ?? '');
        $empEmail = $this->sanitizeString($data['email'] ?? '');
        $empPhone = $this->sanitizeString($data['phone'] ?? '');
        $empPosition = $this->sanitizeString($data['position'] ?? '');

        $stmt = $pdo->prepare("UPDATE employees SET name=?, email=?, phone=?, position=? WHERE id=?");
        $stmt->execute([$empName, $empEmail, $empPhone, $empPosition, $employeeId]);
        if ($stmt->rowCount() === 0) {
            throw new NotFound(['error' => 'Employee not found']);
        } else {
            (new Ok(['message' => 'Employee updated']))->send();
        }
    }

    // Delete an employee
    public function delete($employeeId)
    {
        $pdo = $this->db->getConnection();
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id=?");
        $stmt->execute([$employeeId]);
        if ($stmt->rowCount() === 0) {
            throw new NotFound(['error' => 'Employee not found']);
        } else {
            (new NoContent())->send();
        }
    }
}
