<?php

namespace App\Controllers;

use App\DTO\EmployeeDTO;
use App\Helper\Request;
use App\Middleware\Authenticate;
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
    protected EmployeeRepository $employeeRepo;

    public function __construct()
    {
        Authenticate::check();
        $this->pdo = $this->db->getConnection();
        $this->employeeRepo = new EmployeeRepository($this->pdo);
    }

    /**
     * List all employees for a facility with cursor-based pagination.
     * GET /api/facilities/{facility_id}/employees?limit=20&cursor=0
     * @param $facilityId
     * @return void
     */
    public function list($facilityId): void
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

    /**
     * Get a single employee by ID.
     * GET /api/employees/{employee_id}
     * @param $id
     * @return void
     * @throws NotFound
     */
    public function detail($id): void
    {
        $employee = $this->employeeRepo->getById($id);
        if (!$employee) {
            throw new NotFound(['error' => 'Employee not found']);
        }
        (new Ok($employee))->send();
    }

    /**
     * Create a new employee for a facility
     * POST /api/facilities/{facility_id}/employees
     * Body: { "name": "...", "email": "...", "phone": "...", "position": "..." }
     * @param $facilityId
     * @return void
     * @throws BadRequest
     */
    public function create($facilityId): void
    {
        $data = Request::getJsonData();
        $dto = new EmployeeDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $id = $this->employeeRepo->create($facilityId, $dto->asArray());
        (new Created(['id' => $id]))->send();
    }

    /**
     * Update an employee by ID.
     * PUT /api/employees/{employee_id}
     * Body: { "name": "...", "email": "...", "phone": "...", "position": "..." }
     * @param $employeeId
     * @return void
     * @throws BadRequest
     * @throws NotFound
     */
    public function update($employeeId): void
    {
        $data = Request::getJsonData();
        $dto = new EmployeeDTO($data);

        if (!$dto->isValid()) {
            throw new BadRequest(['error' => 'Invalid input']);
        }

        $updated = $this->employeeRepo->update($employeeId, $dto->asArray());
        if (!$updated) {
            throw new NotFound(['error' => 'Employee not found']);
        }

        (new Ok(['message' => 'Employee updated']))->send();
    }

    /**
     * Delete an employee by ID.
     * DELETE /api/employees/{employee_id}
     * @param $employeeId
     * @return void
     * @throws NotFound
     */
    public function delete($employeeId): void
    {
        $deleted = $this->employeeRepo->delete($employeeId);
        if (!$deleted) {
            throw new NotFound(['error' => 'Employee not found']);
        }
        (new NoContent())->send();
    }
}
