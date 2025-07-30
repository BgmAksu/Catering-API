<?php

namespace App\Controllers;

use App\Helper\Request;
use App\Helper\Sanitizer;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\BadRequest;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\EmployeeRepository;

class EmployeeController extends Injectable
{
    protected $pdo;
    protected $employeeRepo;
    public function __construct()
    {
        $this->pdo = $this->db->getConnection();
        $this->employeeRepo = new EmployeeRepository($this->pdo);
    }


    // List all employees for a facility with cursor-based pagination
    // Usage: /api/facilities/{fid}/employees?limit=10&cursor=0
    public function list($facilityId)
    {
        $limit = Request::limitDecider();
        $cursor = Request::cursorDecider();

        $employees = $this->employeeRepo->getPaginatedByFacility($facilityId, $limit, $cursor);
        $maxId = count($employees) ? end($employees)['id'] : $cursor;
        $nextCursor = count($employees) ? $maxId : null;

        (new Ok([
            'limit' => $limit,
            'cursor' => $cursor,
            'next_cursor' => $nextCursor,
            'employees' => $employees
        ]))->send();
    }

    // Get single employee detail
    public function detail($id)
    {
        $employee = $this->employeeRepo->getById($id);
        if (!$employee) {
            throw new NotFound(['error' => 'Employee not found']);
        }
        (new Ok($employee))->send();
    }

    // Add new employee to a facility
    public function create($facilityId)
    {
        $data = Request::getJsonData();
        $emp = Sanitizer::sanitizeAll($data);

        if (!$emp['name'] || !$emp['email'] || !$emp['phone'] || !$emp['position']) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $id = $this->employeeRepo->create($facilityId, $emp);
        (new Created(['id' => $id]))->send();
    }

    // Update an employee
    public function update($employeeId)
    {
        $data = Request::getJsonData();
        $emp = Sanitizer::sanitizeAll($data);

        if (!$emp['name'] || !$emp['email'] || !$emp['phone'] || !$emp['position']) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $updated = $this->employeeRepo->update($employeeId, $emp);
        if (!$updated) {
            throw new NotFound(['error' => 'Employee not found']);
        }
        (new Ok(['message' => 'Employee updated']))->send();
    }

    // Delete an employee
    public function delete($employeeId)
    {
        $deleted = $this->employeeRepo->delete($employeeId);
        if (!$deleted) {
            throw new NotFound(['error' => 'Employee not found']);
        }
        (new NoContent())->send();
    }
}
