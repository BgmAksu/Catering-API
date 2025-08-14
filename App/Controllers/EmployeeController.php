<?php

namespace App\Controllers;

use App\DTO\EmployeeDTO;
use App\Helper\Cursor;
use App\Helper\Request;
use App\Middleware\Authenticate;
use App\Models\Employee;
use App\Plugins\Di\Injectable;
use App\Plugins\Http\Exceptions\UnprocessableEntity;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Exceptions\NotFound;
use App\Repositories\EmployeeRepository;

class EmployeeController extends Injectable
{
    /**
     * @var
     */
    protected $pdo;

    /**
     * @var EmployeeRepository
     */
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

        [$models, $nextCursor] = $this->employeeRepo->getPaginatedByFacilityModels((int)$facilityId, $limit, $cursor);
        $employees = array_map(fn(Employee $e) => $e->toArray(), $models);

        (new Ok([
            'limit' => $limit,
            'cursor' => isset($_GET['cursor']) ? (string)$_GET['cursor'] : '0',
            'next_cursor' => Cursor::encodeOrNull($nextCursor),
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
        $employee = $this->employeeRepo->getByIdModel((int)$id);
        if (!$employee) {
            throw new NotFound(['error' => 'Employee not found']);
        }

        (new Ok($employee->toArray()))->send();
    }

    /**
     * Create a new employee for a facility
     * POST /api/facilities/{facility_id}/employees
     * Body: { "name": "...", "email": "...", "phone": "...", "position": "..." }
     * @param $facilityId
     * @return void
     * @throws UnprocessableEntity
     */
    public function create($facilityId): void
    {
        $data = Request::getJsonData();
        $dto  = new EmployeeDTO(is_array($data) ? $data : [], false); // create mode
        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        $id = $this->employeeRepo->create((int)$facilityId, $dto->asArray());

        (new Created(['id' => $id]))->send();
    }

    /**
     * Update an employee by ID.
     * PUT /api/employees/{employee_id}
     * Body: { "name": "...", "email": "...", "phone": "...", "position": "..." }
     * @param $employeeId
     * @return void
     * @throws UnprocessableEntity
     * @throws NotFound
     */
    public function update($employeeId): void
    {
        $data = Request::getJsonData();
        $dto  = new EmployeeDTO(is_array($data) ? $data : [], true); // update mode
        if (!$dto->isValid()) {
            throw new UnprocessableEntity([
                'message' => 'Validation failed',
                'errors'  => $dto->errors(),
            ]);
        }

        $existing = $this->employeeRepo->getByIdModel((int)$employeeId);
        if (!$existing) {
            throw new NotFound(['error' => 'Employee not found']);
        }

        $patch = $dto->toPatchArray();
        if (!empty($patch)) {
            $this->employeeRepo->updatePartial((int)$employeeId, $patch);
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
        $deleted = $this->employeeRepo->delete((int)$employeeId);
        if (!$deleted) {
            throw new NotFound(['error' => 'Employee not found']);
        }

        (new NoContent())->send();
    }
}
